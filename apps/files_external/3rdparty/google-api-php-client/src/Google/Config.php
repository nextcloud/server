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

/**
 * A class to contain the library configuration for the Google API client.
 */
class Google_Config
{
  private $configuration;

  /**
   * Create a new Google_Config. Can accept an ini file location with the
   * local configuration. For example:
   *     application_name: "My App";
   *
   * @param [$ini_file_location] - optional - The location of the ini file to load
   */
  public function __construct($ini_file_location = null)
  {
    $this->configuration = array(
      // The application_name is included in the User-Agent HTTP header.
      'application_name' => '',

      // Which Authentication, Storage and HTTP IO classes to use.
      'auth_class'    => 'Google_Auth_OAuth2',
      'io_class'      => 'Google_IO_Stream',
      'cache_class'   => 'Google_Cache_File',

      // Don't change these unless you're working against a special development
      // or testing environment.
      'base_path' => 'https://www.googleapis.com',

      // Definition of class specific values, like file paths and so on.
      'classes' => array(
        // If you want to pass in OAuth 2.0 settings, they will need to be
        // structured like this.
        'Google_Http_Request' => array(
          // Disable the use of gzip on calls if set to true.
          'disable_gzip' => false
        ),
        'Google_Auth_OAuth2' => array(
          // Keys for OAuth 2.0 access, see the API console at
          // https://developers.google.com/console
          'client_id' => '',
          'client_secret' => '',
          'redirect_uri' => '',

          // Simple API access key, also from the API console. Ensure you get
          // a Server key, and not a Browser key.
          'developer_key' => '',

          // Other parameters.
          'access_type' => 'online',
          'approval_prompt' => 'auto',
          'request_visible_actions' => '',
          'federated_signon_certs_url' =>
              'https://www.googleapis.com/oauth2/v1/certs',
        ),
        // Set a default directory for the file cache.
        'Google_Cache_File' => array(
          'directory' => sys_get_temp_dir() . '/Google_Client'
        )
      ),

      // Definition of service specific values like scopes, oauth token URLs,
      // etc. Example:
      'services' => array(
      ),
    );
    if ($ini_file_location) {
      $ini = parse_ini_file($ini_file_location, true);
      if (is_array($ini) && count($ini)) {
        $this->configuration = array_merge($this->configuration, $ini);
      }
    }
  }

  /**
   * Set configuration specific to a given class.
   * $config->setClassConfig('Google_Cache_File',
   *   array('directory' => '/tmp/cache'));
   * @param $class The class name for the configuration
   * @param $config string key or an array of configuration values
   * @param $value optional - if $config is a key, the value
   */
  public function setClassConfig($class, $config, $value = null)
  {
    if (!is_array($config)) {
      if (!isset($this->configuration['classes'][$class])) {
        $this->configuration['classes'][$class] = array();
      }
      $this->configuration['classes'][$class][$config] = $value;
    } else {
      $this->configuration['classes'][$class] = $config;
    }
  }
  
  public function getClassConfig($class, $key = null)
  {
    if (!isset($this->configuration['classes'][$class])) {
      return null;
    }
    if ($key === null) {
      return $this->configuration['classes'][$class];
    } else {
      return $this->configuration['classes'][$class][$key];
    }
  }

  /**
   * Return the configured cache class.
   * @return string
   */
  public function getCacheClass()
  {
    return $this->configuration['cache_class'];
  }

  /**
   * Return the configured Auth class.
   * @return string
   */
  public function getAuthClass()
  {
    return $this->configuration['auth_class'];
  }
  
  /**
   * Set the auth class.
   *
   * @param $class the class name to set
   */
  public function setAuthClass($class)
  {
    $prev = $this->configuration['auth_class'];
    if (!isset($this->configuration['classes'][$class]) &&
        isset($this->configuration['classes'][$prev])) {
      $this->configuration['classes'][$class] =
          $this->configuration['classes'][$prev];
    }
    $this->configuration['auth_class'] = $class;
  }
  
  /**
   * Set the IO class.
   *
   * @param $class the class name to set
   */
  public function setIoClass($class)
  {
    $prev = $this->configuration['io_class'];
    if (!isset($this->configuration['classes'][$class]) &&
        isset($this->configuration['classes'][$prev])) {
      $this->configuration['classes'][$class] =
          $this->configuration['classes'][$prev];
    }
    $this->configuration['io_class'] = $class;
  }

  /**
   * Set the cache class.
   *
   * @param $class the class name to set
   */
  public function setCacheClass($class)
  {
    $prev = $this->configuration['cache_class'];
    if (!isset($this->configuration['classes'][$class]) &&
        isset($this->configuration['classes'][$prev])) {
      $this->configuration['classes'][$class] =
          $this->configuration['classes'][$prev];
    }
    $this->configuration['cache_class'] = $class;
  }

  /**
   * Return the configured IO class.
   * @return string
   */
  public function getIoClass()
  {
    return $this->configuration['io_class'];
  }

  /**
   * Set the application name, this is included in the User-Agent HTTP header.
   * @param string $name
   */
  public function setApplicationName($name)
  {
    $this->configuration['application_name'] = $name;
  }

  /**
   * @return string the name of the application
   */
  public function getApplicationName()
  {
    return $this->configuration['application_name'];
  }

  /**
   * Set the client ID for the auth class.
   * @param $key string - the API console client ID
   */
  public function setClientId($clientId)
  {
    $this->setAuthConfig('client_id', $clientId);
  }

  /**
   * Set the client secret for the auth class.
   * @param $key string - the API console client secret
   */
  public function setClientSecret($secret)
  {
    $this->setAuthConfig('client_secret', $secret);
  }

  /**
   * Set the redirect uri for the auth class. Note that if using the
   * Javascript based sign in flow, this should be the string 'postmessage'.
   * @param $key string - the URI that users should be redirected to
   */
  public function setRedirectUri($uri)
  {
    $this->setAuthConfig('redirect_uri', $uri);
  }

  /**
   * Set the app activities for the auth class.
   * @param $rva string a space separated list of app activity types
   */
  public function setRequestVisibleActions($rva)
  {
    $this->setAuthConfig('request_visible_actions', $rva);
  }

  /**
   * Set the the access type requested (offline or online.)
   * @param $access string - the access type
   */
  public function setAccessType($access)
  {
    $this->setAuthConfig('access_type', $access);
  }

  /**
   * Set when to show the approval prompt (auto or force)
   * @param $approval string - the approval request
   */
  public function setApprovalPrompt($approval)
  {
    $this->setAuthConfig('approval_prompt', $approval);
  }
  
  /**
   * Set the developer key for the auth class. Note that this is separate value
   * from the client ID - if it looks like a URL, its a client ID!
   * @param $key string - the API console developer key
   */
  public function setDeveloperKey($key)
  {
    $this->setAuthConfig('developer_key', $key);
  }

  /**
   * @return string the base URL to use for API calls
   */
  public function getBasePath()
  {
    return $this->configuration['base_path'];
  }
  
  /**
   * Set the auth configuration for the current auth class.
   * @param $key - the key to set
   * @param $value - the parameter value
   */
  private function setAuthConfig($key, $value)
  {
    if (!isset($this->configuration['classes'][$this->getAuthClass()])) {
      $this->configuration['classes'][$this->getAuthClass()] = array();
    }
    $this->configuration['classes'][$this->getAuthClass()][$key] = $value;
  }
}
