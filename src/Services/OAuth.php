<?php
namespace DreamFactory\Core\AzureAD\Services;

use DreamFactory\Core\OAuth\Services\BaseOAuthService;
use DreamFactory\Core\AzureAD\Components\OAuthProvider;
use \Illuminate\Support\Arr;

class OAuth extends BaseOAuthService
{
    const PROVIDER_NAME = 'azure-ad';

    /** @inheritdoc */
    protected function setProvider($config)
    {
        $clientId = Arr::get($config, 'client_id');
        $clientSecret = Arr::get($config, 'client_secret');
        $redirectUrl = Arr::get($config, 'redirect_url');
        $tenantId = Arr::get($config, 'tenant_id');
        $resource = Arr::get($config, 'resource');

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