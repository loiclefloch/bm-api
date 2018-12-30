<?php

namespace BookmarkManager\ApiBundle\Tests\Controller;

use BookmarkManager\ApiBundle\Crawler\WebsiteCrawler;
use BookmarkManager\ApiBundle\Entity\Bookmark;
use BookmarkManager\ApiBundle\Entity\Tag;
use BookmarkManager\ApiBundle\Utils\BookmarkUtils;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class CrawlerTest
 * @package BookmarkManager\ApiBundle\Tests\Controller
 *
 * Run with `phpunit -c app/  src/BookmarkManager/ApiBundle/Tests/Crawler/CrawlerTest.php`
 */
class CrawlerTest extends WebTestCase
{

    protected function getHtmlFileForTest($filename)
    {
        return file_get_contents(__DIR__.'/../Fixtures/Html/'.$filename.'.html');
    }

    protected function getHtmlExpectationForTest($filename)
    {
        return file_get_contents(__DIR__.'/../Fixtures/Html/Expected/'.$filename.'.html');
    }

    /**
     * Remove all spaces, tab and line feeds on the html to avoid error on html equals assertions
     * @param $html
     * @return mixed
     */
    protected function getHtmlToCompare($html)
    {
        return preg_replace('/\s+/', '', $html);
    }

    public function testAnchors()
    {
        $html = $this->getHtmlFileForTest('AnchorTest');

        $crawler = new WebsiteCrawler();

        $bookmark = new Bookmark();
        $bookmark->setUrl('localhost');

        $bookmark = $crawler->crawlWebsiteWithHtml($bookmark, $html);

        $crawler = new Crawler($bookmark->getContent());

        // The given html should return 8 title tags. (the h1 is remove by readability in this case)
        // A fail here does not necessarily equals to a regression, because it can be just a change on the readability
        // render.
        $this->assertEquals(8, $crawler->filter('h1, h2, h3, h4, h5')->count());

        // For each anchor, test if there is an id set.
        $crawler->filter('h1, h2, h3, h4, h5')->each(
            function ($titleNode) {

                $currentId = $titleNode->getNode(0)->getAttribute('id');
                $title = $titleNode->getNode(0)->nodeValue;

                $this->assertNotEquals(0, strlen($currentId), 'Title ['.$title.'] does not have an id');
            }
        );
    }

    public function testImgLinks()
    {
        $html = $this->getHtmlFileForTest('ImgLinksTest');
        $expected = $this->getHtmlExpectationForTest('ImgLinksExpected');

        $crawler = new WebsiteCrawler();

        $bookmark = new Bookmark();
        $bookmark->setUrl('http://localhost');

        $bookmark = $crawler->crawlWebsiteWithHtml($bookmark, $html);

        $expected = $this->getHtmlToCompare($expected);
        $actual = $this->getHtmlToCompare($bookmark->getContent());

        $this->assertEquals($expected, $actual);
    }

    public function testHrefLinks()
    {
        $html = $this->getHtmlFileForTest('HrefTest');
        $expected = $this->getHtmlExpectationForTest('HrefExpected');

        $crawler = new WebsiteCrawler();

        $bookmark = new Bookmark();
        $bookmark->setUrl('http://localhost');

        $bookmark = $crawler->crawlWebsiteWithHtml($bookmark, $html);

        $expected = $this->getHtmlToCompare($expected);
        $actual = $this->getHtmlToCompare($bookmark->getContent());

        $this->assertEquals($expected, $actual);
    }

    /**
     *
     * `phpunit -c app/ --filter testFindTags  src/BookmarkManager/ApiBundle/Tests/Crawler/CrawlerTest.php`
     */
    public function testFindTags()
    {
        $crawler = new WebsiteCrawler();

        $html = $this->getHtmlFileForTest('FindTagsTest');

        $tag1 = new Tag();
        $tag1->setName('dev');

        $tag2 = new Tag();
        $tag2->setName('swift');

        $tag3 = new Tag();
        $tag3->setName('css');

        $tag4 = new Tag();
        $tag4->setName('transport');

        $tags = [
            $tag1,
            $tag2,
            $tag3,
            $tag4,
        ];

        $result = $crawler->findTagsOnText($tags, $html);

        $this->assertEquals(3, count($result)); // 3 results
        $this->assertEquals($tag1->getName(), $result[0]->getName()); // dev
        $this->assertEquals($tag2->getName(), $result[1]->getName()); // swift
        $this->assertEquals($tag3->getName(), $result[2]->getName()); // css
    }
}
