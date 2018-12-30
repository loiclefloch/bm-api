<?php

namespace BookmarkManager\ApiBundle\Utils;


class ArrayUtils
{

    /**
     * Rename the given index on the given array
     * @param $newIndexes
     * @param $data
     * @return array
     */
    public static function renameArrayIndexes($newIndexes, $data)
    {
        $final = array();
        foreach ($data as $name => $value) {
            if (isset($newIndexes[$name])) {
                $name = $newIndexes[$name];
            }
            $final[$name] = $value;
        }

        return ($final);
    }

    /**
     * Return an array with the errors of the form
     * @param $form
     * @return array
     */
    public static function formErrorsToArray($form)
    {
        $errors = array();
        foreach ($form->getErrors() as $error) {
            $message = $error->getMessage();
            if ($message != null) {
                $errors[] = $message.(substr($message, -1) != '.' ? '.' : null);
            }
        }
        foreach ($form->all() as $key => $child) {
            if (($err = ArrayUtils::formErrorsToArray($child))) {
                $errors[$key] = $err;
            }
        }

        return ($errors);
    }
}