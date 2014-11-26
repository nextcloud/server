<?php
/*
 * Copyright 2010 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require_once 'Google/Auth/AssertionCredentials.php';
require_once 'Google/Cache/File.php';
require_once 'Google/Cache/Memcache.php';
require_once 'Google/Config.php';
require_once 'Google/Collection.php';
require_once 'Google/Exception.php';
require_once 'Google/IO/Curl.php';
require_once 'Google/IO/Stream.php';
require_once 'Google/Model.php';
require_once 'Google/Service.php';
require_once 'Google/Service/Resource.php';

/**
 * The Google API Client
 * http://code.google.com/p/google-api-php-client/
 *
 * @author Chris Chabot <chabotc@google.com>
 * @author Chirag Shah <chirags@google.com>
 */
class Google_Client
{
  const LIBVER = "1.0.6-beta";
  const USER_AGENT_SUFFIX = "google-api-php-client/";
  /**
   * @var Google_Auth_Abstract $auth
   */
  private $auth;

  /**
   * @var Google_IO_Abstract $io
   */
  private $io;

  /**
   * @var Google_Cache_Abstract $cache
   */
  private $cache;

  /**
   * @var Google_Config $config
   */
  private $config;

  /**
   * @var boolean $deferExecution
   */
  private $deferExecution = false;

  /** @var array $scopes */
  // Scopes requested by the client
  protected $requestedScopes = array();

  // definitions of services that are discovered.
  protected $services = array();

  // Used to track authenticated state, can't discover services after doing authenticate()
  private $authenticated = false;

  /**
   * Construct the Google Client.
   *
   * @param $config Google_Config or string for the ini file to load
   */
  public function __construct($config = null)
  {
    if (is_string($config) && strlen($config)) {
      $config = new Google_Config($config);
    } else if ( !($config instanceof Google_Config)) {
      $config = new Google_Config();

      if ($this->isAppEngine()) {
        // Automatically use Memcache if we're in AppEngine.
        $config->setCacheClass('Google_Cache_Memcache');
      }

      if (version_compare(phpversion(), "5.3.4", "<=") || $this->isAppEngine()) {
        // Automatically disable compress.zlib, as currently unsupported.
        $config->setClassConfig('Google_Http_Request', 'disable_gzip', true);
      }
    }

    if ($config->getIoClass() == Google_Config::USE_AUTO_IO_SELECTION) {
      if (function_exists('curl_version') && function_exists('curl_exec')) {
        $config->setIoClass("Google_IO_Curl");
      } else {
        $config->setIoClass("Google_IO_Stream");
      }
    }

    $this->config = $config;
  }

  /**
   * Get a string containing the version of the library.
   *
   * @return string
   */
  public function getLibraryVersion()
  {
    return self::LIBVER;
  }

  /**
   * Attempt to exchange a code for an valid authentication token.
   * Helper wrapped around the OAuth 2.0 implementation.
   *
   * @param $code string code from accounts.google.com
   * @return string token
   */
  public function authenticate($code)
  {
    $this->authenticated = true;
    return $this->getAuth()->authenticate($code);
  }

  /**
   * Set the auth config from the JSON string provided.
   * This structure should match the file downloaded from
   * the "Download JSON" button on in the Google Developer
   * Console.
   * @param string $json the configuration json
   */
  public function setAuthConfig($json)
  {
    $data = json_decode($json);
    $key = isset($data->installed) ? 'installed' : 'web';
    if (!isset($data->$key)) {
      throw new Google_Exception("Invalid client secret JSON file.");
    }
    $this->setClientId($data->$key->client_id);
    $this->setClientSecret($data->$key->client_secret);
    if (isset($data->$key->redirect_uris)) {
      $this->setRedirectUri($data->$key->redirect_uris[0]);
    }
  }

  /**
   * Set the auth config from the JSON file in the path
   * provided. This should match the file downloaded from
   * the "Download JSON" button on in the Google Developer
   * Console.
   * @param string $file the file location of the client json
   */
  public function setAuthConfigFile($file)
  {
    $this->setAuthConfig(file_get_contents($file));
  }

  /**
   * @return array
   * @visible For Testing
   */
  public function prepareScopes()
  {
    if (empty($this->requestedScopes)) {
      throw new Google_Auth_Exception("No scopes specified");
    }
    $scopes = implode(' ', $this->requestedScopes);
    return $scopes;
  }

  /**
   * Set the OAuth 2.0 access token using the string that resulted from calling createAuthUrl()
   * or Google_Client#getAccessToken().
   * @param string $accessToken JSON encoded string containing in the following format:
   * {"access_token":"TOKEN", "refresh_token":"TOKEN", "token_type":"Bearer",
   *  "expires_in":3600, "id_token":"TOKEN", "created":1320790426}
   */
  public function setAccessToken($accessToken)
  {
    if ($accessToken == 'null') {
      $accessToken = null;
    }
    $this->getAuth()->setAccessToken($accessToken);
  }



  /**
   * Set the authenticator object
   * @param Google_Auth_Abstract $auth
   */
  public function setAuth(Google_Auth_Abstract $auth)
  {
    $this->config->setAuthClass(get_class($auth));
    $this->auth = $auth;
  }

  /**
   * Set the IO object
   * @param Google_Io_Abstract $auth
   */
  public function setIo(Google_Io_Abstract $io)
  {
    $this->config->setIoClass(get_class($io));
    $this->io = $io;
  }

  /**
   * Set the Cache object
   * @param Google_Cache_Abstract $auth
   */
  public function setCache(Google_Cache_Abstract $cache)
  {
    $this->config->setCacheClass(get_class($cache));
    $this->cache = $cache;
  }

  /**
   * Construct the OAuth 2.0 authorization request URI.
   * @return string
   */
  public function createAuthUrl()
  {
    $scopes = $this->prepareScopes();
    return $this->getAuth()->createAuthUrl($scopes);
  }

  /**
   * Get the OAuth 2.0 access token.
   * @return string $accessToken JSON encoded string in the following format:
   * {"access_token":"TOKEN", "refresh_token":"TOKEN", "token_type":"Bearer",
   *  "expires_in":3600,"id_token":"TOKEN", "created":1320790426}
   */
  public function getAccessToken()
  {
    $token = $this->getAuth()->getAccessToken();
    // The response is json encoded, so could be the string null.
    // It is arguable whether this check should be here or lower
    // in the library.
    return (null == $token || 'null' == $token || '[]' == $token) ? null : $token;
  }

  /**
   * Get the OAuth 2.0 refresh token.
   * @return string $refreshToken refresh token or null if not available
   */
  public function getRefreshToken()
  {
    return $this->getAuth()->getRefreshToken();
  }

  /**
   * Returns if the access_token is expired.
   * @return bool Returns True if the access_token is expired.
   */
  public function isAccessTokenExpired()
  {
    return $this->getAuth()->isAccessTokenExpired();
  }

  /**
   * Set OAuth 2.0 "state" parameter to achieve per-request customization.
   * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-22#section-3.1.2.2
   * @param string $state
   */
  public function setState($state)
  {
    $this->getAuth()->setState($state);
  }

  /**
   * @param string $accessType Possible values for access_type include:
   *  {@code "offline"} to request offline access from the user.
   *  {@code "online"} to request online access from the user.
   */
  public function setAccessType($accessType)
  {
    $this->config->setAccessType($accessType);
  }

  /**
   * @param string $approvalPrompt Possible values for approval_prompt include:
   *  {@code "force"} to force the approval UI to appear. (This is the default value)
   *  {@code "auto"} to request auto-approval when possible.
   */
  public function setApprovalPrompt($approvalPrompt)
  {
    $this->config->setApprovalPrompt($approvalPrompt);
  }

  /**
   * Set the login hint, email address or sub id.
   * @param string $loginHint
   */
  public function setLoginHint($loginHint)
  {
      $this->config->setLoginHint($loginHint);
  }

  /**
   * Set the application name, this is included in the User-Agent HTTP header.
   * @param string $applicationName
   */
  public function setApplicationName($applicationName)
  {
    $this->config->setApplicationName($applicationName);
  }

  /**
   * Set the OAuth 2.0 Client ID.
   * @param string $clientId
   */
  public function setClientId($clientId)
  {
    $this->config->setClientId($clientId);
  }

  /**
   * Set the OAuth 2.0 Client Secret.
   * @param string $clientSecret
   */
  public function setClientSecret($clientSecret)
  {
    $this->config->setClientSecret($clientSecret);
  }

  /**
   * Set the OAuth 2.0 Redirect URI.
   * @param string $redirectUri
   */
  public function setRedirectUri($redirectUri)
  {
    $this->config->setRedirectUri($redirectUri);
  }

  /**
   * If 'plus.login' is included in the list of requested scopes, you can use
   * this method to define types of app activities that your app will write.
   * You can find a list of available types here:
   * @link https://developers.google.com/+/api/moment-types
   *
   * @param array $requestVisibleActions Array of app activity types
   */
  public function setRequestVisibleActions($requestVisibleActions)
  {
    if (is_array($requestVisibleActions)) {
      $requestVisibleActions = join(" ", $requestVisibleActions);
    }
    $this->config->setRequestVisibleActions($requestVisibleActions);
  }

  /**
   * Set the developer key to use, these are obtained through the API Console.
   * @see http://code.google.com/apis/console-help/#generatingdevkeys
   * @param string $developerKey
   */
  public function setDeveloperKey($developerKey)
  {
    $this->config->setDeveloperKey($developerKey);
  }

  /**
   * Set the hd (hosted domain) parameter streamlines the login process for
   * Google Apps hosted accounts. By including the domain of the user, you
   * restrict sign-in to accounts at that domain.
   * @param $hd string - the domain to use.
   */
  public function setHostedDomain($hd)
  {
    $this->config->setHostedDomain($hd);
  }

  /**
   * Set the prompt hint. Valid values are none, consent and select_account.
   * If no value is specified and the user has not previously authorized
   * access, then the user is shown a consent screen.
   * @param $prompt string
   */
  public function setPrompt($prompt)
  {
    $this->config->setPrompt($prompt);
  }

  /**
   * openid.realm is a parameter from the OpenID 2.0 protocol, not from OAuth
   * 2.0. It is used in OpenID 2.0 requests to signify the URL-space for which
   * an authentication request is valid.
   * @param $realm string - the URL-space to use.
   */
  public function setOpenidRealm($realm)
  {
    $this->config->setOpenidRealm($realm);
  }

  /**
   * If this is provided with the value true, and the authorization request is
   * granted, the authorization will include any previous authorizations
   * granted to this user/application combination for other scopes.
   * @param $include boolean - the URL-space to use.
   */
  public function setIncludeGrantedScopes($include)
  {
    $this->config->setIncludeGrantedScopes($include);
  }

  /**
   * Fetches a fresh OAuth 2.0 access token with the given refresh token.
   * @param string $refreshToken
   * @return void
   */
  public function refreshToken($refreshToken)
  {
    return $this->getAuth()->refreshToken($refreshToken);
  }

  /**
   * Revoke an OAuth2 access token or refresh token. This method will revoke the current access
   * token, if a token isn't provided.
   * @throws Google_Auth_Exception
   * @param string|null $token The token (access token or a refresh token) that should be revoked.
   * @return boolean Returns True if the revocation was successful, otherwise False.
   */
  public function revokeToken($token = null)
  {
    return $this->getAuth()->revokeToken($token);
  }

  /**
   * Verify an id_token. This method will verify the current id_token, if one
   * isn't provided.
   * @throws Google_Auth_Exception
   * @param string|null $token The token (id_token) that should be verified.
   * @return Google_Auth_LoginTicket Returns an apiLoginTicket if the verification was
   * successful.
   */
  public function verifyIdToken($token = null)
  {
    return $this->getAuth()->verifyIdToken($token);
  }

  /**
   * Verify a JWT that was signed with your own certificates.
   *
   * @param $jwt the token
   * @param $certs array of certificates
   * @param $required_audience the expected consumer of the token
   * @param [$issuer] the expected issues, defaults to Google
   * @param [$max_expiry] the max lifetime of a token, defaults to MAX_TOKEN_LIFETIME_SECS
   * @return token information if valid, false if not
   */
  public function verifySignedJwt($id_token, $cert_location, $audience, $issuer, $max_expiry = null)
  {
    $auth = new Google_Auth_OAuth2($this);
    $certs = $auth->retrieveCertsFromLocation($cert_location);
    return $auth->verifySignedJwtWithCerts($id_token, $certs, $audience, $issuer, $max_expiry);
  }

  /**
   * @param Google_Auth_AssertionCredentials $creds
   * @return void
   */
  public function setAssertionCredentials(Google_Auth_AssertionCredentials $creds)
  {
    $this->getAuth()->setAssertionCredentials($creds);
  }

  /**
   * Set the scopes to be requested. Must be called before createAuthUrl().
   * Will remove any previously configured scopes.
   * @param array $scopes, ie: array('https://www.googleapis.com/auth/plus.login',
   * 'https://www.googleapis.com/auth/moderator')
   */
  public function setScopes($scopes)
  {
    $this->requestedScopes = array();
    $this->addScope($scopes);
  }

  /**
   * This functions adds a scope to be requested as part of the OAuth2.0 flow.
   * Will append any scopes not previously requested to the scope parameter.
   * A single string will be treated as a scope to request. An array of strings
   * will each be appended.
   * @param $scope_or_scopes string|array e.g. "profile"
   */
  public function addScope($scope_or_scopes)
  {
    if (is_string($scope_or_scopes) && !in_array($scope_or_scopes, $this->requestedScopes)) {
      $this->requestedScopes[] = $scope_or_scopes;
    } else if (is_array($scope_or_scopes)) {
      foreach ($scope_or_scopes as $scope) {
        $this->addScope($scope);
      }
    }
  }

  /**
   * Returns the list of scopes requested by the client
   * @return array the list of scopes
   *
   */
  public function getScopes()
  {
     return $this->requestedScopes;
  }

  /**
   * Declare whether batch calls should be used. This may increase throughput
   * by making multiple requests in one connection.
   *
   * @param boolean $useBatch True if the batch support should
   * be enabled. Defaults to False.
   */
  public function setUseBatch($useBatch)
  {
    // This is actually an alias for setDefer.
    $this->setDefer($useBatch);
  }

  /**
   * Declare whether making API calls should make the call immediately, or
   * return a request which can be called with ->execute();
   *
   * @param boolean $defer True if calls should not be executed right away.
   */
  public function setDefer($defer)
  {
    $this->deferExecution = $defer;
  }

  /**
   * Helper method to execute deferred HTTP requests.
   *
   * @return object of the type of the expected class or array.
   */
  public function execute($request)
  {
    if ($request instanceof Google_Http_Request) {
      $request->setUserAgent(
          $this->getApplicationName()
          . " " . self::USER_AGENT_SUFFIX
          . $this->getLibraryVersion()
      );
      if (!$this->getClassConfig("Google_Http_Request", "disable_gzip")) {
        $request->enableGzip();
      }
      $request->maybeMoveParametersToBody();
      return Google_Http_REST::execute($this, $request);
    } else if ($request instanceof Google_Http_Batch) {
      return $request->execute();
    } else {
      throw new Google_Exception("Do not know how to execute this type of object.");
    }
  }

  /**
   * Whether or not to return raw requests
   * @return boolean
   */
  public function shouldDefer()
  {
    return $this->deferExecution;
  }

  /**
   * @return Google_Auth_Abstract Authentication implementation
   */
  public function getAuth()
  {
    if (!isset($this->auth)) {
      $class = $this->config->getAuthClass();
      $this->auth = new $class($this);
    }
    return $this->auth;
  }

  /**
   * @return Google_IO_Abstract IO implementation
   */
  public function getIo()
  {
    if (!isset($this->io)) {
      $class = $this->config->getIoClass();
      $this->io = new $class($this);
    }
    return $this->io;
  }

  /**
   * @return Google_Cache_Abstract Cache implementation
   */
  public function getCache()
  {
    if (!isset($this->cache)) {
      $class = $this->config->getCacheClass();
      $this->cache = new $class($this);
    }
    return $this->cache;
  }

  /**
   * Retrieve custom configuration for a specific class.
   * @param $class string|object - class or instance of class to retrieve
   * @param $key string optional - key to retrieve
   */
  public function getClassConfig($class, $key = null)
  {
    if (!is_string($class)) {
      $class = get_class($class);
    }
    return $this->config->getClassConfig($class, $key);
  }

  /**
   * Set configuration specific to a given class.
   * $config->setClassConfig('Google_Cache_File',
   *   array('directory' => '/tmp/cache'));
   * @param $class The class name for the configuration
   * @param $config string key or an array of configuration values
   * @param $value optional - if $config is a key, the value
   *
   */
  public function setClassConfig($class, $config, $value = null)
  {
    if (!is_string($class)) {
      $class = get_class($class);
    }
    return $this->config->setClassConfig($class, $config, $value);

  }

  /**
   * @return string the base URL to use for calls to the APIs
   */
  public function getBasePath()
  {
    return $this->config->getBasePath();
  }

  /**
   * @return string the name of the application
   */
  public function getApplicationName()
  {
    return $this->config->getApplicationName();
  }

  /**
   * Are we running in Google AppEngine?
   * return bool
   */
  public function isAppEngine()
  {
    return (isset($_SERVER['SERVER_SOFTWARE']) &&
        strpos($_SERVER['SERVER_SOFTWARE'], 'Google App Engine') !== false);
  }
}
