<?php

namespace BookmarkManager\ApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Post;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\RestBundle\Controller\Annotations as Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use BookmarkManager\ApiBundle\Entity\User;
use BookmarkManager\ApiBundle\Entity\Circle;
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

        return $this->successResponse($user, Response::HTTP_OK, User::GROUP_ME);
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

        $user = $this->getRepository(User::REPOSITORY_NAME)->find($userId);

        if (!$user) {
            return $this->notFoundResponse();
        }

        return $this->successResponse($user, Response::HTTP_OK, User::GROUP_SINGLE);
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
        // TODO
        return $this->successResponse($this->getRepository(User::REPOSITORY_NAME)->findAll(), Response::HTTP_OK, User::GROUP_MULTIPLE);
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
        $user = $this->getUser();

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

        // -- create empty default book.
        $circle = new Circle();
        // name must be unique
        $circle->setName($user->getUsername() . '\'s circle');
        $circle->setDescription($user->getUsername() . '\'s circle');
        $circle->setOwner($user);
        $circle->setIsDefaultCircle(true);
        
        $this->persistEntity($circle);


        $user->addCircle($circle);
        $user->setDefaultCircleId($circle->getId());
        $this->persistEntity($user);

        return $this->successResponse($user, Response::HTTP_CREATED, User::GROUP_SINGLE);
    }

    /**
     * Update an user.
     *
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @ApiDoc(
     *  description="Create a user",
     *  parameters={
     *      {"name"="email",        "dataType"="string",    "required"=true },
     *      {"name"="password",     "dataType"="string",    "required"=true },
     *  },
     *  statusCodes={
     *      201="Returned when successful.",
     *      400="Returned when the parameters are invalid."
     *  }
     * )
     *
     * @ApiErrors({
     * })
     *
     * @param Request $request
     * @param $userId
     * @return Response
     */
    public function putUserAction(Request $request, $userId)
    {
        if (!is_numeric($userId)) {
            return $this->errorResponse(101, "The id must be numeric", Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getRepository(User::REPOSITORY_NAME)->find($userId);

        if (!$user) {
            return $this->notFoundResponse();
        }

        // see http://stackoverflow.com/questions/9183368/symfony2-user-setpassword-updates-password-as-plain-text-datafixtures-fos
        $userManager = $this->container->get('fos_user.user_manager');

        // -- email
        $email = $request->request->get('email');
        if (!empty($email)) {
            $user->setEmail($email);
        }

        // -- password
        $password = $request->request->get('password');
        if (!empty($password)) {
            $user->setPlainPassword($password);
        }

        $userManager->updateUser($user, true);

        return $this->successResponse($user, Response::HTTP_CREATED, User::GROUP_SINGLE);
    }
}