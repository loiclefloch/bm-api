<?php

namespace BookmarkManager\ApiBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Client;

use Liip\FunctionalTestBundle\Test\WebTestCase as WebTestCase;
use Symfony\Component\HttpFoundation\Request;

use BookmarkManager\ApiBundle\Tests\Fixtures\Entity\LoadUserData;
use Symfony\Component\HttpFoundation\Response;

class AbstractWebTestCase extends WebTestCase
{

    public function setUp()
    {
        parent::setUp();

        $this->client = static::createClient();
    }

    public function setUpFixtures(Array $fixturesToLoad = [])
    {
        $fixturesToLoad = array_merge(
            $fixturesToLoad,
            [
                'BookmarkManager\ApiBundle\Tests\Fixtures\Entity\LoadOAuthClient',
                'BookmarkManager\ApiBundle\Tests\Fixtures\Entity\LoadUserData',
            ]
        );

        $this->fixtures = $this->loadFixtures(
            $fixturesToLoad
        )->getReferenceRepository();
    }

    protected function getObjectFromResponse(Response $response)
    {
        return json_decode($response->getContent());
    }

    /**
     * Verify that the response is a json response and the status code is correct
     * @param $response
     * @param int $statusCode
     * @param bool $checkValidJson
     * @param string $contentType
     */
    protected function assertJsonResponse(
        Response $response,
        $statusCode = Response::HTTP_OK,
        $checkValidJson = true,
        $contentType = 'application/json'
    ) {

        $this->assertEquals(
            $statusCode,
            $response->getStatusCode(),
            $response->getContent()
        );
        if ($statusCode != Response::HTTP_NO_CONTENT
        ) {
            $this->assertTrue(
                $response->headers->contains('Content-Type', $contentType),
                $response->headers
            );
        }
        if ($checkValidJson && $statusCode != Response::HTTP_NO_CONTENT) {
            $decode = json_decode($response->getContent());
            $this->assertTrue(
                ($decode != null && $decode != false),
                'is response valid json: ['.$response->getContent().']',
                'Invalid json'
            );
        }
    }

    /**
     * Retrieves an OAuthToken using credentials passed as parameter.
     * Relies on the Webridge\OAuthBundle\DataFixtures\ORM\LoadOAuthClient fixture.
     *
     * @param string $username
     * @param string password
     * @return string    token
     * @throws OAuth2\OAuth2ServerException
     */
    protected function getOAuthToken($username, $password)
    {
        $oauthClient = $this->fixtures->getReference('oauth-fixture-client');

        $kernel = static::createKernel();
        $kernel->boot();
        $container = $kernel->getContainer();

        $server = $container->get('fos_oauth_server.server');

        //create a fake request object with the correct parameters
        //to be processed by the server method
        $request = Request::create(
            '',
            'GET',
            [
                'client_id' => $oauthClient->getPublicId(),
                'client_secret' => $oauthClient->getSecret(),
                'grant_type' => 'password',
                'username' => $username,
                'password' => $password,
            ]
        );

        $response = $server->grantAccessToken($request);

        $content = json_decode($response->getContent());

        return $content->access_token;
    }

    /**
     * Perform a client request with oauth2
     *
     * @param $method
     * @param $urlPath
     * @param null $rawRequestBody
     * @param null $username
     * @param null $password
     * @return null|\Symfony\Component\HttpFoundation\Response
     */
    protected function performClientRequest(
        $method,
        $urlPath,
        $rawRequestBody = null,
        $username = null,
        $password = null
    ) {

        $token = null;
        if ($username != null) {
            $token = $this->getOAuthToken($username, $password);
        }

        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ];

        $this->client = static::createClient(array(), $headers);
        $this->client->request(
            $method,
            $urlPath,
            [],
            [],
            $headers,
            json_encode($rawRequestBody == null ? [] : $rawRequestBody)
        );

        return $this->client->getResponse();
    }

    public function performClientRequestAsAdmin(
        $method,
        $urlPath,
        $rawRequestBody = null
    ) {

        // TODO: change to use an admin account
        $user = $this->fixtures->getReference('user1');

        $username = $user->getUsername();
        $pwd = 'bonjour1';

        return $this->performClientRequest($method, $urlPath, $rawRequestBody, $username, $pwd);
    }


    public function performClientRequestAsUser(
        $method,
        $urlPath,
        $rawRequestBody = null
    ) {

        $user = $this->fixtures->getReference('user1');

        $username = $user->getUsername();
        $pwd = 'bonjour1';

        return $this->performClientRequest($method, $urlPath, $rawRequestBody, $username, $pwd);
    }

}