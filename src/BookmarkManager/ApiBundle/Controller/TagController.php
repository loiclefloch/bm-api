<?php

namespace BookmarkManager\ApiBundle\Controller;

use BookmarkManager\ApiBundle\Entity\Tag;
use BookmarkManager\ApiBundle\Exception\BMErrorResponseException;
use BookmarkManager\ApiBundle\Form\TagType;
use BookmarkManager\ApiBundle\Utils\TagUtils;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use BookmarkManager\ApiBundle\DependencyInjection\BaseController;
use BookmarkManager\ApiBundle\Annotation\ApiErrors;
use Symfony\Component\HttpFoundation\Response;

class TagController extends BaseController
{

    /**
     * Return all Tag entities.
     *
     * @ApiDoc(
     *     description="Get all the user tag"
     * )
     *
     * @return mixed
     */
    public function getTagsAction()
    {
        return [
            'tags' => $this->getUser()->getTags(),
        ];
    }


    /**
     * Creates a new Tag entity.
     *
     * @ApiDoc(
     *  description="Create a new tag",
     *  parameters={
     *      {"name"="name",        "dataType"="string",    "required"=true, "description"="The tag name. Must be unique." },
     *      {"name"="color",     "dataType"="string",    "required"=true, "description"="The tag color. Default is #cecece" }
     *  },
     *  statusCodes={
     *      201="Returned when successful.",
     *      400="Returned when the parameters are invalid."
     *  }
     * )
     *
     * @ApiErrors({
     *      { 400, "The validation of the object failed. Check the message for more details." },
     *      { 101, "Tag already exists" }
     * })
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postTagAction(Request $request)
    {
        $data = $request->request->all();

        $tagEntity = TagUtils::createTag($this, $data);

        return $this->successResponse($tagEntity, Response::HTTP_CREATED);
    }

    /**
     * Finds and returns a Tag entity.
     *
     * @ApiDoc(
     *  description="Get tag's information",
     *  requirements={
     *      {
     *          "name"="tagId",
     *          "dataType"="integer",
     *          "requirement"="[\d]+",
     *          "description"="Tag id"
     *      }
     *  },
     *  statusCodes={
     *      200="Returned when successful.",
     *      404="Returned when the tag is not found.",
     *      400="Returned when the parameter is invalid.",
     *  }
     * )
     *
     * @ApiErrors({
     *      { 101, "The tag id must be numeric" },
     * })
     *
     * @param $tagId
     * @return object|Response
     *
     */
    public function getTagAction($tagId)
    {
        if (!is_numeric($tagId)) {
            return $this->errorResponse(101, "The id must be numeric", Response::HTTP_BAD_REQUEST);
        }

        $tagEntity = $this->getRepository('Tag')->findOneBy(
            [
                'id' => $tagId,
                'owner' => $this->getUser(),
            ]
        );

        if (!$tagEntity) {
            return $this->notFoundResponse();
        }

        return $this->successResponse($tagEntity, Response::HTTP_OK);
    }

    /**
     * Update an existing Tag entity.
     *
     * @ApiDoc(
     *  description="Update tag's information",
     *  requirements={
     *      {
     *          "name"="tagId",
     *          "dataType"="integer",
     *          "requirement"="[\d]+",
     *          "description"="Tag id"
     *      }
     *  },
     *  statusCodes={
     *      200="Returned when successful.",
     *      404="Returned when the tag is not found.",
     *      400="Returned when the parameter is invalid.",
     *  }
     * )
     *
     * @ApiErrors({
     *      { 101, "The tag id must be numeric" },
     * })
     *
     * @param Request $request
     * @param $tagId
     * @return Response
     */
    public function putTagAction(Request $request, $tagId)
    {
        $data = $request->request->all();

        if (!is_numeric($tagId)) {
            return $this->errorResponse(101, "The id must be numeric", Response::HTTP_BAD_REQUEST);
        }

        $tagEntity = $this->getRepository('Tag')->findOneBy(
            [
                'id' => $tagId,
                'owner' => $this->getUser(),
            ]
        );

        if (!$tagEntity) {
            return $this->notFoundResponse();
        }

        $editForm = $this->createForm(
            new TagType(),
            $tagEntity,
            [
                'ignoreRequired' => true,
            ]
        );

        $editForm->submit($data);

        if ($editForm->isValid()) {
            $this->persistEntity($tagEntity);

            return $this->successResponse($tagEntity, Response::HTTP_OK);
        }

        return $this->errorResponse(
            400,
            $this->formErrorsToArray($editForm),
            Response::HTTP_BAD_REQUEST
        );

    }

    /**
     * Deletes a Tag entity.
     *
     * @ApiDoc(
     *  description="Deletes tag's information",
     *  requirements={
     *      {
     *          "name"="tagId",
     *          "dataType"="integer",
     *          "requirement"="[\d]+",
     *          "description"="Tag id"
     *      }
     *  },
     *  statusCodes={
     *      200="Returned when successful.",
     *      404="Returned when the tag is not found.",
     *      400="Returned when the parameter is invalid.",
     *  }
     * )
     *
     * @ApiErrors({
     *      { 101, "The tag id must be numeric" },
     * })
     * @param $tagId
     * @return Response
     * @internal param Request $request
     */
    public function deleteTagAction($tagId)
    {
        if (!is_numeric($tagId)) {
            return $this->errorResponse(101, "The id must be numeric", Response::HTTP_BAD_REQUEST);
        }

        $tagEntity = $this->getRepository('Tag')->findOneBy(
            [
                'id' => $tagId,
                'owner' => $this->getUser(),
            ]
        );

        if (!$tagEntity) {
            return $this->notFoundResponse();
        }

        $this->removeEntity($tagEntity);

        return $this->successResponse([], Response::HTTP_NO_CONTENT);
    }

}