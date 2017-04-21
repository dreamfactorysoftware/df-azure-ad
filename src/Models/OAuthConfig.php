<?php
namespace DreamFactory\Core\AzureAD\Models;

use DreamFactory\Core\Models\Role;
use DreamFactory\Core\Models\Service;
use DreamFactory\Core\Components\AppRoleMapper;
use DreamFactory\Core\Models\BaseServiceConfigModel;

class OAuthConfig extends BaseServiceConfigModel
{
    use AppRoleMapper;

    /** @var string */
    protected $table = 'azure_ad_config';

    /** @var array */
    protected $fillable = [
        'service_id',
        'default_role',
        'client_id',
        'client_secret',
        'redirect_url',
        'icon_class',
        'tenant_id',
        'resource',
    ];

    protected $encrypted = ['client_secret'];

    protected $protected = ['client_secret'];

    protected $casts = [
        'service_id'   => 'integer',
        'default_role' => 'integer',
    ];

    protected $rules = [
        'client_id'    => 'required',
        'redirect_url' => 'required',
        'tenant_id'    => 'required'
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
            case 'resource':
                $schema['label'] = 'Resource';
                $schema['description'] = 'The App ID URI of the web API (secured resource).';
                break;
            case 'icon_class':
                $schema['description'] = 'The icon to display for this OAuth service.';
                break;
        }
    }
}