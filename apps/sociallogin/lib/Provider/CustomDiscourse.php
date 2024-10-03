<?php

namespace OCA\SocialLogin\Provider;

use Hybridauth\Adapter\AbstractAdapter;
use Hybridauth\Adapter\AdapterInterface;
use Hybridauth\Exception\AuthorizationDeniedException;
use Hybridauth\Exception\InvalidAuthorizationStateException;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\HttpClient;
use Hybridauth\User;

/**
 * This class can be used to simplify the authentication flow of Discourse based service providers.
 *
 * Subclasses (i.e., providers adapters) can either use the already provided methods or override
 * them when necessary.
 */
abstract class HybridauthDiscourse extends AbstractAdapter implements AdapterInterface
{
    /**
    * Discourse base url
    *
    * @var string
    */
    protected $baseUrl = '';

    /**
    * Discourse SSO secret
    *
    * @var string
    */
    protected $ssoSecret = '';

    /**
    * {@inheritdoc}
    */
    protected function configure()
    {
        $this->baseUrl   = $this->config->filter('endpoints')->get('base_url');
        $this->ssoSecret = $this->config->filter('keys')->get('secret');

        if (!$this->baseUrl || !$this->ssoSecret) {
            throw new InvalidApplicationCredentialsException(
                'Your application id is required in order to connect to ' . $this->providerId
            );
        }

        $this->setCallback($this->config->get('callback'));
    }

    /**
    * {@inheritdoc}
    */
    protected function initialize()
    {
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

        if (empty($_GET['sso'])) {
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
        return (bool) $this->storage->get($this->providerId . '.user');
    }

    /**
    * {@inheritdoc}
    */
    public function disconnect()
    {
        $this->storage->delete($this->providerId . '.user');

        return true;
    }

    protected function authenticateBegin()
    {
        $payload = $this->encodeAuthenticatePayload($this->getAuthenticatePayload());

        $parameters = [
            'sso' => $payload,
            'sig' => $this->signAuthenticatePayload($payload),
        ];

        $authUrl = rtrim($this->baseUrl, '/') . "/session/sso_provider?" . http_build_query($parameters);

        $this->logger->debug(sprintf('%s::authenticateBegin(), redirecting user to:', get_class($this)), [$authUrl]);

        HttpClient\Util::redirect($authUrl);
    }

    protected function authenticateFinish()
    {
        $this->logger->debug(
            sprintf('%s::authenticateFinish(), callback url:', get_class($this)),
            [HttpClient\Util::getCurrentUrl(true)]
        );

        $sso = filter_input(INPUT_GET, 'sso');
        $sig = filter_input(INPUT_GET, 'sig');

		if ($this->signAuthenticatePayload($sso) !== $sig) {
            throw new UnexpectedApiResponseException('Invalid response received.');
        }

        $payload = $this->decodeAuthenticatePayload($sso);

        $this->verifyAuthenticatePayload($payload);

        $userProfile = new User\Profile();

        $userProfile->identifier        = $payload['external_id'];
        $userProfile->displayName       = $payload['name'];
        $userProfile->photoURL          = $payload['avatar_url'];
        $userProfile->email             = $payload['email'];
        $userProfile->data['admin']     = $payload['admin'];
        $userProfile->data['moderator'] = $payload['moderator'];
        $userProfile->data['groups']    = $payload['groups'];
        $userProfile->data['username']  = $payload['username'];

        $userProfile->displayName = $userProfile->displayName ?: $payload['username'];

        $this->storage->set($this->providerId . '.user', $userProfile);
    }

    public function getUserProfile()
    {
        $userProfile = $this->storage->get($this->providerId . '.user');

        if (! is_object($userProfile)) {
            throw new UnexpectedApiResponseException('Provider returned an unexpected response.');
        }

        return $userProfile;
    }


    private function getAuthenticatePayload()
    {
        $nonce = substr(base64_encode(random_bytes(64)), 0, 30);
        $this->storeData('authorization_nonce', $nonce);

        return [
            'nonce' => $nonce,
            'return_sso_url' => $this->callback
        ];
    }

    private function verifyAuthenticatePayload($payload)
    {
        $nonce = $payload['nonce'];

        if ($this->getStoredData('authorization_nonce') != $nonce) {
            throw new InvalidAuthorizationStateException(
                'The authorization nonce [nonce=' . substr(htmlentities($nonce), 0, 100). '] '
                    . 'of this page is either invalid or has already been consumed.'
            );
        }

        $this->deleteStoredData('authorization_nonce');
    }

    private function encodeAuthenticatePayload($payload)
    {
        return base64_encode(http_build_query($payload));
    }

    private function decodeAuthenticatePayload($payload)
    {
        $ret = [];
        parse_str(base64_decode($payload), $ret);
        return $ret;
    }

    private function signAuthenticatePayload($payload)
    {
        return hash_hmac('sha256', $payload, $this->ssoSecret);
    }
}

class CustomDiscourse extends HybridauthDiscourse
{
    public function getUserProfile()
    {
        $userProfile = parent::getUserProfile();

        if (null !== $groups = $userProfile->data['groups']) {
            $userProfile->data['groups'] = $this->strToArray($groups);
        }
        if ($groupMapping = $this->config->get('group_mapping')) {
            $userProfile->data['group_mapping'] = $groupMapping;
        }

        return $userProfile;
    }

    private function strToArray($str)
    {
        return array_filter(
            array_map('trim', explode(',', $str)),
            function ($val) { return $val !== ''; }
        );
    }
}
