<?php
namespace Aws\Token;

use Aws\Exception\TokenException;
use Aws\SSOOIDC\SSOOIDCClient;
use GuzzleHttp\Promise;

/**
 * Token that comes from the SSO provider
 */
class SsoTokenProvider implements RefreshableTokenProviderInterface
{
    use ParsesIniTrait;

    const ENV_PROFILE = 'AWS_PROFILE';
    const REFRESH_WINDOW_IN_SECS = 300;
    const REFRESH_ATTEMPT_WINDOW_IN_SECS = 30;

    /** @var string $profileName */
    private $profileName;

    /** @var string $configFilePath */
    private $configFilePath;

    /** @var SSOOIDCClient $ssoOidcClient */
    private $ssoOidcClient;

    /** @var string $ssoSessionName */
    private $ssoSessionName;

    /**
     * Constructs a new SsoTokenProvider object, which will fetch a token from an authenticated SSO profile
     * @param string $profileName The name of the profile that contains the sso_session key
     * @param string|null $configFilePath Name of the config file to sso profile from
     * @param SSOOIDCClient|null $ssoOidcClient The sso client for generating a new token
     */
    public function __construct(
        $profileName,
        $configFilePath = null,
        ?SSOOIDCClient $ssoOidcClient = null
    ) {
        $this->profileName = $this->resolveProfileName($profileName);
        $this->configFilePath =  $this->resolveConfigFile($configFilePath);
        $this->ssoOidcClient = $ssoOidcClient;
    }

    /**
     * This method resolves the profile name to be used. The
     * profile provided as instantiation argument takes precedence,
     * followed by AWS_PROFILE env variable, otherwise `default` is
     * used.
     *
     * @param string|null $argProfileName The profile provided as argument.
     *
     * @return string
     */
    private function resolveProfileName($argProfileName): string
    {
        if (empty($argProfileName)) {
            return getenv(self::ENV_PROFILE) ?: 'default';
        } else {
            return $argProfileName;
        }
    }

    /**
     * This method resolves the config file from where the profiles
     * are going to be loaded from. If $argFileName is not empty then,
     * it takes precedence over the default config file location.
     *
     * @param string|null $argConfigFilePath The config path provided as argument.
     *
     * @return string
     */
    private function resolveConfigFile($argConfigFilePath): string
    {
        if (empty($argConfigFilePath)) {
            return self::getHomeDir() . '/.aws/config';
        } else{
            return $argConfigFilePath;
        }
    }

    /**
     *  Loads cached sso credentials.
     *
     * @return Promise\PromiseInterface
     */
    public function __invoke()
    {
        return Promise\Coroutine::of(function () {
            if (empty($this->configFilePath) || !is_readable($this->configFilePath)) {
                throw new TokenException("Cannot read profiles from {$this->configFilePath}");
            }

            $profiles = self::loadProfiles($this->configFilePath);
            if (!isset($profiles[$this->profileName])) {
                throw new TokenException("Profile `{$this->profileName}` does not exist in {$this->configFilePath}.");
            }

            $profile = $profiles[$this->profileName];
            if (empty($profile['sso_session'])) {
                throw new TokenException(
                    "Profile `{$this->profileName}` in {$this->configFilePath} must contain an sso_session."
                );
            }

            $ssoSessionName = $profile['sso_session'];
            $this->ssoSessionName = $ssoSessionName;
            $profileSsoSession = 'sso-session ' . $ssoSessionName;
            if (empty($profiles[$profileSsoSession])) {
                throw new TokenException(
                    "Sso session `{$ssoSessionName}` does not exist in {$this->configFilePath}"
                );
            }

            $sessionProfileData = $profiles[$profileSsoSession];
            foreach (['sso_start_url', 'sso_region'] as $requiredProp) {
                if (empty($sessionProfileData[$requiredProp])) {
                    throw new TokenException(
                        "Sso session `{$ssoSessionName}` in {$this->configFilePath} is missing the required property `{$requiredProp}`"
                    );
                }
            }

            $tokenData = $this->refresh();
            $tokenLocation = self::getTokenLocation($ssoSessionName);
            $this->validateTokenData($tokenLocation, $tokenData);
            $ssoToken = SsoToken::fromTokenData($tokenData);
            // To make sure the token is not expired
            if ($ssoToken->isExpired()) {
                throw new TokenException("Cached SSO token returned an expired token.");
            }

            yield $ssoToken;
        });
    }

    /**
     * This method attempt to refresh when possible.
     * If a refresh is not possible then it just returns
     * the current token data as it is.
     *
     * @return array
     * @throws TokenException
     */
    public function refresh(): array
    {
        $tokenLocation = self::getTokenLocation($this->ssoSessionName);
        $tokenData = $this->getTokenData($tokenLocation);
        if (!$this->shouldAttemptRefresh()) {
            return $tokenData;
        }

        if (null === $this->ssoOidcClient) {
            throw new TokenException(
                "Cannot refresh this token without an 'ssooidcClient' "
            );
        }

        foreach (['clientId', 'clientSecret', 'refreshToken'] as $requiredProp) {
            if (empty($tokenData[$requiredProp])) {
                throw new TokenException(
                    "Cannot refresh this token without `{$requiredProp}` being set"
                );
            }
        }

        $response = $this->ssoOidcClient->createToken([
            'clientId' => $tokenData['clientId'],
            'clientSecret' => $tokenData['clientSecret'],
            'grantType' => 'refresh_token', // REQUIRED
            'refreshToken' => $tokenData['refreshToken'],
        ]);
        if ($response['@metadata']['statusCode'] !== 200) {
            throw new TokenException('Unable to create a new sso token');
        }

        $tokenData['accessToken'] = $response['accessToken'];
        $tokenData['expiresAt'] = time () + $response['expiresIn'];
        $tokenData['refreshToken'] = $response['refreshToken'];

        return $this->writeNewTokenDataToDisk($tokenData, $tokenLocation);
    }

    /**
     * This method checks for whether a token refresh should happen.
     * It will return true just if more than 30 seconds has happened
     * since last refresh, and if the expiration is within a 5-minutes
     * window from the current time.
     *
     * @return bool
     */
    public function shouldAttemptRefresh(): bool
    {
        $tokenLocation = self::getTokenLocation($this->ssoSessionName);
        $tokenData = $this->getTokenData($tokenLocation);
        if (empty($tokenData['expiresAt'])) {
            throw new TokenException(
                "Token file at $tokenLocation must contain an expiration date"
            );
        }

        $tokenExpiresAt = strtotime($tokenData['expiresAt']);
        $lastRefreshAt = filemtime($tokenLocation);
        $now = \time();

        // If last refresh happened after 30 seconds
        // and if the token expiration is in the 5 minutes window
        return ($now - $lastRefreshAt) > self::REFRESH_ATTEMPT_WINDOW_IN_SECS
            && ($tokenExpiresAt - $now) < self::REFRESH_WINDOW_IN_SECS;
    }

    /**
     * @param $sso_session
     * @return string
     */
    public static function getTokenLocation($sso_session): string
    {
        return self::getHomeDir()
            . '/.aws/sso/cache/'
            . mb_convert_encoding(sha1($sso_session), "UTF-8")
            . ".json";
    }

    /**
     * @param $tokenLocation
     * @return array
     */
    function getTokenData($tokenLocation): array
    {
        if (empty($tokenLocation) || !is_readable($tokenLocation)) {
            throw new TokenException("Unable to read token file at {$tokenLocation}");
        }

        return json_decode(file_get_contents($tokenLocation), true);
    }

    /**
     * @param $tokenData
     * @param $tokenLocation
     * @return mixed
     */
    private function validateTokenData($tokenLocation, $tokenData)
    {
        foreach (['accessToken', 'expiresAt'] as $requiredProp) {
            if (empty($tokenData[$requiredProp])) {
                throw new TokenException(
                    "Token file at {$tokenLocation} must contain the required property `{$requiredProp}`"
                );
            }
        }

        $expiration = strtotime($tokenData['expiresAt']);
        if ($expiration === false) {
            throw new TokenException("Cached SSO token returned an invalid expiration");
        } elseif ($expiration < time()) {
            throw new TokenException("Cached SSO token returned an expired token");
        }

        return $tokenData;
    }

    /**
     * @param array $tokenData
     * @param string $tokenLocation
     *
     * @return array
     */
    private function writeNewTokenDataToDisk(array $tokenData, $tokenLocation): array
    {
        $tokenData['expiresAt'] = gmdate(
            'Y-m-d\TH:i:s\Z',
            $tokenData['expiresAt']
        );
        file_put_contents($tokenLocation, json_encode(array_filter($tokenData)));

        return $tokenData;
    }
}
