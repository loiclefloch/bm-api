<?php

namespace BookmarkManager\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\Expose;
use BookmarkManager\ApiBundle\Entity\User;
use JMS\Serializer\Annotation as Serializer;

/**
 * Circle
 *
 * There are two type of circles:
 * - defaultCircle: can be linked to only one user. It is its default circle.
 * - not defaultCircle: can be shared with other users.
 *
 * TODO:
 * - private / public
 * - picture (icon)
 * - picture (cover)
 *
 * @ORM\Table(name="circles")
 * @ORM\Entity(repositoryClass="BookmarkManager\ApiBundle\Repository\CircleRepository")
 */
class Circle
{
    const REPOSITORY_NAME = 'Circle';

    const GROUP_MULTIPLE = "circles";
    const GROUP_SINGLE = "circle";

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Groups({
     *     Circle::GROUP_MULTIPLE,
     *     Circle::GROUP_SINGLE,
     *     User::GROUP_SINGLE,
     *     User::GROUP_ME,
     *     User::GROUP_MULTIPLE,
     *     BOOK::GROUP_MULTIPLE,
     *     BOOK::GROUP_SINGLE
     * })
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=50, unique=true)
     * @Groups({
     *     Circle::GROUP_MULTIPLE,
     *     Circle::GROUP_SINGLE,
     *     BOOK::GROUP_MULTIPLE,
     *     BOOK::GROUP_SINGLE,
     *     User::GROUP_SINGLE,
     *     User::GROUP_MULTIPLE
     * })
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255)
     * @Groups({
     *     Circle::GROUP_MULTIPLE,
     *     Circle::GROUP_SINGLE
     *     })
     */
    private $description;

    /**
     * @var [Circle] circles
     *
     * Any user must have a default circle. Set to true if it is the default circle of someone (see $owner)
     *
     * @ORM\Column(name="is_default_circle", type="boolean", options={"default": false})
     *
     * @Expose
     * @Groups({
     *     Circle::GROUP_MULTIPLE,
     *     Circle::GROUP_SINGLE,
     *     User::GROUP_ME
     *  })
     */
    protected $isDefaultCircle = false;

    /**
     * @var array
     *
     * List of User who subscribed to the circle (including admins)
     *
     * @ORM\ManyToMany(targetEntity="User", inversedBy="circles")
     * @Groups({
     *     Circle::GROUP_MULTIPLE,
     *     Circle::GROUP_SINGLE
     *     })
     */
    private $members;

    /**
     * @var array
     *
     * List of User who aministrate the circle.
     * They must be members too
     *
     * @ORM\ManyToMany(targetEntity="User", inversedBy="circlesAdmin")
     * @ORM\JoinTable(name="circleadmins")
     * @Groups({
     *     Circle::GROUP_MULTIPLE,
     *     Circle::GROUP_SINGLE
     *     })
     */
    private $admins;

    /**
     * @var [User]
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="circles")
     *
     * @Expose
     * @Groups({
     *     Circle::GROUP_MULTIPLE,
     *     Circle::GROUP_SINGLE
     *  })
     */
    protected $owner;

    /**
     * @var [Book] books
     * @ORM\OneToMany(targetEntity="Book", mappedBy="owner")
     * @Expose
     * @Groups({
     *     Circle::GROUP_MULTIPLE,
     *     Circle::GROUP_SINGLE
     *  })
     */
    private $books;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->members = new \Doctrine\Common\Collections\ArrayCollection();
        $this->admins = new \Doctrine\Common\Collections\ArrayCollection();
    }

    // ----------------------------------------------------------------------------------------------------------------
    // GETTERS & SETTERS
    // ----------------------------------------------------------------------------------------------------------------


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
     * @return Circle
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
     * Add members
     *
     * @param \BookmarkManager\ApiBundle\Entity\User $member
     * @return Circle
     */
    public function addMember(User $member)
    {
        if (!$this->haveMember($member)) {
            $this->members[] = $member;
        }

        return $this;
    }

    public function haveMember(User $member)
    {
        return $this->members->indexOf($member) !== false;
    }

    /**
     * Remove members
     *
     * @param \BookmarkManager\ApiBundle\Entity\User $member
     */
    public function removeMember(User $member)
    {
        $this->members->removeElement($member);
    }

    /**
     * Get members
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMembers()
    {
        return $this->members;
    }

    /**
     * @return array
     */
    public function getAdmins()
    {
        return $this->admins;
    }

    /**
     * @param array $admins
     */
    public function setAdmins($admins)
    {
        $this->admins = $admins;
    }

    /**
     * Add members
     *
     * @param \BookmarkManager\ApiBundle\Entity\User $admin
     * @return Circle
     */
    public function addAdmin(User $admin)
    {
        if (!$this->haveAdmin($admin)) {
            $this->admins[] = $admin;
            $this->addMember($admin);
        }

        return $this;
    }

    public function haveAdmin(User $admin)
    {
        return $this->admins->indexOf($admin) !== false;
    }

    /**
     * Remove admins
     *
     * @param \BookmarkManager\ApiBundle\Entity\User $admin
     */
    public function removeAdmin(User $admin)
    {
        $this->admins->removeElement($admin);
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
     * @return mixed
     */
    public function isDefaultCircle()
    {
        return $this->isDefaultCircle;
    }

    /**
     * @param mixed $isDefaultCircle
     */
    public function setIsDefaultBook($isDefaultCircle)
    {
        $this->isDefaultCircle = $isDefaultCircle;
    }


    /**
     * @return mixed
     */
    public function getBooks()
    {
        return $this->books;
    }

    public function addBook(Book $book)
    {
        if (!$this->haveBook($book)) {
            $this->books[] = $book;
        }

        return $this;
    }

    public function haveBook(Book $book)
    {
        return $this->books->indexOf($book) !== false;
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
     * Set isDefaultCircle
     *
     * @param boolean $isDefaultCircle
     * @return Circle
     */
    public function setIsDefaultCircle($isDefaultCircle)
    {
        $this->isDefaultCircle = $isDefaultCircle;

        return $this;
    }

    /**
     * Get isDefaultCircle
     *
     * @return boolean
     */
    public function getisDefaultCircle()
    {
        return $this->isDefaultCircle;
    }

    /**
     * Remove books
     *
     * @param \BookmarkManager\ApiBundle\Entity\Book $books
     */
    public function removeBook(\BookmarkManager\ApiBundle\Entity\Book $books)
    {
        $this->books->removeElement($books);
    }
}
