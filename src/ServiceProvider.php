<?php

namespace DreamFactory\Core\AzureAD;

use DreamFactory\Core\AzureAD\Models\OAuthConfig;
use DreamFactory\Core\AzureAD\Services\OAuth;
use DreamFactory\Core\Enums\LicenseLevel;
use DreamFactory\Core\Enums\ServiceTypeGroups;
use DreamFactory\Core\Services\ServiceManager;
use DreamFactory\Core\Services\ServiceType;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        // Add our service types.
        $this->app->resolving('df.service', function (ServiceManager $df) {
            $df->addType(
                new ServiceType([
                    'name'                  => 'oauth_azure_ad',
                    'label'                 => 'Azure Active Directory OAuth',
                    'description'           => 'OAuth service for supporting Azure Active Directory authentication and API access.',
                    'group'                 => ServiceTypeGroups::OAUTH,
                    'subscription_required' => LicenseLevel::SILVER,
                    'config_handler'        => OAuthConfig::class,
                    'factory'               => function ($config) {
                        return new OAuth($config);
                    },
                ])
            );
        });
    }

    public function boot()
    {
        // add migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}