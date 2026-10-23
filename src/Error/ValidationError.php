<?php

namespace App\Error;

class ValidationError
{

    private $result;
    private $code;

    public function __construct($result, $code)
    {
        $this->result = $result;
        $this->code = $code;
    }
    
    public function getCode() {
        return $this->code;
    }

    public function getResult() {
        return $this->result;
    }

    public function setCode($code) {
        $this->code = $code;
    }

    public function setResult($result) {
        $this->result = $result;
    }
}
