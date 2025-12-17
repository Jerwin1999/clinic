<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ActivityLogController extends AbstractController
{
    #[Route('/activity/log', name: 'app_activity_log')]
    public function index(): Response
    {
        return $this->render('activity_log/index.html.twig', [
            'controller_name' => 'ActivityLogController',
        ]);
    }
}
