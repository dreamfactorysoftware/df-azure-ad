<?php
namespace DreamFactory\Core\AzureAD\Services;

use DreamFactory\Core\OAuth\Services\BaseOAuthService;
use DreamFactory\Core\AzureAD\Components\OAuthOboProvider;
use \Illuminate\Support\Arr;

class OAuthObo extends BaseOAuthService
{
    const PROVIDER_NAME = 'azure-ad-obo';

    /** @inheritdoc */
    protected function setProvider($config)
    {
        $clientId = Arr::get($config, 'client_id');
        $clientSecret = Arr::get($config, 'client_secret');
        $redirectUrl = Arr::get($config, 'redirect_url');
        $tenantId = Arr::get($config, 'tenant_id');
        $resource = Arr::get($config, 'resource');

        $this->provider = new OAuthOboProvider($clientId, $clientSecret, $redirectUrl);
        $this->provider->setEndpoints($tenantId);
        $this->provider->setResource($resource);
    }

    /** @inheritdoc */
    public function getProviderName()
    {
        return self::PROVIDER_NAME;
    }
}