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
 * Microsoft Graph OAuth2 provider adapter.
 *
 * Create an "Azure Active Directory" resource at https://portal.azure.com/
 * (not from the Visual Studio site).
 *
 * The "Supported account types" choice maps to the 'tenant' setting, see "Authority" @
 * https://docs.microsoft.com/en-us/azure/active-directory/develop/msal-client-application-configuration
 *
 * Example:
 *
 *   $config = [
 *       'callback' => Hybridauth\HttpClient\Util::getCurrentUrl(),
 *       'keys' => ['id' => '', 'secret' => ''],
 *       'tenant' => 'user',
 *         // ^ May be 'common', 'organizations' or 'consumers' or a specific tenant ID or a domain
 *   ];
 *
 *   $adapter = new Hybridauth\Provider\MicrosoftGraph($config);
 *
 *   try {
 *       $adapter->authenticate();
 *
 *       $userProfile = $adapter->getUserProfile();
 *       $tokens = $adapter->getAccessToken();
 *   } catch (\Exception $e) {
 *       echo $e->getMessage() ;
 *   }
 */
class MicrosoftGraph extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'openid user.read contacts.read';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://graph.microsoft.com/v1.0/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://developer.microsoft.com/en-us/graph/docs/concepts/php';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        $tenant = $this->config->get('tenant');
        if (!empty($tenant)) {
            $adjustedEndpoints = [
                'authorize_url' => str_replace('/common/', '/' . $tenant . '/', $this->authorizeUrl),
                'access_token_url' => str_replace('/common/', '/' . $tenant . '/', $this->accessTokenUrl),
            ];

            $this->setApiEndpoints($adjustedEndpoints);
        }
    }

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
        $userProfile->displayName = $data->get('displayName');
        $userProfile->firstName = $data->get('givenName');
        $userProfile->lastName = $data->get('surname');
        $userProfile->language = $data->get('preferredLanguage');

        $userProfile->phone = $data->get('mobilePhone');
        if (empty($userProfile->phone)) {
            $businessPhones = $data->get('businessPhones');
            if (isset($businessPhones[0])) {
                $userProfile->phone = $businessPhones[0];
            }
        }

        $userProfile->email = $data->get('mail');
        if (empty($userProfile->email)) {
            $email = $data->get('userPrincipalName');
            if (strpos($email, '@') !== false) {
                $userProfile->email = $email;
            }
        }

        return $userProfile;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserContacts()
    {
        $apiUrl = 'me/contacts?$top=50';
        $contacts = [];

        do {
            $response = $this->apiRequest($apiUrl);
            $data = new Data\Collection($response);
            if (!$data->exists('value')) {
                throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
            }
            foreach ($data->filter('value')->toArray() as $entry) {
                $entry = new Data\Collection($entry);
                $userContact = new User\Contact();
                $userContact->identifier = $entry->get('id');
                $userContact->displayName = $entry->get('displayName');
                $emailAddresses = $entry->get('emailAddresses');
                if (!empty($emailAddresses)) {
                    $userContact->email = $emailAddresses[0]->address;
                }
                // only add to collection if we have usefull data
                if (!empty($userContact->displayName) || !empty($userContact->email)) {
                    $contacts[] = $userContact;
                }
            }

            if ($data->exists('@odata.nextLink')) {
                $apiUrl = $data->get('@odata.nextLink');

                $pagedList = true;
            } else {
                $pagedList = false;
            }
        } while ($pagedList);

        return $contacts;
    }
}
