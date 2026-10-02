<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use TheNetworg\OAuth2\Client\Provider\AzureResourceOwner;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

class AzureAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $users,
        private readonly RouterInterface $router,
        private readonly RequestStack $requestStack,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'oidc_azure_callback';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('azure');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var AzureResourceOwner $azureUser */
                $azureUser = $client->fetchUserFromToken($accessToken);

                $email = $azureUser->getUpn() ?? $azureUser->getEmail() ?? null;

                if (!$email) {
                    throw new AuthenticationException('No email address returned from Azure AD.');
                }

                $email = strtolower(trim($email));

                $user = $this->users->findOneBy(['email' => $email]);

                if (!$user instanceof User) {
                    // Auto-create user on first login
                    $user = new User();
                    $user->setEmail($email);
                    $user->setRoles(['ROLE_USER']);
                    $this->em->persist($user);
                }

                // Only fills an empty name, so a name set by an admin is kept
                if (!$user->getDisplayName()) {
                    $user->setDisplayName(self::firstNameOf($azureUser));
                }

                $this->em->flush();

                return $user;
            })
        );
    }

    /**
     * given_name is an optional claim that v2.0 ID tokens usually leave out, so fall back
     * to the full `name` claim (included with the profile scope) up to the first space.
     */
    private static function firstNameOf(AzureResourceOwner $azureUser): ?string
    {
        $firstName = trim((string) $azureUser->getFirstName());
        if ($firstName === '') {
            $firstName = strtok(trim((string) $azureUser->claim('name')), ' ') ?: '';
        }

        return $firstName !== '' ? mb_substr($firstName, 0, 255) : null;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        if ($user instanceof User) {
            $this->auditLogger->log($user->getEmail(), 'auth.login', 'Signed in');
        }

        return new RedirectResponse($this->router->generate('ledger_index'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $session = $this->requestStack->getSession();

        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add('error', strtr($exception->getMessageKey(), $exception->getMessageData()));
        }

        return new RedirectResponse($this->router->generate('app_login'));
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->router->generate('app_login'));
    }
}
