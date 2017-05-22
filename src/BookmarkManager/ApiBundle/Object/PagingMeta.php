<?php
/**
 * Created by PhpStorm.
 * User: loic
 * Date: 22/05/2017
 * Time: 21:08
 */

namespace BookmarkManager\ApiBundle\Object;


class PagingMeta
{
    protected $total = 0;

    protected $results = 0;



    // ----------------------------------------------------------------------------------------------------------------
    // GETTERS & SETTERS
    // ----------------------------------------------------------------------------------------------------------------

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
    public function getResults()
    {
        return $this->results;
    }

    /**
     * @param int $results
     */
    public function setResults($results)
    {
        $this->results = $results;
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

}