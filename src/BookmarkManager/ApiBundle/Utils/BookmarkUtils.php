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
use BookmarkManager\ApiBundle\Entity\BookmarkCrawlerStatus;
use BookmarkManager\ApiBundle\Entity\BookmarkType;
use BookmarkManager\ApiBundle\Entity\Tag;
use BookmarkManager\ApiBundle\Exception\BmAlreadyExistsException;
use BookmarkManager\ApiBundle\Exception\BmErrorResponseException;
use BookmarkManager\ApiBundle\Crawler\WebsiteCrawler;
use BookmarkManager\ApiBundle\Form\BookmarkFormType;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DomCrawler\Crawler;
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
     * @return Bookmark
     * @throws BmAlreadyExistsException
     * @throws BmErrorResponseException
     */
    public static function createBookmark($controller, $data, $verifyExists = true)
    {
        $bookmarkEntity = new Bookmark();

        $form = $controller->createForm(
            new BookmarkFormType(),
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
            if ($verifyExists) {
            $exists = $controller->getRepository(Bookmark::REPOSITORY_NAME)->findOneBy(
                [
                    'owner' => $controller->getUser()->getId(),
                    'url' => $url,
                ]
            );

            if ($exists) {
                throw new BmAlreadyExistsException(BmAlreadyExistsException::DEFAULT_CODE, [
                    'id' => $exists->getId()
                ]);
            }
        }

            $bookmarkEntity->setUrl($url);

            $bookmarkEntity = $crawler->crawlWebsite($bookmarkEntity, $controller->getUser());

            if ($bookmarkEntity === null) {
                // TODO: throw exception
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
                        } catch (BmErrorResponseException $e) {
                            // do nothing
                            $controller->getLogger()->info('Catch exception '.$e->getMessage());
                        }

                    }
                }
            }

            return $bookmarkEntity;
        }

        throw new BmErrorResponseException(400, ArrayUtils::formErrorsToArray($form), Response::HTTP_BAD_REQUEST);
    }


    public static function testCrawler($controller, $data)
    {
        $bookmarkEntity = new Bookmark();

        $form = $controller->createForm(
            new BookmarkFormType(),
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
        $exists = $controller->getRepository(Bookmark::REPOSITORY_NAME)->findOneBy(
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
     * @param $bookmark
     * @return float
     * @internal param $text
     *
     */
    public static function getReadingTime(Bookmark $bookmark)
    {
        $html = $bookmark->getContent();
        if (!strlen($html)) {
            return Bookmark::DEFAULT_READING_TIME;
        }


        if ($bookmark->getType() === BookmarkType::SLIDE) {
            // count number of slides
            $crawler = new Crawler($html);
            $nbSlides = count($crawler->filter(Bookmark::SLIDE_IMAGE_CLASS));

            // For now: 2 minute per slide
            return $nbSlides * 2;
        } else {
            // count words (do not take care of html tag).
            $words = str_word_count(strip_tags($html));


            $min = floor($words / Bookmark::AVERAGE_WORDS_PER_MINUTES);

            if ($min == 0) {
                return 1;
            }

            // TODO: Handle video ? -> Perhaps move the reading time to the crawler and save it in db.

            return $min;
        }
    }
}
