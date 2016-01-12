<?php

namespace BookmarkManager\ApiBundle\Controller;

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
        $bookmarkEntity = new Bookmark();

        $form = $this->createForm(
            new BookmarkType(),
            $bookmarkEntity,
            ['method' => 'POST']
        );

        $form->submit($data, false);

        if ($form->isValid()) {

            $url = $this->cleanUrl($bookmarkEntity->getUrl());

            if (filter_var($url, FILTER_VALIDATE_URL) === false) {
                return $this->errorResponse(102, "Invalid url", Response::HTTP_BAD_REQUEST);
            }

            // Search if bookmark already exists.
            $exists = $this->getRepository('Bookmark')->findOneBy(
                [
                    'owner' => $this->getUser(),
                    'url' => $url,
                ]
            );

            if ($exists) {
                return $this->errorResponse(101, "Bookmark already exists with this url.");
            }

            $bookmarkEntity->setUrl($url);
            $bookmarkEntity = $this->crawlWebsite($bookmarkEntity);
            $bookmarkEntity->setOwner($this->getUser());

            $this->persistEntity($bookmarkEntity);

            return $this->successResponse($bookmarkEntity, Response::HTTP_CREATED);
        }

        return $this->errorResponse(
            400,
            $this->formErrorsToArray($form),
            Response::HTTP_BAD_REQUEST
        );

    }

    /**
     * Remove useless query parameters from url
     * @param $url
     * @return mixed
     */
    protected function cleanUrl($url)
    {
        // Add http prefix if not exists
        if (preg_match("#https?://#", $url) === 0) {
            $url = 'http://'.$url;
        }

        $QUERIES_TO_REMOVE = [
            'utm_source',
            'utm_medium',
            'utm_term',
            'utm_content',
            'utm_campaign',
        ];

        $parsedUrl = parse_url($url);

        if ($parsedUrl) {

            // Remove queries that must be removed.
            if (isset($parsedUrl['query'])) {
                $parsedUrl['query'] = implode(
                    '&',
                    array_filter(
                        explode('&', $parsedUrl['query']),
                        function ($param) use ($QUERIES_TO_REMOVE) {
                            $queryName = explode('=', $param)[0];
                            $found = array_search($queryName, $QUERIES_TO_REMOVE) !== false;

                            return !$found;
                        }
                    )
                );

                if ($parsedUrl['query'] === '') {
                    unset($parsedUrl['query']);
                }
            }
            $newUrl = $this->buildUrl($parsedUrl);

        } else {
            $newUrl = $url;
        }

        return $newUrl;
    }

    protected function buildUrl($parsed_url)
    {
        $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'].'://' : '';
        $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port = isset($parsed_url['port']) ? ':'.$parsed_url['port'] : '';
        $user = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass = isset($parsed_url['pass']) ? ':'.$parsed_url['pass'] : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query = isset($parsed_url['query']) ? '?'.$parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#'.$parsed_url['fragment'] : '';

        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    protected function crawlWebsite(Bookmark $bookmark)
    {
        // TODO: Handle 404.
        $html = $this->get_data($bookmark->getUrl());

        if ($html) {

            $crawler = new Crawler($html);

            $title = $crawler->filter('head > title')->text();
//            $description = $crawler->filter('meta[name="description"]');
//            $ogTitle = $crawler->filter('meta[property="title"]');
//            $ogType = null;
//            $ogImage = null;

            // -- Meta name

            $metaNameCrawler = $crawler->filter('head > meta')->reduce(
                function (Crawler $node) {
                    $nameValue = $node->attr('name');

                    // We get all non null meta with a name or a property
                    return null !== $nameValue && null !== $node->attr('content');
                }
            );

            $metaNames = [];
            foreach ($metaNameCrawler as $item) {
                $name = $item->getAttribute('name');
                $content = $item->getAttribute('content');

                $metaNames[$name] = $content;
            }

            // -- Meta property

            $metaPropertyCrawler = $crawler->filter('head > meta')->reduce(
                function (Crawler $node) {
                    $propertyValue = $node->attr('property');

                    // We get all non null meta with a name or a property
                    return null !== $propertyValue && null !== $node->attr('content');
                }
            );

            $metaProperties = [];
            foreach ($metaPropertyCrawler as $item) {
                $name = $item->getAttribute('property');
                $content = $item->getAttribute('content');

                $metaProperties[$name] = $content;
            }

//            var_dump($metaNames);
//            var_dump($metaProperties);

            // All the information that we want to retrieve.
            $websiteInfo = [
                'author' => null,
                'keywords' => null,
                'og:title' => null,
                'og:type' => null,
                'og:image' => null,
                'og:description' => null,
            ];

            // -- Retrieve website's information

            $websiteInfo['author'] = $this->array_get_key('Author', $metaNames);
            $websiteInfo['keywords'] = $this->array_get_key('Keywords', $metaNames);
            $websiteInfo['description'] = $this->array_get_key('description', $metaProperties);

            // See http://ogp.me/
            $websiteInfo['og:title'] = $this->array_get_key('og:title', $metaProperties);
            $websiteInfo['og:type'] = $this->array_get_key('og:type', $metaProperties);
            $websiteInfo['og:image'] = $this->array_get_key('og:image', $metaProperties);
            $websiteInfo['og:description'] = $this->array_get_key('og:description', $metaProperties);

            if ($websiteInfo['description'] === null or strlen($websiteInfo['description']) === 0) {
                $description = $websiteInfo['og:description'];
            } else {
                $description = $websiteInfo['description'];
            }

//            var_dump($websiteInfo);

            // TODO: Add $websiteInfo to a new entity.

            $bookmark->setTitle($title);
            $bookmark->setDescription($description);
        }

        // TODO: add https://github.com/j0k3r/php-readability

        return $bookmark;
    }

    protected function array_get_key($key, $array)
    {
        return isset($array[$key]) ? $array[$key] : null;
    }

    protected function get_data($url)
    {
        $ch = curl_init();
        $timeout = 15;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
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

}
