<?php

namespace BookmarkManager\ApiBundle\Tests\Controller;

use BookmarkManager\ApiBundle\Entity\Bookmark;
use BookmarkManager\ApiBundle\Tests\AbstractWebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BookmarkControllerTest extends AbstractWebTestCase
{

    static public $BASE_URL = "/api/bookmarks";

    public function setUp()
    {
        parent::setUp();

        $this->setUpFixtures(
            [
                'BookmarkManager\ApiBundle\Tests\Fixtures\Entity\LoadBookmarkData',
                'BookmarkManager\ApiBundle\Tests\Fixtures\Entity\LoadTagData',
            ]
        );
    }

    public function testCreateBookmark()
    {
        $newBookmark = [
            'name' => 'TestCreateBookmark1',
            'url' => 'github.com',
            'notes' => '# test title
                            ## title 2
                            ### title 3',
        ];

        $response = $this->performClientRequestAsUser(
            Request::METHOD_POST,
            BookmarkControllerTest::$BASE_URL,
            $newBookmark
        );

        $this->validatePost($response, $newBookmark);
    }

    public function testCreateBookmarkWithNotes()
    {

        $newBookmark = [
            'name' => 'TestCreateBookmark1',
            'url' => 'github.com',
            'notes' => '# test title
                            ## title 2
                            ### title 3',
        ];

        $response = $this->performClientRequestAsUser(
            Request::METHOD_POST,
            BookmarkControllerTest::$BASE_URL,
            $newBookmark
        );

        $this->validatePost($response, $newBookmark);
    }

    public function testCreateBookmarkWithQueryToRemove()
    {
        $url = 'http://loiclefloch.fr/?utm_source=dewdewdew&utm_medium=email&utm_term=fav&test=stayinurl';
        $expectedUrl = 'http://loiclefloch.fr/?test=stayinurl';

        $newBookmark = [
            'name' => 'TestCreateBookmark1',
            'url' => $url,
            'notes' => '# test title
                            ## title 2
                            ### title 3',
        ];

        $response = $this->performClientRequestAsUser(
            Request::METHOD_POST,
            BookmarkControllerTest::$BASE_URL,
            $newBookmark
        );

        $this->validatePost($response, $newBookmark);

        $content = $this->getObjectFromResponse($response);

        $this->assertEquals($expectedUrl, $content->url);
    }

    protected function validatePost(Response $response, $newBookmarkData)
    {
        $content = $this->getObjectFromResponse($response);

        $this->assertJsonResponse($response, Response::HTTP_CREATED);

        $this->assertEquals($newBookmarkData->name, $content->name);
    }

    public function testGetBookmark()
    {
        $bookmark1 = $this->fixtures->getReference('bookmark1');

        $response = $this->performClientRequestAsUser(
            Request::METHOD_GET,
            BookmarkControllerTest::$BASE_URL.'/'.$bookmark1->getId(),
            null
        );

        // Verify that the response is correct.
        $this->assertJsonResponse($response, Response::HTTP_OK);

        // Verify the response object.
        $bookmarkToCheck = $this->getObjectFromResponse($response);
        $this->assertEquals($bookmark1->getId(), $bookmarkToCheck->id);
        $this->assertEquals($bookmark1->getName(), $bookmarkToCheck->name);
    }

    public function testUpdateBookmark()
    {
        $bookmarkToUpdateId = $this->fixtures->getReference('bookmarkToUpdate')->getId();
        $newName = 'BLA';

        $response = $this->performClientRequestAsUser(
            Request::METHOD_GET,
            BookmarkControllerTest::$BASE_URL.'/'.$bookmarkToUpdateId,
            null
        );

        // Verify that the response is correct.
        $this->assertJsonResponse($response, Response::HTTP_OK);

        $bookmarkToUpdate = $this->getObjectFromResponse($response);

        $bookmarkToUpdate->name = $newName;

        $response = $this->performClientRequestAsUser(
            Request::METHOD_PUT,
            BookmarkControllerTest::$BASE_URL.'/'.$bookmarkToUpdate->id,
            $bookmarkToUpdate
        );

        $this->assertJsonResponse($response, Response::HTTP_OK);

        $bookmarkUpdated = $this->getObjectFromResponse($response);

        $this->assertEquals($bookmarkToUpdate->id, $bookmarkUpdated->id);
        $this->assertEquals($bookmarkToUpdate->name, $bookmarkUpdated->name);

        // Verify by GET the Bookmark
        $response = $this->performClientRequestAsUser(
            Request::METHOD_GET,
            BookmarkControllerTest::$BASE_URL.'/'.$bookmarkToUpdate->id,
            null
        );
        $this->assertJsonResponse($response, Response::HTTP_OK);
        $bookmarkUpdated = $this->getObjectFromResponse($response);

        // Same as before

        $this->assertEquals($bookmarkToUpdate->id, $bookmarkUpdated->id);
        $this->assertEquals($bookmarkToUpdate->name, $bookmarkUpdated->name);
    }

    public function testDeleteBookmark()
    {
        $BookmarkToDelete = $this->fixtures->getReference('bookmarkToDelete');

        $response = $this->performClientRequestAsUser(
            Request::METHOD_DELETE,
            BookmarkControllerTest::$BASE_URL.'/'.$BookmarkToDelete->getId(),
            null
        );

        $this->assertJsonResponse($response, Response::HTTP_NO_CONTENT, false);

        // Get the Bookmark. Should return a 401.
        $response = $this->performClientRequestAsUser(
            Request::METHOD_GET,
            BookmarkControllerTest::$BASE_URL.'/'.$BookmarkToDelete->getId(),
            null
        );

        $this->assertJsonResponse($response, Response::HTTP_NOT_FOUND, false);
    }

    public function testAddTagToBookmark()
    {
        $bookmarkToAddTag = $this->fixtures->getReference('bookmarkToAddTag');
        $tag2 = $this->fixtures->getReference('tag2');

        // Add user2to the Bookmark.
        $response = $this->performClientRequestAsUser(
            Request::METHOD_POST,
            BookmarkControllerTest::$BASE_URL.'/'.$bookmarkToAddTag->getId().'/tags/'.$tag2->getId(),
            null
        );

        $this->assertJsonResponse($response, Response::HTTP_OK);

        $bookmarkUpdated = $this->getObjectFromResponse($response);

        $this->assertEquals($bookmarkToAddTag->getId(), $bookmarkUpdated->id);
        $this->assertEquals($bookmarkToAddTag->getName(), $bookmarkUpdated->name);
        $this->assertEquals(1, count($bookmarkUpdated->tags));
        $this->assertEquals($tag2->getId(), $bookmarkUpdated->tags[0]->id);

        // Verify by get the Bookmark
        // Get the Bookmark. Should return a 401.
        $response = $this->performClientRequestAsUser(
            Request::METHOD_GET,
            BookmarkControllerTest::$BASE_URL.'/'.$bookmarkToAddTag->getId(),
            null
        );

        $this->assertJsonResponse($response, Response::HTTP_OK);

        // Same assert as before.
        $this->assertEquals($bookmarkToAddTag->getId(), $bookmarkUpdated->id);
        $this->assertEquals($bookmarkToAddTag->getName(), $bookmarkUpdated->name);
        $this->assertEquals(1, count($bookmarkUpdated->tags));
        $this->assertEquals($tag2->getId(), $bookmarkUpdated->tags[0]->id);
    }

    public function testRemoveTagFromBookmark()
    {
        $bookmarkToRemoveTag = $this->fixtures->getReference('bookmarkToRemoveTag');
        $user2 = $this->fixtures->getReference('user2');

        // Add user2to the Bookmark.
        $response = $this->performClientRequestAsUser(
            Request::METHOD_POST,
            BookmarkControllerTest::$BASE_URL.'/'.$bookmarkToRemoveTag->getId().'/tags/'.$user2->getId(),
            null
        );

        $this->assertJsonResponse($response, Response::HTTP_OK);

        // Remove user2to the Bookmark.
        $response = $this->performClientRequestAsUser(
            Request::METHOD_DELETE,
            BookmarkControllerTest::$BASE_URL.'/'.$bookmarkToRemoveTag->getId().'/tags/'.$user2->getId(),
            null
        );

        $this->assertJsonResponse($response, Response::HTTP_OK);

        $bookmarkUpdated = $this->getObjectFromResponse($response);

        $this->assertEquals($bookmarkToRemoveTag->getId(), $bookmarkUpdated->id);
        $this->assertEquals($bookmarkToRemoveTag->getName(), $bookmarkUpdated->name);
        $this->assertEquals(0, count($bookmarkUpdated->tags));

        // Verify by get the Bookmark
        // Get the Bookmark. Should return a 401.
        $response = $this->performClientRequestAsUser(
            Request::METHOD_GET,
            BookmarkControllerTest::$BASE_URL.'/'.$bookmarkToRemoveTag->getId(),
            null
        );

        $this->assertJsonResponse($response, Response::HTTP_OK);

        // Same assert as before.
        $this->assertEquals($bookmarkToRemoveTag->getId(), $bookmarkUpdated->id);
        $this->assertEquals($bookmarkToRemoveTag->getName(), $bookmarkUpdated->name);
        $this->assertEquals(0, count($bookmarkUpdated->tags));
    }

}
