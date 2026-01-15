<?php
namespace DreamFactory\Core\AzureAD\Models;

use DreamFactory\Core\Models\Role;
use DreamFactory\Core\Models\Service;
use DreamFactory\Core\Components\AppRoleMapper;
use DreamFactory\Core\Models\BaseServiceConfigModel;

class OAuthConfig extends BaseServiceConfigModel
{
    use AppRoleMapper {
        getConfigSchema as protected getAppRoleMapperSchema;
    }

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
        'map_group_to_role',
    ];

    protected $encrypted = ['client_secret'];

    protected $protected = ['client_secret'];

    protected $casts = [
        'service_id'        => 'integer',
        'default_role'      => 'integer',
        'map_group_to_role' => 'boolean',
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
     * Get config with group role mappings
     *
     * @param int $id
     * @param mixed $local_config
     * @param bool $protect
     * @return array|null
     */
    public static function getConfig($id, $local_config = null, $protect = true)
    {
        $config = parent::getConfig($id, $local_config, $protect);

        if ($config) {
            // Include group role mappings
            $groupRoleMaps = RoleAzureAD::where('role_id', '>', 0)->get();
            $config['group_role_map'] = [];

            foreach ($groupRoleMaps as $map) {
                $config['group_role_map'][] = [
                    'role_id' => $map->role_id,
                    'group_name' => $map->group_name,
                ];
            }
        }

        return $config;
    }

    /**
     * Set config with group role mappings
     *
     * @param int $id
     * @param array $config
     * @param mixed $local_config
     * @return array
     */
    public static function setConfig($id, $config, $local_config = null)
    {
        // Extract group role map if present
        $groupRoleMap = array_get($config, 'group_role_map', []);
        unset($config['group_role_map']);

        // Save main config
        $result = parent::setConfig($id, $config, $local_config);

        // Update group role mappings
        if (isset($groupRoleMap)) {
            // Delete existing mappings
            RoleAzureAD::query()->delete();

            // Create new mappings
            foreach ($groupRoleMap as $map) {
                if (!empty($map['role_id']) && !empty($map['group_name'])) {
                    RoleAzureAD::create([
                        'role_id' => $map['role_id'],
                        'group_name' => $map['group_name'],
                    ]);
                }
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public static function getConfigSchema()
    {
        // Get schema from AppRoleMapper trait (includes app_role_map)
        $schema = static::getAppRoleMapperSchema();

        // Add group role mapping field
        $schema[] = [
            'name'        => 'group_role_map',
            'label'       => 'Entra ID Group to Role Mapping',
            'description' => 'Map Entra ID (Azure AD) group memberships to DreamFactory roles. When enabled, users will be assigned roles based on their group membership.',
            'type'        => 'array',
            'required'    => false,
            'allow_null'  => true,
            'items'       => RoleAzureAD::getConfigSchema(),
        ];

        return $schema;
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
                $schema['default'] = 'https://graph.microsoft.com/';
                $schema['description'] = 'The App ID URI of the web API (secured resource).';
                break;
            case 'icon_class':
                $schema['description'] = 'The icon to display for this OAuth service.';
                break;
            case 'map_group_to_role':
                $schema['label'] = 'Map Entra ID Groups to Roles';
                $schema['description'] = 'Enable mapping of Entra ID (Azure AD) group memberships to DreamFactory roles.';
                break;
        }
    }
}