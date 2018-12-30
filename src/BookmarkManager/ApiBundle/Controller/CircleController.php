<?php

namespace BookmarkManager\ApiBundle\Controller;

use BookmarkManager\ApiBundle\Annotation\ApiErrors;
use BookmarkManager\ApiBundle\DependencyInjection\BaseController;
use BookmarkManager\ApiBundle\Entity\User;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use BookmarkManager\ApiBundle\Form\CircleType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\FormHelper;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use BookmarkManager\ApiBundle\Entity\Circle;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;

/**
 * Circle controller.
 *
 * @Route("/circles")
 */
class CircleController extends BaseController
{

    /**
     * Lists all Circle entities.
     *
     * @ApiDoc(
     *     description="Get all the circles",
     *     statusCodes={
     *     }
     * )
     *
     */
    public function getCirclesAction()
    {
        $user = $this->getUser();

        $circles = $this->getRepository(Circle::REPOSITORY_NAME)->findAllPublicCircles();

        // TODO: remove once the front handle own circle
        $userCircle = $this->getRepository(Circle::REPOSITORY_NAME)->findUserOwnCircle($user);

        // TODO: paging

        return $this->successResponse(
            [
                'circles' => array_merge($circles, [$userCircle]),
            ],
            Response::HTTP_OK,
            Circle::GROUP_MULTIPLE
        );
    }

    /**
     * Lists all Circle entities for the current user.
     *
     * @Rest\Get("/users/me/circles/mine")
     *
     * @ApiDoc(
     *     description="Get the user's default circle",
     *     statusCodes={
     *     }
     * )
     *
     */
    public function getMeCirclesMineAction()
    {
        $user = $this->getUser();
        $userDefaultCircle = $this->getRepository(Circle::REPOSITORY_NAME)->find($user->getDefaultCircleId());

        return $this->successResponse(
            $userDefaultCircle,
            Response::HTTP_OK,
            Circle::GROUP_SINGLE
        );
    }

    /**
     * Lists all Circle entities for the current user.
     *
     * @Rest\Get("/users/me/circles")
     *
     * @ApiDoc(
     *     description="Get all the circles",
     *     statusCodes={
     *     }
     * )
     *
     */
    public function getMeCirclesAction()
    {
        return $this->getUserCirclesAction($this->getUser()->getId());
    }

    /**
     * Lists all Circle entities for the given user.
     *
     * @Rest\Get("/users/{userId}/circles")
     *
     * @ApiDoc(
     *     description="Get all the circles",
     *     statusCodes={
     *      403="User has not ROLE_ADMIN"
     *     }
     * )
     * @ApiErrors({
     *      { 404, "User not found." },
     *      { 101, "Invalid {userId}" }
     * })
     *
     * @param $userId
     * @return Response
     */
    public function getUserCirclesAction($userId)
    {
        if (!is_numeric($userId)) {
            return $this->errorResponse(101, 'invalid user id');
        }

        $user = $this->getRepository(User::REPOSITORY_NAME)->find($userId);

        if (!$user) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(
            ['circles' => $user->getCircles()],
            Response::HTTP_OK,
            Circle::GROUP_MULTIPLE
        );
    }

    /**
     * Creates a new Circle entity.
     *
     * @ApiDoc(
     *  description="Create a new circle",
     *  parameters={
     *      {"name"="name",        "dataType"="string",    "required"=true, "description"="The circle name. Must be unique." },
     *      {"name"="members",     "dataType"="[User]",    "required"=true, "description"="The members of the circle. Can be empty." }
     *  },
     *  statusCodes={
     *      201="Returned when successful.",
     *      400="Returned when the parameters are invalid.",
     *      403="User has not ROLE_ADMIN"
     *  }
     * )
     *
     * @ApiErrors({
     *      { 400, "The validation of the object failed. Check the message for more details." },
     *      { 101, "Circle already exists" }
     * })
     *
     * @param Request $request
     * @return Response
     */
    public function postCircleAction(Request $request)
    {
        $data = $request->request->all();
        $circleEntity = new Circle();

        $user = $this->getUser();

        $form = $this->createForm(
            new CircleType(),
            $circleEntity,
            ['method' => 'POST']
        );

        $form->submit($data, false);

        if ($form->isValid()) {

            // Search if circle already exists.
            $exists = $this->getRepository(Circle::REPOSITORY_NAME)->findOneBy(
                [
                    'name' => $form->getData()->getName(),
                ]
            );

            if ($exists) {
                return $this->errorResponse(101, "Circle already exists");
            }

            $circleEntity->setOwner($this->getUser());
            $circleEntity->addAdmin($this->getUser());

            $this->persistEntity($circleEntity);

            $user->addCircleToAdmin($circleEntity);
            $this->persistEntity($user);

            return $this->successResponse(
                $circleEntity,
                Response::HTTP_CREATED,
                Circle::GROUP_SINGLE
            );
        }

        return $this->errorResponse(
            400,
            $this->formErrorsToArray($form),
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * Finds and returns a Circle entity.
     *
     * @ApiDoc(
     *  description="Get circle's information",
     *  requirements={
     *      {
     *          "name"="id",
     *          "dataType"="integer",
     *          "requirement"="[\d]+",
     *          "description"="Circle id"
     *      }
     *  },
     *  statusCodes={
     *      200="Returned when successful.",
     *      404="Returned when the circle is not found.",
     *      400="Returned when the parameter is invalid.",
     *  }
     * )
     *
     * @ApiErrors({
     *      { 101, "The circle id must be numeric" },
     * })
     *
     * @param $id
     * @return object|Response
     *
     */
    public function getCircleAction($id)
    {
        if (!is_numeric($id)) {
            return $this->errorResponse(101, "The id must be numeric", Response::HTTP_BAD_REQUEST);
        }

        $circleEntity = $this->getRepository(Circle::REPOSITORY_NAME)->find($id);

        if (!$circleEntity) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(
            $circleEntity,
            Response::HTTP_OK,
            Circle::GROUP_SINGLE
        );
    }

    /**
     * Update an existing Circle entity.
     *
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @ApiDoc(
     *  description="Update circle's information",
     *  statusCodes={
     *      200="Returned when successful.",
     *      404="Returned when the circle is not found.",
     *      400="Returned when the parameter is invalid.",
     *      403="User has not ROLE_ADMIN"
     *  }
     * )
     *
     * @ApiErrors({
     *      { 101, "The circle id must be numeric" },
     * })
     *
     * @param Request $request
     * @param $id
     * @return Response
     */
    public function putCircleAction(Request $request, $id)
    {
        $data = $request->request->all();

        if (!is_numeric($id)) {
            return $this->errorResponse(101, "The id must be numeric", Response::HTTP_BAD_REQUEST);
        }

        $circleEntity = $this->getRepository(Circle::REPOSITORY_NAME)->find($id);

        if (!$circleEntity) {
            return $this->notFoundResponse();
        }

        $editForm = $this->createForm(
            new CircleType(),
            $circleEntity,
            [
                'ignoreRequired' => true,
            ]
        );

        // TODO: do not allow adding members to solo circle 
        // TODO: do not allow to remove owner from admins

        $editForm = $this->bindFormForPut($request, $editForm);

        if ($editForm->isValid()) {
            $this->persistEntity($circleEntity);

            return $this->successResponse($circleEntity, Response::HTTP_OK);
        }

        return $this->errorResponse(
            400,
            $this->formErrorsToArray($editForm),
            Response::HTTP_BAD_REQUEST
        );

    }

    /**
     * Deletes a Circle entity.
     *
     * TODO: Security
     *
     * @ApiDoc(
     *  description="Deletes circle's information",
     *  statusCodes={
     *      200="Returned when successful.",
     *      404="Returned when the circle is not found.",
     *      400="Returned when the parameter is invalid.",
     *      403="User has not ROLE_ADMIN"
     *  }
     * )
     *
     * @ApiErrors({
     *      { 101, "The circle id must be numeric" },
     * })
     * @param Request $request
     * @param $id
     * @return Response
     */
    public function deleteCircleAction(Request $request, $id)
    {
        if (!is_numeric($id)) {
            return $this->errorResponse(101, "The id must be numeric", Response::HTTP_BAD_REQUEST);
        }

        $circleEntity = $this->getRepository(Circle::REPOSITORY_NAME)->find($id);

        if (!$circleEntity) {
            return $this->notFoundResponse();
        }

        $this->removeEntity($circleEntity);

        return $this->successResponse([], Response::HTTP_NO_CONTENT);
    }

    // ---------------------------------------------------------------------------------------------------------------
    //    /circles/{id}/members
    // ---------------------------------------------------------------------------------------------------------------


    /**
     * Update an existing Circle by adding a member.
     *
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @ApiDoc(
     *  description="Add users to circle's members",
     *  requirements={
     *      {
     *          "name"="circleId",
     *          "dataType"="integer",
     *          "requirement"="[\d]+",
     *          "description"="User id"
     *      }
     *  },
     *  statusCodes={
     *      200="Returned when successful.",
     *      404="Returned when the circle or the user is not found.",
     *      400="Returned when the parameter is invalid.",
     *      403="User has not ROLE_ADMIN"
     *  }
     * )
     *
     * @ApiErrors({
     *      { 101, "The circle id must be numeric" },
     *      { 102, "The user id must be numeric" }
     * })
     *
     * @param Request $request
     * @param $circleId
     * @param $userId
     * @return Response
     */
    public function postCircleMemberAction(Request $request, $circleId)
    {
        if (!is_numeric($circleId)) {
            return $this->errorResponse(101, "The id must be numeric", Response::HTTP_BAD_REQUEST);
        }

        //
        // {
        //     members: [
        //         {
        //             id: 1,
        //             later: role ?
        //         }
        //     ]
        // }
        // 
        $data = $request->request->all();

        // verify data
        $circleEntity = $this->getRepository(Circle::REPOSITORY_NAME)->findOneById($circleId);

        if (!$circleEntity) {
            return $this->notFoundResponse();
        }

        foreach ($data['members'] as $user) {
            if (!is_numeric($user['id'])) {
                return $this->errorResponse(102, "The id must be numeric", Response::HTTP_BAD_REQUEST);
            }
        }

        foreach ($data['members'] as $user) {
            $userEntity = $this->getRepository(User::REPOSITORY_NAME)->findOneById($user['id']);
            if ($userEntity) {
                if (!$circleEntity->haveMember($userEntity)) {
                    $circleEntity->addMember($userEntity);
                    $this->persistEntity($circleEntity);
                }
            }
        }

        return $this->successResponse($circleEntity);
    }

    /**
     * Update an existing Circle by removing a member.
     *
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @ApiDoc(
     *  description="Remove member from circle",
     *  requirements={
     *      {
     *          "name"="circleId",
     *          "dataType"="integer",
     *          "requirement"="[\d]+",
     *          "description"="User id"
     *      },
     *     {
     *          "name"="userId",
     *          "dataType"="integer",
     *          "requirement"="[\d]+",
     *          "description"="User id"
     *      }
     *  },
     *  statusCodes={
     *      200="Returned when successful.",
     *      404="Returned when the circle  or the user is not found.",
     *      400="Returned when the parameter is invalid.",
     *      403="User has not ROLE_ADMIN"
     *  }
     * )
     *
     * @ApiErrors({
     *      { 101, "The circle id must be numeric" },
     *      { 102, "The user id must be numeric" }
     * })
     *
     * @param Request $request
     * @param $circleId
     * @param $userId
     * @return Response
     */
    public function deleteCircleMemberAction(Request $request, $circleId, $userId)
    {
        if (!is_numeric($circleId)) {
            return $this->errorResponse(101, "The id must be numeric", Response::HTTP_BAD_REQUEST);
        }

        if (!is_numeric($userId)) {
            return $this->errorResponse(102, "The id must be numeric", Response::HTTP_BAD_REQUEST);
        }

        $circleEntity = $this->getRepository(Circle::REPOSITORY_NAME)->findOneById($circleId);
        $userEntity = $this->getRepository(User::REPOSITORY_NAME)->findOneById($userId);

        if (!$circleEntity || !$userEntity) {
            return $this->notFoundResponse();
        }

        if ($circleEntity->isDefaultCircle()) {
            return $this->notFoundResponse();
        }

        if ($circleEntity->haveMember($userEntity)) {
            $circleEntity->removeMember($userEntity);
            $this->persistEntity($circleEntity);
        }

        return $this->successResponse($circleEntity);
    }

}
