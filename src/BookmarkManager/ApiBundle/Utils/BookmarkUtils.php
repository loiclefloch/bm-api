<?php
/**
 * Created by PhpStorm.
 * User: loic
 * Date: 20/01/16
 * Time: 12:51
 */

namespace BookmarkManager\ApiBundle\Utils;

use BookmarkManager\ApiBundle\Entity\Bookmark;
use BookmarkManager\ApiBundle\Entity\Tag;
use BookmarkManager\ApiBundle\Exception\BMErrorResponseException;
use BookmarkManager\ApiBundle\Form\BookmarkType;
use BookmarkManager\ApiBundle\Form\TagType;
use BookmarkManager\ApiBundle\Tool\WebsiteCrawler;
use Symfony\Component\HttpFoundation\Response;

class BookmarkUtils
{

    /**
     *
     * ApiErrorCode:
     * - 102: Invalid url
     * - 400: Form error
     * - 101, Bookmark already exists with this url.
     *
     * @param $controller
     * @param $data
     * @return Tag
     * @throws BMErrorResponseException
     */
    public static function createBookmark($controller, $data)
    {
        $bookmarkEntity = new Bookmark();

        $form = $controller->createForm(
            new BookmarkType(),
            $bookmarkEntity,
            ['method' => 'POST']
        );

        $form = RequestUtils::bindDataToForm($data, $form);

        if ($form->isValid()) {

            $crawler = new WebsiteCrawler();

            $url = $crawler->cleanUrl($bookmarkEntity->getUrl());

            if (filter_var($url, FILTER_VALIDATE_URL) === false) {
                throw new BmErrorResponseException(102, "Invalid url", Response::HTTP_BAD_REQUEST);
            }

            // Search if bookmark already exists.
            $exists = $controller->getRepository('Bookmark')->findOneBy(
                [
                    'owner' => $controller->getUser()->getId(),
                    'url' => $url,
                ]
            );

            if ($exists) {
                throw new BMAlreadyExistsException(101, "Bookmark already exists with this url.");
            }

            $bookmarkEntity->setUrl($url);
            $bookmarkEntity = $crawler->crawlWebsite($bookmarkEntity);
            $bookmarkEntity->setOwner($controller->getUser());

            // -- set tags
            if (isset($data['tags'])) {

                foreach ($data['tags'] as $tagData) {

                    $tag = $controller->getRepository('Tag')->findOneBy(
                        [
                            'name' => $tagData['name'],
                            'owner' => $controller->getUser(),
                        ]
                    );

                    if ($tag) {
                        $bookmarkEntity->addTag($tag);
                    } else { // create the tag

                        try {
                            $tag = TagUtils::createTag($controller, $tagData);
                            $bookmarkEntity->addTag($tag);
                        } catch (BMErrorResponseException $e) {
                            // do nothing
                            $controller->getLogger()->info('Catch exception '.$e->getMessage());
                        }

                    }
                }
            }

            $controller->persistEntity($bookmarkEntity);

            return $bookmarkEntity;
        }

        throw new BMErrorResponseException(400, ArrayUtils::formErrorsToArray($form), Response::HTTP_BAD_REQUEST);
    }


    public static function testCrawler($controller, $data) {
        $bookmarkEntity = new Bookmark();

        $form = $controller->createForm(
            new BookmarkType(),
            $bookmarkEntity,
            ['method' => 'POST']
        );

        $form = RequestUtils::bindDataToForm($data, $form);
        $crawler = new WebsiteCrawler();

        $url = $crawler->cleanUrl($bookmarkEntity->getUrl());

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new BmErrorResponseException(102, "Invalid url", Response::HTTP_BAD_REQUEST);
        }

        $bookmarkEntity->setUrl($url);
        $bookmarkEntity = $crawler->crawlWebsite($bookmarkEntity);

        return $bookmarkEntity;
    }

    public static function getBookmarkForUrl($controller, $url) {

        $crawler = new WebsiteCrawler();
        $url = $crawler->cleanUrl($bookmarkEntity->getUrl());

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new BmErrorResponseException(102, "Invalid url", Response::HTTP_BAD_REQUEST);
        }

        // Search if bookmark already exists.
        $exists = $controller->getRepository('Bookmark')->findOneBy(
            [
                'owner' => $controller->getUser()->getId(),
                'url' => $url,
            ]
        );

        return $exists;
    }
}
