<?php

namespace BookmarkManager\ApiBundle\Crawler;

use Symfony\Component\Config\Definition\Exception\Exception;

class CrawlerUtils
{

    /**
     * Convert image to base64 string
     * @param $absolute_path
     * @param $fullPath
     * @return string
     */
    public static function picturesToBase64($absolute_path, $fullPath)
    {
        $rawData = CrawlerUtils::getExternalFile($absolute_path, 15);
        $type = pathinfo($fullPath, PATHINFO_EXTENSION);

        return 'data:image/'.$type.';base64,'.base64_encode($rawData);
    }

    /**
     * function define to retrieve url content
     * @param $url
     * @param $timeout
     * @return mixed
     * @throws CrawlerNotFoundException
     * @throws CrawlerRetrieveDataException
     */
    public static function getExternalFile($url, $timeout)
    {
        // spoofing FireFox 18.0
        $userAgent = "Mozilla/5.0 (Windows NT 5.1; rv:18.0) Gecko/20100101 Firefox/18.0";

        if (in_array('curl', get_loaded_extensions())) {
            // Fetch feed from URL
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);

            // handle 302
    		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, false);
            // FeedBurner requires a proper USER-AGENT...
            curl_setopt($curl, CURL_HTTP_VERSION_1_1, true);
            curl_setopt($curl, CURLOPT_ENCODING, "gzip, deflate");
            curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
            $data = curl_exec($curl);

            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $httpCodeOK = isset($httpCode) && ($httpCode == 200 || $httpCode == 301);

            curl_close($curl);
        }
        else {
            throw new Exception("Curl must be installed");
        }

        // if response is not empty && response is OK
        if (isset($data) && isset($httpCodeOK) && $httpCodeOK) {
            // take charset of page && get it
            preg_match('#<meta .*charset=.*>#Usi', $data, $meta);
            // if meta tag is found
            if (!empty($meta[0])) {
                // retrieve encoding in $enc
                preg_match('#charset="?(.*)"#si', $meta[0], $enc);
                // if charset is found set it otherwise, set it to utf-8
                $html_charset = (!empty($enc[1])) ? strtolower($enc[1]) : 'utf-8';
            } else {
                $html_charset = 'utf-8';
                $enc[1] = '';
            }
            // replace charset of url to charset of page
            if (count($enc) >= 1) {
                $data = str_replace('charset='.$enc[1], 'charset='.$html_charset, $data);
            }

            return $data;
        } else {
            if ($httpCode == 404 || $httpCode == 0) {
                throw new CrawlerNotFoundException("404 error", $httpCode);
            }
            throw new CrawlerRetrieveDataException("Could not retrieve content", $httpCode);
        }
    }

}