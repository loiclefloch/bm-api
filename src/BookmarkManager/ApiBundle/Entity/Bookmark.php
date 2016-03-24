<?php

namespace BookmarkManager\ApiBundle\Entity;

use BookmarkManager\ApiBundle\Utils\BookmarkUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use \BookmarkManager\ApiBundle\Entity\Tag;
use \BookmarkManager\ApiBundle\Entity\User;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\VirtualProperty;

/**
 * Define the different type of bookmark. Can be found by looking the og:type website meta.
 *
 * Class BookmarkType
 * @package BookmarkManager\ApiBundle\Entity
 */
class BookmarkType
{
    const WEBSITE = 0; // default
    const ARTICLE = 1;
    const VIDEO = 2;
    const MUSIC = 3;
    const CODE = 4; // for example: github code page or project
    const GAME = 5;
    const SLIDE = 6;
    const IMAGE = 7;
}

/**
 * Bookmark
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table()
 * @ORM\Entity
 *
 * @ExclusionPolicy("ALL")
 */
class Bookmark
{

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Expose
     * @Groups({"list","alone"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     *
     * @Expose
     * @Groups({"list","alone"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, unique=true)
     *
     * @Expose
     * @Groups({"list","alone"})
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     *
     * @Expose
     * @Groups({"list","alone"})
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="icon", type="string", length=255, nullable=true)
     *
     * @Expose
     * @Groups({"list","alone"})
     */
    private $icon;

    /**
     * @var text
     *
     * @ORM\Column(name="notes", type="text", nullable=true)
     *
     * @Expose
     * @Groups({"alone"})
     */
    private $notes;

    /**
     * @ORM\Column(name="content", type="text", nullable=true)
     *
     * @Expose
     * @Groups({"alone"})
     */
    private $content;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     *
     * @Expose
     * @Groups({"list","alone"})
     */
    private $description;

    /**
     * @var int type
     *
     * @ORM\Column(name="type", type="smallint", nullable=false)
     *
     * @Expose
     * @Groups({"list","alone"})
     */
    private $type;

    /**
     * @var Bookmark teams
     * @ORM\ManyToOne(targetEntity="User", inversedBy="bookmarks")
     */
    private $owner;

    /**
     * @var ArrayCollection tags
     *
     * @ORM\ManyToMany(targetEntity="Tag", cascade={"persist"})
     *
     * @Expose
     * @Groups({"list","alone"})
     */
    private $tags;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     *
     * @Expose
     * @Groups({"list","alone"})
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     *
     * @Expose
     * @Groups({"list","alone"})
     */
    private $updatedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="preview_picture", type="text", nullable=true)
     *
     * @Expose
     * @Groups({"list", "alone"})
     */
    private $previewPicture;

    /**
     * @VirtualProperty
     * @Type("string")
     * @SerializedName("reading_time")
     * @Groups({"list","alone"})
     *
     * @return float
     */
    public function getReadingTime() {
        return BookmarkUtils::getReadingTime($this->getContent());
    }

    // ----------------------------------------------------------------------------------------------------------------
    // LIFECYCLE
    // ----------------------------------------------------------------------------------------------------------------

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->type = BookmarkType::WEBSITE;
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
    // TOOLS
    // ----------------------------------------------------------------------------------------------------------------

    /**
     * @param \BookmarkManager\ApiBundle\Entity\Tag $tag
     * @return bool
     */
    public function haveTag(Tag $tag)
    {
        return $this->tags->indexOf($tag) !== false;
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
     * @return Bookmark
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
     * Set url
     *
     * @param string $url
     * @return Bookmark
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Bookmark
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set title
     *
     * @param string $icon
     * @return Bookmark
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Get icon
     *
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Bookmark
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return Bookmark
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set owner
     *
     * @param User $owner
     * @return Bookmark
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

    /**
     * Add tags
     *
     * @param Tag $tag
     * @return Bookmark
     */
    public function addTag(Tag $tag)
    {
        if (!$this->haveTag($tag)) {
            $this->tags[] = $tag;
        }

        return $this;
    }

    public function addTags($tagsFound)
    {
        foreach ($tagsFound as $tag) {
            if (!$this->haveTag($tag)) {
                $this->tags[] = $tag;
            }
        }

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
     * Set notes
     *
     * @param string $notes
     * @return Bookmark
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * Get notes
     *
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Set notes
     *
     * @param string $content
     * @return Bookmark
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get notes
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @return mixed
     */
    public function getPreviewPicture()
    {
        return $this->previewPicture;
    }

    /**
     * @param mixed $previewPicture
     */
    public function setPreviewPicture($previewPicture)
    {
        $this->previewPicture = $previewPicture;
    }

}
