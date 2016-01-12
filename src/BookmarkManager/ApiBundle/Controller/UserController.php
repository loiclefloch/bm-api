<?php

namespace BookmarkManager\ApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Post;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\RestBundle\Controller\Annotations as Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use BookmarkManager\ApiBundle\Entity\User;
use BookmarkManager\ApiBundle\Annotation\ApiErrors;
use BookmarkManager\ApiBundle\DependencyInjection\BaseController;
use BookmarkManager\ApiBundle\Form\UserRegistration;

/**
 * Class UserController
 * @package BookmarkManager\ApiBundle\Controller
 */
class UserController extends BaseController
{
    /**
     * @ApiDoc(
     *  description="Get the user's information"
     * )
     * @return Response
     */
    public function getUsersMeAction()
    {

        $user = $this->getUser();

        if ($user == null) {
            return $this->errorResponse(101, "User is anonymous", Response::HTTP_UNAUTHORIZED);
        }

        return $this->successResponse($user, Response::HTTP_OK);
    }

    /**
     *
     * Get user with the user id `id`.
     *
     * @ApiDoc(
     *  description="Get user's information",
     *  requirements={
     *      {
     *          "name"="userId",
     *          "dataType"="integer",
     *          "requirement"="[\d]+",
     *          "description"="User id"
     *      }
     *  },
     *  statusCodes={
     *      200="Returned when successful.",
     *      404="Returned when the user is not found.",
     *      400="Returned when the parameter is invalid.",
     *  }
     * )
     *
     * @ApiErrors({
     *      { 101, "The user id must be numeric" },
     * })
     *
     * @param $userId
     * @return object|Response
     */
    public function getUserAction($userId)
    {

        if (!is_numeric($userId)) {
            return $this->errorResponse(101, "The id must be numeric", Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getRepository("User")->find($userId);

        if (!$user) {
            return $this->notFoundResponse();
        }

        return $this->successResponse($user, Response::HTTP_OK);
    }

    /**
     * Returns all the users
     *
     * @ApiDoc(
     *  description="Get the user list"
     * )
     */
    public function getUsersAction()
    {
        return $this->getRepository('User')->findAll();
    }

    /**
     * Create a new user.
     *
     * @ApiDoc(
     *  description="Create a user",
     *  parameters={
     *      {"name"="email",        "dataType"="string",    "required"=true },
     *      {"name"="password",     "dataType"="string",    "required"=true },
     *      {"name"="first_name",   "dataType"="string",    "required"=true },
     *      {"name"="last_name",    "dataType"="string",    "required"=true },
     *      {"name"="avatar",       "dataType"="string",    "required"=false, "description"="The path of the file to upload as avatar"}
     *  },
     *  statusCodes={
     *      201="Returned when successful.",
     *      400="Returned when the parameters are invalid."
     *  }
     * )
     *
     * @ApiErrors({
     *      { 400, "The validation of the object failed. Check the message for more details." },
     *      { 31060, "User already exists with this email" }
     * })
     *
     * @param Request $request
     * @return Response
     */
    public function postUserAction(Request $request)
    {

//        var_dump($request->getContent());
        //$debug = $request->get('email');

        $EMAIL_ALREADY_TAKEN_CODE = 31060;

        $registration = $this->deserialize('BookmarkManager\ApiBundle\Form\UserRegistration', $request);

        if ($registration instanceof UserRegistration === false) {
            return $this->errorResponse(
                0,
                array('errors' => $registration, /*'debug' => $debug*/),
                Response::HTTP_BAD_REQUEST
            );
        }

        $user = $registration->getUser();
        $userManager = $this->get('fos_user.user_manager');

        $user->setLastActivity(new \DateTime());

        // TODO: verify username and email
//        $exists = $userManager->findUserBy(array('email' => $user->getEmail()));

//        if ($exists instanceof User) {
//            return $this->errorResponse($EMAIL_ALREADY_TAKEN_CODE, 'Email already used', Response::HTTP_CONFLICT);
//        }

        $userManager->updateUser($user);

        $error = $this->getEntityErrors($user);
        if (count($error) > 0) {
            return $this->errorResponse(
                400,
                $error,
                Response::HTTP_BAD_REQUEST
            );

        }

        return $this->successResponse($user, Response::HTTP_CREATED);
    }

}