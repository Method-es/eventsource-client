<?php


namespace Method;


class MessageEvent
{
    private $type = "";
    private $timestamp;
    private $data;
    private $origin = "";
    private $lastEventID = "";

    public function __construct(string $type, array $options)
    {
        $this->timestamp = time();
        $this->type = $type;
        foreach($options as $key => $val){
            if(property_exists($this, $key)){
                $this->$key = $val;
            }else{
                throw new \Exception('Invalid option parameter provided');
            }
        }

    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getOrigin(): string
    {
        return $this->origin;
    }

    public function getLastEventID(): string
    {
        return $this->lastEventID;
    }
}