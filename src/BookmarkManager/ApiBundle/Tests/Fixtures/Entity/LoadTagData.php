<?php

namespace BookmarkManager\ApiBundle\Tests\Fixtures\Entity;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use BookmarkManager\ApiBundle\Entity\Tag;

class LoadTagData extends AbstractFixture implements FixtureInterface
{

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $tag1 = new Tag();
        $tag1->setName('tag1');

        $tag2 = new Tag();
        $tag2->setName('tag2');

        $tagToDelete = new Tag();
        $tagToDelete->setName('tagToDelete');

        $tagToUpdate = new Tag();
        $tagToUpdate->setName('tagToUpdate');

        // TODO: set owner;
//        $tag1->setOwner();
//        $tag2->setOwner();
//        $tagToDelete->setOwner();
//        $tagToUpdate->setOwner();

        // -- Persist users
        $manager->persist($tag1);
        $manager->persist($tag2);
        $manager->persist($tagToDelete);
        $manager->persist($tagToUpdate);

        $manager->flush();

        $this->addReference('tag1', $tag1);
        $this->addReference('tag2', $tag2);
        $this->addReference('tagToDelete', $tagToDelete);
        $this->addReference('tagToUpdate', $tagToUpdate);
    }
}