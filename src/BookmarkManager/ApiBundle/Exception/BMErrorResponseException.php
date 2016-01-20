<?php

namespace BookmarkManager\ApiBundle\Exception;


class BMErrorResponseException extends \Exception
{
    protected $error_code;
    protected $error_message;
    protected $httpCode;

    /**
     * BMErrorResponse constructor.
     * @param string $code
     * @param string $message
     * @param int $httpCode
     * @internal param $errorCode
     * @internal param int $int
     * @internal param string $string
     */
    public function __construct($code, $message = '', $httpCode = 400)
    {
        $this->error_code = $code;
        $this->error_message = $message;
        $this->httpCode = $httpCode;


        $this->message = json_encode($message);
    }

    public function getErrorCode() {
        return $this->error_code;
    }

    public function getErrorMessage() {
        return $this->error_message;
    }

    public function getHttpCode() {
        return $this->httpCode;
    }

}