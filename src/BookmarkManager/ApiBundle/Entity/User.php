<?php

namespace BookmarkManager\ApiBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Accessor;

use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Validator\ConstraintViolation;

use Symfony\Component\Validator\Constraints as Assert;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use BookmarkManager\ApiBundle\Entity\Bookmark;
use BookmarkManager\ApiBundle\Entity\Tag;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="sw_user") // TODO: bm_user
 * @ExclusionPolicy("ALL")
 *
 * @UniqueEntity(fields="emailCanonical", message="fos_user.email.already_used")
 * @UniqueEntity(fields="usernameCanonical", message="fos_user.username.already_used", groups={"Registration"})
 */
class User extends BaseUser
{
    const REPOSITORY_NAME = 'User';

    const GROUP_MULTIPLE = "users";
    const GROUP_SINGLE = "user";
    const GROUP_ME = "me";

    /**
     * @ORM\Id
     * @Expose
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Groups({
     *     User::GROUP_MULTIPLE,
     *     User::GROUP_SINGLE,
     *     User::GROUP_ME,
     *     Circle::GROUP_MULTIPLE,
     *     Circle::GROUP_SINGLE
     *     })
     */
    protected $id;

    /**
     * @var \DateTime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     *
     * @Expose
     * @Groups({
     *     User::GROUP_ME,
     *     })
     */
    private $createdAt;

    /**
     * @var \DateTime updatedAt
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
     *
     * @Expose
     * @Groups({
     *     User::GROUP_ME
     *     })
     *
     */
    private $updatedAt;

    /**
     * @var string avatar
     * @ORM\Column(name="avatar", type="string", length=255, nullable=true)
     *
     * @Expose
     * @Groups({
     *     User::GROUP_MULTIPLE,
     *     User::GROUP_SINGLE,
     *     User::GROUP_ME,
     *     Circle::GROUP_MULTIPLE
     *     })
     */
    private $avatar;

    /**
     * @var datetime lastActivity
     * @ORM\Column(name="last_activity", type="datetime", nullable=true)
     *
     * @Expose
     * @Groups({
     *     User::GROUP_MULTIPLE,
     *     User::GROUP_SINGLE,
     *     User::GROUP_ME,
     *     Circle::GROUP_MULTIPLE
     *     })
     */
    private $lastActivity;

    /**
     * @var [Bookmark] bookmarks
     * @ORM\OneToMany(targetEntity="Bookmark", mappedBy="owner")
     *
     */
    private $bookmarks;

    /**
     * @var [Tag] tags
     * @Expose
     * @ORM\OneToMany(targetEntity="Tag", mappedBy="owner")
     *
     */
    private $tags;

    /**
     * @var [Circle] circles
     *
     * Disable direct Expose due to serialization bug (serialize as object instead of array)
     * The service to get the user's circles is /circles
     *
     * @ORM\ManyToMany(targetEntity="Circle", mappedBy="members")
     *
     * @Expose
     * @Groups({
     *     User::GROUP_MULTIPLE,
     *     User::GROUP_SINGLE,
     *     User::GROUP_ME,
     * })
     */
    protected $circles;

    /**
     * @var [Circle] circles
     *
     * Disable direct Expose due to serialization bug (serialize as object instead of array)
     * The service to get the user's owned circles is /circles
     *
     * @ORM\ManyToMany(targetEntity="Circle", mappedBy="admins")
     *
     * @Expose
     * @Groups({
     *     User::GROUP_MULTIPLE,
     *     User::GROUP_SINGLE,
     *     User::GROUP_ME,
     *     Circle::GROUP_MULTIPLE
     *     })
     */
    protected $circlesAdmin;

    // ----------------------------------------------------------------------------------------------------------------
    // default circle id

    /**
     * @ORM\Column(name="default_circle_id", type="string", length=255, nullable=false)
     *
     * @Expose
     * @Groups({ User::GROUP_ME })
     *
     */
    public $defaultCircleId = '';

    // ----------------------------------------------------------------------------------------------------------------
    // LIFECYCLE
    // ----------------------------------------------------------------------------------------------------------------

    public function __construct()
    {
        parent::__construct();

        $this->circles = new \Doctrine\Common\Collections\ArrayCollection();
        $this->circlesAdmin = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @Groups({ User::GROUP_MULTIPLE, User::GROUP_SINGLE, Circle::GROUP_MULTIPLE })
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
     *
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
    // GETTERS & SETTERS
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
     * Add bookarks
     *
     * @param Bookmark $bookmark
     * @return User
     */
    public function addBookmark(Bookmark $bookmark)
    {
        $this->bookmarks[] = $bookmark;

        return $this;
    }

    /**
     * Remove bookmarks
     *
     * @param Bookmark $bookmark
     */
    public function removeBookmark(Bookmark $bookmark)
    {
        $this->bookmarks->removeElement($bookmark);
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
     * @param \BookmarkManager\ApiBundle\Entity\Tag $tag
     * @return User
     */
    public function addTag(Tag $tag)
    {
        $this->tags[] = $tag;

        return $this;
    }

    /**
     * Remove tags
     *
     * @param Tag $tag
     */
    public function removeTag(Tag $tag)
    {
        $this->tags->removeElement($tag);
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

    /**
     * Add circle
     *
     * @param \BookmarkManager\ApiBundle\Entity\Circle $circle
     * @return Circle
     */
    public function addCircle(Circle $circle)
    {
        if (!$this->haveCircle($circle)) {
            $this->circles[] = $circle;
        }

        return $this;
    }


    public function haveCircle(Circle $circle)
    {
        return $this->circles->indexOf($circle) !== false;
    }

    /**
     * @return mixed
     */
    public function getCircles()
    {
        return $this->circles;
    }

    /**
     * @param mixed $circles
     */
    public function setCircles($circles)
    {
        $this->circles = $circles;
    }

    /**
     * @return mixed
     */
    public function getCirclesAdmin()
    {
        return $this->circlesAdmin;
    }

    /**
     * @param mixed $circlesAdmin
     */
    public function setCirclesAdmin($circlesAdmin)
    {
        $this->circlesAdmin = $circlesAdmin;
    }

    /**
     * Add members
     *
     * @param \BookmarkManager\ApiBundle\Entity\Circle $circleToAdministrate
     * @return Circle
     */
    public function addCircleToAdmin(Circle $circleToAdministrate)
    {
        if (!$this->haveCircleToAdmin($circleToAdministrate)) {
            $this->circlesAdmin[] = $circleToAdministrate;
            $this->addCircle($circleToAdministrate);
        }

        return $this;
    }

    public function haveCircleToAdmin(Circle $circleToAdministrate)
    {
        return $this->circlesAdmin->indexOf($circleToAdministrate) !== false;
    }

    /**
     * @return mixed
     */
    public function getDefaultCircleId()
    {
        return $this->defaultCircleId;
    }

    /**
     * @param mixed $defaultCircleId
     */
    public function setDefaultCircleId($defaultCircleId)
    {
        $this->defaultCircleId = $defaultCircleId;
    }


    /**
     * Remove circles
     *
     * @param \BookmarkManager\ApiBundle\Entity\Circle $circles
     */
    public function removeCircle(\BookmarkManager\ApiBundle\Entity\Circle $circles)
    {
        $this->circles->removeElement($circles);
    }

    /**
     * Add circlesAdmin
     *
     * @param \BookmarkManager\ApiBundle\Entity\Circle $circlesAdmin
     * @return User
     */
    public function addCirclesAdmin(\BookmarkManager\ApiBundle\Entity\Circle $circlesAdmin)
    {
        $this->circlesAdmin[] = $circlesAdmin;

        return $this;
    }

    /**
     * Remove circlesAdmin
     *
     * @param \BookmarkManager\ApiBundle\Entity\Circle $circlesAdmin
     */
    public function removeCirclesAdmin(\BookmarkManager\ApiBundle\Entity\Circle $circlesAdmin)
    {
        $this->circlesAdmin->removeElement($circlesAdmin);
    }
}
