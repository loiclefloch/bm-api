<?php

namespace BookmarkManager\ApiBundle\Utils;

use BookmarkManager\ApiBundle\Entity\Tag;
use BookmarkManager\ApiBundle\Exception\BMErrorResponseException;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

class RequestUtils
{

    /**
     * The JSON PUT data will include all attributes in the entity, even those that are not update by the user and are
     * not in the form.
     * We need to remove these extra fields or we will get a "This form should not contain extra fields" Form Error.
     * @param $data
     * @param Form $form
     * @return Form
     * @internal param Request $request
     */
    public static function bindDataToForm($data, Form $form)
    {
        $children = $form->all();
        $data = array_intersect_key($data, $children);
        $form->submit($data);

        return $form;
    }

}