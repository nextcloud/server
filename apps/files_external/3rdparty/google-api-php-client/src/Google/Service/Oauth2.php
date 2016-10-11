<?php
/*
 * Copyright 2010 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */

/**
 * Service definition for Oauth2 (v2).
 *
 * <p>
 * Lets you access OAuth2 protocol related APIs.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://developers.google.com/accounts/docs/OAuth2" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_Oauth2 extends Google_Service
{
  /** Know your basic profile info and list of people in your circles.. */
  const PLUS_LOGIN =
      "https://www.googleapis.com/auth/plus.login";
  /** Know who you are on Google. */
  const PLUS_ME =
      "https://www.googleapis.com/auth/plus.me";
  /** View your email address. */
  const USERINFO_EMAIL =
      "https://www.googleapis.com/auth/userinfo.email";
  /** View your basic profile info. */
  const USERINFO_PROFILE =
      "https://www.googleapis.com/auth/userinfo.profile";

  public $userinfo;
  public $userinfo_v2_me;
  private $base_methods;

  /**
   * Constructs the internal representation of the Oauth2 service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://www.googleapis.com/';
    $this->servicePath = '';
    $this->version = 'v2';
    $this->serviceName = 'oauth2';

    $this->userinfo = new Google_Service_Oauth2_Userinfo_Resource(
        $this,
        $this->serviceName,
        'userinfo',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'oauth2/v2/userinfo',
              'httpMethod' => 'GET',
              'parameters' => array(),
            ),
          )
        )
    );
    $this->userinfo_v2_me = new Google_Service_Oauth2_UserinfoV2Me_Resource(
        $this,
        $this->serviceName,
        'me',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userinfo/v2/me',
              'httpMethod' => 'GET',
              'parameters' => array(),
            ),
          )
        )
    );
    $this->base_methods = new Google_Service_Resource(
        $this,
        $this->serviceName,
        '',
        array(
          'methods' => array(
            'getCertForOpenIdConnect' => array(
              'path' => 'oauth2/v2/certs',
              'httpMethod' => 'GET',
              'parameters' => array(),
            ),'tokeninfo' => array(
              'path' => 'oauth2/v2/tokeninfo',
              'httpMethod' => 'POST',
              'parameters' => array(
                'access_token' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'id_token' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'token_handle' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
  }
  /**
   * (getCertForOpenIdConnect)
   *
   * @param array $optParams Optional parameters.
   * @return Google_Service_Oauth2_Jwk
   */
  public function getCertForOpenIdConnect($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->base_methods->call('getCertForOpenIdConnect', array($params), "Google_Service_Oauth2_Jwk");
  }
  /**
   * (tokeninfo)
   *
   * @param array $optParams Optional parameters.
   *
   * @opt_param string access_token
   * @opt_param string id_token
   * @opt_param string token_handle
   * @return Google_Service_Oauth2_Tokeninfo
   */
  public function tokeninfo($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->base_methods->call('tokeninfo', array($params), "Google_Service_Oauth2_Tokeninfo");
  }
}


/**
 * The "userinfo" collection of methods.
 * Typical usage is:
 *  <code>
 *   $oauth2Service = new Google_Service_Oauth2(...);
 *   $userinfo = $oauth2Service->userinfo;
 *  </code>
 */
class Google_Service_Oauth2_Userinfo_Resource extends Google_Service_Resource
{

  /**
   * (userinfo.get)
   *
   * @param array $optParams Optional parameters.
   * @return Google_Service_Oauth2_Userinfoplus
   */
  public function get($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Oauth2_Userinfoplus");
  }
}

/**
 * The "v2" collection of methods.
 * Typical usage is:
 *  <code>
 *   $oauth2Service = new Google_Service_Oauth2(...);
 *   $v2 = $oauth2Service->v2;
 *  </code>
 */
class Google_Service_Oauth2_UserinfoV2_Resource extends Google_Service_Resource
{
}

/**
 * The "me" collection of methods.
 * Typical usage is:
 *  <code>
 *   $oauth2Service = new Google_Service_Oauth2(...);
 *   $me = $oauth2Service->me;
 *  </code>
 */
class Google_Service_Oauth2_UserinfoV2Me_Resource extends Google_Service_Resource
{

  /**
   * (me.get)
   *
   * @param array $optParams Optional parameters.
   * @return Google_Service_Oauth2_Userinfoplus
   */
  public function get($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Oauth2_Userinfoplus");
  }
}




class Google_Service_Oauth2_Jwk extends Google_Collection
{
  protected $collection_key = 'keys';
  protected $internal_gapi_mappings = array(
  );
  protected $keysType = 'Google_Service_Oauth2_JwkKeys';
  protected $keysDataType = 'array';


  public function setKeys($keys)
  {
    $this->keys = $keys;
  }
  public function getKeys()
  {
    return $this->keys;
  }
}

class Google_Service_Oauth2_JwkKeys extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $alg;
  public $e;
  public $kid;
  public $kty;
  public $n;
  public $use;


  public function setAlg($alg)
  {
    $this->alg = $alg;
  }
  public function getAlg()
  {
    return $this->alg;
  }
  public function setE($e)
  {
    $this->e = $e;
  }
  public function getE()
  {
    return $this->e;
  }
  public function setKid($kid)
  {
    $this->kid = $kid;
  }
  public function getKid()
  {
    return $this->kid;
  }
  public function setKty($kty)
  {
    $this->kty = $kty;
  }
  public function getKty()
  {
    return $this->kty;
  }
  public function setN($n)
  {
    $this->n = $n;
  }
  public function getN()
  {
    return $this->n;
  }
  public function setUse($use)
  {
    $this->use = $use;
  }
  public function getUse()
  {
    return $this->use;
  }
}

class Google_Service_Oauth2_Tokeninfo extends Google_Model
{
  protected $internal_gapi_mappings = array(
        "accessType" => "access_type",
        "expiresIn" => "expires_in",
        "issuedTo" => "issued_to",
        "tokenHandle" => "token_handle",
        "userId" => "user_id",
        "verifiedEmail" => "verified_email",
  );
  public $accessType;
  public $audience;
  public $email;
  public $expiresIn;
  public $issuedTo;
  public $scope;
  public $tokenHandle;
  public $userId;
  public $verifiedEmail;


  public function setAccessType($accessType)
  {
    $this->accessType = $accessType;
  }
  public function getAccessType()
  {
    return $this->accessType;
  }
  public function setAudience($audience)
  {
    $this->audience = $audience;
  }
  public function getAudience()
  {
    return $this->audience;
  }
  public function setEmail($email)
  {
    $this->email = $email;
  }
  public function getEmail()
  {
    return $this->email;
  }
  public function setExpiresIn($expiresIn)
  {
    $this->expiresIn = $expiresIn;
  }
  public function getExpiresIn()
  {
    return $this->expiresIn;
  }
  public function setIssuedTo($issuedTo)
  {
    $this->issuedTo = $issuedTo;
  }
  public function getIssuedTo()
  {
    return $this->issuedTo;
  }
  public function setScope($scope)
  {
    $this->scope = $scope;
  }
  public function getScope()
  {
    return $this->scope;
  }
  public function setTokenHandle($tokenHandle)
  {
    $this->tokenHandle = $tokenHandle;
  }
  public function getTokenHandle()
  {
    return $this->tokenHandle;
  }
  public function setUserId($userId)
  {
    $this->userId = $userId;
  }
  public function getUserId()
  {
    return $this->userId;
  }
  public function setVerifiedEmail($verifiedEmail)
  {
    $this->verifiedEmail = $verifiedEmail;
  }
  public function getVerifiedEmail()
  {
    return $this->verifiedEmail;
  }
}

class Google_Service_Oauth2_Userinfoplus extends Google_Model
{
  protected $internal_gapi_mappings = array(
        "familyName" => "family_name",
        "givenName" => "given_name",
        "verifiedEmail" => "verified_email",
  );
  public $email;
  public $familyName;
  public $gender;
  public $givenName;
  public $hd;
  public $id;
  public $link;
  public $locale;
  public $name;
  public $picture;
  public $verifiedEmail;


  public function setEmail($email)
  {
    $this->email = $email;
  }
  public function getEmail()
  {
    return $this->email;
  }
  public function setFamilyName($familyName)
  {
    $this->familyName = $familyName;
  }
  public function getFamilyName()
  {
    return $this->familyName;
  }
  public function setGender($gender)
  {
    $this->gender = $gender;
  }
  public function getGender()
  {
    return $this->gender;
  }
  public function setGivenName($givenName)
  {
    $this->givenName = $givenName;
  }
  public function getGivenName()
  {
    return $this->givenName;
  }
  public function setHd($hd)
  {
    $this->hd = $hd;
  }
  public function getHd()
  {
    return $this->hd;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setLink($link)
  {
    $this->link = $link;
  }
  public function getLink()
  {
    return $this->link;
  }
  public function setLocale($locale)
  {
    $this->locale = $locale;
  }
  public function getLocale()
  {
    return $this->locale;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setPicture($picture)
  {
    $this->picture = $picture;
  }
  public function getPicture()
  {
    return $this->picture;
  }
  public function setVerifiedEmail($verifiedEmail)
  {
    $this->verifiedEmail = $verifiedEmail;
  }
  public function getVerifiedEmail()
  {
    return $this->verifiedEmail;
  }
}
