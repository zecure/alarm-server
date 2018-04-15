<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\Status;

class StatusRepository extends EntityRepository
{
    /**
     * @return Status
     */
    public function getOrCreate()
    {
        $status = $this->createQueryBuilder('s')->getQuery()->getOneOrNullResult();

        if (!$status) {
            $status = new Status();
        }

        return $status;
    }
}