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
     * @return bool
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
            $crawler = $crawler->filter('article.entry-content');


            /**
             * Note:
             * Github have specific anchor: <href="#a"> link to <h1></h1><a name="user-content-a"> for example
             * This type of anchor is handle on the front web.
             */

            // remove div .octicon.octicon-link
            $crawler = $this->removeWithIdentifier($crawler, '.octicon.octicon-link');

            $bookmark->setContent($crawler->html());

            $bookmark->setType(BookmarkType::CODE);
        }
        // Code file
        else if ($crawler->filter('div.blob-wrapper')->count()) {
            $bookmark->setContent($crawler->filter('div.blob-wrapper')->html());
            $bookmark->setType(BookmarkType::CODE);
        }

        // -- Title
        // Remove 'GitHub - ' from the title.
        $bookmark->setTitle(str_replace('GitHub - ', '', $bookmark->getTitle()));

        return $bookmark;
    }
}