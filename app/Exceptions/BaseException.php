<?php

namespace App\Exceptions;

use App\Service\ResponseJSON;
use Exception;

class BaseException extends Exception
{
    public function render($request)
    {
        return ResponseJSON::getInstance()
            ->setMessage($this->getMessage())
            ->setStatusCode(in_array($this->getCode(), ResponseJSON::getAllowedStatus()) ? $this->getCode() : 500)
            ->render();
    }
}
