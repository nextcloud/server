<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Exception\Exception;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\Data;
use Hybridauth\User;

/**
 * Yandex OAuth2 provider adapter.
 */
class Yandex extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://login.yandex.ru/info';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://oauth.yandex.ru/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://oauth.yandex.ru/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation
        = 'https://yandex.com/dev/oauth/doc/dg/concepts/about-docpage/';

    /**
     * Load the user profile from the IDp api client
     *
     * @throws Exception
     */
    public function getUserProfile()
    {
        $this->scope = implode(',', []);

        $response = $this->apiRequest($this->apiBaseUrl, 'GET', ['format' => 'json']);

        if (!isset($response->id)) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $data = new Data\Collection($response);

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();
        $userProfile->identifier = $data->get('id');
        $userProfile->firstName = $data->get('first_name');
        $userProfile->lastName = $data->get('last_name');
        $userProfile->displayName = $data->get('display_name');
        $userProfile->photoURL
            = 'https://avatars.yandex.net/get-yapic/' .
            $data->get('default_avatar_id') . '/islands-200';
        $userProfile->gender = $data->get('sex');
        $userProfile->email = $data->get('default_email');
        $userProfile->emailVerified = $data->get('default_email');

        if ($data->get('birthday')) {
            list($birthday_year, $birthday_month, $birthday_day)
                = explode('-', $response->birthday);
            $userProfile->birthDay = (int)$birthday_day;
            $userProfile->birthMonth = (int)$birthday_month;
            $userProfile->birthYear = (int)$birthday_year;
        }

        return $userProfile;
    }
}
