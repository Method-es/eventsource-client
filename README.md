# eventsource-client

PHP 7 implementation of EventSource as a client (aka Server-Sent Events (SSE))
Uses ReactPHP as the basis

Specification used for implementation:
https://www.w3.org/TR/eventsource/

Trying to match this usage:
https://developer.mozilla.org/en-US/docs/Web/API/EventSource/EventSource


### Example
```PHP
<?php

require __DIR__."/../vendor/autoload.php";

$loop = React\EventLoop\Factory::create();

$eventSource = new \Method\EventSource('http://127.0.0.1/eventsource-server/example.php', [], $loop);
//the eventsource-server project is a very simple implementation of https://github.com/igorw/EventSource

// example: bind to your events here
// $eventSource->on('eventName', function(Method\MessageEvent $event){ //do something } );

$loop->run();
```


### todo
- [ ] handle redirects
- [ ] handle reconnects
- [ ] handle connection failure
- [ ] tests!
- [ ] consider a better dependency system