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
 * Github OAuth2 provider adapter.
 */
class GitHub extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'user:email';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.github.com/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://github.com/login/oauth/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://github.com/login/oauth/access_token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://developer.github.com/v3/oauth/';

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('user');

        $data = new Data\Collection($response);

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier = $data->get('id');
        $userProfile->displayName = $data->get('name');
        $userProfile->description = $data->get('bio');
        $userProfile->photoURL = $data->get('avatar_url');
        $userProfile->profileURL = $data->get('html_url');
        $userProfile->email = $data->get('email');
        $userProfile->webSiteURL = $data->get('blog');
        $userProfile->region = $data->get('location');

        $userProfile->displayName = $userProfile->displayName ?: $data->get('login');

        if (empty($userProfile->email) && strpos($this->scope, 'user:email') !== false) {
            try {
                // user email is not mandatory so keep it quite.
                $userProfile = $this->requestUserEmail($userProfile);
            } catch (\Exception $e) {
            }
        }

        return $userProfile;
    }

    /**
     * Request connected user email
     *
     * https://developer.github.com/v3/users/emails/
     * @param User\Profile $userProfile
     *
     * @return User\Profile
     *
     * @throws \Exception
     */
    protected function requestUserEmail(User\Profile $userProfile)
    {
        $response = $this->apiRequest('user/emails');

        foreach ($response as $idx => $item) {
            if (!empty($item->primary) && $item->primary == 1) {
                $userProfile->email = $item->email;

                if (!empty($item->verified) && $item->verified == 1) {
                    $userProfile->emailVerified = $userProfile->email;
                }

                break;
            }
        }

        return $userProfile;
    }
}
