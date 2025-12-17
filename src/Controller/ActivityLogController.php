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
        
        // Get logs with optional filters
        $logs = $this->activityLogRepository->findWithFilters([
            'action' => $action,
            'role' => $role,
            'user' => $user,
            'date' => $date ? new \DateTime($date) : null,
        ]);
        
        // Get statistics for dashboard
        $totalLogs = $this->activityLogRepository->getTotalCount();
        $todayLogs = $this->activityLogRepository->getTodayCount();
        $adminLogs = $this->activityLogRepository->getCountByRole('ROLE_ADMIN');
        $staffLogs = $this->activityLogRepository->getCountByRole('ROLE_STAFF');
        
        return $this->render('activity_log/index.html.twig', [
            'logs' => $logs,
            'totalLogs' => $totalLogs,
            'todayLogs' => $todayLogs,
            'adminLogs' => $adminLogs,
            'staffLogs' => $staffLogs,
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
}