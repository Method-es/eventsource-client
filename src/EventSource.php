<?php

namespace Method;

use Evenement\EventEmitter;
use Exception;
use React\HttpClient\Client;
use React\HttpClient\Request;
use React\HttpClient\Response;
use RuntimeException;

class EventSource extends EventEmitter
{
    const CONNECTING = 0;
    const OPEN = 1;
    const CLOSED = 2;

    const HEADER_CONTENT_TYPE = 'Content-Type';
    const HEADER_LAST_EVENT_ID = 'Last-Event-ID';

    const MIME_TYPE_EVENT_STREAM = 'text/event-stream';

    const STANDARD_HEADERS = [
        'Connection' => 'keep-alive',
        'Accept' => self::MIME_TYPE_EVENT_STREAM,
        'Cache-Control' => 'no-cache'
    ];

    private $readyState = self::CONNECTING;
    private $url;
    private $finalURL; //todo
    private $withCredentials = false; //todo

    private $httpClient;
    private $httpRequest;
    private $httpResponse;

    private $lastEventID = "";
    private $reconnectTime = 1500; //time in milliseconds

    private $dataBuffer = "";
    private $eventBuffer = "";
    private $responseBuffer = "";

    public function __construct(string $url, array $options, Client $httpClient)
    {

        if (array_key_exists('withCredentials', $options)) {
            $this->withCredentials = (boolean)$options['withCredentials'];
        }

        //todo resolve URL async styles but it's currently done during the httpClient->request() phase
        $this->url = $url;

        $this->httpClient = $httpClient;

        $this->connect();
    }

    protected function connect()
    {
        $headers = self::STANDARD_HEADERS;

        if (!empty($this->lastEventID)) {
            $headers[self::HEADER_LAST_EVENT_ID] = $this->lastEventID;
        }

        $this->httpRequest = $this->httpClient->request('GET', $this->url, $headers);

        $this->httpRequest->on('response', [$this, 'onResponseReceived']);
        $this->httpRequest->on('error', [$this, 'onRequestError']);

        //this is to trigger the header write, but I feel like there must be a better way to do this!
        $this->httpRequest->end();
    }

    public function onResponseReceived(Response $response, Request $request)
    {
        $code = $response->getCode();
        if ($code == 200) {
            //only care about the response when we have a successful connection
            //also double check the content type header, MUST include 'text/event-stream'
            $headers = $response->getHeaders();
            if (!array_key_exists(self::HEADER_CONTENT_TYPE, $headers)) {
                throw new RuntimeException(self::HEADER_CONTENT_TYPE . ' header missing; stopping request');
            }
            if (stripos($headers[self::HEADER_CONTENT_TYPE], self::MIME_TYPE_EVENT_STREAM) === false) {
                throw new RuntimeException(self::HEADER_CONTENT_TYPE . ' mismatch; found: ' . $headers[self::HEADER_CONTENT_TYPE]);
            }

//            $this->finalURL = $request->
            $this->readyState = self::OPEN;
            $this->emit('open', [$this]);

            $this->httpResponse = $response;
            $this->httpResponse->on('data', [$this, 'onResponseData']);
        }
    }

    public function onResponseData(string $data, Response $response)
    {
        /*
         * okay overall we want to treat this process in multiple steps:
         * - accumulate the data buffer; we should assume that there is a chance that data will only be sent in non-discrete chunks
         *      this means that when we receive data, we append it to an internal buffer.
         * - parse the buffer; this is the important step of determining if we have a valid "chunk" of data;
         *      the good news here is that ALL data will END with a \n|\r|\r\n; so we can always split based on \r,
         *      and always append new data onto the last element
         * - parse individual lines; this is where the spec comes into play to separate the events, from data, from retry, etc etc etc
         */

        $responseChunk = (string)$data;
        $this->responseBuffer .= $responseChunk;

        $responseLines = preg_split('/\R/', $this->responseBuffer);

        $this->responseBuffer = array_pop($responseLines);

        foreach ($responseLines as $line) {
            //line parsing time...
            if (empty($line)) {
                //dispatch current event
                $this->dispatchEvent();
                continue;
            }
            $colonPosition = strpos($line, ":");
            if ($colonPosition === 0) {
                //ignore this line!
                continue;
            } else if ($colonPosition !== false) {
                //found a colon somewhere other then the start ...
                //we split at the colon
                $field = substr($line, 0, $colonPosition);
                $value = ltrim(substr($line, $colonPosition + 1), " ");
                $this->processField($field, $value);
                continue;
            } else {
                $this->processField($line, "");
                continue;
            }
        }
    }

    protected function processField(string $field, string $value)
    {
        switch ($field) {
            case 'event':
                $this->eventBuffer = $value;
                break;
            case 'data':
                $this->dataBuffer .= $value . "\n";
                break;
            case 'id':
                $this->lastEventID = $value;
                break;
            case 'retry':
                if (is_numeric($value)) {
                    $this->reconnectTime = (int)$value;
                }
                break;
            default:
                //ignored.
                break;
        }
    }

    protected function dispatchEvent()
    {
        if (empty($this->dataBuffer)) {
            $this->dataBuffer = "";
            $this->eventBuffer = "";
            return;
        }
        if (substr($this->dataBuffer, -1) === "\n") {
            $this->dataBuffer = substr($this->dataBuffer, 0, -1);
        }
        $eventType = (empty($this->eventBuffer)) ? ('message') : ($this->eventBuffer);
        $messageEvent = new MessageEvent($eventType, [
            'data' => $this->dataBuffer,
            'origin' => $this->getUrl(),
            'lastEventID' => $this->getLastEventID()
        ]);
        $this->dataBuffer = "";
        $this->eventBuffer = "";

        $this->emit($messageEvent->getType(), [$messageEvent]);
    }

    public function onRequestError(Exception $error, Request $request)
    {
        throw new \Exception('something went wrong', 0, $error);
    }

    public function close()
    {
        $this->readyState = self::CLOSED;
    }

    public function getReadyState(): int
    {
        return $this->readyState;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getLastEventID(): string
    {
        return $this->lastEventID;
    }
}