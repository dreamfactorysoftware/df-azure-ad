<?php
namespace DreamFactory\Core\AzureAD\Models;

use DreamFactory\Core\Models\BaseModel;
use DreamFactory\Core\Models\Role;

/**
 * RoleAzureAD
 *
 * @property integer  $role_id
 * @property string   $group_name
 * @method static \Illuminate\Database\Query\Builder|RoleAzureAD whereRoleId($value)
 * @method static \Illuminate\Database\Query\Builder|RoleAzureAD whereGroupName($value)
 */
class RoleAzureAD extends BaseModel
{
    /** @type string */
    protected $table = 'role_azure_ad';

    /** @type string */
    protected $primaryKey = 'role_id';

    /** @type array */
    protected $fillable = ['role_id', 'group_name'];

    /** @type bool */
    public $timestamps = false;

    /** @type bool */
    public $incrementing = false;

    /**
     * Get the config schema for group role mapping
     *
     * @return array
     */
    public static function getConfigSchema()
    {
        $roles = Role::whereIsActive(1)->get();
        $roleList = [];

        foreach ($roles as $role) {
            $roleList[] = [
                'label' => $role->name,
                'name'  => $role->id
            ];
        }

        return [
            [
                'name'        => 'role_id',
                'label'       => 'Role',
                'type'        => 'picklist',
                'required'    => true,
                'values'      => $roleList,
                'description' => 'Select the DreamFactory role to assign.'
            ],
            [
                'name'        => 'group_name',
                'label'       => 'Entra ID Group Name',
                'type'        => 'string',
                'required'    => true,
                'description' => 'Enter the display name of the Entra ID (Azure AD) group.'
            ],
        ];
    }
}
