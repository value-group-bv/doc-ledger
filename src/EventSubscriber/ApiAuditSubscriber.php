<?php

namespace App\EventSubscriber;

use App\Security\ApiKeyUser;
use App\Service\AuditLogger;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/** Logs every authenticated call to the /api/* firewall, after the response has been sent. */
class ApiAuditSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly Security $security,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::TERMINATE => 'onKernelTerminate'];
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof ApiKeyUser) {
            return;
        }

        $this->auditLogger->log(
            $user->getSubsidiary()->getCode(),
            'api.request',
            sprintf('%s %s -> %d', $request->getMethod(), $request->getPathInfo(), $event->getResponse()->getStatusCode())
        );
    }
}
