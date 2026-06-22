<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AuthController extends AbstractController
{
    public function __construct(private readonly ClientRegistry $clientRegistry) {}

    #[Route(path: '/login', name: 'app_login')]
    public function login(): Response
    {
        return $this->render('auth/login.html.twig');
    }

    #[Route(path: '/login/oidc/start', name: 'oidc_azure_start', methods: ['GET'])]
    public function oidcStart(): Response
    {
        return $this->clientRegistry
            ->getClient('azure')
            ->redirect(['openid', 'profile', 'email'], ['prompt' => 'select_account']);
    }

    #[Route(path: '/login/oidc/callback', name: 'oidc_azure_callback')]
    public function oidcCallback(): never
    {
        // Intercepted by AzureAuthenticator — this code is never reached.
        throw new \LogicException('AzureAuthenticator did not intercept this request.');
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This should be intercepted by the logout listener.');
    }
}
