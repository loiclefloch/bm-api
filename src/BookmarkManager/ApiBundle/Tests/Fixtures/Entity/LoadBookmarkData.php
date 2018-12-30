<?php

namespace BookmarkManager\ApiBundle\Tests\Fixtures\Entity;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use BookmarkManager\ApiBundle\Entity\Bookmark;

class LoadBookmarkData extends AbstractFixture implements FixtureInterface
{

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $bm1 = new Bookmark();
        $bm1->setName('bm1');
        $bm1->setUrl('google.com');

        $bm2 = new Bookmark();
        $bm2->setName('bm2');
        $bm2->setUrl('google.fr');

        $bmToDelete = new Bookmark();
        $bmToDelete->setName('bmToDelete');
        $bmToDelete->setUrl('google.ie');

        $bmToUpdate = new Bookmark();
        $bmToUpdate->setName('bmToUpdate');
        $bmToUpdate->setUrl('google.ru');

        $bmToAddMember = new Bookmark();
        $bmToAddMember->setName('bmToAddTag');
        $bmToAddMember->setUrl('fwefewfew.fr');

        $bmToRemoveMember = new Bookmark();
        $bmToRemoveMember->setName('bmToRemoveTag');
        $bmToRemoveMember->setUrl('fewef.fr');

        // TODO: set owner;
        //$bm1->setOwner();
        //$bm2->setOwner();
        //$bmToDelete->setOwner();
        //$bmToUpdate->setOwner();
        //$bmToAddMember->setOwner();
        //$bmToRemoveMember->setOwner();

        // -- Persist
        $manager->persist($bm1);
        $manager->persist($bm2);
        $manager->persist($bmToDelete);
        $manager->persist($bmToUpdate);
        $manager->persist($bmToAddMember);
        $manager->persist($bmToRemoveMember);


        $manager->flush();

        $this->addReference('bookmark1', $bm1);
        $this->addReference('bookmark2', $bm2);
        $this->addReference('bookmarkToDelete', $bmToDelete);
        $this->addReference('bookmarkToUpdate', $bmToUpdate);
        $this->addReference('bookmarkToAddTag', $bmToAddMember);
        $this->addReference('bookmarkToRemoveTag', $bmToRemoveMember);
    }
}