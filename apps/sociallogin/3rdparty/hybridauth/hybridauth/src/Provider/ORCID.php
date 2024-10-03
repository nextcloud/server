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
 * ORCID OAuth2 provider adapter.
 */
class ORCID extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = '/authenticate';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://pub.orcid.org/v2.1/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://orcid.org/oauth/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://orcid.org/oauth/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://members.orcid.org/api/';

    /**
     * {@inheritdoc}
     */
    protected function validateAccessTokenExchange($response)
    {
        $data = parent::validateAccessTokenExchange($response);
        $this->storeData('orcid', $data->get('orcid'));
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest($this->getStoredData('orcid') . '/record');
        $data = new Data\Collection($response['record']);

        if (!$data->exists('orcid-identifier')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $profile = new User\Profile();

        $profile = $this->getDetails($profile, $data);
        $profile = $this->getBiography($profile, $data);
        $profile = $this->getWebsite($profile, $data);
        $profile = $this->getName($profile, $data);
        $profile = $this->getEmail($profile, $data);
        $profile = $this->getLanguage($profile, $data);
        $profile = $this->getAddress($profile, $data);

        return $profile;
    }

    /**
     * Get profile details.
     *
     * @param User\Profile $profile
     * @param Data\Collection $data
     *
     * @return User\Profile
     */
    protected function getDetails(User\Profile $profile, Data\Collection $data)
    {
        $data = new Data\Collection($data->get('orcid-identifier'));

        $profile->identifier = $data->get('path');
        $profile->profileURL = $data->get('uri');

        return $profile;
    }

    /**
     * Get profile biography.
     *
     * @param User\Profile $profile
     * @param Data\Collection $data
     *
     * @return User\Profile
     */
    protected function getBiography(User\Profile $profile, Data\Collection $data)
    {
        $data = new Data\Collection($data->get('person'));
        $data = new Data\Collection($data->get('biography'));

        $profile->description = $data->get('content');

        return $profile;
    }

    /**
     * Get profile website.
     *
     * @param User\Profile $profile
     * @param Data\Collection $data
     *
     * @return User\Profile
     */
    protected function getWebsite(User\Profile $profile, Data\Collection $data)
    {
        $data = new Data\Collection($data->get('person'));
        $data = new Data\Collection($data->get('researcher-urls'));
        $data = new Data\Collection($data->get('researcher-url'));

        if ($data->exists(0)) {
            $data = new Data\Collection($data->get(0));
        }

        $profile->webSiteURL = $data->get('url');

        return $profile;
    }

    /**
     * Get profile name.
     *
     * @param User\Profile $profile
     * @param Data\Collection $data
     *
     * @return User\Profile
     */
    protected function getName(User\Profile $profile, Data\Collection $data)
    {
        $data = new Data\Collection($data->get('person'));
        $data = new Data\Collection($data->get('name'));

        if ($data->exists('credit-name')) {
            $profile->displayName = $data->get('credit-name');
        } else {
            $profile->displayName = $data->get('given-names') . ' ' . $data->get('family-name');
        }

        $profile->firstName = $data->get('given-names');
        $profile->lastName = $data->get('family-name');

        return $profile;
    }

    /**
     * Get profile email.
     *
     * @param User\Profile $profile
     * @param Data\Collection $data
     *
     * @return User\Profile
     */
    protected function getEmail(User\Profile $profile, Data\Collection $data)
    {
        $data = new Data\Collection($data->get('person'));
        $data = new Data\Collection($data->get('emails'));
        $data = new Data\Collection($data->get('email'));

        if (!$data->exists(0)) {
            $email = $data;
        } else {
            $email = new Data\Collection($data->get(0));

            $i = 1;
            while ($email->get('@attributes')['primary'] == 'false') {
                $email = new Data\Collection($data->get($i));
                $i++;
            }
        }

        if ($email->get('@attributes')['primary'] == 'false') {
            return $profile;
        }

        $profile->email = $email->get('email');

        if ($email->get('@attributes')['verified'] == 'true') {
            $profile->emailVerified = $email->get('email');
        }

        return $profile;
    }

    /**
     * Get profile language.
     *
     * @param User\Profile $profile
     * @param Data\Collection $data
     *
     * @return User\Profile
     */
    protected function getLanguage(User\Profile $profile, Data\Collection $data)
    {
        $data = new Data\Collection($data->get('preferences'));

        $profile->language = $data->get('locale');

        return $profile;
    }

    /**
     * Get profile address.
     *
     * @param User\Profile $profile
     * @param Data\Collection $data
     *
     * @return User\Profile
     */
    protected function getAddress(User\Profile $profile, Data\Collection $data)
    {
        $data = new Data\Collection($data->get('person'));
        $data = new Data\Collection($data->get('addresses'));
        $data = new Data\Collection($data->get('address'));

        if ($data->exists(0)) {
            $data = new Data\Collection($data->get(0));
        }

        $profile->country = $data->get('country');

        return $profile;
    }
}
