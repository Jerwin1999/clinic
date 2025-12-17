<?php

namespace App\Service;

use App\Entity\ActivityLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ActivityLogger
{
    private $entityManager;
    
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    
    /**
     * Main logging function - logs any action
     */
    public function log(UserInterface $user, string $action, ?string $targetData = null): void
    {
        // Get user info
        $userId = $user->getId();
        $username = $user->getUsername();
        
        // Get primary role
        $roles = $user->getRoles();
        $primaryRole = !empty($roles) ? $roles[0] : 'ROLE_USER';
        
        // Create log entry
        $log = new ActivityLog();
        $log->setUserId($userId);
        $log->setUsername($username);
        $log->setRole($primaryRole);
        $log->setAction($action);
        
        // If targetData is provided, set it directly
        if ($targetData !== null) {
            $log->setTargetData($targetData);
        }
        
        // Save to database
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
    
    /**
     * Log user login
     */
    public function logLogin(UserInterface $user): void
    {
        $this->log($user, ActivityLog::ACTION_LOGIN);
    }
    
    /**
     * Log user logout
     */
    public function logLogout(UserInterface $user): void
    {
        $this->log($user, ActivityLog::ACTION_LOGOUT);
    }
    
    /**
     * Log user creation (admin creates staff/user)
     */
    public function logUserCreation(UserInterface $performedBy, UserInterface $createdUser): void
    {
        $targetData = sprintf(
            'User: %s (ID: %d)',
            $createdUser->getUsername(),
            $createdUser->getId()
        );
        
        $this->log($performedBy, ActivityLog::ACTION_CREATE, $targetData);
    }
    
    /**
     * Log user update
     */
    public function logUserUpdate(UserInterface $performedBy, UserInterface $updatedUser): void
    {
        $targetData = sprintf(
            'User: %s (ID: %d)',
            $updatedUser->getUsername(),
            $updatedUser->getId()
        );
        
        $this->log($performedBy, ActivityLog::ACTION_UPDATE, $targetData);
    }
    
    /**
     * Log user deletion
     */
    public function logUserDeletion(UserInterface $performedBy, string $deletedUsername, int $deletedId): void
    {
        $targetData = sprintf(
            'User: %s (ID: %d)',
            $deletedUsername,
            $deletedId
        );
        
        $this->log($performedBy, ActivityLog::ACTION_DELETE, $targetData);
    }
    
    /**
     * Log patient creation
     */
    public function logPatientCreation(UserInterface $user, $patient): void
    {
        $targetData = sprintf(
            'Patient: %s (ID: %d)',
            $patient->getName(),
            $patient->getId()
        );
        
        $this->log($user, ActivityLog::ACTION_CREATE, $targetData);
    }
    
    /**
     * Log patient update
     */
    public function logPatientUpdate(UserInterface $user, $patient): void
    {
        $targetData = sprintf(
            'Patient: %s (ID: %d)',
            $patient->getName(),
            $patient->getId()
        );
        
        $this->log($user, ActivityLog::ACTION_UPDATE, $targetData);
    }
    
    /**
     * Log patient deletion
     */
    public function logPatientDeletion(UserInterface $user, $patient): void
    {
        $targetData = sprintf(
            'Patient: %s (ID: %d)',
            $patient->getName(),
            $patient->getId()
        );
        
        $this->log($user, ActivityLog::ACTION_DELETE, $targetData);
    }
    
    /**
     * Log doctor creation
     */
    public function logDoctorCreation(UserInterface $user, $doctor): void
    {
        $targetData = sprintf(
            'Doctor: %s (ID: %d)',
            $doctor->getName(),
            $doctor->getId()
        );
        
        $this->log($user, ActivityLog::ACTION_CREATE, $targetData);
    }
    
    /**
     * Log doctor update
     */
    public function logDoctorUpdate(UserInterface $user, $doctor): void
    {
        $targetData = sprintf(
            'Doctor: %s (ID: %d)',
            $doctor->getName(),
            $doctor->getId()
        );
        
        $this->log($user, ActivityLog::ACTION_UPDATE, $targetData);
    }
    
    /**
     * Log doctor deletion
     */
    public function logDoctorDeletion(UserInterface $user, $doctor): void
    {
        $targetData = sprintf(
            'Doctor: %s (ID: %d)',
            $doctor->getName(),
            $doctor->getId()
        );
        
        $this->log($user, ActivityLog::ACTION_DELETE, $targetData);
    }
    
    /**
     * Log appointment creation - SAFE VERSION
     */
    public function logAppointmentCreation(UserInterface $user, $appointment): void
    {
        try {
            $patientName = 'Unknown';
            $doctorName = 'Unknown';
            
            if ($appointment->getPatient() && method_exists($appointment->getPatient(), 'getName')) {
                $patientName = $appointment->getPatient()->getName() ?? 'Unknown';
            }
            
            if ($appointment->getDoctor() && method_exists($appointment->getDoctor(), 'getName')) {
                $doctorName = $appointment->getDoctor()->getName() ?? 'Unknown';
            }
            
            $targetData = sprintf(
                'Appointment: ID %d (Patient: %s, Doctor: %s)',
                $appointment->getId(),
                $patientName,
                $doctorName
            );
        } catch (\Exception $e) {
            // Fallback if there's any error
            $targetData = sprintf('Appointment: ID %d', $appointment->getId());
        }
        
        $this->log($user, ActivityLog::ACTION_CREATE, $targetData);
    }
    
    /**
     * Log appointment update
     */
    public function logAppointmentUpdate(UserInterface $user, $appointment): void
    {
        $targetData = sprintf('Appointment: ID %d', $appointment->getId());
        $this->log($user, ActivityLog::ACTION_UPDATE, $targetData);
    }
    
    /**
     * Log appointment deletion
     */
    public function logAppointmentDeletion(UserInterface $user, $appointment): void
    {
        $targetData = sprintf('Appointment: ID %d', $appointment->getId());
        $this->log($user, ActivityLog::ACTION_DELETE, $targetData);
    }
    
    /**
     * Log password change
     */
    public function logPasswordChange(UserInterface $user): void
    {
        $this->log($user, 'PASSWORD_CHANGE');
    }
    
    /**
     * Log profile update
     */
    public function logProfileUpdate(UserInterface $user): void
    {
        $this->log($user, 'PROFILE_UPDATE');
    }
    
    /**
     * Alternative: Log with basic info
     */
    public function logWithInfo(int $userId, string $username, string $role, string $action, ?string $targetData = null): void
    {
        $log = new ActivityLog();
        $log->setUserId($userId);
        $log->setUsername($username);
        $log->setRole($role);
        $log->setAction($action);
        
        if ($targetData !== null) {
            $log->setTargetData($targetData);
        }
        
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
}