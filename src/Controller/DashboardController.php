<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Doctor;
use App\Entity\Patient;
use App\Entity\Appointment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        // Get simple counts for all entities
        $doctorCount = $entityManager->getRepository(Doctor::class)->count([]);
        $patientCount = $entityManager->getRepository(Patient::class)->count([]);
        $appointmentCount = $entityManager->getRepository(Appointment::class)->count([]);
        
        // Get appointment status breakdown
        $appointmentStatus = $entityManager->getRepository(Appointment::class)
            ->createQueryBuilder('a')
            ->select('a.status, COUNT(a.id) as count')
            ->groupBy('a.status')
            ->getQuery()
            ->getResult();
        
        // Convert to associative array
        $statusCounts = [];
        foreach ($appointmentStatus as $status) {
            $statusCounts[$status['status']] = $status['count'];
        }
        
        // Prepare dashboard data
        $dashboardData = [
            'doctor_count' => $doctorCount,
            'patient_count' => $patientCount,
            'appointment_count' => $appointmentCount,
            'status_counts' => $statusCounts,
        ];
        
        // Add user count only for ADMIN
        if ($this->isGranted('ROLE_ADMIN')) {
            $userCount = $entityManager->getRepository(User::class)->count([]);
            $dashboardData['user_count'] = $userCount;
        }
        
        return $this->render('dashboard/index.html.twig', $dashboardData);
    }
}