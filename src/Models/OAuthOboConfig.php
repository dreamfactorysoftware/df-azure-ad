<?php
namespace DreamFactory\Core\AzureAD\Models;

use DreamFactory\Core\Models\Role;
use DreamFactory\Core\Models\Service;
use DreamFactory\Core\Components\AppRoleMapper;
use DreamFactory\Core\Models\BaseServiceConfigModel;

class OAuthOboConfig extends BaseServiceConfigModel
{
    use AppRoleMapper;

    /** @var string */
    protected $table = 'azure_ad_obo_config';

    /** @var array */
    protected $fillable = [
        'service_id',
        'default_role',
        'tenant_id',
        'client_id',
        'client_secret',
        'redirect_url',
        'icon_class',
        'user_resource',
        'client_resource_scope',
        'api_resource_scope',
    ];

    protected $encrypted = ['client_secret'];

    protected $protected = ['client_secret'];

    protected $casts = [
        'service_id'   => 'integer',
        'default_role' => 'integer',
    ];

    protected $rules = [
        'client_id'    => 'required',
        'client_secret'    => 'required',
        'redirect_url' => 'required',
        'tenant_id'    => 'required',
        'client_resource_scope'    => 'required',
        'api_resource_scope'    => 'required',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'id');
    }

    /**
     * @param array $schema
     */
    protected static function prepareConfigSchemaField(array &$schema)
    {
        parent::prepareConfigSchemaField($schema);

        switch ($schema['name']) {
            case 'default_role':
                $roles = Role::whereIsActive(1)->get();
                $roleList = [];
                foreach ($roles as $role) {
                    $roleList[] = [
                        'label' => $role->name,
                        'name'  => $role->id
                    ];
                }

                $schema['type'] = 'picklist';
                $schema['values'] = $roleList;
                $schema['description'] = 'Select a default role for users logging in with this OAuth service type.';
                break;
            case 'client_id':
                $schema['label'] = 'Client ID';
                $schema['description'] =
                    'A public string used by the service to identify your app and to build authorization URLs.';
                break;
            case 'client_secret':
                $schema['label'] = 'Client Secret';
                $schema['description'] =
                    'A private string used by the service to authenticate the identity of the application.';
                break;
            case 'redirect_url':
                $schema['label'] = 'Redirect URL';
                $schema['description'] = 'The location the user will be redirected to after a successful login.';
                break;

            case 'tenant_id':
                $schema['label'] = 'Tenant ID';
                $schema['description'] =
                    'This is a value in the path of the request that can be used to identify who can sign into the application.';
                break;
            case 'user_resource':
                $schema['label'] = 'User Resource';
                $schema['default'] = 'https://graph.microsoft.com/';
                $schema['description'] = 'The API resource used to load the authenticated SSO users information.';
                break;
            case 'client_resource_scope':
                $schema['label'] = 'Client Resource Scope';
                $schema['description'] = 'A scope created in the Azure AD dashboard on the client API resource, known as API A in the On-Behalf-Of flow documentation.';
                break;
            case 'api_resource_scope':
                $schema['label'] = 'API Resource Scope';
                $schema['description'] = 'A scope created in the Azure AD dashboard on the end API resource, known as API B in the On-Behalf-Of flow documentation.';
                break;
            case 'icon_class':
                $schema['description'] = 'The icon to display for this OAuth service.';
                break;
        }
    }
}