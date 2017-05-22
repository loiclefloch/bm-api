<?php

namespace BookmarkManager\ApiBundle\Controller;

use BookmarkManager\ApiBundle\Crawler\CrawlerNotFoundException;
use BookmarkManager\ApiBundle\Crawler\CrawlerRetrieveDataException;
use BookmarkManager\ApiBundle\Crawler\WebsiteCrawler;
use BookmarkManager\ApiBundle\Exception\BmAlreadyExistsException;
use BookmarkManager\ApiBundle\Exception\BmErrorResponseException;
use BookmarkManager\ApiBundle\Utils\BookmarkUtils;
use BookmarkManager\ApiBundle\Utils\TagUtils;
use Exception;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\Controller\Annotations as Rest;
use JMS\Serializer\SerializerBuilder;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use BookmarkManager\ApiBundle\DependencyInjection\BaseController;
use BookmarkManager\ApiBundle\Entity\Bookmark;
use BookmarkManager\ApiBundle\Form\BookmarkFormType;
use BookmarkManager\ApiBundle\Annotation\ApiErrors;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DomCrawler\Crawler;
use JMS\Serializer\SerializationContext;

/**
 *
 */
class DbUpdateScriptsController extends BaseController
{

    /**
     * Update all bookmarks reading_time.
     * The reading_time was calculate each time we serialize the bookmark.
     * Now, we set it on database.
     *
     * @Rest\Put("/dbupdatescripts/reading_time")
     *
     * @Security("has_role('ROLE_ADMIN')")
     *
     *
     * @return Response
     */
    public function putReadingTimeAction()
    {
        $bookmarks = $this->getRepository(Bookmark::REPOSITORY_NAME)->findAll();

        foreach ($bookmarks as $bookmark) {
            $bookmark->setReadingTime(BookmarkUtils::getReadingTime($bookmark));
            $this->persistEntity($bookmark);
        }

        return $this->successResponse(array('bookmarks' => $bookmarks));
    }

}
