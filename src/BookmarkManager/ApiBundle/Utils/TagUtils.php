<?php
/**
 * Created by PhpStorm.
 * User: loic
 * Date: 20/01/16
 * Time: 12:51
 */

namespace BookmarkManager\ApiBundle\Utils;

use BookmarkManager\ApiBundle\Entity\Tag;
use BookmarkManager\ApiBundle\Exception\BmAlreadyExistsException;
use BookmarkManager\ApiBundle\Exception\BmErrorResponseException;
use BookmarkManager\ApiBundle\Form\TagType;
use Symfony\Component\HttpFoundation\Response;

class TagUtils
{

    /**
     *
     * ApiErrorCode:
     * - 101: Tag already exists
     * - 400: Form error
     *
     * @param $controller
     * @param $data
     * @return Tag
     * @throws BmErrorResponseException
     */
    public static function createTag($controller, $data)
    {
        $tagEntity = new Tag();

        $form = $controller->createForm(
            new TagType(),
            $tagEntity,
            ['method' => 'POST']
        );

        $form = RequestUtils::bindDataToForm($data, $form);

        if ($form->isValid()) {

            // Search if tag already exists.
            $exists = $controller->getRepository('Tag')->findOneBy(
                [
                    'name' => $tagEntity->getName(),
                    'owner' => $controller->getUser()->getId(),
                ]
            );

            $tagEntity->setOwner($controller->getUser());

            if ($exists) {
                throw new BmAlreadyExistsException(101, "Tag already exists");
            }

            $controller->persistEntity($tagEntity);
            return $tagEntity;
        }

        throw new BmErrorResponseException(400, ArrayUtils::formErrorsToArray($form),  Response::HTTP_BAD_REQUEST);
    }

}