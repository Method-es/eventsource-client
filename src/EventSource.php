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

    private $readyState = self::CONNECTING;
    private $url;
    private $withCredentials = false; //todo

    private $httpClient;
    private $httpRequest;
    private $httpResponse;

    private $lastEventID = "";
    private $reconnectTime = 1500; //time in milliseconds

    private $dataBuffer = "";
    private $eventBuffer = "";

    private $responseBuffer = "";

    const HEADER_CONTENT_TYPE = 'Content-Type';
    const HEADER_LAST_EVENT_ID = 'Last-Event-ID';

    const MIME_TYPE_EVENT_STREAM = 'text/event-stream';

    const STANDARD_HEADERS = [
        'Connection' => 'keep-alive',
        'Accept' => self::MIME_TYPE_EVENT_STREAM,
        'Cache-Control' => 'no-cache'
    ];

    public function __construct(string $url, array $options, Client $httpClient)
    {

        if(array_key_exists('withCredentials',$options)){
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

        if(!empty($this->lastEventID)){
            $headers[self::HEADER_LAST_EVENT_ID] = $this->lastEventID;
        }

        $this->httpRequest = $this->httpClient->request('GET', $this->url, $headers);

        $this->httpRequest->on('response', [$this,'onResponseReceived']);
        $this->httpRequest->on('error', [$this, 'onRequestError']);

        //this is to trigger the header write, but I feel like there must be a better way to do this!
        $this->httpRequest->end();
    }

    public function onResponseReceived(Response $response, Request $request)
    {
        $code = $response->getCode();
        if($code == 200){
            //only care about the response when we have a successful connection
            //also double check the content type header, MUST include 'text/event-stream'
            $headers = $response->getHeaders();
            if(!array_key_exists(self::HEADER_CONTENT_TYPE,$headers)){
                throw new RuntimeException(self::HEADER_CONTENT_TYPE.' header missing; stopping request');
            }
            if(stripos($headers[self::HEADER_CONTENT_TYPE], self::MIME_TYPE_EVENT_STREAM) === false){
                throw new RuntimeException(self::HEADER_CONTENT_TYPE.' mismatch; found: '.$headers[self::HEADER_CONTENT_TYPE]);
            }

            $this->readyState = self::OPEN;
            $this->httpResponse = $response;
            $this->httpResponse->on('data', [$this, 'onResponseData']);
        }
    }

    public function onResponseData(string $data, Response $response)
    {
        $responseChunk = (string)$data;
        $this->responseBuffer .= $responseChunk;
        //            var_dump(str_replace(["\r","\n"],['\r','\n'],$responseChunk));

        $responseLines = preg_split('/\R/', $this->responseBuffer);

        $this->responseBuffer = array_pop($responseLines);

        foreach($responseLines as $line){
            //line parsing time...
            if(empty($line)) {
                //dispatch current event
//                dispatchEvent($currentEvent);
            }

        }
    }

    public function onRequestError(Exception $error, Request $request)
    {
        throw new \Exception('something went wrong',0, $error);
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