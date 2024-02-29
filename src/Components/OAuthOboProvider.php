<?php
namespace DreamFactory\Core\AzureAD\Components;

use Illuminate\Http\Request;
use GuzzleHttp\ClientInterface;
use SocialiteProviders\Manager\OAuth2\User;
use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use DreamFactory\Core\OAuth\Components\DfOAuthTwoOboProvider;
use InvalidArgumentException;

/**
 * Class OAuthOboProvider
 *
 * @package DreamFactory\Core\AzureAD\Components
 * 
 * Implementation of Microsoft OAuth 2.0 On-Behalf-Of (OBO) flow
 * https://learn.microsoft.com/en-us/entra/identity-platform/v2-oauth2-on-behalf-of-flow
 */

class OAuthOboProvider extends AbstractProvider implements ProviderInterface
{
    use DfOAuthTwoOboProvider;

    /** @var null|string */
    protected $tokenUrl = null;

    /** @var null|string */
    protected $authUrl = null;

    /** @var array */
    protected $scopes = ['User.Read'];

    /** @var string */
    protected $resource = 'https://graph.microsoft.com/';

    /** @var string */
    protected $graphUrl = 'https://graph.microsoft.com/v1.0/me';

    /** @var null|string */
    protected $clientResourceScope = null;

    /** @var null|string */
    protected $apiResourceScope = null;

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

        return json_decode($response->getBody()->getContents(), true);
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
        $postValue = $this->getClientTokenFields($code);

        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'form_params'  => $postValue,
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabaseTokenResponse($token)
    {
        $postValue = $this->getDatabaseTokenFields($token);

        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'form_params'  => $postValue,
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    public function getGraphTokenResponse($token)
    {
        $postValue = $this->getGraphTokenFields($token);

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
            'scope'         => $this->clientResourceScope,
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
    protected function getClientTokenFields($code)
    {
        $fields = [
            'grant_type'   => 'authorization_code',
            'client_id'    => $this->clientId,
            'client_secret'    => $this->clientSecret,
            'code'         => $code,
            'redirect_uri' => $this->redirectUrl,
        ];

        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDatabaseTokenFields($token)
    {
        if (empty($this->clientSecret)) {
            throw new InvalidArgumentException('The client secret is required for on-behalf-of token request.');
        }else{
            $fields = [
                'grant_type'    => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'client_id'    => $this->clientId,
                'client_secret'    => $this->clientSecret,
                'assertion'     => $token,
                'scope'         => $this->apiResourceScope,
                'requested_token_use' => 'on_behalf_of',
            ];

            return $fields;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getGraphTokenFields($token)
    {
        if (empty($this->clientSecret)) {
            throw new InvalidArgumentException('The client secret is required for on-behalf-of token request.');
        }else{
            $fields = [
                'grant_type'    => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'client_id'    => $this->clientId,
                'client_secret'    => $this->clientSecret,
                'assertion'     => $token,
                'scope'         => $this->formatScopes($this->getScopes(), $this->scopeSeparator),
                'requested_token_use' => 'on_behalf_of',
            ];

            return $fields;
        }
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

    /**
     * Sets the OAuth 2 OBO resource scopes
     *
     * @param $resource
     */
    public function setResourceScopes($clientResourceScope, $apiResourceScope)
    {
        $this->clientResourceScope = $clientResourceScope;
        $this->apiResourceScope = $apiResourceScope;
    }

}