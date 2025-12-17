<?php
// src/Entity/ActivityLog.php

namespace App\Entity;

use App\Repository\ActivityLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActivityLogRepository::class)]
#[ORM\Table(name: 'activity_log')]
#[ORM\HasLifecycleCallbacks]
class ActivityLog
{
    // Define action constants for consistent usage
    public const ACTION_LOGIN = 'LOGIN';
    public const ACTION_LOGOUT = 'LOGOUT';
    public const ACTION_CREATE = 'CREATE';
    public const ACTION_UPDATE = 'UPDATE';
    public const ACTION_DELETE = 'DELETE';
    
    // Define role constants
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_STAFF = 'ROLE_STAFF';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $userId = null;

    #[ORM\Column(length: 180)]
    private ?string $username = null;

    #[ORM\Column(length: 50)]
    private ?string $role = null;

    #[ORM\Column(length: 50)]
    private ?string $action = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $targetData = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $timestamp = null;

    public function __construct()
    {
        $this->timestamp = new \DateTime();
    }

    #[ORM\PrePersist]
    public function setTimestampValue(): void
    {
        if ($this->timestamp === null) {
            $this->timestamp = new \DateTime();
        }
    }

    // Getters and Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): static
    {
        $this->userId = $userId;
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    public function getTargetData(): ?string
    {
        return $this->targetData;
    }

    public function setTargetData(?string $targetData): static
    {
        $this->targetData = $targetData;
        return $this;
    }

    public function getTimestamp(): ?\DateTimeInterface
    {
        return $this->timestamp;
    }

    public function setTimestamp(\DateTimeInterface $timestamp): static
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    // Helper method to format target data based on rubric examples
    public function formatTargetData(string $entityType, string $entityName, ?int $entityId = null): static
    {
        $target = $entityType . ': ' . $entityName;
        if ($entityId !== null) {
            $target .= ' (ID: ' . $entityId . ')';
        }
        $this->targetData = $target;
        return $this;
    }

    // Create a log entry for user actions
    public static function createUserActionLog(
        int $userId,
        string $username,
        string $role,
        string $action,
        ?string $targetEntity = null,
        ?int $targetId = null,
        ?string $targetName = null
    ): self {
        $log = new self();
        $log->setUserId($userId);
        $log->setUsername($username);
        $log->setRole($role);
        $log->setAction($action);
        
        if ($targetEntity !== null && $targetName !== null) {
            $log->formatTargetData($targetEntity, $targetName, $targetId);
        }
        
        return $log;
    }

    // Get readable action name
    public function getActionReadable(): string
    {
        return match($this->action) {
            self::ACTION_LOGIN => 'Login',
            self::ACTION_LOGOUT => 'Logout',
            self::ACTION_CREATE => 'Create',
            self::ACTION_UPDATE => 'Update',
            self::ACTION_DELETE => 'Delete',
            default => $this->action,
        };
    }

    // Get readable role name
    public function getRoleReadable(): string
    {
        return match($this->role) {
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_STAFF => 'Staff',
            default => $this->role,
        };
    }

    // Format timestamp for display
    public function getFormattedTimestamp(): string
    {
        return $this->timestamp ? $this->timestamp->format('Y-m-d H:i:s') : '';
    }

    public function __toString(): string
    {
        return sprintf(
            '[%s] %s (%s) %s %s',
            $this->getFormattedTimestamp(),
            $this->username,
            $this->getRoleReadable(),
            $this->getActionReadable(),
            $this->targetData ?: ''
        );
    }
}