<?php

namespace BookmarkManager\ApiBundle\Controller;

use BookmarkManager\ApiBundle\Exception\BMErrorResponseException;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use BookmarkManager\ApiBundle\DependencyInjection\BaseController;
use BookmarkManager\ApiBundle\Entity\Bookmark;
use BookmarkManager\ApiBundle\Annotation\ApiErrors;
use BookmarkManager\ApiBundle\Tool\WebsiteCrawler;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bookmark controller.
 */
class SearchController extends BaseController
{

    // ---------------------------------------------------------------------------------------------------------------
    //    /search/bookmark
    // ---------------------------------------------------------------------------------------------------------------

    /**
     * @Rest\QueryParam(name="name", description="Bookmark name.")
     * @Rest\QueryParam(name="url", description="Bookmark url.")
     * @Rest\QueryParam(name="keywords", description="Keywords separate by ','")
     * @Rest\QueryParam(name="tags", description="Tags name separate by ','")
     *
     * @Rest\QueryParam(name="page", requirements="[\d]+", default="1", description="Page number.")
     * @Rest\QueryParam(name="limit", requirements="[\d]+", default="25", description="Number of results to display.")
     *
     * @ApiDoc(
     *  description="Search bookmarks",
     *  statusCodes={
     *      200="Returned when successful.",
     *      404="Returned when no bookmark are found for the specified parameters.",
     *      400="Returned when parameters are invalid."
     *  }
     * )
     *
     * @ApiErrors({
     *      { 102, "Resource not found" }
     * })
     *
     * @param ParamFetcher $params
     * @return Response
     */
    public function getSearchBookmarksAction(ParamFetcher $params)
    {
        $max_results_limit = $this->container->getParameter('max_results_limit');
        $default_results_limit = $this->container->getParameter('default_results_limit');

        $params = $params->all();

        $paging = array(
            'page' => $params['page'],
            'limit' => $params['limit'],
            'sort_by' => array('created_at' => 'DESC'),
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
            if (intval($paging['limit']) > $max_results_limit) {
                $errors['limit'][] = 'Limit is too high. Max '.$max_results_limit;
            }
        }

        if (!empty($errors)) {
            return ($this->errorResponse(101, $errors, Response::HTTP_BAD_REQUEST));
        }

        if (!isset($paging['limit']) || $paging['limit'] < 1) {
            $paging['limit'] = $default_results_limit;
        }

        if (!isset($paging['page']) || $paging['page'] < 1) {
            $paging['page'] = 1;
        }

        $paging['offset'] = (($paging['page'] - 1) * $paging['limit']);

        $query = $this->getRepository('Bookmark')->createQueryBuilder('p');

        if (isset($params['name']) && $params['name'] != null && !empty($params['name'])) {
            $query
                ->where($query->expr()->like('p.name', ':name'))
                ->setParameter('name', '%'.$params['name'].'%');
        }

        if (isset($params['url']) && $params['url'] != null && !empty($params['url'])) {
            $query
                ->andWhere($query->expr()->like('p.url', ':url'))
                ->setParameter('url', '%'.$params['url'].'%');
        }

        if (isset($params['keywords'])) {
            // TODO add keywords query.
            $keywords = explode($params['keywords'], ',');
        }

        if (isset($params['tags'])) {
            // -- Handle many tags, separate by ','
            if (strpos($params['tags'], ',')) {
                $tagsName = explode($params['tags'], ',');
            } else {
                $tagsName = [$params['tags']];
            }

            foreach ($tagsName as $tagName) {
                $query
                    ->leftJoin('p.tags', 't')
                    ->andWhere('t.name = :name')
                    ->setParameter('name', $tagName);
            }
        }

        // -- Select only the user's bookmarks
        $query
            ->leftJoin('p.owner', 'u')
            ->andWhere('u.id = :owner')
            ->setParameter('owner', $this->getUser()->getId());

        // -- Count total number of result.
        $countQuery = clone($query);
        $countQuery = $countQuery->select('COUNT(p)')->getQuery();
        $paging['total'] = intval($countQuery->getSingleScalarResult());

        // -- Set limit to the query
        $query = $query->setFirstResult($paging['offset'])
            ->setMaxResults($paging['limit'])
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery();

        $data = $query->getResult();

        // -- Set paging
        $paging['last_page'] = intval(ceil($paging['total'] / $paging['limit']));

        // -- Set number of results
        $paging['results'] = count($data);

        if (!(($paging['page'] <= $paging['last_page'] && $paging['page'] >= 1)
            || ($paging['total'] == 0 && $paging['page'] == 1))
        ) {
            return $this->errorResponse(102, 'resource not found', Response::HTTP_BAD_REQUEST);
        }

        return $this->successResponse(array('bookmarks' => $data), Response::HTTP_OK, ['list'], $paging);
    }

    // ---------------------------------------------------------------------------------------------------------------
    //    /search/bookmark
    // ---------------------------------------------------------------------------------------------------------------

    /**
     * @Rest\QueryParam(name="url", description="Bookmark url.")
     *
     * @ApiDoc(
     *  description="Search a bookmark by url",
     *  statusCodes={
     *      200="Returned when successful.",
     *      400="Returned when parameters are invalid.",
     *      404="Returned when no bookmark is found with the given url"
     *  }
     * )
     *
     * @ApiErrors({
     *  {101, "You must provide an url"},
     *  {102, "Invalid url"},
     *  {404, "No bookmark found"}
     * })
     *
     * @param ParamFetcher $params
     * @return Response
     * @throws BmErrorResponseException
     */
    public function getSearchBookmarkAction(ParamFetcher $params)
    {

        $params = $params->all();

        if (!isset($params['url'])) {
            return $this->errorResponse(101, 'You must provide an url');
        }
        $crawler = new WebsiteCrawler();
        $url = $crawler->cleanUrl($params['url']);

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new BmErrorResponseException(102, "Invalid url", Response::HTTP_BAD_REQUEST);
        }

        // Search if bookmark already exists.
        $bookmark = $this->getRepository('Bookmark')->findOneBy(
            [
                'owner' => $this->getUser()->getId(),
                'url' => $url,
            ]
        );

        if (!$bookmark) {
            return $this->errorResponse(404, 'No bookmark found with this url', Response::HTTP_NOT_FOUND);
        }

        return $this->successResponse($bookmark, Response::HTTP_OK, ['alone']);
    }

}
