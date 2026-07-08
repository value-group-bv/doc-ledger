<?php

namespace App\Security;

use App\Entity\DocSubsidiary;
use Symfony\Component\Security\Core\User\UserInterface;

/** Security principal for the stateless /api firewall — wraps the DocSubsidiary resolved from the request's API key. */
class ApiKeyUser implements UserInterface
{
    public function __construct(private readonly DocSubsidiary $subsidiary) {}

    public function getSubsidiary(): DocSubsidiary
    {
        return $this->subsidiary;
    }

    public function getRoles(): array
    {
        return ['ROLE_API'];
    }

    public function eraseCredentials(): void {}

    public function getUserIdentifier(): string
    {
        return $this->subsidiary->getCode();
    }
}
