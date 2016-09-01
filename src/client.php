<?php

require __DIR__."/../vendor/autoload.php";

$loop = React\EventLoop\Factory::create();

$dnsResolverFactory = new React\Dns\Resolver\Factory();
$dnsResolver = $dnsResolverFactory->createCached('8.8.8.8', $loop);

$factory = new React\HttpClient\Factory();
$client = $factory->create($loop, $dnsResolver);



$eventSource = new \Method\EventSource('http://127.0.0.1/scratch/eventsource-server/example.php',[], $client);



/*
 * okay overall we want to treat this process in multiple steps:
 * - accumulate the data buffer; we should assume that there is a chance that data will only be sent in non-discrete chunks
 *      this means that when we receive data, we append it to an internal buffer.
 * - parse the buffer; this is the important step of determining if we have a valid "chunk" of data;
 *      the good news here is that ALL data will END with a \n|\r|\r\n; so we can always split based on \r,
 *      and always append new data onto the last element
 * - parse individual lines; this is where the spec comes into play to separate the events, from data, from retry, etc etc etc
 */
//
//
//
//$request->on('response', function (\React\HttpClient\Response $response) use ($currentReadyState, &$buffer) {
//
//    $code = $response->getCode();
//
//    if($code == 200){
//        //only care about the response when we have a successful connection
//
//        //also double check the content type header, MUST include 'text/event-stream'
//        $headers = $response->getHeaders();
//        if(!array_key_exists('Content-Type',$headers)){
//            throw new RuntimeException('Content-Type header missing; stopping request');
//        }
//        if(stripos($headers['Content-Type'],'text/event-stream') === false){
//            throw new RuntimeException('Content-Type mismatch; found: '.$headers['Content-Type']);
//        }
//
//        $currentReadyState = READY_STATE_OPEN;
//
//        $currentEvent = new Event();
//
//        $response->on('data', function ($data, \React\HttpClient\Response $response) use(&$buffer, $currentEvent) {
//            $responseChunk = (string)$data;
//
//            $buffer .= $responseChunk;
//
//            var_dump(str_replace(["\r","\n"],['\r','\n'],$responseChunk));
//
//            $responseLines = preg_split('/\R/', $buffer);
//
//            $buffer = array_pop($responseLines);
//
//            foreach($responseLines as $line){
//                //line parsing time...
//                if(empty($line)) {
//                    //dispatch current event
//                    dispatchEvent($currentEvent);
//                }
//
//            }
//
//        });
//    }
//    //todo handle redirects and reconnect conditions  (301,302,303,307 - redirects) (500,502,503,504 - retry)
//});
//
//$request->on('end', function(RuntimeException $error, \React\HttpClient\Response $response, \React\HttpClient\Request $request){
////    var_dump('on end', $error->getMessage());
//});
//
//
//$request->end();
$loop->run();