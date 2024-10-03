<?php

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Data\Collection;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\User\Profile;

class PlexTv extends OAuth2
{
    protected $apiBaseUrl = 'https://plex.tv/api/v2';
    protected $apiDocumentation = 'https://forums.plex.tv/t/authenticating-with-plex/609370';

    private $product = 'OAuth';

    protected function configure()
    {
        if ($product = $this->config->filter('keys')->get('id')) {
            $this->product = $product;
        }
        $this->setCallback($this->config->get('callback'));

        $this->clientId = $this->callback;
    }

    protected function initialize()
    {
        $this->apiRequestHeaders = [
            'Accept' => 'application/json',
            'X-Plex-Product' => $this->product,
            'X-Plex-Client-Identifier' => $this->clientId,
            'X-Plex-Token' =>  $this->getStoredData('access_token') ?: '',
        ];
    }

    protected function getAuthorizeUrl($parameters = [])
    {
        $state = 'HA-' . str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890');
        $this->storeData('authorization_state', $state);
        $pin = $this->apiRequest('pins', 'POST', [
            'strong' => 'true',
            'X-Plex-Client-Identifier' => $this->clientId,
        ]);

        return 'https://app.plex.tv/auth#?'.http_build_query([
            'clientID' => $this->clientId,
            'code' => $pin->code,
            'forwardUrl' => $this->callback.'?'.http_build_query(['code' => $pin->id, 'state' => $state]),
            'context' => ['device' => ['product' => $this->product]],
        ]);
    }

    protected function exchangeCodeForAccessToken($code)
    {
        $pin = $this->apiRequest('pins/'.$code);
        $pin->access_token = $pin->authToken;

        return json_encode($pin);
    }

    public function getUserProfile()
    {
        $data = new Collection($this->apiRequest('user'));

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new Profile();

        $userProfile->identifier = $data->get('id');
        $userProfile->displayName = $data->get('friendlyName');
        $userProfile->photoURL = $data->get('thumb');
        $userProfile->email = $data->get('email');

        return $userProfile;
    }
}
