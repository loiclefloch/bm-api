<?php
/**
 * Created by PhpStorm.
 * User: loic
 * Date: 24/03/16
 * Time: 12:41
 */

namespace BookmarkManager\ApiBundle\Crawler;


use Exception;

class CrawlerRetrieveDataException extends Exception
{

    /**
     * CrawlerRetrieveDataException constructor.
     * @param string $data
     * @param string $httpCode
     * @param Exception $previous
     */
    public function __construct($data, $httpCode, Exception $previous = null)
    {
        parent::__construct($data, $httpCode, $previous);
    }

    // custom string representation of object
    public function __toString() {
        return __CLASS__ . "Impossible to retrieve the website content " . $this->code;
    }

}