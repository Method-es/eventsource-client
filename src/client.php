<?php

require __DIR__."/../vendor/autoload.php";

$loop = React\EventLoop\Factory::create();

$dnsResolverFactory = new React\Dns\Resolver\Factory();
$dnsResolver = $dnsResolverFactory->createCached('8.8.8.8', $loop);

$factory = new React\HttpClient\Factory();
$client = $factory->create($loop, $dnsResolver);

$eventSource = new \Method\EventSource('http://127.0.0.1/scratch/eventsource-server/example.php',[], $client);

// example: bind to your events here
// $eventSource->on('eventName', function(Method\MessageEvent $event){ //do something } );

$loop->run();