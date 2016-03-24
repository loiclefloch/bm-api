<?php

namespace BookmarkManager\ApiBundle\Crawler\Plugin;

use BookmarkManager\ApiBundle\Crawler\CrawlerPlugin;
use BookmarkManager\ApiBundle\Entity\Bookmark;
use BookmarkManager\ApiBundle\Entity\BookmarkType;
use BookmarkManager\ApiBundle\Utils\StringUtils;
use BookmarkManager\ApiBundle\Utils\UrlUtils;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Validator\Constraints\Url;

class ImageCrawlerPlugin extends CrawlerPlugin
{

    /**
     * @param $url
     * @return bool
     */
    public function matchUrl($url)
    {
        $extensions = [
            '.jpg',
            '.png',
            '.jpeg',
        ];

        foreach ($extensions as $extension) {
            if (true === StringUtils::endsWith(strtolower($url), $extension)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Crawler $crawler The html crawler
     * @param Bookmark $bookmark
     * @return Bookmark
     * @internal param $html
     */
    public function parse(Crawler $crawler, Bookmark $bookmark)
    {
        $bookmark->setContent('<div class="content__single_picture"><img src="'.$bookmark->getUrl().'" data-source="'.$bookmark->getUrl().'"/></div>');
        $bookmark->setType(BookmarkType::IMAGE);

        $domainName = UrlUtils::getDomainNameWithoutSubDomains($bookmark->getUrl());
        $pictureName = StringUtils::stringBeautifier(UrlUtils::getFileName($bookmark->getUrl()));

        $bookmark->setTitle($pictureName.' - '.$domainName);

        return $bookmark;
    }
}