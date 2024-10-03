<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\Data\Collection;
use Hybridauth\User\Profile;

/**
 * Mailru OAuth2 provider adapter.
 */
class Mailru extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'http://www.appsmail.ru/platform/api';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://connect.mail.ru/oauth/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://connect.mail.ru/oauth/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = ''; // Not available

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $params = [
            'app_id' => $this->clientId,
            'method' => 'users.getInfo',
            'secure' => 1,
            'session_key' => $this->getStoredData('access_token'),
        ];
        $sign = md5(http_build_query($params, null, '') . $this->clientSecret);

        $param = [
            'app_id' => $this->clientId,
            'method' => 'users.getInfo',
            'secure' => 1,
            'session_key' => $this->getStoredData('access_token'),
            'sig' => $sign,
        ];

        $response = $this->apiRequest('', 'GET', $param);

        $data = new Collection($response[0]);

        if (!$data->exists('uid')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new Profile();

        $userProfile->identifier = $data->get('uid');
        $userProfile->email = $data->get('email');
        $userProfile->firstName = $data->get('first_name');
        $userProfile->lastName = $data->get('last_name');
        $userProfile->displayName = $data->get('nick');
        $userProfile->photoURL = $data->get('pic');
        $userProfile->profileURL = $data->get('link');
        $userProfile->gender = $data->get('sex');
        $userProfile->age = $data->get('age');

        return $userProfile;
    }
}
