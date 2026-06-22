<?php

namespace App\Security;

use TheNetworg\OAuth2\Client\Provider\Azure;

/**
 * Extends the thenetworg Azure provider to fix issuer validation for multi-tenant
 * endpoints (/organizations, /common). The upstream library only substitutes the
 * actual tenant ID when the configured tenant is literally "common" — it ignores
 * "organizations" (and any other multi-tenant slug), leaving "{tenantid}" literally
 * in the expected issuer string which then never matches the real token issuer.
 *
 * The fix is safe: the JWT is still cryptographically verified against Azure's public
 * keys before validateTokenClaims() is ever called. We only add the same tid-substitution
 * that the library already does for "common".
 */
class MultiTenantAzureProvider extends Azure
{
    private const MULTI_TENANT_SLUGS = ['common', 'organizations'];

    public function validateTokenClaims($tokenClaims): void
    {
        if (in_array($this->tenant, self::MULTI_TENANT_SLUGS, true)) {
            $this->tenant = $tokenClaims['tid'] ?? $this->tenant;
        }

        parent::validateTokenClaims($tokenClaims);
    }
}
