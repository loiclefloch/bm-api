<?php
/**
 * Created by PhpStorm.
 * User: loic
 * Date: 12/01/16
 * Time: 20:18
 */

namespace BookmarkManager\ApiBundle\Controller;


use BookmarkManager\ApiBundle\Annotation\ApiErrors;
use BookmarkManager\ApiBundle\DependencyInjection\BaseController;
use BookmarkManager\ApiBundle\Entity\Bookmark;
use BookmarkManager\ApiBundle\Entity\Tag;
use BookmarkManager\ApiBundle\Exception\BMErrorResponseException;
use BookmarkManager\ApiBundle\Form\BookmarkType;
use BookmarkManager\ApiBundle\Form\TagType;
use BookmarkManager\ApiBundle\Tool\WebsiteCrawler;
use BookmarkManager\ApiBundle\Utils\BookmarkUtils;
use BookmarkManager\ApiBundle\Utils\TagUtils;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use Symfony\Component\HttpFoundation\Request;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;

class DataController extends BaseController
{
    const VERSION = 1;

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
     * @ApiErrors({
     *     { 101, "Invalid file" },
     *     { 102, "Invalid version" }
     * })
     *
     * @param Request $request
     * @return Response
     */
    public function postDataImportAction(Request $request)
    {
        $data = $request->request->all();

        if (!isset($data['version']) || !isset($data['tags']) || !isset($data['bookmarks'])) {
            return $this->errorResponse(101, "Invalid file");
        }

        $version = $data['version'];
        $tags = $data['tags'];
        $bookmarks = $data['bookmarks'];

        if (!is_numeric($version)) {
            return $this->errorResponse(102, "Invalid version");
        }

        // -- Create tag

        // contains the created tags
        $newTags = [];

        foreach ($tags as $tagData) {

            try {
                $tagEntity = TagUtils::createTag($this, $tagData);
                $newTags[] = $tagEntity;
            }
            catch (BMErrorResponseException $e) {
                // do nothing
                $this->getLogger()->info('Catch exception ' . $e->getMessage());
            }

        }

        // contains the created bookmarks
        $newBookmarks = [];
        foreach ($bookmarks as $bookmarkData) {

            try {
                $bookmarkEntity = BookmarkUtils::createBookmark($this, $bookmarkData);
                $newBookmarks[] = $bookmarkEntity;
            }
            catch (BMErrorResponseException $e) {
                // do nothing
                $this->getLogger()->info('Catch exception ' . $e->getMessage());
            }

        }

        return $this->successResponse(
            [
                'stats' => [
                    'bookmarks' => count($newBookmarks),
                    'tags' => count($newTags),
                ],
            ],
            Response::HTTP_OK
        );
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
        $filename = 'bm_export_'.gmdate("d_m_Y__H_i_s");

        $content = [
            'version' => DataController::VERSION,
            'bookmarks' => $this->getUser()->getBookmarks(),
            'tags' => $this->toJSON($this->getUser()->getTags()),
        ];

        $jsonContent = $this->toJSON($content);

//        var_dump($jsonContent);

        // Generate response
        $response = new Response();

        // Set headers
        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-type', "application/json");
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'";');
        $response->headers->set('Content-length', sizeof($jsonContent));

        // Send headers before outputting anything
        $response->sendHeaders();

        $response->setContent($jsonContent);

        return $response;
    }

    /**
     * For testing purpose only, remove all bookmarks and tags
     *
     * @param Request $request
     * @return Response
     */
    public function getDataClearAction(Request $request)
    {
        $em = $this->get('doctrine')->getManager();

        $bookmarks = $this->getUser()->getBookmarks();
        foreach ($bookmarks as $bookmark) {
            $em->remove($bookmark);
        }
        $em->flush();

        $tags = $this->getUser()->getTags();
        foreach ($tags as $tag) {
            $em->remove($tag);
        }
        $em->flush();

        return $this->successResponse([]);
    }
}