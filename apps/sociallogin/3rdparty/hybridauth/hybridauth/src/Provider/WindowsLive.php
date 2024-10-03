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
 * Windows Live OAuth2 provider adapter.
 */
class WindowsLive extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'wl.basic wl.contacts_emails wl.emails wl.signin wl.share wl.birthday';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://apis.live.net/v5.0/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://login.live.com/oauth20_authorize.srf';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://login.live.com/oauth20_token.srf';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://msdn.microsoft.com/en-us/library/hh243647.aspx';

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('me');

        $data = new Data\Collection($response);

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier = $data->get('id');
        $userProfile->displayName = $data->get('name');
        $userProfile->firstName = $data->get('first_name');
        $userProfile->lastName = $data->get('last_name');
        $userProfile->gender = $data->get('gender');
        $userProfile->profileURL = $data->get('link');
        $userProfile->email = $data->filter('emails')->get('preferred');
        $userProfile->emailVerified = $data->filter('emails')->get('account');
        $userProfile->birthDay = $data->get('birth_day');
        $userProfile->birthMonth = $data->get('birth_month');
        $userProfile->birthYear = $data->get('birth_year');
        $userProfile->language = $data->get('locale');

        return $userProfile;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserContacts()
    {
        $response = $this->apiRequest('me/contacts');

        $data = new Data\Collection($response);

        if (!$data->exists('data')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $contacts = [];

        foreach ($data->filter('data')->toArray() as $idx => $entry) {
            $userContact = new User\Contact();

            $userContact->identifier = $entry->get('id');
            $userContact->displayName = $entry->get('name');
            $userContact->email = $entry->filter('emails')->get('preferred');

            $contacts[] = $userContact;
        }

        return $contacts;
    }
}
