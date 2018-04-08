<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\User;

class UserRepository extends EntityRepository
{
    /**
     * @param int $seconds
     * @param array $users
     * @return User[]
     */
    public function findDead(int $seconds, array $users = [])
    {
        $queryBuilder = $this->createQueryBuilder('u')
            ->setParameter('seconds', new \DateTime('-' . $seconds . ' seconds'))
            ->where('u.lastPingAt < :seconds OR u.lastPingAt IS NULL')
        ;

        if ($users) {
            $queryBuilder
                ->setParameter('users', $users)
                ->andWhere('u.id IN (:users)')
            ;
        }

        /** @var User[] $deadUsers */
        $deadUsers = $queryBuilder->getQuery()->getResult();

        $deadAlarmUsers = [];
        foreach ($deadUsers as $deadUser) {
            if ($deadUser->hasRole('ROLE_ALARM')) {
                $deadAlarmUsers[] = $deadUser;
            }
        }
        return $deadAlarmUsers;
    }

    /**
     * @return User[]
     */
    public function findAdmins()
    {
        /** @var User[] $users */
        $users = $this->findAll();

        $adminUsers = [];
        foreach ($users as $user) {
            if ($user->hasRole('ROLE_ADMIN')) {
                $adminUsers[] = $user;
            }
        }
        return $adminUsers;
    }
}