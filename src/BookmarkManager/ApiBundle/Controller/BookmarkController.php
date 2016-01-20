<?php

namespace BookmarkManager\ApiBundle\Controller;

use BookmarkManager\ApiBundle\Tool\WebsiteCrawler;
use BookmarkManager\ApiBundle\Utils\BookmarkUtils;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use BookmarkManager\ApiBundle\DependencyInjection\BaseController;
use BookmarkManager\ApiBundle\Entity\Bookmark;
use BookmarkManager\ApiBundle\Form\BookmarkType;
use BookmarkManager\ApiBundle\Annotation\ApiErrors;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Bookmark controller.
 */
class BookmarkController extends BaseController
{

    /**
     * Lists all Bookmark entities.
     *
     * @ApiDoc(
     *     description="Get all the user bookmarks"
     * )
     *
     */
    public function getBookmarksAction()
    {
        $bookmarks = $this->getUser()->getBookmarks();

        return $this->successResponse(
            ['bookmarks' => $bookmarks]
        );
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
     *      { 102, "Invalid url" }
     * })
     *
     * @param Request $request
     * @return Response
     */
    public function postBookmarkAction(Request $request)
    {
        $data = $request->request->all();
        $bookmarkEntity = BookmarkUtils::createBookmark($this, $data);
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

        return $this->successResponse($bookmarkEntity, Response::HTTP_OK);
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

            return $this->successResponse($bookmarkEntity, Response::HTTP_OK);
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

        if (!$bookmarkEntity) {
            return $this->notFoundResponse();
        }

        if (!$tagEntity) {
            // TODO create tag.
        }

        if (!$bookmarkEntity->haveTag($tagEntity)) {
            $bookmarkEntity->addTag($tagEntity);
            $this->persistEntity($bookmarkEntity);
        }

        return $this->successResponse($bookmarkEntity);
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
    public function postCrawlerTestAction(Request $request) {
        $data = $request->request->all();
        $bookmarkEntity = BookmarkUtils::testCrawler($this, $data);
        return $this->successResponse($bookmarkEntity, Response::HTTP_OK);
    }
}
