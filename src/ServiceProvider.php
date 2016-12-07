<?php
namespace DreamFactory\Core\AzureAD;

use DreamFactory\Core\AzureAD\Models\OAuthConfig;
use DreamFactory\Core\AzureAD\Services\OAuth;
use DreamFactory\Core\Components\ServiceDocBuilder;
use DreamFactory\Core\Services\ServiceManager;
use DreamFactory\Core\Services\ServiceType;
use DreamFactory\Core\Enums\ServiceTypeGroups;


class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    use ServiceDocBuilder;

    public function register()
    {
        // Add our service types.
        $this->app->resolving('df.service', function (ServiceManager $df) {
            $df->addType(
                new ServiceType([
                    'name'            => 'oauth_azure_ad',
                    'label'           => 'Azure Active Directory OAuth',
                    'description'     => 'OAuth service for supporting Azure Active Directory authentication and API access.',
                    'group'           => ServiceTypeGroups::OAUTH,
                    'config_handler'  => OAuthConfig::class,
                    'default_api_doc' => function ($service) {
                        return $this->buildServiceDoc($service->id, OAuth::getApiDocInfo($service));
                    },
                    'factory'         => function ($config) {
                        return new OAuth($config);
                    },
                ])
            );
        });
    }
}