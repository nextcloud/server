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
 * Service definition for Playmoviespartner (v1).
 *
 * <p>
 * Lets Google Play Movies Partners get the delivery status of their titles.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://developers.google.com/playmoviespartner/" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_Playmoviespartner extends Google_Service
{
  /** View the digital assets you publish on Google Play Movies and TV. */
  const PLAYMOVIES_PARTNER_READONLY =
      "https://www.googleapis.com/auth/playmovies_partner.readonly";

  public $accounts_avails;
  public $accounts_experienceLocales;
  public $accounts_orders;
  public $accounts_storeInfos;
  public $accounts_storeInfos_country;
  

  /**
   * Constructs the internal representation of the Playmoviespartner service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://playmoviespartner.googleapis.com/';
    $this->servicePath = '';
    $this->version = 'v1';
    $this->serviceName = 'playmoviespartner';

    $this->accounts_avails = new Google_Service_Playmoviespartner_AccountsAvails_Resource(
        $this,
        $this->serviceName,
        'avails',
        array(
          'methods' => array(
            'list' => array(
              'path' => 'v1/accounts/{accountId}/avails',
              'httpMethod' => 'GET',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'pphNames' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'videoIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'pageSize' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'title' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'altId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'territories' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'studioNames' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->accounts_experienceLocales = new Google_Service_Playmoviespartner_AccountsExperienceLocales_Resource(
        $this,
        $this->serviceName,
        'experienceLocales',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'v1/accounts/{accountId}/experienceLocales/{elId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'elId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'v1/accounts/{accountId}/experienceLocales',
              'httpMethod' => 'GET',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'pphNames' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'status' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'titleLevelEidr' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'pageSize' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'studioNames' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'editLevelEidr' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'customId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'altCutId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->accounts_orders = new Google_Service_Playmoviespartner_AccountsOrders_Resource(
        $this,
        $this->serviceName,
        'orders',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'v1/accounts/{accountId}/orders/{orderId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'orderId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'v1/accounts/{accountId}/orders',
              'httpMethod' => 'GET',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'pphNames' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'status' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'name' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'pageSize' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'studioNames' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'customId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->accounts_storeInfos = new Google_Service_Playmoviespartner_AccountsStoreInfos_Resource(
        $this,
        $this->serviceName,
        'storeInfos',
        array(
          'methods' => array(
            'list' => array(
              'path' => 'v1/accounts/{accountId}/storeInfos',
              'httpMethod' => 'GET',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'pphNames' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'name' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'pageSize' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'countries' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'videoId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'studioNames' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'videoIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->accounts_storeInfos_country = new Google_Service_Playmoviespartner_AccountsStoreInfosCountry_Resource(
        $this,
        $this->serviceName,
        'country',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'v1/accounts/{accountId}/storeInfos/{videoId}/country/{country}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'videoId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'country' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
  }
}


/**
 * The "accounts" collection of methods.
 * Typical usage is:
 *  <code>
 *   $playmoviespartnerService = new Google_Service_Playmoviespartner(...);
 *   $accounts = $playmoviespartnerService->accounts;
 *  </code>
 */
class Google_Service_Playmoviespartner_Accounts_Resource extends Google_Service_Resource
{
}

/**
 * The "avails" collection of methods.
 * Typical usage is:
 *  <code>
 *   $playmoviespartnerService = new Google_Service_Playmoviespartner(...);
 *   $avails = $playmoviespartnerService->avails;
 *  </code>
 */
class Google_Service_Playmoviespartner_AccountsAvails_Resource extends Google_Service_Resource
{

  /**
   * List Avails owned or managed by the partner. See _Authentication and
   * Authorization rules_ and _List methods rules_ for more information about this
   * method. (avails.listAccountsAvails)
   *
   * @param string $accountId REQUIRED. See _General rules_ for more information
   * about this field.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pphNames See _List methods rules_ for info about this
   * field.
   * @opt_param string videoIds Filter Avails that match any of the given
   * `video_id`s.
   * @opt_param int pageSize See _List methods rules_ for info about this field.
   * @opt_param string title Filter Avails that match a case-insensitive substring
   * of the default Title name.
   * @opt_param string altId Filter Avails that match a case-insensitive, partner-
   * specific custom id.
   * @opt_param string territories Filter Avails that match (case-insensitive) any
   * of the given country codes, using the "ISO 3166-1 alpha-2" format (examples:
   * "US", "us", "Us").
   * @opt_param string studioNames See _List methods rules_ for info about this
   * field.
   * @opt_param string pageToken See _List methods rules_ for info about this
   * field.
   * @return Google_Service_Playmoviespartner_ListAvailsResponse
   */
  public function listAccountsAvails($accountId, $optParams = array())
  {
    $params = array('accountId' => $accountId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Playmoviespartner_ListAvailsResponse");
  }
}
/**
 * The "experienceLocales" collection of methods.
 * Typical usage is:
 *  <code>
 *   $playmoviespartnerService = new Google_Service_Playmoviespartner(...);
 *   $experienceLocales = $playmoviespartnerService->experienceLocales;
 *  </code>
 */
class Google_Service_Playmoviespartner_AccountsExperienceLocales_Resource extends Google_Service_Resource
{

  /**
   * Get an ExperienceLocale given its id. See _Authentication and Authorization
   * rules_ and _Get methods rules_ for more information about this method.
   * (experienceLocales.get)
   *
   * @param string $accountId REQUIRED. See _General rules_ for more information
   * about this field.
   * @param string $elId REQUIRED. ExperienceLocale ID, as defined by Google.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Playmoviespartner_ExperienceLocale
   */
  public function get($accountId, $elId, $optParams = array())
  {
    $params = array('accountId' => $accountId, 'elId' => $elId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Playmoviespartner_ExperienceLocale");
  }

  /**
   * List ExperienceLocales owned or managed by the partner. See _Authentication
   * and Authorization rules_ and _List methods rules_ for more information about
   * this method. (experienceLocales.listAccountsExperienceLocales)
   *
   * @param string $accountId REQUIRED. See _General rules_ for more information
   * about this field.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pphNames See _List methods rules_ for info about this
   * field.
   * @opt_param string status Filter ExperienceLocales that match one of the given
   * status.
   * @opt_param string titleLevelEidr Filter ExperienceLocales that match a given
   * title-level EIDR.
   * @opt_param int pageSize See _List methods rules_ for info about this field.
   * @opt_param string studioNames See _List methods rules_ for info about this
   * field.
   * @opt_param string pageToken See _List methods rules_ for info about this
   * field.
   * @opt_param string editLevelEidr Filter ExperienceLocales that match a given
   * edit-level EIDR.
   * @opt_param string customId Filter ExperienceLocales that match a case-
   * insensitive, partner-specific custom id.
   * @opt_param string altCutId Filter ExperienceLocales that match a case-
   * insensitive, partner-specific Alternative Cut ID.
   * @return Google_Service_Playmoviespartner_ListExperienceLocalesResponse
   */
  public function listAccountsExperienceLocales($accountId, $optParams = array())
  {
    $params = array('accountId' => $accountId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Playmoviespartner_ListExperienceLocalesResponse");
  }
}
/**
 * The "orders" collection of methods.
 * Typical usage is:
 *  <code>
 *   $playmoviespartnerService = new Google_Service_Playmoviespartner(...);
 *   $orders = $playmoviespartnerService->orders;
 *  </code>
 */
class Google_Service_Playmoviespartner_AccountsOrders_Resource extends Google_Service_Resource
{

  /**
   * Get an Order given its id. See _Authentication and Authorization rules_ and
   * _Get methods rules_ for more information about this method. (orders.get)
   *
   * @param string $accountId REQUIRED. See _General rules_ for more information
   * about this field.
   * @param string $orderId REQUIRED. Order ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Playmoviespartner_Order
   */
  public function get($accountId, $orderId, $optParams = array())
  {
    $params = array('accountId' => $accountId, 'orderId' => $orderId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Playmoviespartner_Order");
  }

  /**
   * List Orders owned or managed by the partner. See _Authentication and
   * Authorization rules_ and _List methods rules_ for more information about this
   * method. (orders.listAccountsOrders)
   *
   * @param string $accountId REQUIRED. See _General rules_ for more information
   * about this field.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pphNames See _List methods rules_ for info about this
   * field.
   * @opt_param string status Filter Orders that match one of the given status.
   * @opt_param string name Filter Orders that match a title name (case-
   * insensitive, sub-string match).
   * @opt_param int pageSize See _List methods rules_ for info about this field.
   * @opt_param string studioNames See _List methods rules_ for info about this
   * field.
   * @opt_param string pageToken See _List methods rules_ for info about this
   * field.
   * @opt_param string customId Filter Orders that match a case-insensitive,
   * partner-specific custom id.
   * @return Google_Service_Playmoviespartner_ListOrdersResponse
   */
  public function listAccountsOrders($accountId, $optParams = array())
  {
    $params = array('accountId' => $accountId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Playmoviespartner_ListOrdersResponse");
  }
}
/**
 * The "storeInfos" collection of methods.
 * Typical usage is:
 *  <code>
 *   $playmoviespartnerService = new Google_Service_Playmoviespartner(...);
 *   $storeInfos = $playmoviespartnerService->storeInfos;
 *  </code>
 */
class Google_Service_Playmoviespartner_AccountsStoreInfos_Resource extends Google_Service_Resource
{

  /**
   * List StoreInfos owned or managed by the partner. See _Authentication and
   * Authorization rules_ and _List methods rules_ for more information about this
   * method. (storeInfos.listAccountsStoreInfos)
   *
   * @param string $accountId REQUIRED. See _General rules_ for more information
   * about this field.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pphNames See _List methods rules_ for info about this
   * field.
   * @opt_param string name Filter StoreInfos that match a case-insensitive
   * substring of the default name.
   * @opt_param int pageSize See _List methods rules_ for info about this field.
   * @opt_param string countries Filter StoreInfos that match (case-insensitive)
   * any of the given country codes, using the "ISO 3166-1 alpha-2" format
   * (examples: "US", "us", "Us").
   * @opt_param string videoId Filter StoreInfos that match a given `video_id`.
   * NOTE: this field is deprecated and will be removed on V2; `video_ids` should
   * be used instead.
   * @opt_param string studioNames See _List methods rules_ for info about this
   * field.
   * @opt_param string pageToken See _List methods rules_ for info about this
   * field.
   * @opt_param string videoIds Filter StoreInfos that match any of the given
   * `video_id`s.
   * @return Google_Service_Playmoviespartner_ListStoreInfosResponse
   */
  public function listAccountsStoreInfos($accountId, $optParams = array())
  {
    $params = array('accountId' => $accountId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Playmoviespartner_ListStoreInfosResponse");
  }
}

/**
 * The "country" collection of methods.
 * Typical usage is:
 *  <code>
 *   $playmoviespartnerService = new Google_Service_Playmoviespartner(...);
 *   $country = $playmoviespartnerService->country;
 *  </code>
 */
class Google_Service_Playmoviespartner_AccountsStoreInfosCountry_Resource extends Google_Service_Resource
{

  /**
   * Get a StoreInfo given its video id and country. See _Authentication and
   * Authorization rules_ and _Get methods rules_ for more information about this
   * method. (country.get)
   *
   * @param string $accountId REQUIRED. See _General rules_ for more information
   * about this field.
   * @param string $videoId REQUIRED. Video ID.
   * @param string $country REQUIRED. Edit country.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Playmoviespartner_StoreInfo
   */
  public function get($accountId, $videoId, $country, $optParams = array())
  {
    $params = array('accountId' => $accountId, 'videoId' => $videoId, 'country' => $country);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Playmoviespartner_StoreInfo");
  }
}




class Google_Service_Playmoviespartner_Avail extends Google_Collection
{
  protected $collection_key = 'pphNames';
  protected $internal_gapi_mappings = array(
  );
  public $altId;
  public $captionExemption;
  public $captionIncluded;
  public $contentId;
  public $displayName;
  public $encodeId;
  public $end;
  public $episodeAltId;
  public $episodeNumber;
  public $episodeTitleInternalAlias;
  public $formatProfile;
  public $licenseType;
  public $pphNames;
  public $priceType;
  public $priceValue;
  public $productId;
  public $ratingReason;
  public $ratingSystem;
  public $ratingValue;
  public $releaseDate;
  public $seasonAltId;
  public $seasonNumber;
  public $seasonTitleInternalAlias;
  public $seriesAltId;
  public $seriesTitleInternalAlias;
  public $start;
  public $storeLanguage;
  public $suppressionLiftDate;
  public $territory;
  public $titleInternalAlias;
  public $videoId;
  public $workType;


  public function setAltId($altId)
  {
    $this->altId = $altId;
  }
  public function getAltId()
  {
    return $this->altId;
  }
  public function setCaptionExemption($captionExemption)
  {
    $this->captionExemption = $captionExemption;
  }
  public function getCaptionExemption()
  {
    return $this->captionExemption;
  }
  public function setCaptionIncluded($captionIncluded)
  {
    $this->captionIncluded = $captionIncluded;
  }
  public function getCaptionIncluded()
  {
    return $this->captionIncluded;
  }
  public function setContentId($contentId)
  {
    $this->contentId = $contentId;
  }
  public function getContentId()
  {
    return $this->contentId;
  }
  public function setDisplayName($displayName)
  {
    $this->displayName = $displayName;
  }
  public function getDisplayName()
  {
    return $this->displayName;
  }
  public function setEncodeId($encodeId)
  {
    $this->encodeId = $encodeId;
  }
  public function getEncodeId()
  {
    return $this->encodeId;
  }
  public function setEnd($end)
  {
    $this->end = $end;
  }
  public function getEnd()
  {
    return $this->end;
  }
  public function setEpisodeAltId($episodeAltId)
  {
    $this->episodeAltId = $episodeAltId;
  }
  public function getEpisodeAltId()
  {
    return $this->episodeAltId;
  }
  public function setEpisodeNumber($episodeNumber)
  {
    $this->episodeNumber = $episodeNumber;
  }
  public function getEpisodeNumber()
  {
    return $this->episodeNumber;
  }
  public function setEpisodeTitleInternalAlias($episodeTitleInternalAlias)
  {
    $this->episodeTitleInternalAlias = $episodeTitleInternalAlias;
  }
  public function getEpisodeTitleInternalAlias()
  {
    return $this->episodeTitleInternalAlias;
  }
  public function setFormatProfile($formatProfile)
  {
    $this->formatProfile = $formatProfile;
  }
  public function getFormatProfile()
  {
    return $this->formatProfile;
  }
  public function setLicenseType($licenseType)
  {
    $this->licenseType = $licenseType;
  }
  public function getLicenseType()
  {
    return $this->licenseType;
  }
  public function setPphNames($pphNames)
  {
    $this->pphNames = $pphNames;
  }
  public function getPphNames()
  {
    return $this->pphNames;
  }
  public function setPriceType($priceType)
  {
    $this->priceType = $priceType;
  }
  public function getPriceType()
  {
    return $this->priceType;
  }
  public function setPriceValue($priceValue)
  {
    $this->priceValue = $priceValue;
  }
  public function getPriceValue()
  {
    return $this->priceValue;
  }
  public function setProductId($productId)
  {
    $this->productId = $productId;
  }
  public function getProductId()
  {
    return $this->productId;
  }
  public function setRatingReason($ratingReason)
  {
    $this->ratingReason = $ratingReason;
  }
  public function getRatingReason()
  {
    return $this->ratingReason;
  }
  public function setRatingSystem($ratingSystem)
  {
    $this->ratingSystem = $ratingSystem;
  }
  public function getRatingSystem()
  {
    return $this->ratingSystem;
  }
  public function setRatingValue($ratingValue)
  {
    $this->ratingValue = $ratingValue;
  }
  public function getRatingValue()
  {
    return $this->ratingValue;
  }
  public function setReleaseDate($releaseDate)
  {
    $this->releaseDate = $releaseDate;
  }
  public function getReleaseDate()
  {
    return $this->releaseDate;
  }
  public function setSeasonAltId($seasonAltId)
  {
    $this->seasonAltId = $seasonAltId;
  }
  public function getSeasonAltId()
  {
    return $this->seasonAltId;
  }
  public function setSeasonNumber($seasonNumber)
  {
    $this->seasonNumber = $seasonNumber;
  }
  public function getSeasonNumber()
  {
    return $this->seasonNumber;
  }
  public function setSeasonTitleInternalAlias($seasonTitleInternalAlias)
  {
    $this->seasonTitleInternalAlias = $seasonTitleInternalAlias;
  }
  public function getSeasonTitleInternalAlias()
  {
    return $this->seasonTitleInternalAlias;
  }
  public function setSeriesAltId($seriesAltId)
  {
    $this->seriesAltId = $seriesAltId;
  }
  public function getSeriesAltId()
  {
    return $this->seriesAltId;
  }
  public function setSeriesTitleInternalAlias($seriesTitleInternalAlias)
  {
    $this->seriesTitleInternalAlias = $seriesTitleInternalAlias;
  }
  public function getSeriesTitleInternalAlias()
  {
    return $this->seriesTitleInternalAlias;
  }
  public function setStart($start)
  {
    $this->start = $start;
  }
  public function getStart()
  {
    return $this->start;
  }
  public function setStoreLanguage($storeLanguage)
  {
    $this->storeLanguage = $storeLanguage;
  }
  public function getStoreLanguage()
  {
    return $this->storeLanguage;
  }
  public function setSuppressionLiftDate($suppressionLiftDate)
  {
    $this->suppressionLiftDate = $suppressionLiftDate;
  }
  public function getSuppressionLiftDate()
  {
    return $this->suppressionLiftDate;
  }
  public function setTerritory($territory)
  {
    $this->territory = $territory;
  }
  public function getTerritory()
  {
    return $this->territory;
  }
  public function setTitleInternalAlias($titleInternalAlias)
  {
    $this->titleInternalAlias = $titleInternalAlias;
  }
  public function getTitleInternalAlias()
  {
    return $this->titleInternalAlias;
  }
  public function setVideoId($videoId)
  {
    $this->videoId = $videoId;
  }
  public function getVideoId()
  {
    return $this->videoId;
  }
  public function setWorkType($workType)
  {
    $this->workType = $workType;
  }
  public function getWorkType()
  {
    return $this->workType;
  }
}

class Google_Service_Playmoviespartner_ExperienceLocale extends Google_Collection
{
  protected $collection_key = 'pphNames';
  protected $internal_gapi_mappings = array(
  );
  public $altCutId;
  public $approvedTime;
  public $channelId;
  public $country;
  public $createdTime;
  public $customIds;
  public $earliestAvailStartTime;
  public $editLevelEidr;
  public $elId;
  public $inventoryId;
  public $language;
  public $name;
  public $normalizedPriority;
  public $playableSequenceId;
  public $pphNames;
  public $presentationId;
  public $priority;
  public $status;
  public $studioName;
  public $titleLevelEidr;
  public $trailerId;
  public $type;
  public $videoId;


  public function setAltCutId($altCutId)
  {
    $this->altCutId = $altCutId;
  }
  public function getAltCutId()
  {
    return $this->altCutId;
  }
  public function setApprovedTime($approvedTime)
  {
    $this->approvedTime = $approvedTime;
  }
  public function getApprovedTime()
  {
    return $this->approvedTime;
  }
  public function setChannelId($channelId)
  {
    $this->channelId = $channelId;
  }
  public function getChannelId()
  {
    return $this->channelId;
  }
  public function setCountry($country)
  {
    $this->country = $country;
  }
  public function getCountry()
  {
    return $this->country;
  }
  public function setCreatedTime($createdTime)
  {
    $this->createdTime = $createdTime;
  }
  public function getCreatedTime()
  {
    return $this->createdTime;
  }
  public function setCustomIds($customIds)
  {
    $this->customIds = $customIds;
  }
  public function getCustomIds()
  {
    return $this->customIds;
  }
  public function setEarliestAvailStartTime($earliestAvailStartTime)
  {
    $this->earliestAvailStartTime = $earliestAvailStartTime;
  }
  public function getEarliestAvailStartTime()
  {
    return $this->earliestAvailStartTime;
  }
  public function setEditLevelEidr($editLevelEidr)
  {
    $this->editLevelEidr = $editLevelEidr;
  }
  public function getEditLevelEidr()
  {
    return $this->editLevelEidr;
  }
  public function setElId($elId)
  {
    $this->elId = $elId;
  }
  public function getElId()
  {
    return $this->elId;
  }
  public function setInventoryId($inventoryId)
  {
    $this->inventoryId = $inventoryId;
  }
  public function getInventoryId()
  {
    return $this->inventoryId;
  }
  public function setLanguage($language)
  {
    $this->language = $language;
  }
  public function getLanguage()
  {
    return $this->language;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setNormalizedPriority($normalizedPriority)
  {
    $this->normalizedPriority = $normalizedPriority;
  }
  public function getNormalizedPriority()
  {
    return $this->normalizedPriority;
  }
  public function setPlayableSequenceId($playableSequenceId)
  {
    $this->playableSequenceId = $playableSequenceId;
  }
  public function getPlayableSequenceId()
  {
    return $this->playableSequenceId;
  }
  public function setPphNames($pphNames)
  {
    $this->pphNames = $pphNames;
  }
  public function getPphNames()
  {
    return $this->pphNames;
  }
  public function setPresentationId($presentationId)
  {
    $this->presentationId = $presentationId;
  }
  public function getPresentationId()
  {
    return $this->presentationId;
  }
  public function setPriority($priority)
  {
    $this->priority = $priority;
  }
  public function getPriority()
  {
    return $this->priority;
  }
  public function setStatus($status)
  {
    $this->status = $status;
  }
  public function getStatus()
  {
    return $this->status;
  }
  public function setStudioName($studioName)
  {
    $this->studioName = $studioName;
  }
  public function getStudioName()
  {
    return $this->studioName;
  }
  public function setTitleLevelEidr($titleLevelEidr)
  {
    $this->titleLevelEidr = $titleLevelEidr;
  }
  public function getTitleLevelEidr()
  {
    return $this->titleLevelEidr;
  }
  public function setTrailerId($trailerId)
  {
    $this->trailerId = $trailerId;
  }
  public function getTrailerId()
  {
    return $this->trailerId;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
  public function setVideoId($videoId)
  {
    $this->videoId = $videoId;
  }
  public function getVideoId()
  {
    return $this->videoId;
  }
}

class Google_Service_Playmoviespartner_ListAvailsResponse extends Google_Collection
{
  protected $collection_key = 'avails';
  protected $internal_gapi_mappings = array(
  );
  protected $availsType = 'Google_Service_Playmoviespartner_Avail';
  protected $availsDataType = 'array';
  public $nextPageToken;


  public function setAvails($avails)
  {
    $this->avails = $avails;
  }
  public function getAvails()
  {
    return $this->avails;
  }
  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
}

class Google_Service_Playmoviespartner_ListExperienceLocalesResponse extends Google_Collection
{
  protected $collection_key = 'experienceLocales';
  protected $internal_gapi_mappings = array(
  );
  protected $experienceLocalesType = 'Google_Service_Playmoviespartner_ExperienceLocale';
  protected $experienceLocalesDataType = 'array';
  public $nextPageToken;


  public function setExperienceLocales($experienceLocales)
  {
    $this->experienceLocales = $experienceLocales;
  }
  public function getExperienceLocales()
  {
    return $this->experienceLocales;
  }
  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
}

class Google_Service_Playmoviespartner_ListOrdersResponse extends Google_Collection
{
  protected $collection_key = 'orders';
  protected $internal_gapi_mappings = array(
  );
  public $nextPageToken;
  protected $ordersType = 'Google_Service_Playmoviespartner_Order';
  protected $ordersDataType = 'array';


  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  public function setOrders($orders)
  {
    $this->orders = $orders;
  }
  public function getOrders()
  {
    return $this->orders;
  }
}

class Google_Service_Playmoviespartner_ListStoreInfosResponse extends Google_Collection
{
  protected $collection_key = 'storeInfos';
  protected $internal_gapi_mappings = array(
  );
  public $nextPageToken;
  protected $storeInfosType = 'Google_Service_Playmoviespartner_StoreInfo';
  protected $storeInfosDataType = 'array';


  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  public function setStoreInfos($storeInfos)
  {
    $this->storeInfos = $storeInfos;
  }
  public function getStoreInfos()
  {
    return $this->storeInfos;
  }
}

class Google_Service_Playmoviespartner_Order extends Google_Collection
{
  protected $collection_key = 'countries';
  protected $internal_gapi_mappings = array(
  );
  public $approvedTime;
  public $channelId;
  public $channelName;
  public $countries;
  public $customId;
  public $earliestAvailStartTime;
  public $episodeName;
  public $legacyPriority;
  public $name;
  public $normalizedPriority;
  public $orderId;
  public $orderedTime;
  public $pphName;
  public $priority;
  public $receivedTime;
  public $rejectionNote;
  public $seasonName;
  public $showName;
  public $status;
  public $statusDetail;
  public $studioName;
  public $type;
  public $videoId;


  public function setApprovedTime($approvedTime)
  {
    $this->approvedTime = $approvedTime;
  }
  public function getApprovedTime()
  {
    return $this->approvedTime;
  }
  public function setChannelId($channelId)
  {
    $this->channelId = $channelId;
  }
  public function getChannelId()
  {
    return $this->channelId;
  }
  public function setChannelName($channelName)
  {
    $this->channelName = $channelName;
  }
  public function getChannelName()
  {
    return $this->channelName;
  }
  public function setCountries($countries)
  {
    $this->countries = $countries;
  }
  public function getCountries()
  {
    return $this->countries;
  }
  public function setCustomId($customId)
  {
    $this->customId = $customId;
  }
  public function getCustomId()
  {
    return $this->customId;
  }
  public function setEarliestAvailStartTime($earliestAvailStartTime)
  {
    $this->earliestAvailStartTime = $earliestAvailStartTime;
  }
  public function getEarliestAvailStartTime()
  {
    return $this->earliestAvailStartTime;
  }
  public function setEpisodeName($episodeName)
  {
    $this->episodeName = $episodeName;
  }
  public function getEpisodeName()
  {
    return $this->episodeName;
  }
  public function setLegacyPriority($legacyPriority)
  {
    $this->legacyPriority = $legacyPriority;
  }
  public function getLegacyPriority()
  {
    return $this->legacyPriority;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setNormalizedPriority($normalizedPriority)
  {
    $this->normalizedPriority = $normalizedPriority;
  }
  public function getNormalizedPriority()
  {
    return $this->normalizedPriority;
  }
  public function setOrderId($orderId)
  {
    $this->orderId = $orderId;
  }
  public function getOrderId()
  {
    return $this->orderId;
  }
  public function setOrderedTime($orderedTime)
  {
    $this->orderedTime = $orderedTime;
  }
  public function getOrderedTime()
  {
    return $this->orderedTime;
  }
  public function setPphName($pphName)
  {
    $this->pphName = $pphName;
  }
  public function getPphName()
  {
    return $this->pphName;
  }
  public function setPriority($priority)
  {
    $this->priority = $priority;
  }
  public function getPriority()
  {
    return $this->priority;
  }
  public function setReceivedTime($receivedTime)
  {
    $this->receivedTime = $receivedTime;
  }
  public function getReceivedTime()
  {
    return $this->receivedTime;
  }
  public function setRejectionNote($rejectionNote)
  {
    $this->rejectionNote = $rejectionNote;
  }
  public function getRejectionNote()
  {
    return $this->rejectionNote;
  }
  public function setSeasonName($seasonName)
  {
    $this->seasonName = $seasonName;
  }
  public function getSeasonName()
  {
    return $this->seasonName;
  }
  public function setShowName($showName)
  {
    $this->showName = $showName;
  }
  public function getShowName()
  {
    return $this->showName;
  }
  public function setStatus($status)
  {
    $this->status = $status;
  }
  public function getStatus()
  {
    return $this->status;
  }
  public function setStatusDetail($statusDetail)
  {
    $this->statusDetail = $statusDetail;
  }
  public function getStatusDetail()
  {
    return $this->statusDetail;
  }
  public function setStudioName($studioName)
  {
    $this->studioName = $studioName;
  }
  public function getStudioName()
  {
    return $this->studioName;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
  public function setVideoId($videoId)
  {
    $this->videoId = $videoId;
  }
  public function getVideoId()
  {
    return $this->videoId;
  }
}

class Google_Service_Playmoviespartner_StoreInfo extends Google_Collection
{
  protected $collection_key = 'subtitles';
  protected $internal_gapi_mappings = array(
  );
  public $audioTracks;
  public $country;
  public $editLevelEidr;
  public $episodeNumber;
  public $hasAudio51;
  public $hasEstOffer;
  public $hasHdOffer;
  public $hasInfoCards;
  public $hasSdOffer;
  public $hasVodOffer;
  public $liveTime;
  public $mid;
  public $name;
  public $pphNames;
  public $seasonId;
  public $seasonName;
  public $seasonNumber;
  public $showId;
  public $showName;
  public $studioName;
  public $subtitles;
  public $titleLevelEidr;
  public $trailerId;
  public $type;
  public $videoId;


  public function setAudioTracks($audioTracks)
  {
    $this->audioTracks = $audioTracks;
  }
  public function getAudioTracks()
  {
    return $this->audioTracks;
  }
  public function setCountry($country)
  {
    $this->country = $country;
  }
  public function getCountry()
  {
    return $this->country;
  }
  public function setEditLevelEidr($editLevelEidr)
  {
    $this->editLevelEidr = $editLevelEidr;
  }
  public function getEditLevelEidr()
  {
    return $this->editLevelEidr;
  }
  public function setEpisodeNumber($episodeNumber)
  {
    $this->episodeNumber = $episodeNumber;
  }
  public function getEpisodeNumber()
  {
    return $this->episodeNumber;
  }
  public function setHasAudio51($hasAudio51)
  {
    $this->hasAudio51 = $hasAudio51;
  }
  public function getHasAudio51()
  {
    return $this->hasAudio51;
  }
  public function setHasEstOffer($hasEstOffer)
  {
    $this->hasEstOffer = $hasEstOffer;
  }
  public function getHasEstOffer()
  {
    return $this->hasEstOffer;
  }
  public function setHasHdOffer($hasHdOffer)
  {
    $this->hasHdOffer = $hasHdOffer;
  }
  public function getHasHdOffer()
  {
    return $this->hasHdOffer;
  }
  public function setHasInfoCards($hasInfoCards)
  {
    $this->hasInfoCards = $hasInfoCards;
  }
  public function getHasInfoCards()
  {
    return $this->hasInfoCards;
  }
  public function setHasSdOffer($hasSdOffer)
  {
    $this->hasSdOffer = $hasSdOffer;
  }
  public function getHasSdOffer()
  {
    return $this->hasSdOffer;
  }
  public function setHasVodOffer($hasVodOffer)
  {
    $this->hasVodOffer = $hasVodOffer;
  }
  public function getHasVodOffer()
  {
    return $this->hasVodOffer;
  }
  public function setLiveTime($liveTime)
  {
    $this->liveTime = $liveTime;
  }
  public function getLiveTime()
  {
    return $this->liveTime;
  }
  public function setMid($mid)
  {
    $this->mid = $mid;
  }
  public function getMid()
  {
    return $this->mid;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setPphNames($pphNames)
  {
    $this->pphNames = $pphNames;
  }
  public function getPphNames()
  {
    return $this->pphNames;
  }
  public function setSeasonId($seasonId)
  {
    $this->seasonId = $seasonId;
  }
  public function getSeasonId()
  {
    return $this->seasonId;
  }
  public function setSeasonName($seasonName)
  {
    $this->seasonName = $seasonName;
  }
  public function getSeasonName()
  {
    return $this->seasonName;
  }
  public function setSeasonNumber($seasonNumber)
  {
    $this->seasonNumber = $seasonNumber;
  }
  public function getSeasonNumber()
  {
    return $this->seasonNumber;
  }
  public function setShowId($showId)
  {
    $this->showId = $showId;
  }
  public function getShowId()
  {
    return $this->showId;
  }
  public function setShowName($showName)
  {
    $this->showName = $showName;
  }
  public function getShowName()
  {
    return $this->showName;
  }
  public function setStudioName($studioName)
  {
    $this->studioName = $studioName;
  }
  public function getStudioName()
  {
    return $this->studioName;
  }
  public function setSubtitles($subtitles)
  {
    $this->subtitles = $subtitles;
  }
  public function getSubtitles()
  {
    return $this->subtitles;
  }
  public function setTitleLevelEidr($titleLevelEidr)
  {
    $this->titleLevelEidr = $titleLevelEidr;
  }
  public function getTitleLevelEidr()
  {
    return $this->titleLevelEidr;
  }
  public function setTrailerId($trailerId)
  {
    $this->trailerId = $trailerId;
  }
  public function getTrailerId()
  {
    return $this->trailerId;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
  public function setVideoId($videoId)
  {
    $this->videoId = $videoId;
  }
  public function getVideoId()
  {
    return $this->videoId;
  }
}
