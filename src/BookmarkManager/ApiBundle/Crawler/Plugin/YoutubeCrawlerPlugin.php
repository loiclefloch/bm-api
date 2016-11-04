<?php

namespace BookmarkManager\ApiBundle\Crawler\Plugin;

use BookmarkManager\ApiBundle\Crawler\CrawlerPlugin;
use BookmarkManager\ApiBundle\Entity\Bookmark;
use BookmarkManager\ApiBundle\Entity\BookmarkType;
use Symfony\Component\DomCrawler\Crawler;

class YouTubeCrawlerPlugin extends CrawlerPlugin
{

    /**
     * @param $url
     * @return bool
     */
    public function matchUrl($url)
    {
        return strpos($url, "youtube.com") !== FALSE;
    }

    /**
     * @param Crawler $crawler The html crawler
     * @param Bookmark $bookmark
     * @return Bookmark
     * @internal param $html
     */
    public function parse(Crawler $crawler, Bookmark $bookmark)
    {
        // for url like www.youtube.com/watch?v=d0pOgY8__JM
        // note that with the og meta data, we already set it to video.
        if (strpos($bookmark->getUrl(), "watch") !== FALSE) {
            $bookmark->setType(BookmarkType::VIDEO);

            // just keep the description
            $bookmark->setContent($crawler->filter('div#watch-description-text')->html());
        }

        // -- Title
        // Remove ' - YouTube' from the title.
        $bookmark->setTitle(str_replace(' - YouTube', '', $bookmark->getTitle()));


        return $bookmark;
    }
}