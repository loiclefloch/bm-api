<?php

namespace BookmarkManager\ApiBundle\Utils;


class UrlUtils
{

    public static function getDomainNameWithoutSubDomains($url)
    {
        $parts = parse_url($url);

        $domain = $parts['host'];

        if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {
            return $regs['domain'];
        }

        return $domain;
    }

    public static function getFileName($url)
    {
        $pathInfo = pathinfo($url);
        $extension = '.'.$pathInfo['extension'];

        return basename($url, $extension);
    }

}