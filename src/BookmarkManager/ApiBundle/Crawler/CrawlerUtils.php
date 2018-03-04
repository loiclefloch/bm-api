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

            $options = array(
                CURLOPT_URL            => $url,
                CURLOPT_TIMEOUT        => $timeout,

                // handle 302
                CURLOPT_FOLLOWLOCATION => true,

                CURLOPT_RETURNTRANSFER => true,

                CURLOPT_HEADER         => false,

                CURLOPT_ENCODING       => "gzip, deflate",
                CURLOPT_AUTOREFERER    => true,
                CURLOPT_CONNECTTIMEOUT => 120,
                CURLOPT_MAXREDIRS      => 10,

                CURL_HTTP_VERSION_1_1 => true,

                CURLOPT_USERAGENT     => $userAgent,

                //
                // One of:
                // CURL_SSLVERSION_DEFAULT (0), 
                // CURL_SSLVERSION_TLSv1 (1), 
                // CURL_SSLVERSION_SSLv2 (2), 
                // CURL_SSLVERSION_SSLv3 (3), 
                // CURL_SSLVERSION_TLSv1_0 (4), 
                // CURL_SSLVERSION_TLSv1_1 (5) 
                // or CURL_SSLVERSION_TLSv1_2 (6).
                // Note: Setting it to 2 or 3 is very dangerous given the known vulnerabilities in SSLv2 and SSLv3.
                // Tried to change value here to fix the `alert handshake failure` bug, didn't worked.
                // CURLOPT_SSLVERSION    => 0
            );

            curl_setopt_array($curl, $options);

            $data = curl_exec($curl);

            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $httpCodeOK = isset($httpCode) && ($httpCode >= 200 && $httpCode <= 301);

            // TODO: errors to handle:

            // -  'error:14077410:SSL routines:SSL23_GET_SERVER_HELLO:sslv3 alert handshake failure', due to ssl with curl

            // uncomment to debug
            // TODO: add curl_error results to the CrawlerRetrieveDataException thrown exception
            // print curl_error($curl);
            // die();

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
            if ($httpCode == 404) {
                throw new CrawlerNotFoundException("404 error", $httpCode);
            }
            throw new CrawlerRetrieveDataException("Could not retrieve content", $httpCode);
        }
    }

}