<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/info', name: 'info_')]
class InfoController extends AbstractController
{
    #[Route(path: '', name: 'index')]
    public function info(): Response
    {
        return $this->render('info/index.html.twig');
    }
}
