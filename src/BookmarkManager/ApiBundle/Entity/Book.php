<?php

namespace BookmarkManager\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;


/**
 * @ORM\Entity
 * @ExclusionPolicy("ALL")
 *
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
     *     User::GROUP_SIMPLE
     *     })
     */
    protected $id;

    /**
     * @ORM\Column(name="name", type="string", length=32, unique=true, nullable=false)
     *
     * @Expose
     * @Groups({
     *     Book::GROUP_MULTIPLE,
     *     Book::GROUP_SINGLE
     *     })
     */
    protected $name;

    /**
     * @var [Bookmark] bookmarks
     *
     * @ORM\ManyToMany(targetEntity="Bookmark", mappedBy="books")
     *
     * @Expose
     * @Groups({
     *     Book::GROUP_MULTIPLE,
     *     Book::GROUP_SINGLE
     *     })
     */
    protected $bookmarks;

    /**
     * @var [User]
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="books")
     *
     * @Expose
     * @Groups({
     *     Book::GROUP_MULTIPLE,
     *     Book::GROUP_SINGLE
     *  })
     */
    protected $owner;

    /**
     * @var [Circle] circles
     *
     * Any user must have a default book. Set to true if it is the default book of someone (see $owner)
     *
     * @ORM\Column(name="is_default_book", type="boolean")
     *
     * @Expose
     * @Groups({
     *     Book::GROUP_MULTIPLE,
     *     Book::GROUP_SINGLE,
     *     User::GROUP_ME
     *  })
     */
    protected $isDefaultBook = false;


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
     * @return mixed
     */
    public function isDefaultBook()
    {
        return $this->isDefaultBook;
    }

    /**
     * @param mixed $isDefaultBook
     */
    public function setIsDefaultBook($isDefaultBook)
    {
        $this->isDefaultBook = $isDefaultBook;
    }

}