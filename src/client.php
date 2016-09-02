<?php

require __DIR__."/../vendor/autoload.php";

$loop = React\EventLoop\Factory::create();

$eventSource = new \Method\EventSource('http://127.0.0.1/scratch/eventsource-server/example.php',[], $loop);
//the eventsource-server project is a very simple implementation of https://github.com/igorw/EventSource

// example: bind to your events here
// $eventSource->on('eventName', function(Method\MessageEvent $event){ //do something } );

$loop->run();