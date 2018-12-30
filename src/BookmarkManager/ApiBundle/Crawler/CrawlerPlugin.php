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

    /**
     * Remove the given identifier on the crawler
     *
     * @param Crawler $crawler The crawler to use.
     * @param $identifier String identifier, example: '.btn'
     * @return Crawler The modified crawler
     */
    protected function removeWithIdentifier(Crawler $crawler, $identifier)
    {
        $crawler->filter($identifier)->each(
            function (Crawler $c) {
                foreach ($c as $node) {
                    $node->parentNode->removeChild($node);
                }
            }
        );

        return $crawler;
    }

    protected function getClasses(Crawler $crawler, $class, \Closure $closure)
    {
        $crawler->filter($class)->each($closure);
    }

    protected function getClass(Crawler $crawler, $class)
    {
        return $crawler->filter($class)->getNode(0);
    }
}