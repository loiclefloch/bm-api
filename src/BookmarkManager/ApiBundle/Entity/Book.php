<?php

namespace BookmarkManager\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;


/**
 * A Book is a collection of bookmarks.
 * A book is linked to a circle, and accessible by all the circle's members
 * 
 * @ORM\Entity
 * @ExclusionPolicy("ALL")
 */
class Book
{
    const REPOSITORY_NAME = "Book";

    const GROUP_MULTIPLE = "books";
    const GROUP_SINGLE = "book";

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Expose
     * @Groups({
     *     Book::GROUP_MULTIPLE,
     *     Book::GROUP_SINGLE,
     *     User::GROUP_ME,
     *     User::GROUP_SINGLE,
     *     Circle::GROUP_SINGLE,
     *     Circle::GROUP_MULTIPLE,
     * })
     */
    protected $id;

    /**
     * @ORM\Column(name="name", type="string", length=32, unique=true, nullable=false)
     *
     * @Expose
     * @Groups({
     *     Book::GROUP_MULTIPLE,
     *     Book::GROUP_SINGLE,
     *     Circle::GROUP_SINGLE,
     *     Circle::GROUP_MULTIPLE
     * })
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255)
     * 
     * @Expose
     * @Groups({
     *     Book::GROUP_MULTIPLE,
     *     Book::GROUP_SINGLE,
     *     Circle::GROUP_MULTIPLE,
     *     Circle::GROUP_SINGLE
     * })
     */
    private $description;

    /**
     * @var [Bookmark] bookmarks
     *
     * @ORM\ManyToMany(targetEntity="Bookmark", mappedBy="books")
     *
     */
    protected $bookmarks;

    /**
     * @var [Circle]
     *
     * @ORM\ManyToOne(targetEntity="Circle", inversedBy="books")
     *
     * @Expose
     * @Groups({
     *     Book::GROUP_MULTIPLE,
     *     Book::GROUP_SINGLE,
     *     Circle::GROUP_SINGLE,
     *     Circle::GROUP_MULTIPLE
     *  })
     */
    protected $owner;

    // ----------------------------------------------------------------------------------------------------------------
    // GETTERS & SETTERS
    // ----------------------------------------------------------------------------------------------------------------


    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getBookmarks()
    {
        return $this->bookmarks;
    }

    public function addBookmark(Bookmark $bookmark)
    {
        if (!$this->haveBookmark($bookmark)) {
            $this->bookmarks[] = $bookmark;
        }

        return $this;
    }

    public function haveBookmark(Bookmark $bookmark)
    {
        return $this->bookmarks->indexOf($bookmark) !== false;
    }

    /**
     * @param mixed $bookmarks
     */
    public function setBookmarks($bookmarks)
    {
        $this->bookmarks = $bookmarks;
    }

    /**
     * @return mixed
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param mixed $owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->bookmarks = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Remove bookmarks
     *
     * @param \BookmarkManager\ApiBundle\Entity\Bookmark $bookmarks
     */
    public function removeBookmark(\BookmarkManager\ApiBundle\Entity\Bookmark $bookmarks)
    {
        $this->bookmarks->removeElement($bookmarks);
    }
}
