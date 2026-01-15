<?php
namespace DreamFactory\Core\AzureAD\Components;

use Illuminate\Http\Request;
use GuzzleHttp\ClientInterface;
use SocialiteProviders\Manager\OAuth2\User;
use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use DreamFactory\Core\OAuth\Components\DfOAuthTwoProvider;

class OAuthProvider extends AbstractProvider implements ProviderInterface
{
    use DfOAuthTwoProvider;

    /** @var null|string */
    protected $tokenUrl = null;

    /** @var null|string */
    protected $authUrl = null;

    /** @var array */
    protected $scopes = ['User.Read', 'GroupMember.Read.All'];

    /** @var string */
    protected $scopeSeparator = ' ';

    /** @var string */
    protected $resource = 'https://graph.microsoft.com/';

    /** @var string */
    protected $graphUrl = 'https://graph.microsoft.com/v1.0/me';

    /**
     * @param string $clientId
     * @param string $clientSecret
     * @param string $redirectUrl
     */
    public function __construct($clientId, $clientSecret, $redirectUrl)
    {
        /** @var Request $request */
        $request = \Request::instance();
        parent::__construct($request, $clientId, $clientSecret, $redirectUrl);
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            $this->authUrl, $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->tokenUrl;
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->graphUrl, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token
            ]
        ]);

        $user = json_decode($response->getBody()->getContents(), true);

        // Fetch user's group memberships
        $user['groups'] = $this->getUserGroups($token);

        return $user;
    }

    /**
     * Get user's group memberships from Microsoft Graph
     *
     * @param string $token
     * @return array
     */
    protected function getUserGroups($token)
    {
        try {
            $response = $this->getHttpClient()->get(
                'https://graph.microsoft.com/v1.0/me/memberOf',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token
                    ]
                ]
            );

            $data = json_decode($response->getBody()->getContents(), true);

            // Extract group display names from the response
            $groups = [];
            if (isset($data['value']) && is_array($data['value'])) {
                foreach ($data['value'] as $member) {
                    // Only include groups (not directory roles or other objects)
                    if (isset($member['@odata.type']) && $member['@odata.type'] === '#microsoft.graph.group') {
                        $groups[] = [
                            'id' => $member['id'] ?? null,
                            'displayName' => $member['displayName'] ?? null,
                        ];
                    }
                }
            }

            return $groups;
        } catch (\Exception $e) {
            // If group fetching fails, return empty array
            // This allows authentication to continue even if group API fails
            \Log::warning('Failed to fetch Azure AD groups: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => $user['displayName'],
            'name'     => $user['givenName'] . ' ' . $user['surname'],
            'email'    => $user['userPrincipalName'],
            'avatar'   => null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
        $postValue = $this->getTokenFields($code);

        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'form_params'  => $postValue,
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCodeFields($state = null)
    {
        $fields = [
            'client_id'     => $this->clientId,
            'scope'         => $this->formatScopes($this->getScopes(), $this->scopeSeparator),
            'response_type' => 'code',
            'redirect_uri'  => $this->redirectUrl,
        ];

        if ($this->usesState()) {
            $fields['state'] = $state;
        }

        return array_merge($fields, $this->parameters);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        $fields = [
            'grant_type'   => 'authorization_code',
            'client_id'    => $this->clientId,
            'code'         => $code,
            'redirect_uri' => $this->redirectUrl,
        ];

        if (!empty($this->clientSecret)) {
            $fields['client_secret'] = $this->clientSecret;
        }

        return $fields;
    }

    /**
     * Sets the OAuth2 endpoints based on tenant ID
     *
     * @param $tenantId
     */
    public function setEndpoints($tenantId)
    {
        $this->tokenUrl = 'https://login.microsoftonline.com/' . $tenantId . '/oauth2/v2.0/token';
        $this->authUrl = 'https://login.microsoftonline.com/' . $tenantId . '/oauth2/v2.0/authorize';
    }

    /**
     * Sets the OAuth 2 resource
     *
     * @param $resource
     */
    public function setResource($resource)
    {
        $this->resource = $resource;
    }

}