<?php
/**
 * Created by PhpStorm.
 * User: loic
 * Date: 20/01/16
 * Time: 12:51
 */

namespace BookmarkManager\ApiBundle\Utils;

use BookmarkManager\ApiBundle\Crawler\CrawlerNotFoundException;
use BookmarkManager\ApiBundle\Crawler\CrawlerRetrieveDataException;
use BookmarkManager\ApiBundle\Entity\Bookmark;
use BookmarkManager\ApiBundle\Entity\Tag;
use BookmarkManager\ApiBundle\Exception\BmErrorResponseException;
use BookmarkManager\ApiBundle\Form\BookmarkType;
use BookmarkManager\ApiBundle\Form\TagType;
use BookmarkManager\ApiBundle\Crawler\WebsiteCrawler;
use Symfony\Component\Config\Definition\Exception\Exception;
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
     * @throws BMAlreadyExistsException
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
            $bookmarkEntity = $crawler->crawlWebsite($bookmarkEntity, $controller->getUser());

            if ($bookmarkEntity === null) {
                return null;
            }

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


    public static function testCrawler($controller, $data)
    {
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

        $bookmarkEntity = $crawler->crawlWebsite($bookmarkEntity, $controller->getUser());

        return $bookmarkEntity;
    }

    public static function getBookmarkForUrl($controller, $url)
    {

        $crawler = new WebsiteCrawler();
        $url = $crawler->cleanUrl($url);

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


    /**
     * For a given text, we calculate reading time for an article
     * based on 200 words per minute.
     *
     * @param $text
     *
     * @return float
     */
    public static function getReadingTime($text)
    {
        if (!strlen($text)) {
            return 1;
        }
        $words = str_word_count(strip_tags($text));
        $min = floor($words / 200);

        if ($min == 0) {
            return 1;
        }

        return $min;
    }
}
