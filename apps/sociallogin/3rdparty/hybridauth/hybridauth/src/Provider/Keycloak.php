<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2021 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Exception\InvalidApplicationCredentialsException;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\Data;
use Hybridauth\User;

/**
 * Keycloak OpenId Connect provider adapter.
 *
 * Example:
 *         'Keycloak' => [
 *             'enabled' => true,
 *             'url' => 'https://your-keycloak', // depending on your setup you might need to add '/auth'
 *             'realm' => 'your-realm',
 *             'keys' => [
 *                 'id' => 'client-id',
 *                 'secret' => 'client-secret'
 *             ]
 *         ]
 *
 */
class Keycloak extends OAuth2
{

    /**
     * {@inheritdoc}
     */
    public $scope = 'openid profile email';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://www.keycloak.org/docs/latest/securing_apps/#_oidc';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        if (!$this->config->exists('url')) {
            throw new InvalidApplicationCredentialsException(
                'You must define a provider url'
            );
        }
        $url = $this->config->get('url');

        if (!$this->config->exists('realm')) {
            throw new InvalidApplicationCredentialsException(
                'You must define a realm'
            );
        }
        $realm = $this->config->get('realm');

        $this->apiBaseUrl = $url . '/realms/' . $realm . '/protocol/openid-connect/';

        $this->authorizeUrl = $this->apiBaseUrl . 'auth';
        $this->accessTokenUrl = $this->apiBaseUrl . 'token';

    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('userinfo');

        $data = new Data\Collection($response);

        if (!$data->exists('sub')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier = $data->get('sub');
        $userProfile->displayName = $data->get('preferred_username');
        $userProfile->email = $data->get('email');
        $userProfile->firstName = $data->get('given_name');
        $userProfile->lastName = $data->get('family_name');
        $userProfile->emailVerified = $data->get('email_verified');

        return $userProfile;
    }
}
