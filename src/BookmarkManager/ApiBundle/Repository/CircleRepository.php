<?php

namespace BookmarkManager\ApiBundle\Repository;

use BookmarkManager\ApiBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\Response;
use BookmarkManager\ApiBundle\Entity\Circle;
use BookmarkManager\ApiBundle\Exception\BmErrorResponseException;

class CircleRepository extends EntityRepository
{

    public function findAllPublicCircles()
    {
        // TODO: paginate

        $builder = $this->getEntityManager()->createQueryBuilder('p')
            ->select('circle')
            ->from('BookmarkManager\ApiBundle\Entity\Circle', 'circle')
            ->where('circle.isDefaultCircle = false')
            ->getQuery();

        $data = $builder->getResult();

        return $data;
    }

    public function findUserOwnCircle(User $user)
    {
        $builder = $this->getEntityManager()->createQueryBuilder('p')
            ->select('circle')
            ->from('BookmarkManager\ApiBundle\Entity\Circle', 'circle')
            ->where('circle.isDefaultCircle = true')
            ->where('circle.owner = :user')
            ->setParameter("user", $user)
            ->setMaxResults(1)
            ->getQuery();

        $data = $builder->getResult();

        return $data[0];
    }
}