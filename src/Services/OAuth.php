<?php
namespace DreamFactory\Core\AzureAD\Services;

use Carbon\Carbon;
use DreamFactory\Core\Models\User;
use DreamFactory\Core\OAuth\Services\BaseOAuthService;
use DreamFactory\Core\AzureAD\Components\OAuthProvider;
use DreamFactory\Core\AzureAD\Models\RoleAzureAD;
use Laravel\Socialite\Contracts\User as OAuthUserContract;
use \Illuminate\Support\Arr;

class OAuth extends BaseOAuthService
{
    const PROVIDER_NAME = 'azure-ad';

    /** @var bool */
    protected $mapGroupToRole = false;

    /** @inheritdoc */
    protected function setProvider($config)
    {
        $clientId = Arr::get($config, 'client_id');
        $clientSecret = Arr::get($config, 'client_secret');
        $redirectUrl = Arr::get($config, 'redirect_url');
        $tenantId = Arr::get($config, 'tenant_id');
        $resource = Arr::get($config, 'resource');
        $this->mapGroupToRole = Arr::get($config, 'map_group_to_role', false);

        $this->provider = new OAuthProvider($clientId, $clientSecret, $redirectUrl);
        $this->provider->setEndpoints($tenantId);
        $this->provider->setResource($resource);
    }

    /** @inheritdoc */
    public function getProviderName()
    {
        return self::PROVIDER_NAME;
    }

    /**
     * Get role based on Entra ID group membership
     *
     * @param array $groups Array of user's group information
     * @return int|null
     */
    protected function getRoleByGroup(array $groups)
    {
        if (!$this->mapGroupToRole || empty($groups)) {
            return null;
        }

        // Iterate through user's groups and find first matching role
        foreach ($groups as $group) {
            $groupName = Arr::get($group, 'displayName');
            if (!empty($groupName)) {
                $role = $this->findRoleByGroupName($groupName);
                if (!empty($role)) {
                    return $role->role_id;
                }
            }
        }

        return null;
    }

    /**
     * Find a DreamFactory role by Entra ID group name
     *
     * @param string $groupName
     * @return RoleAzureAD|null
     */
    protected function findRoleByGroupName($groupName)
    {
        if (empty($groupName)) {
            return null;
        }

        return RoleAzureAD::whereGroupName($groupName)->first();
    }

    /**
     * Override to support group-based role mapping
     * {@inheritdoc}
     */
    public function createShadowOAuthUser(OAuthUserContract $OAuthUser)
    {
        $fullName = $OAuthUser->getName() || $OAuthUser->getNickname();
        @list($firstName, $lastName) = explode(' ', $fullName);

        $email = $OAuthUser->getEmail();
        $serviceName = $this->getName();
        $providerName = $this->getProviderName();

        if (empty($email)) {
            $email = $OAuthUser->getId() . '+' . $serviceName . '@' . $serviceName . '.com';
        } else {
            list($emailId, $domain) = explode('@', $email);
            $email = $emailId . '+' . $serviceName . '@' . $domain;
        }

        $user = User::whereEmail($email)->first();

        if (empty($user)) {
            $data = [
                'username'       => $email,
                'name'           => $fullName,
                'first_name'     => $firstName,
                'last_name'      => $lastName,
                'email'          => $email,
                'is_active'      => true,
                'oauth_provider' => $providerName,
            ];

            $user = User::create($data);
        }

        // Priority: Group mapping > App role map > Default role
        $roleToApply = null;

        // Check for group-based role mapping (highest priority)
        if ($this->mapGroupToRole) {
            $userRaw = $OAuthUser->getRaw();
            $groups = Arr::get($userRaw, 'groups', []);
            $roleToApply = $this->getRoleByGroup($groups);
        }

        // If no group-based role found, check fallbacks
        if (empty($roleToApply)) {
            if (!empty($serviceId = $this->getServiceId())) {
                // Check if there are app-specific role mappings
                // Note: applyAppRoleMapByService handles its own logic
            }

            // Fall back to default role if no group match
            if (empty($roleToApply) && !empty($defaultRole = $this->getDefaultRole())) {
                $roleToApply = $defaultRole;
            }
        }

        // Always refresh role assignments on login to reflect current group membership
        if (!empty($roleToApply)) {
            // Clear existing role assignments for this user
            \DB::table('user_to_app_to_role')->where('user_id', $user->id)->delete();

            // Re-apply the current role based on latest group membership
            User::applyDefaultUserAppRole($user, $roleToApply);
        } elseif (!empty($serviceId = $this->getServiceId())) {
            // No group mapping, use app-specific roles
            // Clear and re-apply to ensure they're current
            \DB::table('user_to_app_to_role')->where('user_id', $user->id)->delete();
            User::applyAppRoleMapByService($user, $serviceId);
        }

        return $user;
    }
}