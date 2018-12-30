<?php

namespace BookmarkManager\ApiBundle\Crawler\Plugin;

use BookmarkManager\ApiBundle\Crawler\CrawlerPlugin;
use BookmarkManager\ApiBundle\Entity\Bookmark;
use BookmarkManager\ApiBundle\Entity\BookmarkType;
use Symfony\Component\DomCrawler\Crawler;

class SlideshareCrawlerPlugin extends CrawlerPlugin
{

    /**
     * @param $url
     * @return bool
     */
    public function matchUrl($url)
    {
        return strpos($url, "slideshare.net") !== FALSE;
    }

    /**
     * @param Crawler $crawler The html crawler
     * @param Bookmark $bookmark
     * @return Bookmark $content
     * @internal param $html
     */
    public function parse(Crawler $crawler, Bookmark $bookmark)
    {
        // Simple markdown file
        if ($crawler->filter('div.slide_container')->count()) {

            /*
             * replace src attribute with the slide img url contains on data-full
             */
            $crawler->filter('img.slide_image')->each(
                function (Crawler $c) {
                    $slideImgFullSize = $c->getNode(0)->getAttribute('data-full'); // data-normal or data-small
                    $c->getNode(0)->setAttribute('src', $slideImgFullSize);


                    $currentIndex = $c->getNode(0)->parentNode->getAttribute('data-index');
                    // rewrite classes
                    $c->getNode(0)->parentNode->setAttribute('class', 'slide slide_' . $currentIndex);
                }
            );

            /*
             * Remove spinners and next container
             */
            $crawler->filter('i.fa-spinner, i.fa-spin, .next-container')->each(function (Crawler $c) {
                foreach ($c as $node) {
                    $node->parentNode->removeChild($node);
                }
            });

            $bookmark->setContent($crawler->filter('div.slide_container')->html());
            $bookmark->setType(BookmarkType::SLIDE);
        }

        return $bookmark;
    }
}