<?php

namespace BookmarkManager\ApiBundle\Controller;

use BookmarkManager\ApiBundle\Annotation\ApiErrors;
use BookmarkManager\ApiBundle\DependencyInjection\BaseController;
use BookmarkManager\ApiBundle\Entity\User;
use BookmarkManager\ApiBundle\Entity\Circle;
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
 * @Route("/circles/{circleId}/books")
 */
class BooksController extends BaseController {

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
    public function getBooksAction($circle)
    {
        $books = $this->getRepository(Book::REPOSITORY_NAME)->findBy(
          [
              'owner' => $circle
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
     * Lists all Book entities for the given circle.
     *
     * @Rest\Get("/")
     *
     * @ApiDoc(
     *     description="Get all the books",
     *     statusCodes={
     *      403="User has not ROLE_ADMIN"
     *     }
     * )
     *  @ApiErrors({
     *      { 404, "User not found." },
     *      { 101, "Invalid {circleId}" }
     * })
     *
     * @param $circleId
     * @return Response
     */
    public function getCircleBooksAction($circleId)
    {
        if (!is_numeric($circleId)) {
            return $this->errorResponse(101, 'invalid circle id');
        }

        $circle = $this->getRepository(Circle::REPOSITORY_NAME)->find($circleId);

        if (!$circle) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(
            ['books' => $circle->getBooks()],
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
    public function postBookAction(Request $request, $circleId)
    {
        $data = $request->request->all();
        $bookEntity = new Book();

        $user = $this->getUser();

        if (!is_numeric($circleId)) {
            return $this->errorResponse(101, 'invalid circle id');
        }

        $circle = $this->getRepository(Circle::REPOSITORY_NAME)->find($circleId);

        if (!$circle) {
            return $this->notFoundResponse();
        }

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

            $bookEntity->setOwner($circle);
            $this->persistEntity($bookEntity);

            $circle->addBook($bookEntity);
            $this->persistEntity($circle);

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
    public function getBookAction($circleId, $bookId)
    {
        if (!is_numeric($bookId)) {
            return $this->errorResponse(101, "The id must be numeric", Response::HTTP_BAD_REQUEST);
        }

        $bookEntity = $this->getRepository(Book::REPOSITORY_NAME)->find($bookId);

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
    public function putBookAction(Request $request, $circleId, $bookId)
    {
        $data = $request->request->all();

        if (!is_numeric($bookId)) {
            return $this->errorResponse(101, "The id must be numeric", Response::HTTP_BAD_REQUEST);
        }

        $bookEntity = $this->getRepository(Book::REPOSITORY_NAME)->find($bookId);

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
    public function deleteBookAction(Request $request, $circleId, $bookId)
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

        // TODO: do not allow to remove if in a circle that is not user circle
        // if in a circle, only owner can delete it (TODO: v2)

        $this->removeEntity($bookEntity);

        return $this->successResponse([], Response::HTTP_NO_CONTENT);
    }

}
