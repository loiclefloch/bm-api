<?php

namespace BookmarkManager\ApiBundle\Object;

/**
 * This class represent the paging info used to make a request on the database, but also used to returns the paging info.
 * TODO: finish to implement
 * Json example:
 *
 * ```
 * "paging":{
    "page":"1",
    "limit":"20",
    "sort_by":{
        "updated_at":"DESC"
     },
        "total":0,
        "results":0,
        "last_page":0,
    "offset":0
    }
 * ```
 */
class Paging
{
    protected $page = 1;

    protected $limit = 20; // TODO: $this->container->getParameter('default_results_limit')

    protected $sortBy = [
        'created_at' => 'DESC'
    ];

    protected $lastPage = 0;

    protected $url = "";

    /**
     * @var PagingMeta
     */
    protected $metas;

    /**
     * Paging constructor.
     */
    public function __construct()
    {
        $this->metas = new PagingMeta();
    }


    /**
     * Paging constructor.
     * @param array|The $params The params given to an Api request.
     * @internal param int $page
     * @internal param int $limit
     */
    public static function withParams($params)
    {
        $instance = new self();
        $instance->page =  $params['page'];

        $instance->setLimit($params['limit']);
    }

    // ----------------------------------------------------------------------------------------------------------------
    //
    // ----------------------------------------------------------------------------------------------------------------

    /**
     * Simple function to transform paging into an object to serialize. Use it to keep compatibility with old paging
     * way.
     */
    public function getData() {
        $paging = array(
            'metas' => ,
        );


        return $paging;
    }


    // ----------------------------------------------------------------------------------------------------------------
    // GETTERS & SETTERS
    // ----------------------------------------------------------------------------------------------------------------


    /**
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param int $page
     */
    public function setPage($page)
    {
        $this->page = $page;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    /**
     * @return array
     */
    public function getSortBy()
    {
        return $this->sortBy;
    }

    /**
     * @param array $sortBy
     */
    public function setSortBy($sortBy)
    {
        $this->sortBy = $sortBy;
    }

    /**
     * @return int
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @param int $total
     */
    public function setTotal($total)
    {
        $this->total = $total;
    }

    /**
     * @return int
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param int $result
     */
    public function setResult($result)
    {
        $this->result = $result;
    }

    /**
     * @return int
     */
    public function getLastPage()
    {
        return $this->lastPage;
    }

    /**
     * @param int $lastPage
     */
    public function setLastPage($lastPage)
    {
        $this->lastPage = $lastPage;
    }


}
