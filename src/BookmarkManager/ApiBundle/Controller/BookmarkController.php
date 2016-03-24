<?php

namespace BookmarkManager\ApiBundle\Controller;

use BookmarkManager\ApiBundle\Crawler\CrawlerNotFoundException;
use BookmarkManager\ApiBundle\Crawler\CrawlerRetrieveDataException;
use BookmarkManager\ApiBundle\Crawler\WebsiteCrawler;
use BookmarkManager\ApiBundle\Utils\BookmarkUtils;
use Exception;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\Controller\Annotations as Rest;
use JMS\Serializer\SerializerBuilder;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use BookmarkManager\ApiBundle\DependencyInjection\BaseController;
use BookmarkManager\ApiBundle\Entity\Bookmark;
use BookmarkManager\ApiBundle\Form\BookmarkType;
use BookmarkManager\ApiBundle\Annotation\ApiErrors;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DomCrawler\Crawler;
use JMS\Serializer\SerializationContext;

/**
 * Bookmark controller.
 */
class BookmarkController extends BaseController
{

    /**
     * Lists all Bookmark entities.
     * @Rest\QueryParam(name="page", requirements="[\d]+", default="1", description="Page number.")
     * @Rest\QueryParam(name="limit", requirements="[\d]+", default="25", description="Number of results to display.")
     *
     * @ApiDoc(
     *     description="Get the user bookmarks"
     * )
     *
     * @ApiErrors({
     *      { 102, "Resource not found" }
     * })
     *
     * @param ParamFetcher $params
     * @return Response
     */
    public function getBookmarksAction(ParamFetcher $params)
    {
        $max_results_limit = $this->container->getParameter('max_results_limit');
        $default_results_limit = $this->container->getParameter('default_results_limit');

        $params = $params->all();

        $paging = array(
            'page' => $params['page'],
            'limit' => $params['limit'],
            'sort_by' => array('updated_at' => 'DESC'),
            'total' => 0,
            'results' => 0,
            'last_page' => 0,
        );

        if (isset($paging['page']) && intval($paging['page']) < 1) {
            $errors['page'][] = 'Page must be positive';
        }

        if (isset($paging['limit'])) {
            if (intval($paging['limit']) < 1) {
                $errors['limit'][] = 'Limit must be positive';
            }
            if (intval($paging['limit']) > $max_results_limit) {
                $errors['limit'][] = 'Limit is too high. Max '.$max_results_limit;
            }
        }

        if (!empty($errors)) {
            return ($this->errorResponse(101, $errors, Response::HTTP_BAD_REQUEST));
        }

        if (!isset($paging['limit']) || $paging['limit'] < 1) {
            $paging['limit'] = $default_results_limit;
        }

        if (!isset($paging['page']) || $paging['page'] < 1) {
            $paging['page'] = 1;
        }

        $paging['offset'] = (($paging['page'] - 1) * $paging['limit']);

        $query = $this->getRepository('Bookmark')->createQueryBuilder('p');

        // -- Select only the user's bookmarks
        $query
            ->leftJoin('p.owner', 'u')
            ->andWhere('u.id = :owner')
            ->setParameter('owner', $this->getUser()->getId());

        // -- Count total number of result.
        $countQuery = clone($query);
        $countQuery = $countQuery->select('COUNT(p)')->getQuery();
        $paging['total'] = intval($countQuery->getSingleScalarResult());

        // -- Set limit to the query
        $query = $query->setFirstResult($paging['offset'])
            ->setMaxResults($paging['limit'])
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery();

        $data = $query->getResult();

        // -- Set paging
        $paging['last_page'] = intval(ceil($paging['total'] / $paging['limit']));

        // -- Set number of results
        $paging['results'] = count($data);

        if (!(($paging['page'] <= $paging['last_page'] && $paging['page'] >= 1)
            || ($paging['total'] == 0 && $paging['page'] == 1))
        ) {
            return ($this->errorResponse(102, 'resource not found', Response::HTTP_BAD_REQUEST));
        }

        return ($this->successResponse(array('bookmarks' => $data), Response::HTTP_OK, ['list'], $paging));
    }

    /**
     * Creates a new Bookmark entity.
     *
     * @ApiDoc(
     *  description="Create a new bookmark",
     *  parameters={
     *      {"name"="name",        "dataType"="string",    "required"=true, "description"="The bookmark name. Must be unique." },
     *      {"name"="tags",     "dataType"="[User]",    "required"=true, "description"="The tags of the group. Can be empty." }
     *  },
     *  statusCodes={
     *      201="Returned when successful.",
     *      400="Returned when the parameters are invalid."
     *  }
     * )
     *
     * @ApiErrors({
     *      { 400, "The validation of the object failed. Check the message for more details." },
     *      { 101, "Bookmark already exists with the given url." },
     *      { 102, "Invalid url" },
     *     { 103, "Impossible to retrieve the website content."},
     *     { 104, "Unknown error when create the bookmark" },
     * })
     *
     * @param Request $request
     * @return Response
     */
    public function postBookmarkAction(Request $request)
    {
        $data = $request->request->all();

        try {
            $bookmarkEntity = BookmarkUtils::createBookmark($this, $data);
        } catch (CrawlerNotFoundException $e) {
            $this->getLogger()->info('[IMPORT] 404 for '.$data['url']);

            return $this->notFoundResponse();
        } catch (CrawlerRetrieveDataException $e) {
            $this->getLogger()->info('[IMPORT] Impossible to retrieve the website content for '.$data['url']);

            return $this->errorResponse(103, 'Impossible to retrieve the website content.', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            $this->getLogger()->info('[IMPORT] Unknown error  for '.$data['url']);

            return $this->errorResponse(104, 'Unknown error', Response::HTTP_BAD_REQUEST);
        }

        return $this->successResponse($bookmarkEntity, Response::HTTP_CREATED);
    }

    /**
     * Finds and returns a Bookmark entity.
     *
     * @ApiDoc(
     *  description="Get bookmark's information",
     *  requirements={
     *      {
     *          "name"="bookmarkId",
     *          "dataType"="integer",
     *          "requirement"="[\d]+",
     *          "description"="Bookmark id"
     *      }
     *  },
     *  statusCodes={
     *      200="Returned when successful.",
     *      404="Returned when the bookmark is not found.",
     *      400="Returned when the parameter is invalid.",
     *  }
     * )
     *
     * @ApiErrors({
     *      { 101, "The bookmark id must be numeric" },
     * })
     *
     * @param $bookmarkId
     * @return object|Response
     *
     */
    public function getBookmarkAction($bookmarkId)
    {
        if (!is_numeric($bookmarkId)) {
            return $this->errorResponse(101, "The id must be numeric", Response::HTTP_BAD_REQUEST);
        }

        $bookmarkEntity = $this->getRepository('Bookmark')->findOneBy(
            [
                'id' => $bookmarkId,
                'owner' => $this->getUser(),
            ]
        );

        if (!$bookmarkEntity) {
            return $this->notFoundResponse();
        }

        return $this->successResponse($bookmarkEntity, Response::HTTP_OK, ['alone']);
    }

    /**
     * Update an existing Bookmark entity.
     *
     * @ApiDoc(
     *  description="Update bookmark's information",
     *  requirements={
     *      {
     *          "name"="bookmarkId",
     *          "dataType"="integer",
     *          "requirement"="[\d]+",
     *          "description"="Bookmark id"
     *      }
     *  },
     *  statusCodes={
     *      200="Returned when successful.",
     *      404="Returned when the bookmark is not found.",
     *      400="Returned when the parameter is invalid.",
     *  }
     * )
     *
     * @ApiErrors({
     *      { 101, "The bookmark id must be numeric" },
     * })
     *
     * @param Request $request
     * @param $bookmarkId
     * @return Response
     */
    public function putBookmarkAction(Request $request, $bookmarkId)
    {
        $data = $request->request->all();

        if (!is_numeric($bookmarkId)) {
            return $this->errorResponse(101, "The id must be numeric", Response::HTTP_BAD_REQUEST);
        }

        $bookmarkEntity = $this->getRepository('Bookmark')->findOneBy(
            [
                'id' => $bookmarkId,
                'owner' => $this->getUser(),
            ]
        );

        if (!$bookmarkEntity) {
            return $this->notFoundResponse();
        }

        $editForm = $this->createForm(
            new BookmarkType(),
            $bookmarkEntity,
            [
                'ignoreRequired' => true,
            ]
        );

        $editForm->submit($data);

        if ($editForm->isValid()) {
            $this->persistEntity($bookmarkEntity);

            return $this->successResponse($bookmarkEntity, Response::HTTP_OK, ['alone']);
        }

        return $this->errorResponse(
            400,
            $this->formErrorsToArray($editForm),
            Response::HTTP_BAD_REQUEST
        );

    }

    /**
     * Deletes a Bookmark entity.
     *
     * @ApiDoc(
     *  description="Deletes bookmark's information",
     *  requirements={
     *      {
     *          "name"="bookmarkId",
     *          "dataType"="integer",
     *          "requirement"="[\d]+",
     *          "description"="Bookmark id"
     *      }
     *  },
     *  statusCodes={
     *      200="Returned when successful.",
     *      404="Returned when the bookmark is not found.",
     *      400="Returned when the parameter is invalid.",
     *  }
     * )
     *
     * @ApiErrors({
     *      { 101, "The bookmark id must be numeric" },
     * })
     * @param Request $request
     * @param $bookmarkId
     * @return Response
     */
    public function deleteBookmarkAction(Request $request, $bookmarkId)
    {
        if (!is_numeric($bookmarkId)) {
            return $this->errorResponse(101, "The id must be numeric", Response::HTTP_BAD_REQUEST);
        }

        $bookmarkEntity = $this->getRepository('Bookmark')->findOneBy(
            [
                'id' => $bookmarkId,
                'owner' => $this->getUser(),
            ]
        );

        if (!$bookmarkEntity) {
            return $this->notFoundResponse();
        }

        $this->removeEntity($bookmarkEntity);

        return $this->successResponse([], Response::HTTP_NO_CONTENT);
    }

    // ---------------------------------------------------------------------------------------------------------------
    //    /bookmarks/{id}/tags
    // ---------------------------------------------------------------------------------------------------------------


    /**
     * Update an existing Bookmark. Creates the new tag and add it to the bookmark.
     *
     * @ApiDoc(
     *  description="Create and add a tag to a bookmark",
     *  requirements={
     *      {
     *          "name"="bookmarkId",
     *          "dataType"="integer",
     *          "requirement"="[\d]+",
     *          "description"="Bookmark id"
     *      },
     *     {
     *          "name"="$tagId",
     *          "dataType"="integer",
     *          "requirement"="[\d]+",
     *          "description"="Tag id"
     *      }
     *  },
     *  statusCodes={
     *      200="Returned when successful.",
     *      404="Returned when the bookmark is not found.",
     *      400="Returned when the parameter is invalid."
     *  }
     * )
     *
     * @ApiErrors({
     *      { 101, "The bookmark id must be numeric" }
     * })
     *
     * @param Request $request
     * @param $bookmarkId
     * @return Response
     */
    public function postBookmarkTagAction(Request $request, $bookmarkId)
    {

        if (!is_numeric($bookmarkId)) {
            return $this->errorResponse(101, "The id must be numeric", Response::HTTP_BAD_REQUEST);
        }

        $bookmarkEntity = $this->getRepository('Bookmark')->findOneBy(
            [
                'id' => $bookmarkId,
                'owner' => $this->getUser(),
            ]
        );

        return $this->errorResponse(null, null, Response::HTTP_NOT_IMPLEMENTED);

        // TODO: Create the tag if not exists for this user.
//        $tagEntity = $tagService->createTag($request);

//        if (!$bookmarkEntity || !$tagEntity) {
//            return $this->notFoundResponse();
//        }
//
//        if (!$bookmarkEntity->haveTag($tagEntity)) {
//            $bookmarkEntity->addTag($tagEntity);
//            $this->persistEntity($bookmarkEntity);
//        }
//
//        return $this->successResponse($bookmarkEntity);
    }

    // ---------------------------------------------------------------------------------------------------------------
    //    /bookmarks/{id}/tags/{id}
    // ---------------------------------------------------------------------------------------------------------------


    /**
     * Update an existing Bookmark by adding a tag.
     * @ApiDoc(
     *  description="Add tag to bookmark",
     *  requirements={
     *      {
     *          "name"="bookmarkId",
     *          "dataType"="integer",
     *          "requirement"="[\d]+",
     *          "description"="Bookmark id"
     *      },
     *     {
     *          "name"="$tagId",
     *          "dataType"="integer",
     *          "requirement"="[\d]+",
     *          "description"="Tag id"
     *      }
     *  },
     *  statusCodes={
     *      201="Returned when successfully created.",
     *      404="Returned when the bookmark is not found.",
     *      400="Returned when the parameter is invalid."
     *  }
     * )
     *
     * @ApiErrors({
     *      { 101, "The bookmark id must be numeric" },
     *      { 102, "The tag id must be numeric" }
     * })
     * @param Request $request
     * @param $bookmarkId
     * @param $tagId
     * @return Response
     */
    public function postBookmarkTagsAction(Request $request, $bookmarkId, $tagId)
    {

        if (!is_numeric($bookmarkId)) {
            return $this->errorResponse(101, "The id must be numeric", Response::HTTP_BAD_REQUEST);
        }

        if (!is_numeric($tagId)) {
            return $this->errorResponse(102, "The id must be numeric", Response::HTTP_BAD_REQUEST);
        }

        $bookmarkEntity = $this->getRepository('Bookmark')->findOneBy(
            [
                'id' => $bookmarkId,
                'owner' => $this->getUser(),
            ]
        );

        $tagEntity = $this->getRepository('Tag')->findOneBy(
            [
                'id' => $tagId,
                'owner' => $this->getUser(),
            ]
        );

        if (!$bookmarkEntity || !$tagEntity) {
            return $this->notFoundResponse();
        }

        if (!$bookmarkEntity->haveTag($tagEntity)) {
            $bookmarkEntity->addTag($tagEntity);
            $this->persistEntity($bookmarkEntity);
        }

        return $this->successResponse($bookmarkEntity, Response::HTTP_CREATED, ['alone']);
    }

    /**
     * Update an existing Bookmark by removing a tag.
     * @ApiDoc(
     *  description="Remove tag from bookmark",
     *  requirements={
     *      {
     *          "name"="bookmarkId",
     *          "dataType"="integer",
     *          "requirement"="[\d]+",
     *          "description"="Bookmark id"
     *      },
     *     {
     *          "name"="$tagId",
     *          "dataType"="integer",
     *          "requirement"="[\d]+",
     *          "description"="Tag id"
     *      }
     *  },
     *  statusCodes={
     *      200="Returned when successful.",
     *      404="Returned when the bookmark is not found.",
     *      400="Returned when the parameter is invalid."
     *  }
     * )
     *
     * @ApiErrors({
     *      { 101, "The bookmark id must be numeric" },
     *      { 102, "The tag id must be numeric" }
     * })
     * @param Request $request
     * @param $bookmarkId
     * @param $tagId
     * @return Response
     */
    public function deleteBookmarkTagAction(Request $request, $bookmarkId, $tagId)
    {

        if (!is_numeric($bookmarkId)) {
            return $this->errorResponse(101, "The id must be numeric", Response::HTTP_BAD_REQUEST);
        }

        if (!is_numeric($tagId)) {
            return $this->errorResponse(102, "The id must be numeric", Response::HTTP_BAD_REQUEST);
        }

        $bookmarkEntity = $this->getRepository('Bookmark')->findOneBy(
            [
                'id' => $bookmarkId,
                'owner' => $this->getUser(),
            ]
        );

        $tagEntity = $this->getRepository('Tag')->findOneBy(
            [
                'id' => $tagId,
                'owner' => $this->getUser(),
            ]
        );

        if (!$bookmarkEntity || !$tagEntity) {
            return $this->notFoundResponse();
        }

        $bookmarkEntity->removeTag($tagEntity);

        $this->persistEntity($bookmarkEntity);

        return $this->successResponse($bookmarkEntity);
    }

    /**
     * Should be used only for test
     *
     * @ApiDoc(
     *  description="test crawler"
     * )
     * @param Request $request
     * @return Response
     * @throws \BookmarkManager\ApiBundle\Exception\BMErrorResponseException
     */
    public function postCrawlerTestAction(Request $request)
    {
        $data = $request->request->all();

        try {
            $bookmarkEntity = BookmarkUtils::testCrawler($this, $data);
        } catch (CrawlerNotFoundException $e) {
            $this->getLogger()->info('[IMPORT] 404 for '.$data['url']);

            return $this->notFoundResponse();
        } catch (CrawlerRetrieveDataException $e) {
            $this->getLogger()->info('[IMPORT] Impossible to retrieve the website content for '.$data['url']);

            return $this->errorResponse(103, 'Impossible to retrieve the website content.', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            $this->getLogger()->info('[IMPORT] Unknown error  for '.$data['url']);

            return $this->errorResponse(104, 'Unknown error', Response::HTTP_BAD_REQUEST);
        }

        return $this->successResponse($bookmarkEntity, Response::HTTP_OK, ['alone']);
    }
}
