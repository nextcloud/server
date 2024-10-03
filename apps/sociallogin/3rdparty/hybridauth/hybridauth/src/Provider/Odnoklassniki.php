<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Data;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\User;

/**
 * Odnoklassniki OAuth2 provider adapter.
 */
class Odnoklassniki extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.ok.ru/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://connect.ok.ru/oauth/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://api.ok.ru/oauth/token.do';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://apiok.ru/en/ext/oauth/';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        if ($this->isRefreshTokenAvailable()) {
            $this->tokenRefreshParameters += [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $fields = array(
            'uid', 'locale', 'first_name', 'last_name', 'name', 'gender', 'age', 'birthday',
            'has_email', 'current_status', 'current_status_id', 'current_status_date', 'online',
            'photo_id', 'pic_1', 'pic_2', 'pic1024x768', 'location', 'email'
        );

        $sig = md5(
            'application_key=' . $this->config->get('keys')['key'] .
            'fields=' . implode(',', $fields) .
            'method=users.getCurrentUser' .
            md5($this->getStoredData('access_token') . $this->config->get('keys')['secret'])
        );

        $parameters = [
            'access_token' => $this->getStoredData('access_token'),
            'application_key' => $this->config->get('keys')['key'],
            'method' => 'users.getCurrentUser',
            'fields' => implode(',', $fields),
            'sig' => $sig,
        ];

        $response = $this->apiRequest('fb.do', 'GET', $parameters);

        $data = new Data\Collection($response);

        if (!$data->exists('uid')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();


        $userProfile->identifier = $data->get('uid');
        $userProfile->email = $data->get('email');
        $userProfile->firstName = $data->get('first_name');
        $userProfile->lastName = $data->get('last_name');
        $userProfile->displayName = $data->get('name');
        $userProfile->photoURL = $data->get('pic1024x768');
        $userProfile->profileURL = 'http://ok.ru/profile/' . $data->get('uid');

        // Handle birthday.
        if ($data->get('birthday')) {
            $bday = explode('-', $data->get('birthday'));
            $userProfile->birthDay = (int)$bday[0];
            $userProfile->birthMonth = (int)$bday[1];
            $userProfile->birthYear = (int)$bday[2];
        }

        return $userProfile;
    }
}
