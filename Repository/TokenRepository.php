<?php

namespace Sh4bang\UserBundle\Repository;

use DateTime;
use Doctrine\ORM\EntityRepository;

class TokenRepository extends EntityRepository
{
    /**
     * Delete all tokens that have expired
     *
     * @return int
     */
    public function deleteExpiredTokens(): int
    {
        $query = $this->createQueryBuilder('t')
            ->delete()
            ->where('t.expiredAt <= :now')
            ->setParameter('now',new DateTime())
            ->getQuery()
        ;

        $nbDeletedRows = $query->execute();

        return (int)$nbDeletedRows;
    }
}
