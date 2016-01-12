<?php

namespace BookmarkManager\ApiBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Accessor;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Validator\ConstraintViolation;

use Symfony\Component\Validator\Constraints as Assert;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use BookmarkManager\ApiBundle\Entity\Bookmark;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="sw_user")
 * @ExclusionPolicy("ALL")
 *
 * @UniqueEntity(fields="emailCanonical", message="fos_user.email.already_used")
 * @UniqueEntity(fields="usernameCanonical", message="fos_user.username.already_used", groups={"Registration"})
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @Expose
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var \DateTime $createdAt
     * @Expose
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * @var \DateTime updatedAt
     * @Expose
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
     */
    private $updatedAt;

    /**
     * @var string avatar
     * @Expose
     * @ORM\Column(name="avatar", type="string", length=255, nullable=true)
     */
    private $avatar;

    /**
     * @var datetime lastActivity
     * @Expose
     * @ORM\Column(name="last_activity", type="datetime", nullable=true)
     */
    private $lastActivity;

    /**
     * @var [Bookmark] bookmarks
     * @Expose
     * @ORM\OneToMany(targetEntity="Bookmark", mappedBy="owner")
     */
    private $bookmarks;

    /**
     * @var [Tag] tags
     * @Expose
     * @ORM\OneToMany(targetEntity="Tag", mappedBy="owner")
     */
    private $tags;

    // ----------------------------------------------------------------------------------------------------------------

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatedTimestamps()
    {
        $now = new \DateTime('now');

        $this->setUpdatedAt($now);

        if ($this->getCreatedAt() == null) {
            $this->setCreatedAt($now);
        }
    }

    // ----------------------------------------------------------------------------------------------------------------
    // Activity tracking

    /**
     * @Accessor(getter="isActive")
     * @Expose
     */
    public $isActive = false;

    /**
     * Set lastActivity
     *
     * @param \DateTime $lastActivity
     * @return User
     */
    public function setLastActivity($lastActivity)
    {
        $this->lastActivity = $lastActivity;

        return $this;
    }

    /**
     * Get lastActivity
     *
     * @return \DateTime
     */
    public function getLastActivity()
    {
        return $this->lastActivity;
    }

    /**
     * Set the user as active
     */
    public function setIsActiveNow()
    {
        $this->setLastActivity(new \DateTime());
    }

    /**
     * Returns true if the user was active in the last 15 minutes
     * @return bool
     */
    public function isActive()
    {
        $delay = new \DateTime();
        $delay->setTimestamp(strtotime('15 minutes ago'));
        if ($this->lastActivity > $delay) {
            return true;
        }

        return false;
    }

    // ----------------------------------------------------------------------------------------------------------------

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

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
     * @return string
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * @param string $avatar
     */
    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    /**
     * @return mixed
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param mixed $lastName
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }


    /**
     * Add bookmarks
     *
     * @param \BookmarkManager\ApiBundle\Entity\Bookmark $bookmarks
     * @return User
     */
    public function addBookmark(\BookmarkManager\ApiBundle\Entity\Bookmark $bookmarks)
    {
        $this->bookmarks[] = $bookmarks;

        return $this;
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

    /**
     * Get bookmarks
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getBookmarks()
    {
        return $this->bookmarks;
    }

    /**
     * Add tags
     *
     * @param \BookmarkManager\ApiBundle\Entity\Tag $tags
     * @return User
     */
    public function addTag(\BookmarkManager\ApiBundle\Entity\Tag $tags)
    {
        $this->tags[] = $tags;

        return $this;
    }

    /**
     * Remove tags
     *
     * @param \BookmarkManager\ApiBundle\Entity\Tag $tags
     */
    public function removeTag(\BookmarkManager\ApiBundle\Entity\Tag $tags)
    {
        $this->tags->removeElement($tags);
    }

    /**
     * Get tags
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getTags()
    {
        return $this->tags;
    }
}
