<?php
/**
 * Created by PhpStorm.
 * User: loic
 * Date: 12/01/16
 * Time: 20:18
 */

namespace BookmarkManager\ApiBundle\Controller;


use BookmarkManager\ApiBundle\DependencyInjection\BaseController;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use Symfony\Component\HttpFoundation\Request;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;

class DataController extends BaseController
{

    /**
     * Import json file and create bookmarks and tags.
     *
     * @Post("/data/import", name="import")
     *
     * @ApiDoc(
     *  description="Import",
     *  statusCodes={
     *      200="Returned when successful."
     *  }
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function postDataImportAction(Request $request)
    {
        return $this->errorResponse(0, "Not implemented", Response::HTTP_NOT_IMPLEMENTED);
    }

    /**
     * Export bookmarks and tags to a json file.
     *
     * @Get("/data/export", name="export")
     *
     * @ApiDoc(
     *  description="Export",
     *  statusCodes={
     *      200="Returned when successful."
     *  }
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function getDataExportAction(Request $request)
    {
        return $this->errorResponse(0, "Not implemented", Response::HTTP_NOT_IMPLEMENTED);
    }

}