<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OpenID;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\Data;
use Hybridauth\User;

/**
 * Steam OpenID provider adapter.
 *
 * Example:
 *
 *   $config = [
 *       'callback' => Hybridauth\HttpClient\Util::getCurrentUrl(),
 *       'keys' => ['secret' => 'steam-api-key']
 *   ];
 *
 *   $adapter = new Hybridauth\Provider\Steam($config);
 *
 *   try {
 *       $adapter->authenticate();
 *
 *       $userProfile = $adapter->getUserProfile();
 *   } catch (\Exception $e) {
 *       echo $e->getMessage() ;
 *   }
 */
class Steam extends OpenID
{
    /**
     * {@inheritdoc}
     */
    protected $openidIdentifier = 'http://steamcommunity.com/openid';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://steamcommunity.com/dev';

    /**
     * {@inheritdoc}
     */
    public function authenticateFinish()
    {
        parent::authenticateFinish();

        $userProfile = $this->storage->get($this->providerId . '.user');

        $userProfile->identifier = str_ireplace([
            'http://steamcommunity.com/openid/id/',
            'https://steamcommunity.com/openid/id/',
        ], '', $userProfile->identifier);

        if (!$userProfile->identifier) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        try {
            $apiKey = $this->config->filter('keys')->get('secret');

            // if api key is provided, we attempt to use steam web api
            if ($apiKey) {
                $result = $this->getUserProfileWebAPI($apiKey, $userProfile->identifier);
            } else {
                // otherwise we fallback to community data
                $result = $this->getUserProfileLegacyAPI($userProfile->identifier);
            }

            // fetch user profile
            foreach ($result as $k => $v) {
                $userProfile->$k = $v ?: $userProfile->$k;
            }
        } catch (\Exception $e) {
        }

        // store user profile
        $this->storage->set($this->providerId . '.user', $userProfile);
    }

    /**
     * Fetch user profile on Steam web API
     *
     * @param $apiKey
     * @param $steam64
     *
     * @return array
     */
    public function getUserProfileWebAPI($apiKey, $steam64)
    {
        $q = http_build_query(['key' => $apiKey, 'steamids' => $steam64]);
        $apiUrl = 'http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?' . $q;

        $response = $this->httpClient->request($apiUrl);

        $data = json_decode($response);

        $data = isset($data->response->players[0]) ? $data->response->players[0] : null;

        $data = new Data\Collection($data);

        $userProfile = [];

        $userProfile['displayName'] = (string)$data->get('personaname');
        $userProfile['firstName'] = (string)$data->get('realname');
        $userProfile['photoURL'] = (string)$data->get('avatarfull');
        $userProfile['profileURL'] = (string)$data->get('profileurl');
        $userProfile['country'] = (string)$data->get('loccountrycode');

        return $userProfile;
    }

    /**
     * Fetch user profile on community API
     * @param $steam64
     * @return array
     */
    public function getUserProfileLegacyAPI($steam64)
    {
        libxml_use_internal_errors(false);

        $apiUrl = 'http://steamcommunity.com/profiles/' . $steam64 . '/?xml=1';

        $response = $this->httpClient->request($apiUrl);

        $data = new \SimpleXMLElement($response);

        $data = new Data\Collection($data);

        $userProfile = [];

        $userProfile['displayName'] = (string)$data->get('steamID');
        $userProfile['firstName'] = (string)$data->get('realname');
        $userProfile['photoURL'] = (string)$data->get('avatarFull');
        $userProfile['description'] = (string)$data->get('summary');
        $userProfile['region'] = (string)$data->get('location');
        $userProfile['profileURL'] = (string)$data->get('customURL')
            ? 'http://steamcommunity.com/id/' . (string)$data->get('customURL')
            : 'http://steamcommunity.com/profiles/' . $steam64;

        return $userProfile;
    }
}
