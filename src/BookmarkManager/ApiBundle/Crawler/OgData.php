<?php
/**
 * Created by PhpStorm.
 * User: loic
 * Date: 24/03/16
 * Time: 03:49
 */

namespace BookmarkManager\ApiBundle\Crawler;


class OgData
{

    // ----------------------------------------------------------------------------------------------------------------
    // BASICS
    // ----------------------------------------------------------------------------------------------------------------

    private $title;

    private $type;

    private $image;

    private $description;


    // ----------------------------------------------------------------------------------------------------------------
    // LIFECYCLE
    // ----------------------------------------------------------------------------------------------------------------
    
    public function __construct() {
        $this->type = 'website';
    }

    // ----------------------------------------------------------------------------------------------------------------
    // TOOLS
    // ----------------------------------------------------------------------------------------------------------------


    // ----------------------------------------------------------------------------------------------------------------
    // GETTERS & SETTERS
    // ----------------------------------------------------------------------------------------------------------------


    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param mixed $image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

}