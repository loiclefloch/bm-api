<?php

namespace BookmarkManager\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use BookmarkManager\ApiBundle\Entity\User;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;

/**
 * Circle
 *
 * TODO:
 * - private / public
 * - picture
 *
 * @ORM\Table(name="circles")
 * @ORM\Entity
 */
class Circle
{
    const REPOSITORY_NAME = 'Circle';

    const GROUP_MULTIPLE_CIRCLES = ["circles"];
    const GROUP_SINGLE_CIRCLE = ["circles", "circle"];

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Groups(Circle::GROUP_MULTIPLE_CIRCLES)
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     * @Groups(Circle::GROUP_MULTIPLE_CIRCLES)
     */
    private $name;

    /**
     * @var array
     *
     * @ORM\ManyToMany(targetEntity="User", inversedBy="circles")
     * @Groups(Circle::GROUP_MULTIPLE_CIRCLES)
     */
    private $members;


    /**
     * @var array
     *
     * @ORM\ManyToMany(targetEntity="User", inversedBy="circlesAdmin")
     * @ORM\JoinTable(name="circleadmins")
     * @Groups(Circle::GROUP_MULTIPLE_CIRCLES)
     */
    private $admins;

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

}
