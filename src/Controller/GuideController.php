<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/guide', name: 'guide_')]
class GuideController extends AbstractController
{
    #[Route(path: '', name: 'index')]
    public function guide(): Response
    {
        return $this->render('guide/index.html.twig');
    }
}
