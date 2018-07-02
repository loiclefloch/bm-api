<?php

namespace BookmarkManager\ApiBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\Response;
use BookmarkManager\ApiBundle\Entity\Bookmark;
use BookmarkManager\ApiBundle\Entity\Book;
use BookmarkManager\ApiBundle\Exception\BmErrorResponseException;

class BookmarkRepository extends EntityRepository
{
    const PARENT_TYPE_BOOK = "BOOK";
    const PARENT_TYPE_USER = "USER";

    /**
     * @param $params
     *  - page
     *  - limit
     * 
     * @param $options
     *  - parentType
     *  - id userId or circleId
     *  - max_results_limit
     *  - default_results_limit
     * 
     * 
     */
    public function findAllOrderedByName($params, $options) {
      $paging = array(
        'page' => isset($params['page']) ? $params['page'] : 1,
        'limit' => isset($params['limit']) ? $params['limit'] : $options['default_results_limit'],
        'sort_by' => array('updated_at' => 'DESC'),
        'total' => 0,
        'results' => 0,
        'last_page' => 0,
      );

      if (isset($paging['page']) && intval($paging['page']) < 1) {
          $errors['page'][] = 'Page must be positive';
      }

      if (isset($paging['limit'])) {
          if (intval($paging['limit']) < 1) {
              $errors['limit'][] = 'Limit must be positive';
          }
          if (intval($paging['limit']) > $options['max_results_limit']) {
              $errors['limit'][] = 'Limit is too high. Max '.$options['max_results_limit'];
          }
      }

      if (!empty($errors)) {
          throw new BmErrorResponseException(101, $errors, Response::HTTP_BAD_REQUEST);
      }

      if (!isset($paging['limit']) || $paging['limit'] < 1) {
          $paging['limit'] = $options['default_results_limit'];
      }

      if (!isset($paging['page']) || $paging['page'] < 1) {
          $paging['page'] = 1;
      }

      $paging['offset'] = (($paging['page'] - 1) * $paging['limit']);

      $builder = $this->getEntityManager()->createQueryBuilder('p');

      if ($options['parentType'] === BookmarkRepository::PARENT_TYPE_USER) {
        // -- Select only the user's bookmarks
        $builder
            ->select('bookmark')
            ->from('BookmarkManager\ApiBundle\Entity\Bookmark', 'bookmark')
            ->leftJoin('bookmark.owner', 'owner')
            ->andWhere('owner.id = :ownerId')
            ->setParameter('ownerId', $options['id']);
      } else if ($options['parentType'] === BookmarkRepository::PARENT_TYPE_BOOK) {
        // -- Select only the user's bookmarks
        $builder
            ->select('bookmark')
            ->from('BookmarkManager\ApiBundle\Entity\Bookmark', 'bookmark')
            ->join('bookmark.books', 'books')
            ->where('books.id = :bookId')
            ->setParameter('bookId', $options['id'])
            ;
      }

      // -- Count total number of result.
      $countQuery = clone($builder);
      $countQuery = $countQuery->select('COUNT(bookmark)')->getQuery();
      $paging['total'] = intval($countQuery->getSingleScalarResult());

      // -- Set limit to the query
      $builder = $builder->setFirstResult($paging['offset'])
          ->setMaxResults($paging['limit'])
          ->orderBy('bookmark.createdAt', 'DESC')
          ->getQuery();

      $data = $builder->getResult();

      // -- Set paging
      $paging['last_page'] = intval(ceil($paging['total'] / $paging['limit']));

      // -- Set number of results
      $paging['results'] = count($data);

      if (!(($paging['page'] <= $paging['last_page'] && $paging['page'] >= 1)
          || ($paging['total'] == 0 && $paging['page'] == 1))
      ) {
        throw new BmErrorResponseException(102, 'resource not found', Response::HTTP_BAD_REQUEST);
      }

      return [ 'bookmarks' => $data, 'paging' => $paging ];
    }
}