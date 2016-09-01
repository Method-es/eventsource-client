<?php

require __DIR__."/../vendor/autoload.php";

$loop = React\EventLoop\Factory::create();

$dnsResolverFactory = new React\Dns\Resolver\Factory();
$dnsResolver = $dnsResolverFactory->createCached('8.8.8.8', $loop);

$factory = new React\HttpClient\Factory();
$client = $factory->create($loop, $dnsResolver);

$request = $client->request('GET', 'http://127.0.0.1/scratch/eventsource-server/example.php',
    [
        'Connection'=> 'keep-alive',
        'Accept' => 'text/event-stream',
        'Cache-Control' => 'no-cache'
    ]);


$request->on('response', function (\React\HttpClient\Response $response) {


    var_dump('response',$response->getCode(), $response->getHeaders());
    $response->on('data', function ($data, \React\HttpClient\Response $response) {
//        var_dump('on data', (string)$data);
    });
});


$request->on('end', function(RuntimeException $error, \React\HttpClient\Response $response, \React\HttpClient\Request $request){
//    var_dump('on end', $error->getMessage());
});


$request->end();
$loop->run();