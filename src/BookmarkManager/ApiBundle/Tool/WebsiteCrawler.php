<?php

namespace BookmarkManager\ApiBundle\Tool;

use BookmarkManager\ApiBundle\Entity\Bookmark;
use BookmarkManager\ApiBundle\Entity\BookmarkType;
use Exception;
use Symfony\Component\DomCrawler\Crawler;

/**
 */
class WebsiteCrawler
{

    public function crawlWebsite(Bookmark $bookmark)
    {
        // TODO: Handle 404.
        $html = $this->get_data($bookmark->getUrl());

        if ($html) {

            $crawler = new Crawler($html);

            try {
                $title = trim($crawler->filter('head > title')->text());
            }
            catch (Exception $e) {
                $title = 'Unknown title';
            }
//            $description = $crawler->filter('meta[name="description"]');
//            $ogTitle = $crawler->filter('meta[property="title"]');
//            $ogType = null;
//            $ogImage = null;

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

            $ogData = $this->handleOg($html);

            // -- Select the best description
            if ($websiteInfo['description'] === null or strlen($websiteInfo['description']) === 0) {
                $description = $ogData['data']['og:description'];
            } else {
                $description = $websiteInfo['description'];
            }

            // -- Retrieve the bookmark type
            $types = [
                'website' => BookmarkType::TYPE_WEBSITE,
                'article' => BookmarkType::TYPE_ARTICLE,
                'video' => BookmarkType::TYPE_VIDEO,
                'music' => BookmarkType::TYPE_MUSIC,
            ];

            if (isset($ogData['data']['og:type'])
                && $ogData['data']['og:type'] != null
                && isset($types[$ogData['data']['og:type']])
            ) {
                $bookmark->setType($types[$ogData['data']['og:type']]);
            } else {
                $bookmark->setType(BookmarkType::TYPE_WEBSITE);
            }

//            var_dump($websiteInfo);

            // TODO: Add $websiteInfo to a new entity.

            // TODO: Add bookmark type: ARTICLE, VIDEO, IMAGE, MUSIC cf http://ogp.me/

            $bookmark->setTitle($title);
            $bookmark->setDescription($description);
        }

        // TODO: add https://github.com/j0k3r/php-readability

        /*
         * TODO: add custom crawler according to the website.
         * For example, github.com we need to retrieve the README.md if it's
         * the main page of a project.
         * If the url end with .md, we need to get the content on
         * the article.entry-content
         * Github have specific anchor: href="#a" link to <a name="user-content-a">
         */

        // TODO: remove scripts and html from content
        // TODO: Add good urls to img and links
        // TODO: anchors: add prefix to all the id

        return $bookmark;
    }

    public function handleOg($html)
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

        // -- Handle basic info

        $websiteInfo = [
            'og:title' => null,
            'og:type' => 'website', // Any non-marked up webpage should be treated as og:type website.
            'og:image' => null,
            'og:description' => null,
        ];

        // -- Handle basic og information See http://ogp.me/
        $websiteInfo['og:title'] = $this->array_get_key('og:title', $metaProperties);
        $websiteInfo['og:image'] = $this->array_get_key('og:image', $metaProperties);

        $websiteInfo['og:description'] = $this->array_get_key('og:description', $metaProperties);

        $ogType = $this->array_get_key('og:type', $metaProperties);
        if (null != $ogType) { // do not override default value ('website')
            $websiteInfo['og:type'] = $ogType;
        }

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
        if (isset($websiteInfo['og:type']) && isset($keysOgByType[$websiteInfo['og:type']])) {
            foreach ($keysOgByType[$websiteInfo['og:type']] as $value) {
                $detail[$value] = $this->array_get_key($value, $metaProperties);
            }
        }

        return [
            'data' => $websiteInfo,
            'detail' => $detail,
        ];
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
            $url = 'http://'.$url;
        }

        $QUERIES_TO_REMOVE = [
            'utm_source',
            'utm_medium',
            'utm_term',
            'utm_content',
            'utm_campaign',
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

    /**
     * Returns the value at the given key or null. If the value exists, the value is trim.
     * @param $key
     * @param $array
     * @return null|string
     */
    protected function array_get_key($key, $array)
    {
        return isset($array[$key]) ? trim($array[$key]) : null;
    }

    protected function get_data($url)
    {
        $ch = curl_init();
        $timeout = 15;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }


}
