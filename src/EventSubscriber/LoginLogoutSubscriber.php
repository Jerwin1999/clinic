<?php

namespace App\EventSubscriber;

use App\Service\ActivityLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use App\Entity\ActivityLog;

class LoginLogoutSubscriber implements EventSubscriberInterface
{
    private $activityLogger;
    
    public function __construct(ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }
    
    public static function getSubscribedEvents(): array
    {
        return [
            InteractiveLoginEvent::class => 'onLogin',
            LogoutEvent::class => 'onLogout',
        ];
    }
    
    public function onLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        $this->activityLogger->log($user, ActivityLog::ACTION_LOGIN);
    }
    
    public function onLogout(LogoutEvent $event): void
    {
        $token = $event->getToken();
        if ($token !== null) {
            $user = $token->getUser();
            if ($user instanceof \Symfony\Component\Security\Core\User\UserInterface) {
                $this->activityLogger->log($user, ActivityLog::ACTION_LOGOUT);
            }
        }
    }
}