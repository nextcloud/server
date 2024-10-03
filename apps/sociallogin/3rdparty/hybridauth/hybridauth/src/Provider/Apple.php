<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2020 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Composer\InstalledVersions;
use Exception;
use Firebase\JWT\ExpiredException;
use Hybridauth\Exception\InvalidApplicationCredentialsException;
use Hybridauth\Exception\UnexpectedValueException;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Data;
use Hybridauth\User;

use phpseclib\Crypt\RSA;
use phpseclib\Math\BigInteger;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Apple OAuth2 provider adapter.
 *
 * Example:
 *
 *   $config = [
 *       'callback' => Hybridauth\HttpClient\Util::getCurrentUrl(),
 *       'keys' => ['id' => '', 'team_id' => '', 'key_id' => '', 'key_file' => '', 'key_content' => ''],
 *       'scope' => 'name email',
 *
 *        // Apple's custom auth url params
 *       'authorize_url_parameters' => [
 *              'response_mode' => 'form_post'
 *       ]
 *   ];
 *
 *   $adapter = new Hybridauth\Provider\Apple($config);
 *
 *   try {
 *       $adapter->authenticate();
 *
 *       $userProfile = $adapter->getUserProfile();
 *       $tokens = $adapter->getAccessToken();
 *       $response = $adapter->setUserStatus("Hybridauth test message..");
 *   } catch (\Exception $e) {
 *       echo $e->getMessage() ;
 *   }
 *
 * Requires:
 *
 * composer require codercat/jwk-to-pem
 * composer require firebase/php-jwt
 *
 * @see https://github.com/sputnik73/hybridauth-sign-in-with-apple
 * @see https://developer.apple.com/documentation/sign_in_with_apple/sign_in_with_apple_rest_api
 */
class Apple extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'name email';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://appleid.apple.com/auth/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://appleid.apple.com/auth/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://appleid.apple.com/auth/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://developer.apple.com/documentation/sign_in_with_apple';

    /**
     * {@inheritdoc}
     * The Sign in with Apple servers require percent encoding (or URL encoding)
     * for its query parameters. If you are using the Sign in with Apple REST API,
     * you must provide values with encoded spaces (`%20`) instead of plus (`+`) signs.
     */
    protected $AuthorizeUrlParametersEncType = PHP_QUERY_RFC3986;

    protected $supportRequestState = false;

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();
        $this->AuthorizeUrlParameters['response_mode'] = 'form_post';

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
    protected function configure()
    {
        $keys = $this->config->get('keys');
        $keys['secret'] = $this->getSecret();
        $this->config->set('keys', $keys);
        parent::configure();
    }

    /**
     * {@inheritdoc}
     *
     * include id_token $tokenNames
     */
    public function getAccessToken()
    {
        $tokenNames = [
            'access_token',
            'id_token',
            'access_token_secret',
            'token_type',
            'refresh_token',
            'expires_in',
            'expires_at',
        ];

        $tokens = [];

        foreach ($tokenNames as $name) {
            if ($this->getStoredData($name)) {
                $tokens[$name] = $this->getStoredData($name);
            }
        }

        return $tokens;
    }

    /**
     * {@inheritdoc}
     */
    protected function validateAccessTokenExchange($response)
    {
        $collection = parent::validateAccessTokenExchange($response);

        $this->storeData('id_token', $collection->get('id_token'));

        return $collection;
    }

    public function getUserProfile()
    {
        $id_token = $this->getStoredData('id_token');

        $verifyTokenSignature =
            $this->config->exists('verifyTokenSignature') ? $this->config->get('verifyTokenSignature') : true;

        if (!$verifyTokenSignature) {
            // payload extraction by https://github.com/omidborjian
            // https://github.com/hybridauth/hybridauth/issues/1095#issuecomment-626479263
            // JWT splits the string to 3 components 1) first is header 2) is payload 3) is signature
            $payload = explode('.', $id_token)[1];
            $payload = json_decode(base64_decode($payload));
        } else {
            // validate the token signature and get the payload
            $publicKeys = $this->apiRequest('keys');

            JWT::$leeway = 120;

            $error = false;
            $payload = null;

            foreach ($publicKeys->keys as $publicKey) {
                try {
                    $rsa = new RSA();
                    $jwk = (array)$publicKey;

                    $rsa->loadKey(
                        [
                            'e' => new BigInteger(base64_decode($jwk['e']), 256),
                            'n' => new BigInteger(base64_decode(strtr($jwk['n'], '-_', '+/'), true), 256)
                        ]
                    );
                    $pem = $rsa->getPublicKey();

                    $payload = ($this->getJwtVersion() < '6.2') ?
                        JWT::decode($id_token, $pem, ['RS256']) :
                        JWT::decode($id_token, new Key($pem, 'RS256'));
                    break;
                } catch (Exception $e) {
                    $error = $e->getMessage();
                    if ($e instanceof ExpiredException) {
                        break;
                    }
                }
            }

            if ($error && !$payload) {
                throw new Exception($error);
            }
        }

        $data = new Data\Collection($payload);

        if (!$data->exists('sub')) {
            throw new UnexpectedValueException('Missing token payload.');
        }

        $userProfile = new User\Profile();
        $userProfile->identifier = $data->get('sub');
        $userProfile->email = $data->get('email');
        $this->storeData('expires_at', $data->get('exp'));

        if (!empty($_REQUEST['user'])) {
            $objUser = json_decode($_REQUEST['user']);
            $user = new Data\Collection($objUser);
            if (!$user->isEmpty()) {
                $name = $user->get('name');
                $userProfile->firstName = $name->firstName;
                $userProfile->lastName = $name->lastName;
                $userProfile->displayName = join(' ', [$userProfile->firstName, $userProfile->lastName]);
            }
        }

        return $userProfile;
    }

    /**
     * @return string secret token
     */
    private function getSecret()
    {
        // Your 10-character Team ID
        if (!$team_id = $this->config->filter('keys')->get('team_id')) {
            throw new InvalidApplicationCredentialsException(
                'Missing parameter team_id: your team id is required to generate the JWS token.'
            );
        }

        // Your Services ID, e.g. com.aaronparecki.services
        if (!$client_id = $this->config->filter('keys')->get('id') ?: $this->config->filter('keys')->get('key')) {
            throw new InvalidApplicationCredentialsException(
                'Missing parameter id: your client id is required to generate the JWS token.'
            );
        }

        // Find the 10-char Key ID value from the portal
        if (!$key_id = $this->config->filter('keys')->get('key_id')) {
            throw new InvalidApplicationCredentialsException(
                'Missing parameter key_id: your key id is required to generate the JWS token.'
            );
        }

        // Find the 10-char Key ID value from the portal
        $key_content = $this->config->filter('keys')->get('key_content');

        // Save your private key from Apple in a file called `key.txt`
        if (!$key_content) {
            if (!$key_file = $this->config->filter('keys')->get('key_file')) {
                throw new InvalidApplicationCredentialsException(
                    'Missing parameter key_content or key_file: your key is required to generate the JWS token.'
                );
            }

            if (!file_exists($key_file)) {
                throw new InvalidApplicationCredentialsException(
                    "Your key file $key_file does not exist."
                );
            }

            $key_content = file_get_contents($key_file);
        }

        $data = [
            'iat' => time(),
            'exp' => time() + 86400 * 180,
            'iss' => $team_id,
            'aud' => 'https://appleid.apple.com',
            'sub' => $client_id
        ];

        $secret = JWT::encode($data, $key_content, 'ES256', $key_id);

        return $secret;
    }

    private function getJwtVersion()
    {
        return InstalledVersions::getVersion('firebase/php-jwt');
    }
}
