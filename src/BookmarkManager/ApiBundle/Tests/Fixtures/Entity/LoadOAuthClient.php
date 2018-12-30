<?php

namespace BookmarkManager\ApiBundle\Tests\Fixtures\Entity;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadOAuthClient extends AbstractFixture implements FixtureInterface, ContainerAwareInterface
{

    private $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $objectManager)
    {
        $clientManager = $this->container->get('fos_oauth_server.client_manager');
        $client = $clientManager->createClient();
        $client->setRedirectUris([]);
        $client->setAllowedGrantTypes(['password']);
        $clientManager->updateClient($client);

        $this->addReference('oauth-fixture-client', $client);
    }

}