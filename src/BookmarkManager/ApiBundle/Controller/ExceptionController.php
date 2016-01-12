<?php

namespace BookmarkManager\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

class ExceptionController extends \FOS\RestBundle\Controller\ExceptionController
{

    public function showAction(Request $request, $exception, DebugLoggerInterface $logger = null)
    {
        $format = $this->getFormat($request, $request->getRequestFormat());
        if (null === $format) {
            $message = 'No matching accepted Response format could be determined, while handling: ';
            $message .= $this->getExceptionMessage($exception);
            return new Response($message, Response::HTTP_NOT_ACCEPTABLE, $exception->getHeaders());
        }
        $currentContent = $this->getAndCleanOutputBuffering(
            $request->headers->get('X-Php-Ob-Level', -1)
        );
        $code = $this->getStatusCode($exception);
        /** @var ViewHandler $viewHandler */
        $viewHandler = $this->container->get('fos_rest.view_handler');
        $parameters = $this->getParameters($viewHandler, $currentContent, $code, $exception, $logger, $format);
        $showException = $request->attributes->get('showException', $this->container->get('kernel')->isDebug());
        try {
            if (!$viewHandler->isFormatTemplating($format)) {
                $parameters = $this->createExceptionWrapper($parameters);
            }
            $view = View::create($parameters, $code, $exception->getHeaders());
            $view->setFormat($format);
            if ($viewHandler->isFormatTemplating($format)) {
                $view->setTemplate($this->findTemplate($request, $format, $code, $showException));
            }
            $response = $viewHandler->handle($view);
        } catch (\Exception $e) {
            $message = 'An Exception was thrown while handling: ';
            $message .= $this->getExceptionMessage($exception);
            $response = new Response($message, Response::HTTP_INTERNAL_SERVER_ERROR, $exception->getHeaders());
        }

        return $response;
    }


}
