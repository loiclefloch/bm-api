<?php

namespace BookmarkManager\ApiBundle\Crawler\Plugin;

use BookmarkManager\ApiBundle\Crawler\CrawlerPlugin;
use BookmarkManager\ApiBundle\Entity\Bookmark;
use BookmarkManager\ApiBundle\Entity\BookmarkType;
use Symfony\Component\DomCrawler\Crawler;

class GithubCrawlerPlugin extends CrawlerPlugin
{

    /**
     * @param $url
     * @return array
     */
    public function matchUrl($url)
    {
        return strpos($url, "github.com") !== FALSE;
    }

    /**
     * @param Crawler $crawler The html crawler
     * @param Bookmark $bookmark
     * @return Bookmark
     * @internal param $html
     */
    public function parse(Crawler $crawler, Bookmark $bookmark)
    {
        // Simple markdown file
        if ($crawler->filter('article.entry-content')->count()) {
            $bookmark->setContent($crawler->filter('article.entry-content')->html());
            /**
             * Note:
             * Github have specific anchor: <href="#a"> link to <h1></h1><a name="user-content-a"> for example
             * This type of anchor is handle on the front web.
             */

            // TODO: remove div .octicon-link


            $bookmark->setType(BookmarkType::CODE);
        }
        // Code file
        else if ($crawler->filter('div.blob-wrapper')->count()) {
            $bookmark->setContent($crawler->filter('div.blob-wrapper')->html());
            $bookmark->setType(BookmarkType::CODE);
        }

        return $bookmark;
    }
}