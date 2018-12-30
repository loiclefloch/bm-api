<?php

namespace BookmarkManager\ApiBundle\Crawler\Plugin;

use BookmarkManager\ApiBundle\Crawler\CrawlerPlugin;
use BookmarkManager\ApiBundle\Entity\Bookmark;
use BookmarkManager\ApiBundle\Entity\BookmarkType;
use Symfony\Component\DomCrawler\Crawler;

class MediumCrawlerPlugin extends CrawlerPlugin
{

    /**
     * @param $url
     * @return bool
     */
    public function matchUrl($url)
    {
        return strpos($url, "medium.com") !== FALSE;
    }

    /**
     * @param Crawler $crawler The html crawler
     * @param Bookmark $bookmark
     * @return Bookmark
     * @internal param $html
     */
    public function parse(Crawler $crawler, Bookmark $bookmark)
    {

        $contentNode = $crawler->filter('.postArticle-content');
        if ($contentNode->count()) {
            $bookmark->setContent($contentNode->html());
        }

        // -- Title˙
        // Remove ' – Medium' from the title. Warning: '–' is not a '-'.
        $bookmark->setTitle(str_replace(' – Medium', '', $bookmark->getTitle()));

        $websiteInfo = $bookmark->getWebsiteInfo();


        $avatarImg = $this->getClass($crawler, '.avatar-image--small');
        if ($avatarImg !== null) {
            $avatarUrl = $avatarImg->getAttribute('src');
            $websiteInfo['authorAvatar'] = $avatarUrl;
        }


        $bookmark->setWebsiteInfo($websiteInfo);

        return $bookmark;
    }
}