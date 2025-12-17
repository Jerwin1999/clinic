<?php

namespace App\Controller;

use App\Entity\ActivityLog;
use App\Repository\ActivityLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/activity-log')]
final class ActivityLogController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ActivityLogRepository $activityLogRepository
    ) {
    }

    #[Route('/', name: 'app_activity_log_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(Request $request): Response
    {
        // Get filter parameters from request
        $action = $request->query->get('action');
        $role = $request->query->get('role');
        $user = $request->query->get('user');
        $date = $request->query->get('date');
        
        // Prepare filters array
        $filters = [];
        
        if ($action) {
            $filters['action'] = $action;
        }
        
        if ($role) {
            $filters['role'] = $role;
        }
        
        if ($user) {
            $filters['user'] = $user;
        }
        
        if ($date) {
            $filters['date'] = new \DateTime($date);
        }
        
        // Get logs with optional filters
        $logs = $this->activityLogRepository->findWithFilters($filters);
        
        // Get statistics for dashboard
        $totalLogs = $this->activityLogRepository->getTotalCount();
        $todayLogs = $this->activityLogRepository->getTodayCount();
        $adminLogs = $this->activityLogRepository->getCountByRole('ROLE_ADMIN');
        $staffLogs = $this->activityLogRepository->getCountByRole('ROLE_STAFF');
        
        // Get action statistics
        $actionStats = $this->activityLogRepository->getActionStatistics();
        
        return $this->render('activity_log/index.html.twig', [
            'logs' => $logs,
            'totalLogs' => $totalLogs,
            'todayLogs' => $todayLogs,
            'adminLogs' => $adminLogs,
            'staffLogs' => $staffLogs,
            'actionStats' => $actionStats,
            'filters' => [
                'action' => $action,
                'role' => $role,
                'user' => $user,
                'date' => $date,
            ],
        ]);
    }

    #[Route('/api', name: 'app_activity_log_api', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function api(Request $request): JsonResponse
    {
        // Get DataTables parameters
        $draw = $request->query->getInt('draw', 1);
        $start = $request->query->getInt('start', 0);
        $length = $request->query->getInt('length', 10);
        $search = $request->query->all('search');
        $order = $request->query->all('order');
        $columns = $request->query->all('columns');
        
        // Build query based on DataTables parameters
        $queryBuilder = $this->activityLogRepository->createQueryBuilder('a');
        
        // Apply search
        if (!empty($search['value'])) {
            $searchValue = $search['value'];
            $queryBuilder
                ->andWhere('a.username LIKE :search OR a.targetData LIKE :search OR a.action LIKE :search')
                ->setParameter('search', '%' . $searchValue . '%');
        }
        
        // Apply column-specific searches
        foreach ($columns as $key => $column) {
            if (!empty($column['search']['value'])) {
                $columnSearchValue = $column['search']['value'];
                $fieldMap = [
                    0 => 'a.id',
                    1 => 'a.timestamp',
                    2 => 'a.username',
                    3 => 'a.role',
                    4 => 'a.action',
                    5 => 'a.targetData',
                    6 => 'a.ipAddress',
                    7 => 'a.details',
                ];
                
                if (isset($fieldMap[$key])) {
                    $queryBuilder
                        ->andWhere($fieldMap[$key] . ' LIKE :columnSearch' . $key)
                        ->setParameter('columnSearch' . $key, '%' . $columnSearchValue . '%');
                }
            }
        }
        
        // Apply ordering
        if (!empty($order)) {
            $orderColumnIndex = $order[0]['column'];
            $orderDirection = $order[0]['dir'];
            
            $orderMap = [
                0 => 'a.id',
                1 => 'a.timestamp',
                2 => 'a.username',
                3 => 'a.role',
                4 => 'a.action',
                5 => 'a.targetData',
                6 => 'a.ipAddress',
                7 => 'a.details',
            ];
            
            if (isset($orderMap[$orderColumnIndex])) {
                $queryBuilder->orderBy($orderMap[$orderColumnIndex], $orderDirection);
            } else {
                $queryBuilder->orderBy('a.timestamp', 'DESC');
            }
        } else {
            $queryBuilder->orderBy('a.timestamp', 'DESC');
        }
        
        // Get total count
        $totalQueryBuilder = clone $queryBuilder;
        $totalRecords = $totalQueryBuilder
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
        
        // Apply pagination
        $queryBuilder
            ->setFirstResult($start)
            ->setMaxResults($length);
        
        // Get filtered results
        $results = $queryBuilder->getQuery()->getResult();
        
        // Format data for DataTables
        $data = [];
        foreach ($results as $log) {
            $data[] = [
                'id' => $log->getId(),
                'timestamp' => $log->getTimestamp()->format('Y-m-d H:i:s'),
                'username' => $log->getUsername(),
                'role' => $log->getRole(),
                'action' => $log->getAction(),
                'actionReadable' => $log->getActionReadable(),
                'targetData' => $log->getTargetData(),
                'ipAddress' => $log->getIpAddress(),
                'details' => $log->getDetails(),
                'userId' => $log->getUserId(),
            ];
        }
        
        return new JsonResponse([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $data,
        ]);
    }

    #[Route('/details/{id}', name: 'app_activity_log_details', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function details(int $id): JsonResponse
    {
        $log = $this->activityLogRepository->find($id);
        
        if (!$log) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Log not found',
            ], 404);
        }
        
        return new JsonResponse([
            'success' => true,
            'data' => [
                'id' => $log->getId(),
                'timestamp' => $log->getTimestamp()->format('Y-m-d H:i:s'),
                'username' => $log->getUsername(),
                'role' => $log->getRole(),
                'action' => $log->getAction(),
                'actionReadable' => $log->getActionReadable(),
                'targetData' => $log->getTargetData(),
                'ipAddress' => $log->getIpAddress(),
                'details' => $log->getDetails(),
                'userId' => $log->getUserId(),
            ],
        ]);
    }

    #[Route('/clear', name: 'app_activity_log_clear', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function clear(Request $request): JsonResponse
    {
        $days = $request->request->getInt('days', 30);
        
        if ($days === 0) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid days parameter',
            ], 400);
        }
        
        try {
            $deletedCount = $this->activityLogRepository->deleteOldLogs($days);
            
            return new JsonResponse([
                'success' => true,
                'message' => sprintf('Successfully cleared %d old logs', $deletedCount),
                'count' => $deletedCount,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error clearing logs: ' . $e->getMessage(),
            ], 500);
        }
    }

    #[Route('/statistics', name: 'app_activity_log_statistics', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function statistics(): JsonResponse
    {
        $totalLogs = $this->activityLogRepository->getTotalCount();
        $todayLogs = $this->activityLogRepository->getTodayCount();
        $adminLogs = $this->activityLogRepository->getCountByRole('ROLE_ADMIN');
        $staffLogs = $this->activityLogRepository->getCountByRole('ROLE_STAFF');
        $actionStats = $this->activityLogRepository->getActionStatistics();
        
        return new JsonResponse([
            'success' => true,
            'data' => [
                'total' => $totalLogs,
                'today' => $todayLogs,
                'admin' => $adminLogs,
                'staff' => $staffLogs,
                'actions' => $actionStats,
            ],
        ]);
    }

    #[Route('/export', name: 'app_activity_log_export', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function export(Request $request): Response
    {
        $format = $request->query->get('format', 'csv');
        $filters = [
            'action' => $request->query->get('action'),
            'role' => $request->query->get('role'),
            'user' => $request->query->get('user'),
            'startDate' => $request->query->get('startDate'),
            'endDate' => $request->query->get('endDate'),
        ];
        
        $logs = $this->activityLogRepository->findWithFilters($filters);
        
        switch ($format) {
            case 'csv':
                return $this->exportToCsv($logs);
            case 'json':
                return $this->exportToJson($logs);
            default:
                return $this->exportToCsv($logs);
        }
    }
    
    private function exportToCsv(array $logs): Response
    {
        $csvData = "ID,Timestamp,Username,Role,Action,Target Data,IP Address,Details\n";
        
        foreach ($logs as $log) {
            $csvData .= sprintf(
                '%d,%s,%s,%s,%s,%s,%s,%s',
                $log->getId(),
                $log->getTimestamp()->format('Y-m-d H:i:s'),
                $this->escapeCsv($log->getUsername()),
                $log->getRole(),
                $log->getAction(),
                $this->escapeCsv($log->getTargetData() ?? ''),
                $log->getIpAddress() ?? '',
                $this->escapeCsv($log->getDetails() ?? '')
            ) . "\n";
        }
        
        $response = new Response($csvData);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="activity_logs_' . date('Y-m-d') . '.csv"');
        
        return $response;
    }
    
    private function exportToJson(array $logs): Response
    {
        $data = [];
        foreach ($logs as $log) {
            $data[] = [
                'id' => $log->getId(),
                'timestamp' => $log->getTimestamp()->format('Y-m-d H:i:s'),
                'username' => $log->getUsername(),
                'role' => $log->getRole(),
                'action' => $log->getAction(),
                'targetData' => $log->getTargetData(),
                'ipAddress' => $log->getIpAddress(),
                'details' => $log->getDetails(),
                'userId' => $log->getUserId(),
            ];
        }
        
        $response = new Response(json_encode($data, JSON_PRETTY_PRINT));
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Content-Disposition', 'attachment; filename="activity_logs_' . date('Y-m-d') . '.json"');
        
        return $response;
    }
    
    private function escapeCsv(string $value): string
    {
        if (strpos($value, ',') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
            $value = str_replace('"', '""', $value);
            $value = '"' . $value . '"';
        }
        return $value;
    }

    #[Route('/test-log', name: 'app_activity_log_test', methods: ['GET'])]
    public function testLog(): Response
    {
        // This is just for testing - creates a sample log entry
        $log = new ActivityLog();
        $log->setUserId(1);
        $log->setUsername('admin');
        $log->setRole('ROLE_ADMIN');
        $log->setAction('CREATE');
        $log->setTargetData('Test log entry created');
        $log->setIpAddress('127.0.0.1');
        $log->setDetails('This is a test log entry created for demonstration purposes.');
        
        $this->entityManager->persist($log);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Test log entry created successfully!');
        
        return $this->redirectToRoute('app_activity_log_index');
    }
}