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
 * Medium OAuth2 provider adapter.
 */
class Medium extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'basicProfile';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.medium.com/v1/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://medium.com/m/oauth/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://api.medium.com/v1/tokens';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://github.com/Medium/medium-api-docs';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        if ($this->isRefreshTokenAvailable()) {
            $this->tokenRefreshParameters += [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ];
        }
    }

    /**
     * {@inheritdoc}
     *
     * See: https://github.com/Medium/medium-api-docs#getting-the-authenticated-users-details
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('me');

        $data = new Data\Collection($response);

        $userProfile = new User\Profile();
        $data = $data->filter('data');

        $full_name = explode(' ', $data->get('name'));
        if (count($full_name) < 2) {
            $full_name[1] = '';
        }

        $userProfile->identifier = $data->get('id');
        $userProfile->displayName = $data->get('username');
        $userProfile->profileURL = $data->get('imageUrl');
        $userProfile->firstName = $full_name[0];
        $userProfile->lastName = $full_name[1];
        $userProfile->profileURL = $data->get('url');

        return $userProfile;
    }
}
