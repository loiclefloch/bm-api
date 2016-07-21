<?php
/**
 * Created by PhpStorm.
 * User: loic
 * Date: 20/01/16
 * Time: 14:17
 */

namespace BookmarkManager\ApiBundle\Exception;


use BookmarkManager\ApiBundle\Exception\BmErrorResponseException;
use Symfony\Component\HttpFoundation\Response;

class BmAlreadyExistsException extends BmErrorResponseException
{
    public function __construct($code = 101, $previous = null)
    {
        parent::__construct($code, "Bookmark already exists with this url.", Response::HTTP_BAD_REQUEST);
    }

}