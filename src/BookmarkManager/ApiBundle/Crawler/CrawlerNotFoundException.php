<?php

namespace BookmarkManager\ApiBundle\Crawler;

use Exception;

class CrawlerNotFoundException extends Exception
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
    public function __toString()
    {
        return __CLASS__."Impossible to retrieve the website content: 404 error.\n";
    }

}