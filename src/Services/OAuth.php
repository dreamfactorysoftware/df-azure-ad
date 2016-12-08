<?php
namespace DreamFactory\Core\AzureAD\Services;

use DreamFactory\Core\OAuth\Services\BaseOAuthService;
use DreamFactory\Core\AzureAD\Components\OAuthProvider;

class OAuth extends BaseOAuthService
{
    const PROVIDER_NAME = 'azure-ad';

    /** @inheritdoc */
    protected function setProvider($config)
    {
        $clientId = array_get($config, 'client_id');
        $clientSecret = array_get($config, 'client_secret');
        $redirectUrl = array_get($config, 'redirect_url');
        $tenantId = array_get($config, 'tenant_id');
        $resource = array_get($config, 'resource');

        $this->provider = new OAuthProvider($clientId, $clientSecret, $redirectUrl);
        $this->provider->setEndpoints($tenantId);
        $this->provider->setResource($resource);
    }

    /** @inheritdoc */
    public function getProviderName()
    {
        return self::PROVIDER_NAME;
    }
}