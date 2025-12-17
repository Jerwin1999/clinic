<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminLoginController extends AbstractController
{
    #[Route('/admin/login', name: 'admin_login', methods: ['GET'])]
    public function login(): Response
    {
        return $this->render('admin/login.html.twig');
    }
}
