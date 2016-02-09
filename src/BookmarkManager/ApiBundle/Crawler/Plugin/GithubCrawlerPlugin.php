<?php

namespace BookmarkManager\ApiBundle\Crawler\Plugin;

use BookmarkManager\ApiBundle\Crawler\CrawlerPlugin;

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
     * @param $crawler The html crawler
     * @param $bookmark
     * @return  $content
     * @internal param $html
     */
    public function parse($crawler, $bookmark)
    {
        // Simple markdown file
        if ($crawler->filter('article.entry-content')->count()) {
            $bookmark->setContent($crawler->filter('article.entry-content')->html());
            /**
             * Note:
             * Github have specific anchor: <href="#a"> link to <h1></h1><a name="user-content-a"> for example
             * This type of anchor is handle on the front web, but we can add TODO: [LOW] handle github anchor.
             */

            // TODO: remove div .octicon-link
        }
        // Code file
        else if ($crawler->filter('div.blob-wrapper')->count()) {
            $bookmark->setContent($crawler->filter('div.blob-wrapper')->html());
        }
        return $bookmark;
    }
}