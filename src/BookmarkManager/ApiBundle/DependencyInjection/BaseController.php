<?php

namespace BookmarkManager\ApiBundle\DependencyInjection;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

class BaseController extends FOSRestController
{

    /**
     * @param array $data the content of the response. It will be serialize.
     * @param int $httpCode the http code of the response
     * @return Response The response object
     */
    protected function buildResponse($data = array(), $httpCode = Response::HTTP_OK)
    {

        $view = View::create();

        $view->setStatusCode($httpCode);
        $view->setData($data);

        return $this->handleView($view);
    }

    /**
     * This function build a Response object based on the $data and $httpCode given.
     * @param array $data
     * @param int $httpCode You must use Response defines
     * @param array $paging
     * @return Response The response to return
     */
    protected function successResponse($data = array(), $httpCode = Response::HTTP_OK, $paging = [])
    {
        if (!empty($paging)) {
            if (isset($paging['last_page'])
                && isset($paging['results']) && isset($paging['total'])
            ) {
                $defaultValues = array(
                    'page' => 1,
                    'limit' => $this->container->getParameter('default_results_limit'),
                );
                foreach ($defaultValues as $k => $v) {
                    if (!isset($paging[$k])) {
                        $paging[$k] = $v;
                    }
                }

                if (!empty($paging['url'])) {
                    $url = $paging['url'];
                } else {
                    $url = $this->generateUrl(
                        (!empty($paging['route']) ? $paging['route'] : $this->getRequest()->attributes->get('_route')),
                        array(),
                        true
                    );
                }

                $final['paging'] = array(
                    'metas' => array(
                        'results' => $paging['results'],
                        'total' => $paging['total'],
                        'limit' => $paging['limit'],
                    ),
                );

                if ($paging['total'] > 0) {
                    $final['paging'] = array_merge_recursive(
                        $final['paging'],
                        array(
                            'metas' => array(
                                'page' => $paging['page'],
                            ),
                            'links' => array(
                                'first' => ($url.'?'.http_build_query(
                                        array(
                                            'page' => 1,
                                            'limit' => $paging['limit'],
                                        )
                                    )),
                                'prev' => ($paging['page'] > 1 ? ($url.'?'.http_build_query(
                                        array(
                                            'page' => ($paging['page'] - 1),
                                            'limit' => $paging['limit'],
                                        )
                                    )) : ''),
                                'next' => ($paging['page'] < $paging['last_page'] ? ($url.'?'.http_build_query(
                                        array(
                                            'page' => ($paging['page'] + 1),
                                            'limit' => $paging['limit'],
                                        )
                                    )) : ''),
                                'last' => ($url.'?'.http_build_query(
                                        array(
                                            'page' => $paging['last_page'],
                                            'limit' => $paging['limit'],
                                        )
                                    )),
                            ),
                        )
                    );
                }
            } else {
                throw new InvalidArgumentException('invalid arguments');
            }
            $data = array_merge($data, ['paging' => $paging]);
        }

        return $this->buildResponse($data, $httpCode);
    }

    /**
     * Send a formatted error response
     * @param $code int The api error code
     * @param $message string A comprehensive message of the error, for the developper
     * @param $httpCode int The httpCode to return
     * @return Response The response to return
     */
    protected function errorResponse($code, $message, $httpCode = Response::HTTP_BAD_REQUEST)
    {

        return $this->buildResponse(
            [
                'code' => $code,
                'message' => $message,
            ],
            $httpCode
        );
    }

    protected function notFoundResponse()
    {
        return $this->errorResponse(404, "Not found", Response::HTTP_NOT_FOUND);
    }

    protected function accessDeniedResponse()
    {
        return $this->errorResponse(403, "Access denied", Response::HTTP_FORBIDDEN);
    }

    // ---------------------------------------------------------------------------------------------------------------

    protected function getRepository($name = null)
    {
        assert($name !== null, "repository name is null");

        return $this->getDoctrine()->getManager()->getRepository("ApiBundle:".$name);
    }

    protected function persistEntity($entity)
    {
        $em = $this->get('doctrine')->getManager();
        $em->persist($entity);
        try {
            $em->flush();
        } catch (Exception $e) {
            die('ERROR: '.$e->getMessage());
        }
    }

    protected function flush()
    {
        $em = $this->get('doctrine')->getManager();
        try {
            $em->flush();
        } catch (Exception $e) {
            die('ERROR: '.$e->getMessage());
        }
    }

    protected function removeEntity($entity)
    {
        $em = $this->get('doctrine')->getManager();
        $em->remove($entity);
        try {
            $em->flush();
        } catch (Exception $e) {
            die('ERROR: '.$e->getMessage());
        }
    }


    protected function getLogger()
    {
        return $this->get('logger');
    }

    // ---------------------------------------------------------------------------------------------------------------

    /**
     * Rename the given index on the given array
     * @param $newIndexes
     * @param $data
     * @return array
     */
    protected function renameArrayIndexes($newIndexes, $data)
    {
        $final = array();
        foreach ($data as $name => $value) {
            if (isset($newIndexes[$name])) {
                $name = $newIndexes[$name];
            }
            $final[$name] = $value;
        }

        return ($final);
    }

    /**
     * Return an array with the errors of the form
     * @param $form
     * @return array
     */
    protected function formErrorsToArray($form)
    {
        $errors = array();
        foreach ($form->getErrors() as $error) {
            $message = $error->getMessage();
            if ($message != null) {
                $errors[] = $message.(substr($message, -1) != '.' ? '.' : null);
            }
        }
        foreach ($form->all() as $key => $child) {
            if (($err = $this->formErrorsToArray($child))) {
                $errors[$key] = $err;
            }
        }

        return ($errors);
    }

    // ---------------------------------------------------------------------------------------------------------------

    protected function getEntityManager()
    {
        return $this->getDoctrine()->getEntityManager();
    }

    protected function deserialize($class, Request $request, $format = 'json')
    {
        $serializer = $this->get('serializer');
        $validator = $this->get('validator');

        try {
            $entity = $serializer->deserialize($request->getContent(), $class, $format);
        } catch (RuntimeException $e) {
            throw new HttpException(400, $e->getMessage());
        }
        if (count($errors = $validator->validate($entity))) {
            return $errors;
        }

        return $entity;
    }

    protected function entityHaveError($entity)
    {
        return count($this->getEntityErrors($entity)) !== 0;
    }

    /**
     * Returns the entity validation errors.
     *
     * @param $entity
     * @return array
     * @throws LogicException
     */
    protected function getEntityErrors($entity)
    {
        $validator = $this->get('validator');

        $errors = $validator->validate($entity);

        return $errors;
    }
}