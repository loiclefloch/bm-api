<?php

namespace BookmarkManager\ApiBundle\Tests\Fixtures\Entity;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use BookmarkManager\ApiBundle\Entity\User;
use BookmarkManager\ApiBundle\Entity\Auth\AccessToken;
use BookmarkManager\ApiBundle\Entity\Auth\Client;

class LoadUserData extends AbstractFixture implements FixtureInterface
{

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /**
         * Note: We always set 'bonjour1' as password to simplify test and requests with username / password.
         */

        $user = new User();
        $user->setFirstName('firstname');
        $user->setLastName('lastname');
        $user->setEmail('test@test.fr');
        $user->setUsername('test@test.fr');
        $user->setAvatar('/files/1/user');
        $user->setPlainPassword('bonjour1'); // setPassword does not encrypt the password.
        $user->setEnabled(true);

        $user1 = new User();
        $user1->setFirstName('firstname');
        $user1->setLastName('lastname');
        $user1->setEmail('test1@test.fr');
        $user1->setUsername('test1@test.fr');
        $user1->setPlainPassword('bonjour1');  // setPassword does not encrypt the password.
        $user1->setEnabled(true);

        $user2 = new User();
        $user2->setFirstName('firstname');
        $user2->setLastName('lastname');
        $user2->setEmail('test2@test.fr');
        $user2->setUsername('test2@test.fr');
        $user2->setPlainPassword('bonjour1');  // setPassword does not encrypt the password.
        $user2->setEnabled(true);

        // -- Persist users
        $manager->persist($user1);
        $manager->persist($user2);

        $manager->flush();

        $this->addReference('user', $user);
        $this->addReference('user1', $user1);
        $this->addReference('user2', $user2);
    }
}