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
 * Disqus OAuth2 provider adapter.
 */
class Disqus extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'read,email';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://disqus.com/api/3.0/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://disqus.com/api/oauth/2.0/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://disqus.com/api/oauth/2.0/access_token/';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://disqus.com/api/docs/auth/';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        $this->apiRequestParameters = [
            'api_key' => $this->clientId, 'api_secret' => $this->clientSecret
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('users/details');

        $data = new Data\Collection($response);

        if (!$data->filter('response')->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $data = $data->filter('response');

        $userProfile->identifier = $data->get('id');
        $userProfile->displayName = $data->get('name');
        $userProfile->description = $data->get('bio');
        $userProfile->profileURL = $data->get('profileUrl');
        $userProfile->email = $data->get('email');
        $userProfile->region = $data->get('location');
        $userProfile->description = $data->get('about');

        $userProfile->photoURL = $data->filter('avatar')->get('permalink');

        $userProfile->displayName = $userProfile->displayName ?: $data->get('username');

        return $userProfile;
    }
}
