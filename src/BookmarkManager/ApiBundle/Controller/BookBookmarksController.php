<?php

namespace BookmarkManager\ApiBundle\Controller;

use BookmarkManager\ApiBundle\Exception\BmAlreadyExistsException;
use BookmarkManager\ApiBundle\Exception\BmErrorResponseException;
use BookmarkManager\ApiBundle\Repository\BookmarkRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use JMS\Serializer\SerializerBuilder;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use FOS\RestBundle\Request\ParamFetcher;
use Symfony\Component\HttpFoundation\Request;
use BookmarkManager\ApiBundle\DependencyInjection\BaseController;
use BookmarkManager\ApiBundle\Entity\Bookmark;
use BookmarkManager\ApiBundle\Entity\Circle;
use BookmarkManager\ApiBundle\Entity\Book;
use BookmarkManager\ApiBundle\Annotation\ApiErrors;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DomCrawler\Crawler;
use JMS\Serializer\SerializationContext;

/**
 * Book's bookmarks controller.
 * 
 * @Route("/circles/{circleId}/books/{bookId}/bookmarks")
 */
class BookBookmarksController extends BaseController
{

  /**
   * Lists all Bookmark entities for the book.
   * @Rest\QueryParam(name="page", requirements="[\d]+", default="1", description="Page number.")
   * @Rest\QueryParam(name="limit", requirements="[\d]+", default="25", description="Number of results to display.")
   *
   */
  public function getBookmarksAction(ParamFetcher $params, $circleId, $bookId) {
    // verifs:
    // - book on circle
    // - user on circle

    $params = $params->all();

    $options = [
        'parentType' => BookmarkRepository::PARENT_TYPE_BOOK,
        'id' => $bookId,
        'max_results_limit' => $this->container->getParameter('max_results_limit'),
        'default_results_limit' => $this->container->getParameter('default_results_limit'),
    ];

     $result = $this->getRepository(Bookmark::REPOSITORY_NAME)->findAllOrderedByName(
          $params,
          $options
      );

      $paging = $result['paging'];
      $paging['routeParams'] = [
        'circleId' => $circleId,
        'bookId' => $bookId
      ];

      return $this->successResponse(array('bookmarks' => $result['bookmarks']),
          Response::HTTP_OK,
          [Bookmark::GROUP_MULTIPLE],
          $paging
      );
  }

  public function postBookmarkAction(Request $request, $circleId, $bookId) {
    if (!is_numeric($circleId) || !is_numeric($bookId)) {
      return $this->errorResponse(101, 'invalid circle id');
    }

    $circle = $this->getRepository(Circle::REPOSITORY_NAME)->find($circleId);

    if (!$circle) {
        return $this->notFoundResponse();
    }

    $book = $this->getRepository(Book::REPOSITORY_NAME)->find($bookId);
    
    if (!$book) {
      return $this->notFoundResponse();
    }

    $data = $request->request->all();

    $bookmarksData = $data['bookmarks'];

    foreach ($bookmarksData as $bookmarkData) {
      $bookmark = $this->getRepository(Bookmark::REPOSITORY_NAME)->find($bookmarkData['id']);

      if ($bookmark !== null) {
        $book->addBookmark($bookmark);
      }
    }

    $bookmark->addBook($book);

    $this->persistentity($book);
    $this->persistentity($bookmark);

    return $this->successResponse(
      $book,
      Response::HTTP_CREATED,
      Book::GROUP_SINGLE
    );
  }
}
