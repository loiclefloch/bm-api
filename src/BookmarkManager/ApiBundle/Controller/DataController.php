<?php

namespace BookmarkManager\ApiBundle\Controller;

use BookmarkManager\ApiBundle\Annotation\ApiErrors;
use BookmarkManager\ApiBundle\Crawler\CrawlerNotFoundException;
use BookmarkManager\ApiBundle\Crawler\CrawlerRetrieveDataException;
use BookmarkManager\ApiBundle\DependencyInjection\BaseController;
use BookmarkManager\ApiBundle\Entity\Bookmark;
use BookmarkManager\ApiBundle\Entity\Tag;
use BookmarkManager\ApiBundle\Exception\BMErrorResponseException;
use BookmarkManager\ApiBundle\Form\BookmarkType;
use BookmarkManager\ApiBundle\Form\TagType;
use BookmarkManager\ApiBundle\Crawler\WebsiteCrawler;
use BookmarkManager\ApiBundle\Utils\BookmarkUtils;
use BookmarkManager\ApiBundle\Utils\TagUtils;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcher;
use Symfony\Component\HttpFoundation\Request;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;

class DataController extends BaseController
{
    const VERSION = 1;

    /**
     * Import json file and create bookmarks and tags.
     *
     * @Rest\Post("/data/import")
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
            } catch (BMErrorResponseException $e) {
                // do nothing
                $this->getLogger()->info('Catch exception '.$e->getMessage());
            }

        }

        // contains the created bookmarks
        $newBookmarks = [];
        foreach ($bookmarks as $bookmarkData) {

            try {

                $bookmarkEntity = BookmarkUtils::createBookmark($this, $bookmarkData);

                if ($bookmarkEntity !== null) {
                    $newBookmarks[] = $bookmarkEntity;
                } else {
                    $this->getLogger()->warning('[IMPORT] Can\'t create bookmark: '.$bookmarkData['url']);
                }
            } catch (CrawlerNotFoundException $e) {
                $this->getLogger()->info('[IMPORT] 404 for '.$bookmarkData['url']);
            } catch (CrawlerRetrieveDataException $e) {
                $this->getLogger()->info('[IMPORT] Impossible to retrieve the website content for '.$bookmarkData['url']);
            } catch (BMErrorResponseException $e) {
                // do nothing
                $this->getLogger()->info('[IMPORT] Catch exception for '.$bookmarkData['url'].' - '.$e->getMessage());
            } catch (Exception $e) {
                $this->getLogger()->info('[IMPORT] Unknown error  for '.$bookmarkData['url']);
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
     * Import json file. Just to test the request. Do nothing
     *
     * @Rest\Post("/data/import/test")
     *
     * @ApiDoc(
     *  description="Test import upload",
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
    public function postDataImportTestAction(Request $request)
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

        return $this->successResponse(
            [
                'version' => $version,
                'tags' => $tags,
                'bookmarks' => $bookmarks,
            ],
            Response::HTTP_OK
        );
    }

    /**
     * Export bookmarks and tags to a json file.
     *
     * @Rest\Get("/data/download")
     *
     * @Rest\QueryParam(name="filename", description="The name of the file to download")
     *
     * @ApiDoc(
     *  description="Download file",
     *  statusCodes={
     *      200="Returned when successful."
     *  }
     * )
     *
     * @ApiErrors({
     *      { 101, "No filename given" }
     * })
     *
     * [ROUTE] get_data_download
     *
     * @param ParamFetcher $params
     * @return Response
     * @internal param $filename
     */
    public function getDataDownloadAction(ParamFetcher $params)
    {
        $filename = $params->get('filename');

        if (!$filename) {
            return $this->errorResponse(101, 'No filename given');
        }

        $tmpDir = sys_get_temp_dir();
        $filePath = $tmpDir.'/'.$filename;

        $jsonContent = file_get_contents($filePath);

        // Generate response
        $response = new Response();

        // Set headers
        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-type', "application/json");
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'";');
        $response->headers->set('Content-length', strlen($jsonContent));

        // Send headers before outputting anything
        $response->sendHeaders();

        $response->setContent($jsonContent);

        return $response;
    }

    /**
     * Create json file that contains bookmarks and tags to a json file. Returns the url to retrieve the file.
     *
     * @Rest\Get("/data/export")
     *
     * @ApiDoc(
     *  description="Create file to export",
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
        $content = [
            'version' => DataController::VERSION,
            'bookmarks' => $this->getUser()->getBookmarks(),
            'tags' => $this->toJSON($this->getUser()->getTags()),
        ];

        $jsonContent = $this->toJSON($content);

        $filename = 'bm_export_'.$this->getUser()->getId().'_'.gmdate("d_m_Y__H_i_s").'.json';
        $tmpDir = sys_get_temp_dir();
        $filePath = $tmpDir.'/'.$filename;

        $file = fopen($filePath, "w");
        fwrite($file, $jsonContent);
        fclose($file);

        return $this->successResponse(
            [
                'file' => $filename,
                'url' => $request->getScheme().'://'.$request->getHost().$this->generateUrl(
                        'get_data_download',
                        ['filename' => $filename]
                    ),
            ],
            Response::HTTP_OK
        );
    }

    /**
     * For testing purpose only, remove all bookmarks and tags
     *
     * @param Request $request
     * @return Response
     *
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
