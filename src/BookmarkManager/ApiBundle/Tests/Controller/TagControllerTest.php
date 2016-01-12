<?php

namespace TagManager\ApiBundle\Tests\Controller;

use BookmarkManager\ApiBundle\Entity\Tag;
use BookmarkManager\ApiBundle\Tests\AbstractWebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TagControllerTest extends AbstractWebTestCase
{
    static public $BASE_URL = "/api/tags";

    public function setUp()
    {
        parent::setUp();

        $this->setUpFixtures(
            [
                'TagManager\ApiBundle\Tests\Fixtures\Entity\LoadTagData',
            ]
        );
    }

    public function testCreateTag()
    {
        $newTagName = 'TestCreateTag1';
        $newTagColor = '#ccc';

        $response = $this->performClientRequestAsUser(
            Request::METHOD_POST,
            TagControllerTest::$BASE_URL,
            [
                'name' => $newTagName,
                'color' => $newTagColor
            ]
        );

        $content = $this->getObjectFromResponse($response);

        $this->assertJsonResponse($response, Response::HTTP_CREATED);

        $this->assertEquals($newTagName, $content->name);
        $this->assertEquals($newTagColor, $content->color);
    }

    public function testCreateTagWithoutColor()
    {

        $newTagName = 'TestCreateTag1';

        $response = $this->performClientRequestAsUser(
            Request::METHOD_POST,
            TagControllerTest::$BASE_URL,
            [
                'name' => $newTagName
            ]
        );

        $content = $this->getObjectFromResponse($response);

        $this->assertJsonResponse($response, Response::HTTP_CREATED);

        $this->assertEquals($newTagName, $content->name);
        $this->assertEquals(Tag::$DEFAULT_COLOR, $content->color);
    }

    public function testGetTag()
    {
        $tag1 = $this->fixtures->getReference('tag1');

        $response = $this->performClientRequestAsUser(
            Request::METHOD_GET,
            TagControllerTest::$BASE_URL.'/'.$tag1->getId(),
            null
        );

        // Verify that the response is correct.
        $this->assertJsonResponse($response, Response::HTTP_OK);

        // Verify the response object.
        $tagToCheck = $this->getObjectFromResponse($response);
        $this->assertEquals($tag1->getId(), $tagToCheck->id);
        $this->assertEquals($tag1->getName(), $tagToCheck->name);
        $this->assertEquals($tag1->getColor(), $tagToCheck->color);
    }

    public function testUpdateTag()
    {
        $tagToUpdateId = $this->fixtures->getReference('tagToUpdate')->getId();
        $newName = 'BLA';

        $response = $this->performClientRequestAsUser(
            Request::METHOD_GET,
            TagControllerTest::$BASE_URL.'/'.$tagToUpdateId,
            null
        );

        // Verify that the response is correct.
        $this->assertJsonResponse($response, Response::HTTP_OK);

        $tagToUpdate = $this->getObjectFromResponse($response);

        $tagToUpdate->name = $newName;

        $response = $this->performClientRequestAsUser(
            Request::METHOD_PUT,
            TagControllerTest::$BASE_URL.'/'.$tagToUpdate->id,
            $tagToUpdate
        );

        $this->assertJsonResponse($response, Response::HTTP_OK);

        $tagUpdated = $this->getObjectFromResponse($response);

        $this->assertEquals($tagToUpdate->id, $tagUpdated->id);
        $this->assertEquals($tagToUpdate->name, $tagUpdated->name);
        $this->assertEquals($tagToUpdate->color, $tagUpdated->color);

        // Verify by GET the Tag
        $response = $this->performClientRequestAsUser(
            Request::METHOD_GET,
            TagControllerTest::$BASE_URL.'/'.$tagToUpdate->id,
            null
        );
        $this->assertJsonResponse($response, Response::HTTP_OK);
        $tagUpdated = $this->getObjectFromResponse($response);

        // Same as before

        $this->assertEquals($tagToUpdate->id, $tagUpdated->id);
        $this->assertEquals($tagToUpdate->name, $tagUpdated->name);
        $this->assertEquals($tagToUpdate->color, $tagUpdated->color);
    }

    public function testDeleteTag()
    {
        $TagToDelete = $this->fixtures->getReference('tagToDelete');

        $response = $this->performClientRequestAsUser(
            Request::METHOD_DELETE,
            TagControllerTest::$BASE_URL.'/'.$TagToDelete->getId(),
            null
        );

        $this->assertJsonResponse($response, Response::HTTP_NO_CONTENT, false);

        // Get the Tag. Should return a 401.
        $response = $this->performClientRequestAsUser(
            Request::METHOD_GET,
            TagControllerTest::$BASE_URL.'/'.$TagToDelete->getId(),
            null
        );

        $this->assertJsonResponse($response, Response::HTTP_NOT_FOUND, false);
    }


}
