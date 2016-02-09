<?php

namespace BookmarkManager\ApiBundle\Crawler;


abstract class CrawlerPlugin
{
    /**
     * @param $url
     * @return bool
     */
    abstract public function matchUrl($url);

    /**
     * @param $crawler
     * @param $bookmark
     * @return  $content
     * @internal param $html
     */
    abstract public function parse($crawler, $bookmark);
}