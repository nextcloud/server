<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2020 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\User;
use Hybridauth\User\Profile;
use Hybridauth\Data\Collection;

/**
 * Patreon OAuth2 provider adapter.
 */
class Patreon extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'identity identity[email]';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://www.patreon.com/api';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://www.patreon.com/oauth2/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://www.patreon.com/api/oauth2/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://docs.patreon.com/#oauth';

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
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('oauth2/v2/identity', 'GET', [
            'fields[user]' => 'created,first_name,last_name,email,full_name,is_email_verified,thumb_url,url',
        ]);

        $collection = new Collection($response);
        if (!$collection->exists('data')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new Profile();

        $data = $collection->filter('data');
        $attributes = $data->filter('attributes');

        $userProfile->identifier = $data->get('id');
        $userProfile->email = $attributes->get('email');
        $userProfile->firstName = $attributes->get('first_name');
        $userProfile->lastName = $attributes->get('last_name');
        $userProfile->displayName = $attributes->get('full_name') ?: $data->get('id');
        $userProfile->photoURL = $attributes->get('thumb_url');
        $userProfile->profileURL = $attributes->get('url');

        $userProfile->emailVerified = $attributes->get('is_email_verified') ? $userProfile->email : '';

        return $userProfile;
    }

    /**
     * Contacts are defined as Patrons here
     */
    public function getUserContacts()
    {
        $campaignId = $this->config->get('campaign_id') ?: null;
        $tierFilter = $this->config->get('tier_filter') ?: null;

        $campaigns = [];
        if ($campaignId === null) {
            $campaignsUrl = 'oauth2/v2/campaigns';
            do {
                $response = $this->apiRequest($campaignsUrl);
                $data = new Collection($response);

                if (!$data->exists('data')) {
                    throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
                }

                foreach ($data->filter('data')->toArray() as $item) {
                    $campaign = new Collection($item);
                    $campaigns[] = $campaign->get('id');
                }

                if ($data->filter('links')->exists('next')) {
                    $campaignsUrl = $data->filter('links')->get('next');

                    $pagedList = true;
                } else {
                    $pagedList = false;
                }
            } while ($pagedList);
        } else {
            $campaigns[] = $campaignId;
        }

        $contacts = [];

        foreach ($campaigns as $campaignId) {
            $params = [
                'include' => 'currently_entitled_tiers',
                'fields[member]' => 'full_name,patron_status,email',
                'fields[tier]' => 'title',
            ];
            $membersUrl = 'oauth2/v2/campaigns/' . $campaignId . '/members?' . http_build_query($params);

            do {
                $response = $this->apiRequest($membersUrl);

                $data = new Collection($response);

                if (!$data->exists('data')) {
                    throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
                }

                $tierTitles = [];

                foreach ($data->filter('included')->toArray() as $item) {
                    $includedItem = new Collection($item);
                    if ($includedItem->get('type') == 'tier') {
                        $tierTitles[$includedItem->get('id')] = $includedItem->filter('attributes')->get('title');
                    }
                }

                foreach ($data->filter('data')->toArray() as $item) {
                    $member = new Collection($item);

                    if ($member->filter('attributes')->get('patron_status') == 'active_patron') {
                        $tiers = [];
                        $tierObs = $member->filter('relationships')->filter('currently_entitled_tiers')->get('data');
                        foreach ($tierObs as $item) {
                            $tier = new Collection($item);
                            $tierId = $tier->get('id');
                            $tiers[] = $tierTitles[$tierId];
                        }

                        if (($tierFilter === null) || (in_array($tierFilter, $tiers))) {
                            $userContact = new User\Contact();

                            $userContact->identifier = $member->get('id');
                            $userContact->email = $member->filter('attributes')->get('email');
                            $userContact->displayName = $member->filter('attributes')->get('full_name');
                            $userContact->description = json_encode($tiers);

                            $contacts[] = $userContact;
                        }
                    }
                }

                if ($data->filter('links')->exists('next')) {
                    $membersUrl = $data->filter('links')->get('next');

                    $pagedList = true;
                } else {
                    $pagedList = false;
                }
            } while ($pagedList);
        }

        return $contacts;
    }
}
