<?php

namespace BookmarkManager\ApiBundle\Entity;

use Symfony\Component\DomCrawler\Crawler;
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
abstract class BookmarkType
{
    const WEBSITE = 0; // default
    const ARTICLE = 1;
    const VIDEO = 2;
    const MUSIC = 3;
    const CODE = 4; // for example: github code page or project
    const GAME = 5;
    const SLIDE = 6; // for example: slideshare
    const IMAGE = 7;
}

/**
 * Define the different possible status for the bookmark considering the content crawler.
 *
 * Class BookmarkCrawlerStatus
 * @package BookmarkManager\ApiBundle\Entity
 */
abstract class BookmarkCrawlerStatus
{
    /**
     * Could not retrieve the content
     */
    const NO_RETRIEVE = 0;

    /**
     * Retrieved content, but there is some issues with
     * Can be set manually by the front
     */
    const CONTENT_BUG = 1;

    /**
     * Content correctly retrieved
     */
    const RETRIEVED = 2;
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
    const REPOSITORY_NAME = 'Bookmark';


    /**
     * The default readingTime value. Help us to know if the readingTime have been calculated or not.
     */
    const DEFAULT_READING_TIME = -1;

    /**
     * Average readers only reach around 200 wpm (words per minute) with a typical comprehension of 60%.
     *
     * @see http://www.readingsoft.com/
     */
    const AVERAGE_WORDS_PER_MINUTES = 200;

    const GROUP_MULTIPLE = "bookmarks";

    const GROUP_SINGLE = "bookmark";

    /**
     * The class of a slide on the `content`.
     */
    const SLIDE_IMAGE_CLASS = 'img.slide_image';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Expose
     * @Groups({Bookmark::GROUP_MULTIPLE, Bookmark::GROUP_SINGLE, Book::GROUP_MULTIPLE})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     *
     * @Expose
     * @Groups({Bookmark::GROUP_MULTIPLE, Bookmark::GROUP_SINGLE, Book::GROUP_MULTIPLE})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, unique=true)
     *
     * @Expose
     * @Groups({Bookmark::GROUP_MULTIPLE, Bookmark::GROUP_SINGLE, Book::GROUP_MULTIPLE})
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     *
     * @Expose
     * @Groups({Bookmark::GROUP_MULTIPLE, Bookmark::GROUP_SINGLE, Book::GROUP_MULTIPLE})
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="icon", type="string", length=255, nullable=true)
     *
     * @Expose
     * @Groups({Bookmark::GROUP_MULTIPLE, Bookmark::GROUP_SINGLE, Book::GROUP_MULTIPLE})
     */
    private $icon;

    /**
     * @var text
     *
     * @ORM\Column(name="notes", type="text", nullable=true)
     *
     * @Expose
     * @Groups(Bookmark::GROUP_SINGLE)
     */
    private $notes;

    /**
     * @ORM\Column(name="content", type="text", nullable=true)
     *
     * @Expose
     * @Groups(Bookmark::GROUP_SINGLE)
     */
    private $content;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     *
     * @Expose
     * @Groups({Bookmark::GROUP_MULTIPLE, Bookmark::GROUP_SINGLE, Book::GROUP_MULTIPLE})
     */
    private $description;

    /**
     * @var int type
     *
     * @ORM\Column(name="type", type="smallint", nullable=false)
     *
     * @Expose
     * @Groups({Bookmark::GROUP_MULTIPLE, Bookmark::GROUP_SINGLE, Book::GROUP_MULTIPLE})
     */
    private $type;

    /**
     * @var Bookmark teams
     * @ORM\ManyToOne(targetEntity="User", inversedBy="bookmarks")
     *
     * @deprecated
     */
    private $owner;

    /**
     * @ORM\ManyToMany(targetEntity="Book", inversedBy="bookmarks")
     *
     * @deprecated
     */
    private $books;

    /**
     * @var ArrayCollection tags
     *
     * @ORM\ManyToMany(targetEntity="Tag", cascade={"persist"})
     *
     * @Expose
     * @Groups({Bookmark::GROUP_MULTIPLE, Bookmark::GROUP_SINGLE, Book::GROUP_MULTIPLE})
     */
    private $tags;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     *
     * @Expose
     * @Groups({Bookmark::GROUP_MULTIPLE, Bookmark::GROUP_SINGLE, Book::GROUP_MULTIPLE})
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     *
     * @Expose
     * @Groups({Bookmark::GROUP_MULTIPLE, Bookmark::GROUP_SINGLE, Book::GROUP_MULTIPLE})
     */
    private $updatedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="preview_picture", type="text", nullable=true)
     *
     * @Expose
     * @Groups({Bookmark::GROUP_MULTIPLE, Bookmark::GROUP_SINGLE, Book::GROUP_MULTIPLE})
     */
    private $previewPicture;


    /**
     * @var number
     *
     * @ORM\Column(name="reading_time", type="smallint", nullable=false)
     *
     * @see #getReadingTime
     *
     * @Expose
     * @Groups({Bookmark::GROUP_MULTIPLE, Bookmark::GROUP_SINGLE, Book::GROUP_MULTIPLE})
     */
    private $readingTime = Bookmark::DEFAULT_READING_TIME;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_read", type="boolean", options={"default": false})
     *
     * @Expose
     * @Groups({Bookmark::GROUP_MULTIPLE, Bookmark::GROUP_SINGLE, Book::GROUP_MULTIPLE})
     */
    private $read = false;

    /**
     * @var array
     * Contains all the parsed og data
     *
     * @ORM\Column(name="website_info", type="json_array")
     * @Expose
     * @Groups({Bookmark::GROUP_MULTIPLE, Bookmark::GROUP_SINGLE, Book::GROUP_MULTIPLE})
     */
    private $websiteInfo;

    /**
     * @var BookmarkCrawlerStatus
     *
     * @ORM\Column(name="crawler_status", type="smallint", nullable=false, options={"default": 2})
     * @Expose
     * @Groups({Bookmark::GROUP_MULTIPLE, Bookmark::GROUP_SINGLE, Book::GROUP_MULTIPLE})
     */
    private $crawlerStatus;

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


    public function clearTags()
    {
        $this->tags = new ArrayCollection();
    }

    /**
     * Add tags
     *
     * @param Tag $tag
     * @return Bookmark
     */
    public function addTag(Tag $tag)
    {
        // check if tag already exist but has not yet be persisted
        if (!$this->haveTag($tag)) {
            $this->tags[] = $tag;
        }

        return $this;
    }

    public function addTags($tagsFound)
    {
        if (is_array($tagsFound)) {
            foreach ($tagsFound as $tag) {
                $this->addTag($tag);
            }
        }

        return $this;
    }

    /**
     * Remove tags
     *
     * @param Tag $tag
     * @return Bookmark
     */
    public function removeTag(Tag $tag)
    {
        $this->tags->removeElement($tag);

        return $this;
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
        /**
         * Add the charset for the DomCrawler to use UTF-8 and not the default 'ISO-8859-1'.
         * @see Crawler#addContent
         */
        $this->content = "<!DOCTYPE html><html><head><meta charset='utf-8>' /></head><body>".$content."</body></html>";

        return $this;
    }

    public function removeContent()
    {
        $this->content = "";

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

    /**
     * @return float
     */
    public function getReadingTime()
    {
        return $this->readingTime;
    }

    /**
     * @param number $readingTime
     */
    public function setReadingTime($readingTime)
    {
        $this->readingTime = $readingTime;
    }

    /**
     * @return boolean
     */
    public function isRead()
    {
        return $this->read;
    }

    /**
     * @param boolean $read
     */
    public function setRead($read)
    {
        $this->read = $read;
    }

    /**
     * @return mixed
     */
    public function getBooks()
    {
        return $this->books;
    }

    /**
     * @param mixed $books
     */
    public function setBooks($books)
    {
        $this->books = $books;
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
     * @return array
     */
    public function getWebsiteInfo()
    {
        return $this->websiteInfo;
    }

    /**
     * @param array $websiteInfo json array
     */
    public function setWebsiteInfo($websiteInfo)
    {
        $this->websiteInfo = $websiteInfo;
    }

    /**
     * @return mixed
     */
    public function getCrawlerStatus()
    {
        return $this->crawlerStatus;
    }

    /**
     * @param mixed $crawlerStatus
     */
    public function setCrawlerStatus($crawlerStatus)
    {
        $this->crawlerStatus = $crawlerStatus;
    }

}
