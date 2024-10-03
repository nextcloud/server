<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2020 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Data\Collection;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\User;

/**
 * Instagram OAuth2 provider adapter via Instagram Basic Display API.
 */
class Instagram extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'user_profile,user_media';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://graph.instagram.com';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://api.instagram.com/oauth/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://api.instagram.com/oauth/access_token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://developers.facebook.com/docs/instagram-basic-display-api';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        // The Instagram API requires an access_token from authenticated users
        // for each endpoint.
        $accessToken = $this->getStoredData($this->accessTokenName);
        $this->apiRequestParameters[$this->accessTokenName] = $accessToken;
    }

    /**
     * {@inheritdoc}
     */
    protected function validateAccessTokenExchange($response)
    {
        $collection = parent::validateAccessTokenExchange($response);

        if (!$collection->exists('expires_in')) {
            // Instagram tokens always expire in an hour, but this is implicit not explicit

            $expires_in = 60 * 60;

            $expires_at = time() + $expires_in;

            $this->storeData('expires_in', $expires_in);
            $this->storeData('expires_at', $expires_at);
        }

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function maintainToken()
    {
        if (!$this->isConnected()) {
            return;
        }

        // Handle token exchange prior to the standard handler for an API request
        $exchange_by_expiry_days = $this->config->get('exchange_by_expiry_days') ?: 45;
        if ($exchange_by_expiry_days !== null) {
            $projected_timestamp = time() + 60 * 60 * 24 * $exchange_by_expiry_days;
            if (!$this->hasAccessTokenExpired() && $this->hasAccessTokenExpired($projected_timestamp)) {
                $this->exchangeAccessToken();
            }
        }
    }

    /**
     * Exchange the Access Token with one that expires further in the future.
     *
     * @return string Raw Provider API response
     * @throws \Hybridauth\Exception\HttpClientFailureException
     * @throws \Hybridauth\Exception\HttpRequestFailedException
     * @throws InvalidAccessTokenException
     */
    public function exchangeAccessToken()
    {
        if ($this->getStoredData('expires_in') >= 5000000) {
            /*
            Refresh a long-lived token (needed on Instagram, but not Facebook).
            It's not an oAuth style refresh using a refresh token.
            Actually it's really just another exchange, and invalidates the old token.
            Facebook/Instagram documentation is not very helpful at explaining that!
            */
            $exchangeTokenParameters = [
                'grant_type'        => 'ig_refresh_token',
                'client_secret'     => $this->clientSecret,
                'access_token'      => $this->getStoredData('access_token'),
            ];
            $url = 'https://graph.instagram.com/refresh_access_token';
        } else {
            // Exchange short-lived to long-lived
            $exchangeTokenParameters = [
                'grant_type'        => 'ig_exchange_token',
                'client_secret'     => $this->clientSecret,
                'access_token'      => $this->getStoredData('access_token'),
            ];
            $url = 'https://graph.instagram.com/access_token';
        }

        $response = $this->httpClient->request(
            $url,
            'GET',
            $exchangeTokenParameters
        );

        $this->validateApiResponse('Unable to exchange the access token');

        $this->validateAccessTokenExchange($response);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('me', 'GET', [
            'fields' => 'id,username,account_type,media_count',
        ]);

        $data = new Collection($response);
        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();
        $userProfile->identifier = $data->get('id');
        $userProfile->displayName = $data->get('username');
        $userProfile->profileURL = "https://instagram.com/{$userProfile->displayName}";
        $userProfile->data = [
            'account_type' => $data->get('account_type'),
            'media_count' => $data->get('media_count'),
        ];

        return $userProfile;
    }

    /**
     * Fetch user medias.
     *
     * @param int $limit Number of elements per page.
     * @param string $pageId Current pager ID.
     * @param array|null $fields Fields to fetch per media.
     *
     * @return \Hybridauth\Data\Collection
     *
     * @throws \Hybridauth\Exception\HttpClientFailureException
     * @throws \Hybridauth\Exception\HttpRequestFailedException
     * @throws \Hybridauth\Exception\InvalidAccessTokenException
     * @throws \Hybridauth\Exception\UnexpectedApiResponseException
     */
    public function getUserMedia($limit = 12, $pageId = null, array $fields = null)
    {
        if (empty($fields)) {
            $fields = [
                'id',
                'caption',
                'media_type',
                'media_url',
                'thumbnail_url',
                'permalink',
                'timestamp',
                'username',
            ];
        }

        $params = [
            'fields' => implode(',', $fields),
            'limit' => $limit,
        ];
        if ($pageId !== null) {
            $params['after'] = $pageId;
        }

        $response = $this->apiRequest('me/media', 'GET', $params);

        $data = new Collection($response);
        if (!$data->exists('data')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        return $data;
    }

    /**
     * Fetches a single user's media.
     *
     * @param string $mediaId Media ID.
     * @param array|null $fields Fields to fetch per media.
     *
     * @return \Hybridauth\Data\Collection
     *
     * @throws \Hybridauth\Exception\HttpClientFailureException
     * @throws \Hybridauth\Exception\HttpRequestFailedException
     * @throws \Hybridauth\Exception\InvalidAccessTokenException
     * @throws \Hybridauth\Exception\UnexpectedApiResponseException
     */
    public function getMedia($mediaId, array $fields = null)
    {
        if (empty($fields)) {
            $fields = [
                'id',
                'caption',
                'media_type',
                'media_url',
                'thumbnail_url',
                'permalink',
                'timestamp',
                'username',
            ];
        }

        $response = $this->apiRequest($mediaId, 'GET', [
            'fields' => implode(',', $fields),
        ]);

        $data = new Collection($response);
        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        return $data;
    }
}
