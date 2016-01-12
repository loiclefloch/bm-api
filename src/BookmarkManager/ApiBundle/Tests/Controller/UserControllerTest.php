<?php

namespace BookmarkManager\ApiBundle\Tests\Controller;

use BookmarkManager\ApiBundle\Tests\AbstractWebTestCase;

use BookmarkManager\ApiBundle\Tests\Fixtures\Entity\LoadUserData;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends AbstractWebTestCase
{
    static public $BASE_URL = "/api/users";

    public function setUp()
    {
        parent::setUp();

        $this->setUpFixtures();
    }

    /**
     * Get a simple user
     */
    public function testGetActionSuccess()
    {
        $user = $this->fixtures->getReference('user1');

        $response = $this->performClientRequest(
            Request::METHOD_GET,
            'api/users/'.$user->getId(),
            null,
            $user->getUsername(),
            'bonjour1'
        );

        $content = $response->getContent();

        $this->assertJsonResponse($response, Response::HTTP_OK);
    }

    /**
     * Get current user
     */
    public function testGetMeActionSuccess()
    {
        $user = $this->fixtures->getReference('user1');

        $response = $this->performClientRequest(
            Request::METHOD_GET,
            'api/users/me',
            null,
            $user->getEmail(),
            'bonjour1'
        );

        $content = $response->getContent();

        $this->assertJsonResponse($response, Response::HTTP_OK);
    }

    public function testGet404()
    {
        $this->buildSimpleRequest(Request::METHOD_GET, "1000");
        $this->assertJsonResponse($this->client->getResponse(), Response::HTTP_NOT_FOUND);
    }

    public function testPostSimpleUser()
    {
        $url = UserControllerTest::$BASE_URL;
        $response = $this->performClientRequestAsAdmin(
            Request::METHOD_POST,
            $url,
            array(
                "email" => "test3@test.fr",
                "password" => "bonjour1",
                "first_name" => "test",
                "last_name" => "test",
            )
        );

        $this->assertJsonResponse($response, Response::HTTP_CREATED);
    }

    public function testPostCompleteUser()
    {
        $url = UserControllerTest::$BASE_URL;
        $response = $this->performClientRequestAsAdmin(
            "POST",
            $url,
            array(
                "email" => "test12@test.fr",
                "password" => "bonjour1",
                "first_name" => "test",
                "last_name" => "test",
                "gender" => 1,
                "avatar" => "32131",
            )
        );

        $response = $this->client->getResponse();

        $this->assertJsonResponse($response, Response::HTTP_CREATED);
    }

    public function testPostCompleteUserFailOnGender()
    {
        $url = UserControllerTest::$BASE_URL;
        $response = $this->performClientRequestAsAdmin(
            "POST",
            $url,
            array(
                "email" => "test4@test.fr",
                "password" => "bonjour1",
                "first_name" => "test",
                "last_name" => "test",
                "gender" => 0,
                "avatar" => "32131",
            )
        );

        $response = $this->client->getResponse();

        $this->assertJsonResponse($response, Response::HTTP_CREATED);
    }

    // --

    protected function buildSimpleRequest($method, $url)
    {
        $url = UserControllerTest::$BASE_URL.$url;
        $this->client->request($method, $url, array(), array(), array('ACCEPT' => 'application/json'));
    }

}
