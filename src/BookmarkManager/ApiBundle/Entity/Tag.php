<?php

namespace BookmarkManager\ApiBundle\Entity;

use JMS\Serializer\Annotation\ExclusionPolicy;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use \BookmarkManager\ApiBundle\Entity\Bookmark;
use JMS\Serializer\Annotation\Expose;

/**
 * Tag
 *
 * @ORM\Table()
 * @ORM\Entity
 * @ExclusionPolicy("ALL")
 */
class Tag
{

    public static $DEFAULT_COLOR = '#c2c2c2';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Expose
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Expose
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="color", type="string", length=7, nullable=true)
     * @Expose
     */
    private $color;

    /**
     * @var Bookmark teams
     * @ORM\ManyToOne(targetEntity="User", inversedBy="tags")
     * @Expose
     */
    private $owner;

    // ----------------------------------------------------------------------------------------------------------------

    public function __construct()
    {
        $this->setColor(Tag::$DEFAULT_COLOR);
        $this->tags = new ArrayCollection();
    }

    // ----------------------------------------------------------------------------------------------------------------
    // Generated

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Tag
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set color
     *
     * @param string $color
     * @return Tag
     */
    public function setColor($color)
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Get color
     *
     * @return string 
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Set owner
     *
     * @param User $owner
     * @return Tag
     */
    public function setOwner(User $owner = null)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get owner
     *
     * @return User 
     */
    public function getOwner()
    {
        return $this->owner;
    }
}
