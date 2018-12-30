<?php

namespace BookmarkManager\ApiBundle\DependencyInjection;

use BookmarkManager\ApiBundle\Utils\ArrayUtils;
use BookmarkManager\ApiBundle\Utils\RequestUtils;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;

use JMS\Serializer\SerializationContext;
use Symfony\Component\Form\Form;
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
     * @param array $groups
     * @return Response The response object
     * @internal param array $group
     */
    protected function buildResponse($data = array(), $httpCode = 200, $groups = [])
    {

        $view = View::create();

        $view->setStatusCode($httpCode);
        $view->setData($data);

        if (!empty($groups)) {
            $context = SerializationContext::create()
                ->enableMaxDepthChecks()
                ->setGroups($groups);
            $view->setSerializationContext($context);
        }

        return $this->handleView($view);
    }

    /**
     * This function build a Response object based on the $data and $httpCode given.
     * @param array $data
     * @param int $httpCode You must use Response defines
     * @param array $paging
     * @param array $groups
     * @return Response The response to return
     */
    protected function successResponse($data = array(), $httpCode = Response::HTTP_OK, $groups = [], $paging = [])
    {
        if (!is_array($groups)) {
            $groups = [ $groups ];
        }
        if (!empty($paging)) {
            // TODO: remove this and fix bad data given as string instead of int
            if (isset($paging['page'])) {
                $paging['page'] = (int)$paging['page'];
            }
            if (isset($paging['limit'])) {
                $paging['limit'] = (int)$paging['limit'];
            }
            // END

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
                        isset($paging['routeParams']) ? $paging['routeParams'] : array(),
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
                                'page' => (int)$paging['page'],
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

        return $this->buildResponse($data, $httpCode, $groups);
    }

    /**
     * Send a formatted error response
     * @param $code int The api error code
     * @param $message string A comprehensive message of the error, for the developper
     * @param $httpCode int The httpCode to return
     * @return Response The response to return
     */
    protected function errorResponse($code, $message, $httpCode = 400)
    {

        return $this->buildResponse(
            [
                'code' => $code,
                'message' => $message,
            ],
            $httpCode
        );
    }

     /**
      * BmErrorResponseException | BMAlreadyExistsException
     */
    protected function errorResponseWithException($e)
    {

        return $this->buildResponse(
            [
                'code' => $e->getErrorCode(),
                'message' => $e->getErrorMessage(),
                'detail' => $e->getErrorDetail()
            ],
            $e->getHttpCode()
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

    protected function notImplementedResponse()
    {
        return $this->errorResponse(501, "Not implemented", Response::HTTP_NOT_IMPLEMENTED);
    }

    // ---------------------------------------------------------------------------------------------------------------

    /**
     * The JSON PUT data will include all attributes in the entity, even those that are not update by the user and are
     * not in the form.
     * We need to remove these extra fields or we will get a "This form should not contain extra fields" Form Error.
     * @param Request $request
     * @param Form $form
     * @return Form
     */
    protected function bindFormForPut(Request $request, Form $form)
    {
        return RequestUtils::bindDataToForm($data = $request->request->all(), $form);
    }

    // ---------------------------------------------------------------------------------------------------------------


    /**
     *
     * ```php
     * $this->getRepository(Bookmark::REPOSITORY_NAME);
     * ```
     *
     * @param null $name The repository name. Use a constant: Ex: `BOOKMARK::REPOSITORY_NAME`
     * @return \Doctrine\Common\Persistence\ObjectRepository
     *
     */
    public function getRepository($name = null)
    {
        assert($name !== null, "repository name is null");

        return $this->getDoctrine()->getManager()->getRepository("ApiBundle:".$name);
    }

    public function persistEntity($entity)
    {
        $em = $this->get('doctrine')->getManager();
        $em->persist($entity);
        try {
            $em->flush();
        } catch (Exception $e) {
            die('ERROR: '.$e->getMessage());
        }
    }

    public function flush()
    {
        $em = $this->get('doctrine')->getManager();
        try {
            $em->flush();
        } catch (Exception $e) {
            die('ERROR: '.$e->getMessage());
        }
    }

    public function removeEntity($entity)
    {
        $em = $this->get('doctrine')->getManager();
        $em->remove($entity);
        try {
            $em->flush();
        } catch (Exception $e) {
            die('ERROR: '.$e->getMessage());
        }
    }

    public function getLogger()
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
        return ArrayUtils::renameArrayIndexes($newIndexes, $data);
    }

    /**
     * Return an array with the errors of the form
     * @param $form
     * @return array
     */
    protected function formErrorsToArray(Form $form)
    {
       return ArrayUtils::formErrorsToArray($form);
    }

    // ---------------------------------------------------------------------------------------------------------------
    protected function toJSON($obj)
    {
        return $this->container->get('serializer')->serialize($obj, 'json');
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

    // ---------------------------------------------------------------------------------------------------------------


}
