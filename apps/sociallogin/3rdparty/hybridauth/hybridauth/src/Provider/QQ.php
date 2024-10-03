<?php

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\Data;
use Hybridauth\User\Profile;

/**
 * Tencent QQ International OAuth2 provider adapter.
 */
class QQ extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'get_user_info';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://graph.qq.com/oauth2.0/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://graph.qq.com/oauth2.0/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://graph.qq.com/oauth2.0/token';

    /**
     * {@ịnheritdoc}
     */
    protected $accessTokenInfoUrl = 'https://graph.qq.com/oauth2.0/me';

    /**
     * User Information Endpoint
     * @var string
     */
    protected $accessUserInfo = 'https://graph.qq.com/user/get_user_info';

    /**
     * {@inheritdoc}
     */
    protected $tokenExchangeMethod = 'GET';

    /**
     * {@inheritdoc}
     */
    protected $tokenRefreshMethod = 'GET';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = ''; // Not available

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

        $this->apiRequestParameters = [
            'access_token' => $this->getStoredData('access_token')
        ];

        $this->apiRequestHeaders = [];
    }

    /**
     * {@inheritdoc}
     */
    protected function validateAccessTokenExchange($response)
    {
        $collection = parent::validateAccessTokenExchange($response);

        $resp = $this->apiRequest($this->accessTokenInfoUrl);
        $resp = key($resp);

        $len = strlen($resp);
        $res = substr($resp, 10, $len - 14);

        $response = (new Data\Parser())->parse($res);

        if (!isset($response->openid)) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $this->storeData('openid', $response->openid);

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $openid = $this->getStoredData('openid');

        $userRequestParameters = [
            'oauth_consumer_key' => $this->clientId,
            'openid' => $openid,
            'format' => 'json'
        ];

        $response = $this->apiRequest($this->accessUserInfo, 'GET', $userRequestParameters);

        $data = new Data\Collection($response);

        if ($data->get('ret') < 0) {
            throw new UnexpectedApiResponseException('Provider API returned an error: ' . $data->get('msg'));
        }

        $userProfile = new Profile();

        $userProfile->identifier = $openid;
        $userProfile->displayName = $data->get('nickname');
        $userProfile->photoURL = $data->get('figureurl_2');
        $userProfile->gender = $data->get('gender');
        $userProfile->region = $data->get('province');
        $userProfile->city = $data->get('city');

        return $userProfile;
    }
}
