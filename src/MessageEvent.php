<?php


namespace Method;


class MessageEvent
{
    private $type;
    private $timestamp;
    private $data;
    private $origin;
    private $lastEventID;

    public function __construct(string $type, array $options)
    {
        foreach($options as $key => $val){

        }
    }
}