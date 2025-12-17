<?php
// src/Repository/ActivityLogRepository.php

namespace App\Repository;

use App\Entity\ActivityLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityLog>
 */
class ActivityLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityLog::class);
    }

    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.timestamp', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('a.timestamp', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByAction(string $action): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.action = :action')
            ->setParameter('action', $action)
            ->orderBy('a.timestamp', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.role = :role')
            ->setParameter('role', $role)
            ->orderBy('a.timestamp', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByDateRange(\DateTime $start, \DateTime $end): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.timestamp BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('a.timestamp', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function search(string $query): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.username LIKE :query')
            ->orWhere('a.targetData LIKE :query')
            ->orWhere('a.details LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('a.timestamp', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getStats(): array
    {
        $qb = $this->createQueryBuilder('a');
        
        // Get total logs
        $total = $qb->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
        
        // Get today's logs
        $today = $qb->resetDQLParts()
            ->select('COUNT(a.id)')
            ->from(ActivityLog::class, 'a')
            ->where('DATE(a.timestamp) = CURRENT_DATE()')
            ->getQuery()
            ->getSingleScalarResult();
        
        // Get logs by action type
        $byAction = $qb->resetDQLParts()
            ->select('a.action, COUNT(a.id) as count')
            ->from(ActivityLog::class, 'a')
            ->groupBy('a.action')
            ->getQuery()
            ->getResult();
        
        return [
            'total' => $total,
            'today' => $today,
            'by_action' => $byAction,
        ];
    }

    public function save(ActivityLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ActivityLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}