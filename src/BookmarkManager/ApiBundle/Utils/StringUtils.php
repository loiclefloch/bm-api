<?php
/**
 * Created by PhpStorm.
 * User: loic
 * Date: 26/02/16
 * Time: 16:12
 */

namespace BookmarkManager\ApiBundle\Utils;


class StringUtils
{
    public static function startsWith($haystack, $needle) {
        // search backwards starting from haystack length characters from the end
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }

    public static function endsWith($haystack, $needle) {
        // search forward starting from end minus needle length characters
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
    }

    /**
     * Replace all specials characters by spaces.
     * @param $str
     * @return string
     */
    public static function stringBeautifier($str)
    {
        return preg_replace('/[^a-zA-Z0-9]+/', ' ', $str);
    }

}