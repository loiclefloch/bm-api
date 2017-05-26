<?php

namespace BookmarkManager\ApiBundle\Controller;

use BookmarkManager\ApiBundle\Annotation\ApiErrors;
use BookmarkManager\ApiBundle\DependencyInjection\BaseController;
use BookmarkManager\ApiBundle\Entity\User;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use BookmarkManager\ApiBundle\Form\BookType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\FormHelper;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use BookmarkManager\ApiBundle\Entity\Book;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;

/**
 * Book controller.
 *
 * @Route("/book")
 */
class BookController extends BaseController {

    /**
     * Lists all Book entities.
     *
     * @ApiDoc(
     *     description="Get all the books",
     *     statusCodes={
     *     }
     * )
     *
     */
    public function getBooksAction()
    {
        $user = $this->getUser();

        $books = $this->getRepository(Book::REPOSITORY_NAME)->findBy(
          [
              'owner' => $user
          ]
        );

        // TODO: paging

        return $this->successResponse(
            [
                'books' => $books
            ],
            Response::HTTP_OK,
            Book::GROUP_MULTIPLE
        );
    }

    /**
     * Lists all Book entities for the current user.
     *
     * @Rest\Get("/users/me/books")
     *
     * @ApiDoc(
     *     description="Get all the books",
     *     statusCodes={
     *     }
     * )
     *
     */
    public function getMeBooksAction()
    {
        return $this->getUserBooksAction($this->getUser()->getId());
    }

    /**
     * Lists all Book entities for the given user.
     *
     * @Rest\Get("/users/{userId}/books")
     *
     * @ApiDoc(
     *     description="Get all the books",
     *     statusCodes={
     *      403="User has not ROLE_ADMIN"
     *     }
     * )
     *  @ApiErrors({
     *      { 404, "User not found." },
     *      { 101, "Invalid {userId}" }
     * })
     *
     * @param $userId
     * @return Response
     */
    public function getUserBooksAction($userId)
    {
        if (!is_numeric($userId)) {
            return $this->errorResponse(101, 'invalid user id');
        }

        $user = $this->getRepository(User::REPOSITORY_NAME)->find($userId);

        if (!$user) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(
            ['books' => $user->getBooks()],
            Response::HTTP_OK,
            Book::GROUP_MULTIPLE
        );
    }

    /**
     * Creates a new Book entity.
     *
     * @ApiDoc(
     *  description="Create a new book",
     *  parameters={
     *      {"name"="name",        "dataType"="string",    "required"=true, "description"="The book name. Must be unique." },
     *      {"name"="members",     "dataType"="[User]",    "required"=true, "description"="The members of the book. Can be empty." }
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
     *      { 101, "Book already exists" }
     * })
     *
     * @param Request $request
     * @return Response
     */
    public function postBookAction(Request $request)
    {
        $data = $request->request->all();
        $bookEntity = new Book();

        $user = $this->getUser();

        $form = $this->createForm(
            new BookType(),
            $bookEntity,
            ['method' => 'POST']
        );

        $form->submit($data, false);

        if ($form->isValid()) {

            // Search if book already exists.
            $exists = $this->getRepository(Book::REPOSITORY_NAME)->findOneBy(
                [
                    'name' => $form->getData()->getName(),
                ]
            );

            if ($exists) {
                return $this->errorResponse(101, "Book already exists");
            }

            $bookEntity->setOwner($user);
            $this->persistEntity($bookEntity);

            $user->addBook($bookEntity);
            $this->persistEntity($user);

            return $this->successResponse(
                $bookEntity,
                Response::HTTP_CREATED,
                Book::GROUP_SINGLE
            );
        }

        return $this->errorResponse(
            400,
            $this->formErrorsToArray($form),
            Response::HTTP_BAD_REQUEST
        );

    }

    /**
     * Finds and returns a Book entity.
     *
     * @ApiDoc(
     *  description="Get book's information",
     *  requirements={
     *      {
     *          "name"="id",
     *          "dataType"="integer",
     *          "requirement"="[\d]+",
     *          "description"="Book id"
     *      }
     *  },
     *  statusCodes={
     *      200="Returned when successful.",
     *      404="Returned when the book is not found.",
     *      400="Returned when the parameter is invalid.",
     *  }
     * )
     *
     * @ApiErrors({
     *      { 101, "The book id must be numeric" },
     * })
     *
     * @param $id
     * @return object|Response
     *
     */
    public function getBookAction($id)
    {
        if (!is_numeric($id)) {
            return $this->errorResponse(101, "The id must be numeric", Response::HTTP_BAD_REQUEST);
        }

        $bookEntity = $this->getRepository(Book::REPOSITORY_NAME)->find($id);

        if (!$bookEntity) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(
            $bookEntity,
            Response::HTTP_OK,
            Book::GROUP_SINGLE
        );
    }

    /**
     * Update an existing Book entity.
     *
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @ApiDoc(
     *  description="Update book's information",
     *  statusCodes={
     *      200="Returned when successful.",
     *      404="Returned when the book is not found.",
     *      400="Returned when the parameter is invalid.",
     *      403="User has not ROLE_ADMIN"
     *  }
     * )
     *
     * @ApiErrors({
     *      { 101, "The book id must be numeric" },
     * })
     *
     * @param Request $request
     * @param $id
     * @return Response
     */
    public function putBookAction(Request $request, $id)
    {
        $data = $request->request->all();

        if (!is_numeric($id)) {
            return $this->errorResponse(101, "The id must be numeric", Response::HTTP_BAD_REQUEST);
        }

        $bookEntity = $this->getRepository(Book::REPOSITORY_NAME)->find($id);

        if (!$bookEntity) {
            return $this->notFoundResponse();
        }

        $editForm = $this->createForm(
            new BookType(),
            $bookEntity,
            [
                'ignoreRequired' => true,
            ]
        );

        $editForm = $this->bindFormForPut($request, $editForm);

        if ($editForm->isValid()) {
            $this->persistEntity($bookEntity);
            return $this->successResponse(
                $bookEntity,
                Response::HTTP_OK,
                Book::GROUP_SINGLE
            );
        }

        return $this->errorResponse(
            400,
            $this->formErrorsToArray($editForm),
            Response::HTTP_BAD_REQUEST
        );

    }

    /**
     * Deletes a Book entity.
     *
     * TODO: Security
     *
     * @ApiDoc(
     *  description="Deletes book's information",
     *  statusCodes={
     *      200="Returned when successful.",
     *      404="Returned when the book is not found.",
     *      400="Returned when the parameter is invalid.",
     *      403="User has not ROLE_ADMIN"
     *  }
     * )
     *
     * @ApiErrors({
     *      { 101, "The book id must be numeric" },
     * })
     * @param Request $request
     * @param $id
     * @return Response
     */
    public function deleteBookAction(Request $request, $id)
    {
        if (!is_numeric($id)) {
            return $this->errorResponse(101, "The id must be numeric", Response::HTTP_BAD_REQUEST);
        }

        $bookEntity = $this->getRepository(Book::REPOSITORY_NAME)->find($id);

        if (!$bookEntity) {
            return $this->notFoundResponse();
        }

        if ($this->getUser()->getId() != $bookEntity->getOwner()->getId()) {
            return $this->notFoundResponse();
        }

        $this->removeEntity($bookEntity);

        return $this->successResponse([], Response::HTTP_NO_CONTENT);
    }

    // ---------------------------------------------------------------------------------------------------------------
    //    /books/{id}/bookmarks
    // ---------------------------------------------------------------------------------------------------------------

}
