<?php

namespace BookmarkManager\ApiBundle\Controller;

use BookmarkManager\ApiBundle\Crawler\CrawlerNotFoundException;
use BookmarkManager\ApiBundle\Crawler\CrawlerRetrieveDataException;
use BookmarkManager\ApiBundle\Crawler\WebsiteCrawler;
use BookmarkManager\ApiBundle\Exception\BmAlreadyExistsException;
use BookmarkManager\ApiBundle\Exception\BmErrorResponseException;
use BookmarkManager\ApiBundle\Utils\BookmarkUtils;
use BookmarkManager\ApiBundle\Utils\TagUtils;
use BookmarkManager\ApiBundle\Repository\BookmarkRepository;
use Exception;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\Controller\Annotations as Rest;
use JMS\Serializer\SerializerBuilder;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use BookmarkManager\ApiBundle\DependencyInjection\BaseController;
use BookmarkManager\ApiBundle\Entity\Bookmark;
use BookmarkManager\ApiBundle\Entity\Book;
use BookmarkManager\ApiBundle\Form\BookmarkFormType;
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
        $params = $params->all();

        $options = [
            'parentType' => BookmarkRepository::PARENT_TYPE_USER,
            'id' => $this->getUser()->getId(),
            'max_results_limit' => $this->container->getParameter('max_results_limit'),
            'default_results_limit' => $this->container->getParameter('default_results_limit'),
        ];

        $result = $this->getRepository(Bookmark::REPOSITORY_NAME)->findAllOrderedByName(
            $params,
            $options
        );

        return $this->successResponse(array('bookmarks' => $result['bookmarks']),
                Response::HTTP_OK,
                [Bookmark::GROUP_MULTIPLE],
                $result['paging']
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
        //return $this->postCrawlerTestAction($request);
        $data = $request->request->all();

        try {
            $bookmarkEntity = BookmarkUtils::createBookmark($this, $data, true);
        } catch (BmAlreadyExistsException $e) {
            return $this->errorResponseWithException($e);
        }
        catch (Exception $e) {
            $this->getLogger()->info('[IMPORT] Unknown error  for '.$data['url']);

            print_r($e->getTraceAsString());

            return $this->errorResponse(104, 'Unknown error ' . $e, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->persistEntity($bookmarkEntity);

        return $this->successResponse(
            $bookmarkEntity,
            Response::HTTP_CREATED,
            [Bookmark::GROUP_SINGLE]
        );
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

        $bookmarkEntity = $this->getRepository(Bookmark::REPOSITORY_NAME)->findOneBy(
            [
                'id' => $bookmarkId,
                'owner' => $this->getUser(),
            ]
        );

        if (!$bookmarkEntity) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(
            $bookmarkEntity,
            Response::HTTP_OK,
            [Bookmark::GROUP_SINGLE]
        );
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

        $bookmarkEntity = $this->getRepository(Bookmark::REPOSITORY_NAME)->findOneBy(
            [
                'id' => $bookmarkId,
                'owner' => $this->getUser(),
            ]
        );

        if (!$bookmarkEntity) {
            return $this->notFoundResponse();
        }

        $editForm = $this->createForm(
            new BookmarkFormType(),
            $bookmarkEntity,
            [
                'ignoreRequired' => true,
            ]
        );

        $editForm->submit($data);

        if ($editForm->isValid()) {
            $this->persistEntity($bookmarkEntity);

            return $this->successResponse(
                $bookmarkEntity,
                Response::HTTP_OK,
                Bookmark::GROUP_SINGLE
            );
        }

        return $this->errorResponse(
            400,
            $this->formErrorsToArray($editForm),
            Response::HTTP_BAD_REQUEST
        );

    }

    /**
     * Update an existing Bookmark entity.
     *
     * @Rest\Put("/bookmarks/{bookmarkId}/recrawl")
     *
     * @ApiDoc(
     *  description="Update bookmark's content by crawling it",
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
     public function recrawlBookmarkAction(Request $request, $bookmarkId)
     {
         $data = $request->request->all();
 
         if (!is_numeric($bookmarkId)) {
             return $this->errorResponse(101, "The id must be numeric", Response::HTTP_BAD_REQUEST);
         }
 
         $bookmarkEntity = $this->getRepository(Bookmark::REPOSITORY_NAME)->findOneBy(
             [
                 'id' => $bookmarkId,
                 'owner' => $this->getUser(),
             ]
         );
 
         if (!$bookmarkEntity) {
             return $this->notFoundResponse();
         }
 
         try {
            $data = [
                'url' => $bookmarkEntity->getUrl(),
            ];
            $newBookmark = BookmarkUtils::createBookmark($this, $data, false);
        } catch (BmAlreadyExistsException $e) {
            return $this->errorResponseWithException($e);
        } catch (Exception $e) {
            $this->getLogger()->info('[IMPORT] Unknown error  for '.$bookmarkEntity->getUrl());

            print_r($e->getTraceAsString());

            return $this->errorResponse(104, 'Unknown error ' . $e, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
 
        // update bookmark data 
        $bookmarkEntity->setContent($newBookmark->getContent());
        $bookmarkEntity->setReadingTime($newBookmark->getReadingTime());
        $bookmarkEntity->setTitle($newBookmark->getTitle());
        $bookmarkEntity->setDescription($newBookmark->getDescription());
        $bookmarkEntity->setType($newBookmark->getType());
        $bookmarkEntity->setPreviewPicture($newBookmark->getPreviewPicture());
        $bookmarkEntity->setWebsiteInfo($newBookmark->getWebsiteInfo());
        $bookmarkEntity->setCrawlerStatus($newBookmark->getCrawlerStatus());

        $this->persistEntity($bookmarkEntity);

        return $this->successResponse(
            $bookmarkEntity,
            Response::HTTP_OK,
            Bookmark::GROUP_SINGLE
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

        $bookmarkEntity = $this->getRepository(Bookmark::REPOSITORY_NAME)->findOneBy(
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
     * Update an existing Bookmark's tags.
     *
     * @ApiDoc(
     *  description="Update tag(s) of the bookmark",
     *  requirements={
     *      {
     *          "name"="bookmarkId",
     *          "dataType"="integer",
     *          "requirement"="[\d]+",
     *          "description"="Bookmark id"
     *      }
     *  },
     *  parameters={
     *     {
     *          "name"="tag",
     *          "dataType"="Tag|integer",
     *          "required"=false
     *     },
     *     {
     *          "name"="tags",
     *          "dataType"="Array: Tag|integer",
     *          "required"=false
     *     }
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
     *      { 102, "The tag id must be numeric" },
     *      { 103, "You must specify a 'tag' or 'tags' field" },
     *      { 104, "None tag id found" }
     * })
     * @param Request $request
     * @param $bookmarkId
     * @return Response
     */
    public function putBookmarkTagsAction(Request $request, $bookmarkId)
    {

        if (!is_numeric($bookmarkId)) {
            return $this->errorResponse(101, "The id must be numeric", Response::HTTP_BAD_REQUEST);
        }

        // -- get bookmark
        $bookmarkEntity = $this->getRepository(Bookmark::REPOSITORY_NAME)->findOneBy(
            [
                'id' => $bookmarkId,
                'owner' => $this->getUser(),
            ]
        );

        if (!$bookmarkEntity) {
            return $this->notFoundResponse();
        }

        // -- Format tags data
        $data = $request->request->all();

        $tags = [];
        if (!empty($data['tags'])) {
            $tags = $data['tags'];
        } elseif (!empty($data['tag'])) {
            $tags = [$data['tag']];
        }

        // reset tags
        $bookmarkEntity->clearTags();

        // for each tag, try to create it if does not exists.
        foreach ($tags as $tag) {

            $tagId = null;
            if (is_numeric($tag)) {
                $tagId = $tag;
            } elseif (isset($tag['id'])) {
                $tagId = $tag['id'];
            }

            if (!is_null($tagId)) {
                $tagEntity = $this->getRepository('Tag')->findOneBy(
                    [
                        'id' => $tagId,
                        'owner' => $this->getUser(),
                    ]
                );
            } else {
                $tagEntity = TagUtils::createTag($this, $tag);
            }

            if (!$tagEntity) {
                return $this->errorResponse(104, 'No tag found for id ' . $tagId, Response::HTTP_BAD_REQUEST);
            }
            
            $bookmarkEntity->addTag($tagEntity);
        }
        $this->persistEntity($bookmarkEntity);

        return $this->successResponse($bookmarkEntity, Response::HTTP_CREATED, Bookmark::GROUP_SINGLE);
    }


    /**
     * Update an existing Bookmark by adding one or multiple tag.
     *
     * @ApiDoc(
     *  description="Add tag(s) to bookmark",
     *  requirements={
     *      {
     *          "name"="bookmarkId",
     *          "dataType"="integer",
     *          "requirement"="[\d]+",
     *          "description"="Bookmark id"
     *      }
     *  },
     *  parameters={
     *     {
     *          "name"="tag",
     *          "dataType"="Tag|integer",
     *          "required"=false
     *     },
     *     {
     *          "name"="tags",
     *          "dataType"="Array: Tag|integer",
     *          "required"=false
     *     }
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
     *      { 102, "The tag id must be numeric" },
     *      { 103, "You must specify a 'tag' or 'tags' field" },
     *      { 104, "None tag id found" }
     * })
     * @param Request $request
     * @param $bookmarkId
     * @return Response
     */
    public function postBookmarkTagsAction(Request $request, $bookmarkId)
    {

        if (!is_numeric($bookmarkId)) {
            return $this->errorResponse(101, "The id must be numeric", Response::HTTP_BAD_REQUEST);
        }

        // -- get bookmark
        $bookmarkEntity = $this->getRepository(Bookmark::REPOSITORY_NAME)->findOneBy(
            [
                'id' => $bookmarkId,
                'owner' => $this->getUser(),
            ]
        );

        if (!$bookmarkEntity) {
            return $this->notFoundResponse();
        }

        // -- Format tags data
        $data = $request->request->all();

        if (!empty($data['tags'])) {
            $tags = $data['tags'];
        } elseif (!empty($data['tag'])) {
            $tags = [$data['tag']];
        } else {
            return $this->errorResponse(
                103,
                'You must specify a "tag" or "tags" field',
                Response::HTTP_BAD_REQUEST
            );

        }

        foreach ($tags as $tag) {

            if (is_numeric($tag)) {
                $tagId = $tag;
            } elseif (isset($tag['id'])) {
                $tagId = $tag['id'];
            } else {
                return $this->errorResponse(104, 'None tag id found', Response::HTTP_BAD_REQUEST);
            }

            $tagEntity = $this->getRepository('Tag')->findOneBy(
                [
                    'id' => $tagId,
                    'owner' => $this->getUser(),
                ]
            );

            if (!$tagEntity) {
                $tagEntity = TagUtils::createTag($this, $tag);
            }

            $bookmarkEntity->addTag($tagEntity);
        }
        $this->persistEntity($bookmarkEntity);

        return $this->successResponse($bookmarkEntity, Response::HTTP_CREATED, Bookmark::GROUP_SINGLE);
    }

    /**
     * Update an existing Bookmark by removing one or multiple tag.
     * @ApiDoc(
     *  description="Remove tag(s) from bookmark",
     *  requirements={
     *      {
     *          "name"="bookmarkId",
     *          "dataType"="integer",
     *          "requirement"="[\d]+",
     *          "description"="Bookmark id"
     *      }
     *  },
     *  parameters={
     *     {
     *          "name"="tag",
     *          "dataType"="Tag|integer",
     *          "required"=false
     *     },
     *     {
     *          "name"="tags",
     *          "dataType"="Array: Tag|integer",
     *          "required"=false
     *     }
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
     *      { 102, "The tag id must be numeric" },
     *      { 103, "You must specify a 'tag' or 'tags' field" },
     *      { 104, "None tag id found" }
     * })
     * @param Request $request
     * @param $bookmarkId
     * @return Response
     */
    public function deleteBookmarkTagsAction(Request $request, $bookmarkId)
    {

        if (!is_numeric($bookmarkId)) {
            return $this->errorResponse(101, "The id must be numeric", Response::HTTP_BAD_REQUEST);
        }

        // -- get bookmark
        $bookmarkEntity = $this->getRepository(Bookmark::REPOSITORY_NAME)->findOneBy(
            [
                'id' => $bookmarkId,
                'owner' => $this->getUser(),
            ]
        );

        if (!$bookmarkEntity) {
            return $this->notFoundResponse();
        }

        // -- Format tags data
        $data = $request->request->all();

        if (!empty($data['tags'])) {
            $tags = $data['tags'];
        } elseif (!empty($data['tag'])) {
            $tags = [$data['tag']];
        } else {
            return $this->errorResponse(
                103,
                'You must specify a "tag" or "tags" field',
                Response::HTTP_BAD_REQUEST
            );

        }

        foreach ($tags as $tag) {

            if (is_numeric($tag)) {
                $tagId = $tag;
            } elseif (isset($tag['id'])) {
                $tagId = $tag['id'];
            } else {
                return $this->errorResponse(104, 'None tag id found', Response::HTTP_BAD_REQUEST);
            }

            $tagEntity = $this->getRepository('Tag')->findOneBy(
                [
                    'id' => $tagId,
                    'owner' => $this->getUser(),
                ]
            );

            if ($tagEntity) {
                $bookmarkEntity->removeTag($tagEntity);
            }
        }
        $this->persistEntity($bookmarkEntity);

        return $this->successResponse($bookmarkEntity, Response::HTTP_OK, Bookmark::GROUP_SINGLE);
    }

    /**
     * Should be used only for test
     *
     * @ApiDoc(
     *  description="test crawler"
     * )
     * @param Request $request
     * @return Response
     * @throws \BookmarkManager\ApiBundle\Exception\BmErrorResponseException
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
//            var_dump($e->getTrace());
            $this->getLogger()->info('[IMPORT] Unknown error  for '.$data['url']);

            print_r($e->getTraceAsString());

            return $this->errorResponse(104, 'Unknown error' . $e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return $this->successResponse($bookmarkEntity, Response::HTTP_OK, Bookmark::GROUP_SINGLE);
    }


    // ---------------------------------------------------------------------------------------------------------------
    //    /bookmarks/{bookmarkId}books
    // ---------------------------------------------------------------------------------------------------------------

    /**
     * @Rest\Post("/bookmarks/{bookmarkId}/books")
     * 
     * Allows to add a bookmark to multiple books
     */
    public function postBooksAction(Request $request, $bookmarkId) {
        if (!is_numeric($bookmarkId)) {
            return $this->errorResponse(101, "The id must be numeric", Response::HTTP_BAD_REQUEST);
        }

        $bookmark = $this->getRepository(Bookmark::REPOSITORY_NAME)->find($bookmarkId);
        if (!$bookmark) {
            return $this->notFoundResponse();
        }

        $data = $request->request->all();

        $booksData = $data['books'];

        foreach ($booksData as $bookData) {
            $book = $this->getRepository(Book::REPOSITORY_NAME)->find($bookData['id']);

            if ($book !== null) {
                $book->addBookmark($bookmark);
                $bookmark->addBook($book);
                $this->persistentity($book);
                $this->persistentity($bookmark);
            }
        }

        return $this->successResponse(
            $booksData,
            Response::HTTP_CREATED
            // Book::GROUP_SINGLE
        );
    }
}
