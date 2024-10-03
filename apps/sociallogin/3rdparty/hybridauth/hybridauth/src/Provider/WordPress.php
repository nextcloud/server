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
 * WordPress OAuth2 provider adapter.
 */
class WordPress extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://public-api.wordpress.com/rest/v1/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://public-api.wordpress.com/oauth2/authenticate';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://public-api.wordpress.com/oauth2/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://developer.wordpress.com/docs/api/';

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('me/');

        $data = new Data\Collection($response);

        if (!$data->exists('ID')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier = $data->get('ID');
        $userProfile->displayName = $data->get('display_name');
        $userProfile->photoURL = $data->get('avatar_URL');
        $userProfile->profileURL = $data->get('profile_URL');
        $userProfile->email = $data->get('email');
        $userProfile->language = $data->get('language');

        $userProfile->displayName = $userProfile->displayName ?: $data->get('username');

        $userProfile->emailVerified = $data->get('email_verified') ? $data->get('email') : '';

        return $userProfile;
    }
}
