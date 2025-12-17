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
    // In src/Repository/ActivityLogRepository.php

public function getTotalCount(): int
{
    return $this->createQueryBuilder('a')
        ->select('COUNT(a.id)')
        ->getQuery()
        ->getSingleScalarResult();
}

public function getTodayCount(): int
{
    $today = new \DateTime();
    $today->setTime(0, 0, 0);
    
    return $this->createQueryBuilder('a')
        ->select('COUNT(a.id)')
        ->where('a.timestamp >= :today')
        ->setParameter('today', $today)
        ->getQuery()
        ->getSingleScalarResult();
}

public function getCountByRole(string $role): int
{
    return $this->createQueryBuilder('a')
        ->select('COUNT(a.id)')
        ->where('a.role = :role')
        ->setParameter('role', $role)
        ->getQuery()
        ->getSingleScalarResult();
}

public function getActionStatistics(): array
{
    return $this->createQueryBuilder('a')
        ->select('a.action, COUNT(a.id) as count')
        ->groupBy('a.action')
        ->orderBy('count', 'DESC')
        ->getQuery()
        ->getResult();
}

public function deleteOldLogs(int $daysToKeep): int
{
    $cutoffDate = new \DateTime();
    $cutoffDate->modify('-' . $daysToKeep . ' days');
    
    $query = $this->createQueryBuilder('a')
        ->delete()
        ->where('a.timestamp < :cutoffDate')
        ->setParameter('cutoffDate', $cutoffDate)
        ->getQuery();
    
    return $query->execute();
}

public function findWithFilters(array $filters = []): array
{
    $qb = $this->createQueryBuilder('a');
    
    if (!empty($filters['action'])) {
        $qb->andWhere('a.action = :action')
            ->setParameter('action', $filters['action']);
    }
    
    if (!empty($filters['role'])) {
        $qb->andWhere('a.role = :role')
            ->setParameter('role', $filters['role']);
    }
    
    if (!empty($filters['user'])) {
        $qb->andWhere('a.username LIKE :user')
            ->setParameter('user', '%' . $filters['user'] . '%');
    }
    
    if (!empty($filters['date'])) {
        $qb->andWhere('DATE(a.timestamp) = :date')
            ->setParameter('date', $filters['date']->format('Y-m-d'));
    }
    
    if (!empty($filters['startDate'])) {
        $qb->andWhere('a.timestamp >= :startDate')
            ->setParameter('startDate', new \DateTime($filters['startDate']));
    }
    
    if (!empty($filters['endDate'])) {
        $qb->andWhere('a.timestamp <= :endDate')
            ->setParameter('endDate', new \DateTime($filters['endDate']));
    }
    
    return $qb->orderBy('a.timestamp', 'DESC')
        ->getQuery()
        ->getResult();
}
}