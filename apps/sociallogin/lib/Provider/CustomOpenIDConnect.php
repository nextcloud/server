<?php

namespace OCA\SocialLogin\Provider;

use Hybridauth\User;
use Hybridauth\Data;
use Hybridauth\Exception\Exception;

class CustomOpenIDConnect extends CustomOAuth2
{
    protected function validateAccessTokenExchange($response)
    {
        $collection = parent::validateAccessTokenExchange($response);
        if ($collection->exists('id_token')) {
            $idToken = $collection->get('id_token');
            //get payload from id_token
            $parts = explode('.', $idToken);
            list($headb64, $payload) = $parts;
            // JWT token is base64url encoded
            $data = base64_decode(str_pad(strtr($payload, '-_', '+/'), strlen($payload) % 4, '=', STR_PAD_RIGHT));
            $this->storeData('user_data', $data);
        } else {
            throw new Exception('No id_token was found.');
        }
        return $collection;
    }

    public function getUserProfile()
    {
        $userData = $this->getStoredData('user_data');
        $user = json_decode($userData);
        $data = new Data\Collection($user);

        $displayNameClaim = $this->config->get('displayname_claim');

        $userProfile = new User\Profile();
        $userProfile->identifier  = $data->get('sub');
        $userProfile->displayName = $data->get($displayNameClaim) ?: $data->get('name') ?: $data->get('preferred_username');
        $userProfile->photoURL    = $data->get('picture');
        $userProfile->email       = $data->get('email');
        if (!is_string($userProfile->photoURL)) {
            $userProfile->photoURL = null;
        }
        if ($data->exists('street_address')) {
            $userProfile->address = $data->get('street_address');
        }
        if (null !== $groups = $this->getGroups($data)) {
            $userProfile->data['groups'] = $groups;
        }
        if ($groupMapping = $this->config->get('group_mapping')) {
            $userProfile->data['group_mapping'] = $groupMapping;
        }

        $userInfoUrl = trim($this->config->get('endpoints')['user_info_url']);
        if (!empty($userInfoUrl)) {
            $profile = new Data\Collection( $this->apiRequest($userInfoUrl) );
            if (empty($userProfile->identifier)) {
                $userProfile->identifier = $profile->get('sub');
            }
            $userProfile->displayName = $profile->get($displayNameClaim) ?: $profile->get('name') ?: $profile->get('preferred_username') ?: $profile->get('nickname');
            if (!$userProfile->photoURL) {
                $userProfile->photoURL = $profile->get('picture') ?: $profile->get('avatar');
            }
            if (!is_string($userProfile->photoURL)) {
                $userProfile->photoURL = null;
            }
            if (preg_match('#<img.+src=["\'](.+?)["\']#', (string)$userProfile->photoURL, $m)) {
                $userProfile->photoURL = $m[1];
            }
            if (!$userProfile->email) {
                $userProfile->email = $profile->get('email');
            }
            if (empty($userProfile->data['groups']) && null !== $groups = $this->getGroups($profile)) {
                $userProfile->data['groups'] = $groups;
            }
        }

        return $userProfile;
    }
}
