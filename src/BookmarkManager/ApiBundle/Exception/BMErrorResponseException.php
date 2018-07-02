<?php

namespace BookmarkManager\ApiBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

use Exception;

class BmErrorResponseException extends Exception
{
    protected $error_code;
    protected $error_message;
    protected $httpCode;
    protected $detail;

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
    public function __construct($code, $message = '', $httpCode = Response::HTTP_BAD_REQUEST, $detail = null, $previous = null)
    {
        parent::__construct(is_array($message) ? '' : $message, $code, $previous);

        $this->error_code = $code;
        $this->error_message = $message;
        $this->httpCode = $httpCode;
        $this->detail = $detail;

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

    public function getErrorDetail() {
        return $this->detail;
    }
}