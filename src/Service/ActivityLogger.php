<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\User;
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
        $username = $user->getUsername(); // Your User entity uses username field
        
        // Get primary role
        $roles = $user->getRoles();
        $primaryRole = !empty($roles) ? $roles[0] : 'ROLE_USER';
        
        // Create log entry
        $log = new ActivityLog();
        $log->setUserId($userId);
        $log->setUsername($username);
        $log->setRole($primaryRole);
        $log->setAction($action);
        $log->setTargetData($targetData);
        $log->setTimestamp(new \DateTime());
        
        // Save to database
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
    
    /**
     * Log user login
     */
    public function logLogin(UserInterface $user): void
    {
        $this->log($user, 'LOGIN');
    }
    
    /**
     * Log user logout
     */
    public function logLogout(UserInterface $user): void
    {
        $this->log($user, 'LOGOUT');
    }
    
    /**
     * Log user creation (admin creates staff)
     */
    public function logUserCreation(UserInterface $performedBy, UserInterface $createdUser): void
    {
        $roles = $createdUser->getRoles();
        $role = !empty($roles) ? $roles[0] : 'ROLE_USER';
        
        $targetData = sprintf(
            'User: %s (ID: %d, Role: %s)',
            $createdUser->getUsername(),
            $createdUser->getId(),
            $role
        );
        
        $this->log($performedBy, 'CREATE_USER', $targetData);
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
        
        $this->log($performedBy, 'UPDATE_USER', $targetData);
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
        
        $this->log($performedBy, 'DELETE_USER', $targetData);
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
        
        $this->log($user, 'CREATE_PATIENT', $targetData);
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
        
        $this->log($user, 'UPDATE_PATIENT', $targetData);
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
        
        $this->log($user, 'DELETE_PATIENT', $targetData);
    }
    
    /**
     * Log doctor creation
     */
    public function logDoctorCreation(UserInterface $user, $doctor): void
    {
        $targetData = sprintf(
            'Doctor: %s (ID: %d, Specialization: %s)',
            $doctor->getName(),
            $doctor->getId(),
            $doctor->getSpecialization()
        );
        
        $this->log($user, 'CREATE_DOCTOR', $targetData);
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
        
        $this->log($user, 'UPDATE_DOCTOR', $targetData);
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
        
        $this->log($user, 'DELETE_DOCTOR', $targetData);
    }
    
    /**
     * Log appointment creation
     */
    public function logAppointmentCreation(UserInterface $user, $appointment): void
    {
        $targetData = sprintf(
            'Appointment: ID %d (Patient: %s, Doctor: %s, Date: %s)',
            $appointment->getId(),
            $appointment->getPatient()->getName(),
            $appointment->getDoctor()->getName(),
            $appointment->getDate()->format('Y-m-d H:i')
        );
        
        $this->log($user, 'CREATE_APPOINTMENT', $targetData);
    }
    
    /**
     * Log appointment update
     */
    public function logAppointmentUpdate(UserInterface $user, $appointment): void
    {
        $targetData = sprintf(
            'Appointment: ID %d',
            $appointment->getId()
        );
        
        $this->log($user, 'UPDATE_APPOINTMENT', $targetData);
    }
    
    /**
     * Log appointment deletion
     */
    public function logAppointmentDeletion(UserInterface $user, $appointment): void
    {
        $targetData = sprintf(
            'Appointment: ID %d',
            $appointment->getId()
        );
        
        $this->log($user, 'DELETE_APPOINTMENT', $targetData);
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
}