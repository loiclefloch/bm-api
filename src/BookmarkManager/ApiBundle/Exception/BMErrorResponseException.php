<?php

namespace BookmarkManager\ApiBundle\Exception;

use Exception;

class BmErrorResponseException extends Exception
{
    protected $error_code;
    protected $error_message;
    protected $httpCode;

    /**
     * BMErrorResponse constructor.
     * @param string $code
     * @param string $message
     * @param int $httpCode
     * @param null $previous
     * @internal param null $previous
     * @internal param $errorCode
     * @internal param int $int
     * @internal param string $string
     */
    public function __construct($code, $message = '', $httpCode = 400, $previous = null)
    {
        parent::__construct($message, $code, $previous);

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