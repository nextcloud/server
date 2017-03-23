<?php

namespace Office365\PHP\Client\Runtime\Auth;

use Office365\PHP\Client\Runtime\Utilities\ClientCredential;
use Office365\PHP\Client\Runtime\Utilities\RequestOptions;
use Office365\PHP\Client\Runtime\Utilities\UserCredentials;

/**
 * Authentication context for Azure AD/Office 365.
 *
 */
class AuthenticationContext implements IAuthenticationContext
{
    /**
     * @var BaseTokenProvider
     */
    private $provider;

    /**
     * @var string
     */
    private $authorityUrl;

    /**
     * AuthenticationContext constructor.
     * @param string $authorityUrl
     */
    public function __construct($authorityUrl)
    {
        $this->authorityUrl = $authorityUrl;
    }

    /**
     * Gets URL of the authorize endpoint including the query parameters.
     * @param string $authorizeUrl
     * @param string $clientId
     * @param string $redirectUrl
     * @param array $parameters
     * @return string
     */
    public function getAuthorizationRequestUrl($authorizeUrl, $clientId, $redirectUrl, $parameters = [])
    {
        $parameters = array_merge($parameters, array(
            'response_type' => 'code',
            'client_id' => $clientId,
            //'nonce' => $stateGuid->toString(),
            'redirect_uri' => $redirectUrl,
            //'post_logout_redirect_uri' => $redirectUrl,
            //'response_mode' => 'form_post',
            //'scope' => 'openid+profile'
        ));
        return $authorizeUrl . "?" . http_build_query($parameters);
    }

    /**
     * Acquire security token from STS
     * @param string $username
     * @param string $password
     */
    public function acquireTokenForUser($username, $password)
    {
        $this->provider = new SamlTokenProvider($this->authorityUrl);
        $parameters = array(
            'username' => $username,
            'password' => $password
        );
        $this->provider->acquireToken($parameters);
    }

    /**
     * @param string $resource
     * @param ClientCredential $clientCredentials
     */
    public function acquireTokenForClientCredential($resource, $clientCredentials)
    {
        $this->provider = new OAuthTokenProvider($this->authorityUrl);
        $parameters = array(
            'grant_type' => 'client_credentials',
            'client_id' => $clientCredentials->ClientId,
            'client_secret' => $clientCredentials->ClientSecret,
            'scope' => $resource,
            'resource' => $resource
        );
        $this->provider->acquireToken($parameters);
    }

    /**
     * @param $resource
     * @param $clientId
     * @param $clientSecret
     * @param $refreshToken
     * @param $redirectUri
     */
    public function exchangeRefreshToken($resource, $clientId, $clientSecret, $refreshToken, $redirectUri)
    {
        $this->provider = new OAuthTokenProvider($this->authorityUrl);
        $parameters = array(
            'grant_type' => 'refresh_token',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'resource' => $resource,
            'redirect_uri' => $redirectUri,
            'refresh_token' => $refreshToken
        );
        $this->provider->acquireToken($parameters);
    }

    /**
     * @param string $resource
     * @param string $clientId
     * @param string $clientSecret
     * @param UserCredentials $credentials
     */
    public function acquireTokenForUserCredential($resource, $clientId, $clientSecret, $credentials)
    {
        $this->provider = new OAuthTokenProvider($this->authorityUrl);
        $parameters = array(
            'grant_type' => 'password',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'username' => $credentials->Username,
            'password' => $credentials->Password,
            'scope' => 'openid',
            'resource' => $resource
        );
        $this->provider->acquireToken($parameters);
    }

    /**
     * @param string $uri
     * @param string $resource
     * @param string $clientId
     * @param string $clientSecret
     * @param string $code
     * @param string $redirectUrl
     */
    public function acquireTokenByAuthorizationCode($uri,$resource, $clientId, $clientSecret, $code, $redirectUrl)
    {
        $this->provider = new OAuthTokenProvider($uri);
        $parameters = array(
            'grant_type' => 'authorization_code',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code,
            'resource' => $resource,
            "redirect_uri" => $redirectUrl
        );
        $this->provider->acquireToken($parameters);
    }

    /**
     * @param RequestOptions $options
     * @throws \Exception
     */
    public function authenticateRequest(RequestOptions $options)
    {
        if ($this->provider instanceof SamlTokenProvider) {
            $options->addCustomHeader('Cookie', $this->provider->getAuthenticationCookie());
        } elseif ($this->provider instanceof ACSTokenProvider || $this->provider instanceof OAuthTokenProvider) {
            $options->addCustomHeader('Authorization', $this->provider->getAuthorizationHeader());
        } else {
            throw new \Exception("Unknown authentication provider");
        }
    }

    /**
     * @return null|\stdClass
     */
    public function getAccessToken()
    {
        if ($this->provider instanceof OAuthTokenProvider) {
            return $this->provider->getAccessToken();
        }
        return null;
    }

    /**
     * @param $accessToken
     */
    public function setAccessToken($accessToken)
    {
        $this->provider = new OAuthTokenProvider($this->authorityUrl);
        $this->provider->setAccessToken($accessToken);
    }
}
