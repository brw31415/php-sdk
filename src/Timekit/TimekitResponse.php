<?php

namespace Timekit;

class TimekitResponse
{
    private $data;
    private $code;

    /**
     * @param $data
     * @param $code
     */
    public function __construct($data, $code)
    {
        $this->data = $data;
        $this->code = $code;
    }

    /**
     * @return Array
     */
    public function getData()
    {
        return $this->data['data'];
    }

    /**
     * @return int
     */
    public function getCode()
    {
        return $this->code;
    }
}
