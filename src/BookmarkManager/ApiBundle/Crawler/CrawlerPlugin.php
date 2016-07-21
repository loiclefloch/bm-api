<?php

namespace BookmarkManager\ApiBundle\Crawler;


use BookmarkManager\ApiBundle\Entity\Bookmark;
use Symfony\Component\DomCrawler\Crawler;

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
    abstract public function parse(Crawler $crawler, Bookmark $bookmark);

    protected function removeWithIdentifier(Crawler $crawler, $identifier) {
        return $crawler->filter($identifier)->each(function (Crawler $c) {
            foreach ($c as $node) {
                $node->parentNode->removeChild($node);
            }
        });
    }
}