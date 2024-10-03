<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Adapter;

use Hybridauth\Exception\InvalidOpenidIdentifierException;
use Hybridauth\Exception\AuthorizationDeniedException;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\Data;
use Hybridauth\HttpClient;
use Hybridauth\User;
use Hybridauth\Thirdparty\OpenID\LightOpenID;

/**
 * This class can be used to simplify the authentication flow of OpenID based service providers.
 *
 * Subclasses (i.e., providers adapters) can either use the already provided methods or override
 * them when necessary.
 */
abstract class OpenID extends AbstractAdapter implements AdapterInterface
{
    /**
     * LightOpenID instance
     *
     * @var object
     */
    protected $openIdClient = null;

    /**
     * Openid provider identifier
     *
     * @var string
     */
    protected $openidIdentifier = '';

    /**
     * IPD API Documentation
     *
     * OPTIONAL.
     *
     * @var string
     */
    protected $apiDocumentation = '';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        if ($this->config->exists('openid_identifier')) {
            $this->openidIdentifier = $this->config->get('openid_identifier');
        }

        if (empty($this->openidIdentifier)) {
            throw new InvalidOpenidIdentifierException('OpenID adapter requires an openid_identifier.', 4);
        }

        $this->setCallback($this->config->get('callback'));
        $this->setApiEndpoints($this->config->get('endpoints'));
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        $hostPort = parse_url($this->callback, PHP_URL_PORT);
        $hostUrl = parse_url($this->callback, PHP_URL_HOST);

        if ($hostPort) {
            $hostUrl .= ':' . $hostPort;
        }

        // @fixme: add proxy
        $this->openIdClient = new LightOpenID($hostUrl, null);
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate()
    {
        $this->logger->info(sprintf('%s::authenticate()', get_class($this)));

        if ($this->isConnected()) {
            return true;
        }

        if (empty($_REQUEST['openid_mode'])) {
            $this->authenticateBegin();
        } else {
            return $this->authenticateFinish();
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected()
    {
        return (bool)$this->storage->get($this->providerId . '.user');
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        $this->storage->delete($this->providerId . '.user');

        return true;
    }

    /**
     * Initiate the authorization protocol
     *
     * Include and instantiate LightOpenID
     */
    protected function authenticateBegin()
    {
        $this->openIdClient->identity = $this->openidIdentifier;
        $this->openIdClient->returnUrl = $this->callback;
        $this->openIdClient->required = [
            'namePerson/first',
            'namePerson/last',
            'namePerson/friendly',
            'namePerson',
            'contact/email',
            'birthDate',
            'birthDate/birthDay',
            'birthDate/birthMonth',
            'birthDate/birthYear',
            'person/gender',
            'pref/language',
            'contact/postalCode/home',
            'contact/city/home',
            'contact/country/home',

            'media/image/default',
        ];

        $authUrl = $this->openIdClient->authUrl();

        $this->logger->debug(sprintf('%s::authenticateBegin(), redirecting user to:', get_class($this)), [$authUrl]);

        HttpClient\Util::redirect($authUrl);
    }

    /**
     * Finalize the authorization process.
     *
     * @throws AuthorizationDeniedException
     * @throws UnexpectedApiResponseException
     */
    protected function authenticateFinish()
    {
        $this->logger->debug(
            sprintf('%s::authenticateFinish(), callback url:', get_class($this)),
            [HttpClient\Util::getCurrentUrl(true)]
        );

        if ($this->openIdClient->mode == 'cancel') {
            throw new AuthorizationDeniedException('User has cancelled the authentication.');
        }

        if (!$this->openIdClient->validate()) {
            throw new UnexpectedApiResponseException('Invalid response received.');
        }

        $openidAttributes = $this->openIdClient->getAttributes();

        if (!$this->openIdClient->identity) {
            throw new UnexpectedApiResponseException('Provider returned an unexpected response.');
        }

        $userProfile = $this->fetchUserProfile($openidAttributes);

        /* with openid providers we only get user profiles once, so we store it */
        $this->storage->set($this->providerId . '.user', $userProfile);
    }

    /**
     * Fetch user profile from received openid attributes
     *
     * @param array $openidAttributes
     *
     * @return User\Profile
     */
    protected function fetchUserProfile($openidAttributes)
    {
        $data = new Data\Collection($openidAttributes);

        $userProfile = new User\Profile();

        $userProfile->identifier = $this->openIdClient->identity;

        $userProfile->firstName = $data->get('namePerson/first');
        $userProfile->lastName = $data->get('namePerson/last');
        $userProfile->email = $data->get('contact/email');
        $userProfile->language = $data->get('pref/language');
        $userProfile->country = $data->get('contact/country/home');
        $userProfile->zip = $data->get('contact/postalCode/home');
        $userProfile->gender = $data->get('person/gender');
        $userProfile->photoURL = $data->get('media/image/default');
        $userProfile->birthDay = $data->get('birthDate/birthDay');
        $userProfile->birthMonth = $data->get('birthDate/birthMonth');
        $userProfile->birthYear = $data->get('birthDate/birthDate');

        $userProfile = $this->fetchUserGender($userProfile, $data->get('person/gender'));

        $userProfile = $this->fetchUserDisplayName($userProfile, $data);

        return $userProfile;
    }

    /**
     * Extract users display names
     *
     * @param User\Profile $userProfile
     * @param Data\Collection $data
     *
     * @return User\Profile
     */
    protected function fetchUserDisplayName(User\Profile $userProfile, Data\Collection $data)
    {
        $userProfile->displayName = $data->get('namePerson');

        $userProfile->displayName = $userProfile->displayName
            ? $userProfile->displayName
            : $data->get('namePerson/friendly');

        $userProfile->displayName = $userProfile->displayName
            ? $userProfile->displayName
            : trim($userProfile->firstName . ' ' . $userProfile->lastName);

        return $userProfile;
    }

    /**
     * Extract users gender
     *
     * @param User\Profile $userProfile
     * @param string $gender
     *
     * @return User\Profile
     */
    protected function fetchUserGender(User\Profile $userProfile, $gender)
    {
        $gender = strtolower((string)$gender);

        if ('f' == $gender) {
            $gender = 'female';
        }

        if ('m' == $gender) {
            $gender = 'male';
        }

        $userProfile->gender = $gender;

        return $userProfile;
    }

    /**
     * OpenID only provide the user profile one. This method will attempt to retrieve the profile from storage.
     */
    public function getUserProfile()
    {
        $userProfile = $this->storage->get($this->providerId . '.user');

        if (!is_object($userProfile)) {
            throw new UnexpectedApiResponseException('Provider returned an unexpected response.');
        }

        return $userProfile;
    }
}
