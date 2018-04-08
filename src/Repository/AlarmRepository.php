<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\User;

class AlarmRepository extends EntityRepository
{
    /**
     * @param User $user
     * @param string $threshold
     * @return bool
     */
    public function hasTooMany(User $user, string $threshold)
    {
        $queryBuilder = $this->createQueryBuilder('a')
            ->setParameter('threshold', new \DateTime($threshold))
            ->where('a.createdAt > :threshold')
            ->setParameter('username', $user->getUsername())
            ->andWhere('a.createdBy = :username')
            ->setMaxResults(1)
        ;

        return !empty($queryBuilder->getQuery()->getResult());
    }
}