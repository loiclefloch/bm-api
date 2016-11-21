<?php

namespace BookmarkManager\ApiBundle\Crawler;

use BookmarkManager\ApiBundle\Crawler\Plugin\GithubCrawlerPlugin;
use BookmarkManager\ApiBundle\Crawler\Plugin\ImageCrawlerPlugin;
use BookmarkManager\ApiBundle\Crawler\Plugin\MediumCrawlerPlugin;
use BookmarkManager\ApiBundle\Crawler\Plugin\SlideshareCrawlerPlugin;
use BookmarkManager\ApiBundle\Crawler\Plugin\YouTubeCrawlerPlugin;
use BookmarkManager\ApiBundle\Entity\Bookmark;
use BookmarkManager\ApiBundle\Entity\BookmarkType;
use BookmarkManager\ApiBundle\Entity\User;
use BookmarkManager\ApiBundle\Utils\BookmarkUtils;
use BookmarkManager\ApiBundle\Utils\StringUtils;
use Exception;
use Symfony\Component\DomCrawler\Crawler;
use BookmarkManager\ApiBundle\Crawler\Readability\Readability;

/**
 */
class WebsiteCrawler
{
    /**
     * Number of occurrences find for a tag name on the bookmark text content to propose to set the tag.
     * This value is completely arbitrary.
     * TODO: Run tests to find an appreciable value.
     */
    const NB_OCCURRENCES_TO_SET_TAG = 3;

    /**
     * @param Bookmark $bookmark
     * @param User $user
     * @return Bookmark
     */
    public function crawlWebsite(Bookmark $bookmark, User $user)
    {
        $bookmark->setUrl($this->cleanUrl($bookmark->getUrl()));

        $html = $this->get_data($bookmark->getUrl());

        return $this->crawlWebsiteWithHtml($bookmark, $html, $user);
    }

    /**
     * @param Bookmark $bookmark
     * @param string $html
     * @param User $user
     * @return Bookmark
     */
    public function crawlWebsiteWithHtml(Bookmark $bookmark, $html = "", User $user = null)
    {
        $bookmark->setUrl($this->cleanUrl($bookmark->getUrl()));
        $crawler = new Crawler($html);

        if ($html) {

            try {
                $title = trim($crawler->filter('head > title')->text());
            } catch (Exception $e) {
                $title = 'Unknown title';
            }

            // -- Meta name

            $metaNameCrawler = $crawler->filter('head > meta')->reduce(
                function (Crawler $node) {
                    $nameValue = $node->attr('name');

                    // We get all non null meta with a name or a property
                    return null !== $nameValue && null !== $node->attr('content');
                }
            );

            $metaNames = [];
            foreach ($metaNameCrawler as $item) {
                $name = $item->getAttribute('name');
                $content = $item->getAttribute('content');

                $metaNames[$name] = $content;
            }

            // -- Meta property

            $metaPropertyCrawler = $crawler->filter('head > meta')->reduce(
                function (Crawler $node) {
                    $propertyValue = $node->attr('property');

                    // We get all non null meta with a name or a property
                    return null !== $propertyValue && null !== $node->attr('content');
                }
            );

            $metaProperties = [];
            foreach ($metaPropertyCrawler as $item) {
                $name = $item->getAttribute('property');
                $content = $item->getAttribute('content');

                $metaProperties[$name] = $content;
            }

//            var_dump($metaNames);
//            var_dump($metaProperties);

            // All the information that we want to retrieve. Og information is handle with #handleOg
            $websiteInfo = [
                'author' => null,
                'keywords' => null,
            ];

            // -- Retrieve website's information

            $websiteInfo['author'] = trim($this->array_get_key('Author', $metaNames));
            $websiteInfo['keywords'] = trim($this->array_get_key('Keywords', $metaNames));
            $websiteInfo['description'] = trim($this->array_get_key('description', $metaProperties));

            $ogData = $this->handleOg($html, $bookmark);

            // -- Select the best description
            if ($websiteInfo['description'] === null or strlen($websiteInfo['description']) === 0) {
                $description = $ogData->getDescription();
            } else {
                $description = $websiteInfo['description'];
            }

            // -- Retrieve the bookmark type
            $types = [
                'website' => BookmarkType::WEBSITE,
                'article' => BookmarkType::ARTICLE,
                'video' => BookmarkType::VIDEO,
                'music' => BookmarkType::MUSIC,
            ];

            // set bookmark type according to the og:type
            if ($ogData->getType() != null
                && $ogData->getType() != null
                && isset($types[$ogData->getType()])
            ) {
                $bookmark->setType($types[$ogData->getType()]);
            } else { // by default, the bookmark will be a WEBSITE
                $bookmark->setType(BookmarkType::WEBSITE);
            }

            // -- Get website icon
            $crawler->filter('link')->each(
                function (Crawler $node) use ($bookmark) {
                    $rel = $node->attr('rel');

                    if ($rel == 'icon'
                        || $rel == 'shortcut icon'
                        || $rel == 'apple-touch-icon'
                    ) {

                        $href = $node->attr('href');
                        $href = $this->getRealLink($href, $bookmark->getUrl());
                        $bookmark->setIcon($href);
                    }
                }
            );

            // TODO: Add $websiteInfo to a new entity.

            // -- set preview picture
            $ogImage = $ogData->getImage();
            if ($ogImage !== null && strlen($ogImage) > 0) {
                $bookmark->setPreviewPicture($ogImage);
            }

            $bookmark->setTitle($title);
            $bookmark->setDescription($description);
        }

        // -----------------
        // Handle content
        // -----------------

        /*
         * Custom crawler for websites.
         */

        // Register crawler plugins
        $crawlerPlugins = [
            new ImageCrawlerPlugin(),
            new GithubCrawlerPlugin(),
            new SlideshareCrawlerPlugin(),
            new YouTubeCrawlerPlugin(),
            new MediumCrawlerPlugin()
        ];

        foreach ($crawlerPlugins as $plugin) {
            if ($plugin->matchUrl($bookmark->getUrl())) {
                $bookmark = $plugin->parse($crawler, $bookmark);
            }
        }

        // no plugin where used to get the website content or the plugin did not handle the content, so we use readability
        if (strlen($bookmark->getContent()) == 0) {
            $readability = new Readability($html, $bookmark->getUrl());
            $success = $readability->init();

            if ($success) {
                $bookmark->setContent($readability->getContent()->innerHTML);
            } else {
                // no content where found.
            }

        }

        // -- automatic tags
        if ($user != null) {
            $tagsFound = $this->findTagsOnText($user->getTags()->toArray(), $bookmark->getContent());
            $bookmark->addTags($tagsFound);
        }

        // -- handle links on the content
        $bookmark->setContent($this->handleLinks($bookmark->getContent(), $bookmark->getUrl()));

        // TODO: replace img links by data:image/ ? Cf CrawlerUtils::picturesToBase64

        // TODO: remove scripts and css from content

        // TODO: remove inline styles

        $bookmark->setContent($this->handleAnchors($bookmark->getContent()));

//        $bookmark->setRead(false);

        // Set readingTime if the crawler have not.
        if ($bookmark->getReadingTime() === $bookmark::DEFAULT_READING_TIME) {
            $bookmark->setReadingTime(BookmarkUtils::getReadingTime($bookmark));
        }

        return $bookmark;
    }

    /**
     * @param $html
     * @param Bookmark $bookmark
     * @return OgData
     */
    public function handleOg($html, Bookmark $bookmark)
    {
        $metaProperties = $this->getMetaPropertiesFromHtml($html);

        // -- Handle basic info

        $websiteInfo = new OgData();

        // -- Handle basic og information See http://ogp.me/
        $websiteInfo->setTitle($this->array_get_key('og:title', $metaProperties));
        $ogImage = $this->array_get_key('og:image', $metaProperties);

        if (strlen($ogImage) > 0) {
            $websiteInfo->setImage($this->getRealLink($ogImage, $bookmark->getUrl()));
        }

        $websiteInfo->setDescription($this->array_get_key('og:description', $metaProperties));

        $ogType = $this->array_get_key('og:type', $metaProperties);
        if (null != $ogType) { // do not override default value ('website')
            $websiteInfo->setType($ogType);
        }

        return $websiteInfo;
    }

    public function handleOgDetailsForType($ogType, $html)
    {
        $metaProperties = $this->getMetaPropertiesFromHtml($html);

        // -- Handle detail info

        /**
         * Note about the different types:
         * Bool: true, false, 1, 0
         * DateTime [ISO 8601](http://en.wikipedia.org/wiki/ISO_8601)
         * Enum
         * Float
         * Integer
         * String
         * URL
         */
        $keysOgByType = [
            'article' => [
                'article:published_time', // datetime - When the article was first published.
                'article:modified_time', // datetime - When the article was last changed.
                'article:expiration_time', // datetime - When the article is out of date after.
                'article:author', // profile array - Writers of the article.
                'article:section', // string - A high-level section name. E.g. Technology
                'article:tag', // string array - Tag words associated with this article.
            ],
            'book' => [
                'book:author', // profile array - Who wrote this book.
                'book:isbn', // string - The ISBN
                'book:release_date', // datetime - The date the book was released.
                'book:tag', // string array - Tag words associated with this book.
            ],
            'profile' => [
                'profile:first_name',
                // string - A name normally given to an individual by a parent or self-chosen.
                'profile:last_name',
                // string - A name inherited from a family or marriage and by which the individual is commonly known.
                'profile:username',
                // string - A short unique string to identify them.
                'profile:gender',
                // enum(male, female) - Their gender.
            ],
            'website' => [ // No additional properties other than the basic ones. Any non-marked up webpage should be treated as og:type website.

            ],
            'music.song' => [
                'music:duration', // integer >=1 - The song's length in seconds.
                'music:album', // music.album array - The album this song is from.
                'music:album:disc', // integer >=1 - Which disc of the album this song is on.
                'music:album:track', // integer >=1 - Which track this song is.
                'music:musician', // profile array - The musician that made this song.
            ],
            'music.album' => [
                'music:song - ', // The song on this album.
                'music:song:disc', // integer >=1 - The same as music:album:disc but in reverse.
                'music:song:track', //  - integer >=1 - The same as music:album:track but in reverse.
                'music:musician', // profile - The musician that made this song.
                'music:release_date', // datetime - The date the album was released.
            ],
            'music.playlist' => [
                'music:song', // Identical to the ones on music.album
                'music:song:disc',
                'music:song:track',
                'music:creator', // profile - The creator of this playlist.
            ],
            'music.radio_station' => [
                'music:creator' // profile - The creator of this station.
            ],

            'video.movie' => [
                'video:actor', // profile array - Actors in the movie.
                'video:actor:role', // string - The role they played.
                'video:director', // profile array - Directors of the movie.
                'video:writer', //  profile array - Writers of the movie.
                'video:duration', // integer >=1 - The movie's length in seconds.
                'video:release_date', // datetime - The date the movie was released.
                'video:tag', // string array - Tag words associated with this movie.
            ],
            'video.tv_show' => [ //  The metadata is identical to video.movie.
                'video:actor', // profile array - Actors in the movie.
                'video:actor:role', // string - The role they played.
                'video:director', // profile array - Directors of the movie.
                'video:writer', //  profile array - Writers of the movie.
                'video:duration', // integer >=1 - The movie's length in seconds.
                'video:release_date', // datetime - The date the movie was released.
                'video:tag', // string array - Tag words associated with this movie.
            ],
            'video.other' => [  // The metadata is identical to video.movie.
                'video:actor', // profile array - Actors in the movie.
                'video:actor:role', // string - The role they played.
                'video:director', // profile array - Directors of the movie.
                'video:writer', //  profile array - Writers of the movie.
                'video:duration', // integer >=1 - The movie's length in seconds.
                'video:release_date', // datetime - The date the movie was released.
                'video:tag', // string array - Tag words associated with this movie.
            ],
            'video.episode' => [
                'video:actor', // Identical to video.movie
                'video:actor:role', //
                'video:director', //
                'video:writer', //
                'video:duration', //
                'video:release_date', //
                'video:tag', //
                'video:series', // - video.tv_show - Which series this episode belongs to.
            ],

        ];

        $detail = [];

        // -- populate detail.
        // TODO: handle nested object such as article:author.
        if ($ogType != null) {
            foreach ($keysOgByType[$ogType] as $value) {
                $detail[$value] = $this->array_get_key($value, $metaProperties);
            }
        }

        return $detail;

    }

    /**
     * Remove useless query parameters from url
     * @param $url
     * @return mixed
     */
    public function cleanUrl($url)
    {
        // Add http prefix if not exists
        if (preg_match("#https?://#", $url) === 0) {
            $url = 'https://'.$url;
        }

        $QUERIES_TO_REMOVE = [
            'utm_source',
            'utm_medium',
            'utm_term',
            'utm_content',
            'utm_campaign',
            'ref',
        ];

        $parsedUrl = parse_url($url);

        if ($parsedUrl) {

            // Remove queries that must be removed.
            if (isset($parsedUrl['query'])) {
                $parsedUrl['query'] = implode(
                    '&',
                    array_filter(
                        explode('&', $parsedUrl['query']),
                        function ($param) use ($QUERIES_TO_REMOVE) {
                            $queryName = explode('=', $param)[0];
                            $found = array_search($queryName, $QUERIES_TO_REMOVE) !== false;

                            return !$found;
                        }
                    )
                );

                if ($parsedUrl['query'] === '') {
                    unset($parsedUrl['query']);
                }
            }
            $newUrl = $this->buildUrl($parsedUrl);

        } else {
            $newUrl = $url;
        }

        return $newUrl;
    }

    // ----------------------------------------------------------------------------------------------------------------
    // Protected
    // ----------------------------------------------------------------------------------------------------------------

    protected function buildUrl($parsed_url)
    {
        $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'].'://' : '';
        $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port = isset($parsed_url['port']) ? ':'.$parsed_url['port'] : '';
        $user = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass = isset($parsed_url['pass']) ? ':'.$parsed_url['pass'] : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query = isset($parsed_url['query']) ? '?'.$parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#'.$parsed_url['fragment'] : '';

        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    protected function getBaseUrl($url)
    {
        $parsed_url = parse_url($url);
        $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'].'://' : '';
        $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port = isset($parsed_url['port']) ? ':'.$parsed_url['port'] : '';

        return "$scheme$host$port";
    }

    /**
     * Returns the value at the given key or null. If the value exists, the value is trim.
     * @param $key
     * @param $array
     * @param null $default
     * @return null|string
     */
    protected function array_get_key($key, $array, $default = null)
    {
        if (isset($array[$key])) {
            $value = $array[$key];
            if (isset($value)) {
                if (is_string($value)) {
                    return trim($value);
                }

                return $value;
            }
        }

        return $default;
    }

    protected function get_data($url)
    {
        $timeout = 15;
        $html = CrawlerUtils::getExternalFile($url, $timeout);

        return $html;
    }

    protected function handleLinks($content, $url)
    {
        $crawler = new Crawler($content);

        // -- Images
        $crawler->filter('img')->each(
            function ($imgCrawler) use ($url) {

                $currentSrc = $imgCrawler->getNode(0)->getAttribute('src');
                $newSrc = $this->getRealImgLink($currentSrc, $url);

                if (strlen($newSrc) > 0) {
                    $imgCrawler->getNode(0)->setAttribute('src', $newSrc);
                }

            }
        );

        // -- Links
        $crawler->filter('a')->each(
            function ($linkCrawler) use ($url) {

                $currentSrc = $linkCrawler->getNode(0)->getAttribute('href');

                $newSrc = $this->getRealLink($currentSrc, $url);
                $linkCrawler->getNode(0)->setAttribute('href', $newSrc);
            }
        );

        try {
            return $crawler->html();
        } catch (\InvalidArgumentException $e) {

        }

        return $content;
    }

    /**
     * @param $currentSrc
     * @param $url
     * @return string
     */
    protected function getRealImgLink($currentSrc, $url)
    {
        $newSrc = $currentSrc;

        /* return if already absolute URL */
        if (parse_url($currentSrc, PHP_URL_SCHEME) != '') {
            return $currentSrc;
        }

        if (strlen($currentSrc) > 0 && $currentSrc[0] === '/') {
            $newSrc = $this->getBaseUrl($url).$currentSrc;
        } else {
            if (preg_match("#https?://#", $currentSrc) === 0) { // relative link
                $newSrc = $url.'/'.$currentSrc;
            }
        }

        // replace // to /
        $newSrc = str_replace('//', '/', $newSrc);

        return $newSrc;
    }

    protected function getRealLink($currentSrc, $url)
    {
        $newSrc = $currentSrc;

        // -- Handle specific href.

        $prefixesStartWithToIgnore = [
            "tel:",
            "mailto:",
            "ftp:",
            "#",
        ];

        foreach ($prefixesStartWithToIgnore as $prefixToIgnore) {

            if (StringUtils::startsWith($currentSrc, $prefixToIgnore)) {

                // The url just contains the prefix. It's not a valid url, so we remove it.
                if (strlen($currentSrc) === strlen($prefixToIgnore)) {
                    // cf http://www.ietf.org/rfc/rfc2396.txt
                    // "A URI reference that does not contain a URI is a reference to the current document.
                    // In other words an empty URI reference within a document is interpreted as a reference
                    // to the start of that document"
                    return "";
                }

                return $currentSrc;
            }

        }

        // -- Handle page link (simple link)

        if (strlen($currentSrc) > 0 && $currentSrc[0] === '/') {
            $newSrc = $this->getBaseUrl($url).$currentSrc;
        } else {
            if (preg_match("#https?://#", $currentSrc) === 0
                && strlen($currentSrc) > 0
                && $currentSrc[0] != '#'
            ) { // relative link

                $newSrc = $url.'/'.$currentSrc;
            }
        }

        // replace // to /
        $newSrc = str_replace('//', '/', $newSrc);

        return $newSrc;
    }

    protected function handleAnchors($content)
    {
        $crawler = new Crawler($content);

        $crawler->filter('h1, h2, h3, h4, h5')->each(
            function ($titleNode) {

                $currentId = $titleNode->getNode(0)->getAttribute('id');
                $title = $titleNode->getNode(0)->nodeValue;

                if (strlen($currentId) == 0) {
                    $titleAsId = preg_replace("/[^A-Za-z0-9]/", '', $title); // keep just alphanumeric chars
                    $newId = $titleAsId.'_'.md5(uniqid(time().'_', true)); // generate unique identifier
                    $titleNode->getNode(0)->setAttribute('id', $newId);
                }

            }
        );

        try {
            return $crawler->html();
        } catch (\InvalidArgumentException $e) {

        }

        return $content;
    }
    
    public function findTagsOnText(Array $tags, $text)
    {
        // Contains the tag and the number of occurrences
        $tagsInfo = [];

        // -- remove html. Keep only the text.
        $crawler = new Crawler($text);
        $text = $crawler->text();

        foreach ($tags as $tag) {
            $nbOccurrences = $this->findNumberOfOccurrencesOfStringOnText($tag->getName(), $text);

            if ($nbOccurrences >= WebsiteCrawler::NB_OCCURRENCES_TO_SET_TAG) {
                $tagsInfo[] = [
                    'tag' => $tag,
                    'nbOccurrences' => $nbOccurrences,
                ];
            }

        }

        $found = [];

        // Sort the tags found by number of occurrences
        usort(
            $tagsInfo,
            function ($a, $b) {
                return $b['nbOccurrences'] - $a['nbOccurrences'];
            }
        );

        // Only took the 3 most relevant
        $tagsInfo = array_slice($tagsInfo, 0, 3);

        foreach ($tagsInfo as $tagInfo) {
            $found[] = $tagInfo['tag'];
        }

        return $found;
    }

    public function findNumberOfOccurrencesOfStringOnText($toFind, $text)
    {

        // disable case-sensitive
        $toFind = strtolower($toFind);
        $text = strtolower($text);

        return substr_count($text, $toFind);
    }

    public function findStringOnText($toFind, $text)
    {

        // disable case-sensitive
        $toFind = strtolower($toFind);
        $text = strtolower($text);

        if (strpos($text, $toFind) !== false) {
            return true;
        }

        return false;
    }

    public function getMetaPropertiesFromHtml($html)
    {
        $crawler = new Crawler($html);

        // -- Meta property

        $metaPropertyCrawler = $crawler->filter('head > meta')->reduce(
            function (Crawler $node) {
                $propertyValue = $node->attr('property');

                // We get all non null meta with a name or a property
                return null !== $propertyValue && null !== $node->attr('content');
            }
        );

        $metaProperties = [];
        foreach ($metaPropertyCrawler as $item) {
            $name = $item->getAttribute('property');
            $content = $item->getAttribute('content');

            $metaProperties[$name] = $content;
        }

        return $metaProperties;
    }

}
