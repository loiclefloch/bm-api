<?php
/**
 * Created by PhpStorm.
 * User: loic
 * Date: 20/01/16
 * Time: 14:02
 */

namespace BookmarkManager\ApiBundle\Listener;

use BookmarkManager\ApiBundle\Exception\BMErrorResponseException;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener
{
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        // You get the exception object from the received event
        $exception = $event->getException();

        $message = [
            'code' => 0,
            'message' => 'Uncaught exception'
        ];

        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;

        // -- Handle non catch BMErrorResponseException
        if ($exception instanceof BMErrorResponseException) {

            $message = [
                'code' => $exception->getErrorCode(),
                'message' => $exception->getErrorMessage(),
            ];

            $statusCode = $exception->getHttpCode();
        }


        if ($message['code'] != 0) {
            $response = new Response();
            $response->setContent(json_encode($message));
            $response->setStatusCode($statusCode);

            // Send the modified response object to the event
            $event->setResponse($response);
        }
    }
}
