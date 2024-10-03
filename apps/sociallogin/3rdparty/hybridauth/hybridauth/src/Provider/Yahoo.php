<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\Data;
use Hybridauth\User;

/**
 * For this provider to work it is necessary to assign the "OpenID Connect Permissions",
 * even if you only use basic OAuth2.
 */

/**
 * Yahoo OAuth2 provider adapter.
 */
class Yahoo extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'profile';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.login.yahoo.com/openid/v1/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://api.login.yahoo.com/oauth2/request_auth';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://api.login.yahoo.com/oauth2/get_token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://developer.yahoo.com/oauth2/guide/';

    /**
     * Currently authenticated user
     */
    protected $userId = null;

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        $this->tokenExchangeHeaders = [
            'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)
        ];

        $this->tokenRefreshHeaders = $this->tokenExchangeHeaders;
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
        $userProfile->firstName = $data->get('given_name');
        $userProfile->lastName = $data->get('family_name');
        $userProfile->displayName = $data->get('name');
        $userProfile->gender = $data->get('gender');
        $userProfile->language = $data->get('locale');
        $userProfile->email = $data->get('email');

        $userProfile->emailVerified = $data->get('email_verified') ? $userProfile->email : '';

        $profileImages = $data->get('profile_images');
        if ($this->config->get('photo_size')) {
            $prop = 'image' . $this->config->get('photo_size');
        } else {
            $prop = 'image192';
        }
        $userProfile->photoURL = $profileImages->$prop;

        return $userProfile;
    }
}
