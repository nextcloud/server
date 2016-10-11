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
 * Service definition for GamesConfiguration (v1configuration).
 *
 * <p>
 * The Publishing API for Google Play Game Services.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://developers.google.com/games/services" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_GamesConfiguration extends Google_Service
{
  /** View and manage your Google Play Developer account. */
  const ANDROIDPUBLISHER =
      "https://www.googleapis.com/auth/androidpublisher";

  public $achievementConfigurations;
  public $imageConfigurations;
  public $leaderboardConfigurations;
  

  /**
   * Constructs the internal representation of the GamesConfiguration service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://www.googleapis.com/';
    $this->servicePath = 'games/v1configuration/';
    $this->version = 'v1configuration';
    $this->serviceName = 'gamesConfiguration';

    $this->achievementConfigurations = new Google_Service_GamesConfiguration_AchievementConfigurations_Resource(
        $this,
        $this->serviceName,
        'achievementConfigurations',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'achievements/{achievementId}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'achievementId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'achievements/{achievementId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'achievementId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'applications/{applicationId}/achievements',
              'httpMethod' => 'POST',
              'parameters' => array(
                'applicationId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'applications/{applicationId}/achievements',
              'httpMethod' => 'GET',
              'parameters' => array(
                'applicationId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
              ),
            ),'patch' => array(
              'path' => 'achievements/{achievementId}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'achievementId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'achievements/{achievementId}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'achievementId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->imageConfigurations = new Google_Service_GamesConfiguration_ImageConfigurations_Resource(
        $this,
        $this->serviceName,
        'imageConfigurations',
        array(
          'methods' => array(
            'upload' => array(
              'path' => 'images/{resourceId}/imageType/{imageType}',
              'httpMethod' => 'POST',
              'parameters' => array(
                'resourceId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'imageType' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->leaderboardConfigurations = new Google_Service_GamesConfiguration_LeaderboardConfigurations_Resource(
        $this,
        $this->serviceName,
        'leaderboardConfigurations',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'leaderboards/{leaderboardId}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'leaderboardId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'leaderboards/{leaderboardId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'leaderboardId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'applications/{applicationId}/leaderboards',
              'httpMethod' => 'POST',
              'parameters' => array(
                'applicationId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'applications/{applicationId}/leaderboards',
              'httpMethod' => 'GET',
              'parameters' => array(
                'applicationId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
              ),
            ),'patch' => array(
              'path' => 'leaderboards/{leaderboardId}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'leaderboardId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'leaderboards/{leaderboardId}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'leaderboardId' => array(
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
 * The "achievementConfigurations" collection of methods.
 * Typical usage is:
 *  <code>
 *   $gamesConfigurationService = new Google_Service_GamesConfiguration(...);
 *   $achievementConfigurations = $gamesConfigurationService->achievementConfigurations;
 *  </code>
 */
class Google_Service_GamesConfiguration_AchievementConfigurations_Resource extends Google_Service_Resource
{

  /**
   * Delete the achievement configuration with the given ID.
   * (achievementConfigurations.delete)
   *
   * @param string $achievementId The ID of the achievement used by this method.
   * @param array $optParams Optional parameters.
   */
  public function delete($achievementId, $optParams = array())
  {
    $params = array('achievementId' => $achievementId);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Retrieves the metadata of the achievement configuration with the given ID.
   * (achievementConfigurations.get)
   *
   * @param string $achievementId The ID of the achievement used by this method.
   * @param array $optParams Optional parameters.
   * @return Google_Service_GamesConfiguration_AchievementConfiguration
   */
  public function get($achievementId, $optParams = array())
  {
    $params = array('achievementId' => $achievementId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_GamesConfiguration_AchievementConfiguration");
  }

  /**
   * Insert a new achievement configuration in this application.
   * (achievementConfigurations.insert)
   *
   * @param string $applicationId The application ID from the Google Play
   * developer console.
   * @param Google_AchievementConfiguration $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_GamesConfiguration_AchievementConfiguration
   */
  public function insert($applicationId, Google_Service_GamesConfiguration_AchievementConfiguration $postBody, $optParams = array())
  {
    $params = array('applicationId' => $applicationId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_GamesConfiguration_AchievementConfiguration");
  }

  /**
   * Returns a list of the achievement configurations in this application.
   * (achievementConfigurations.listAchievementConfigurations)
   *
   * @param string $applicationId The application ID from the Google Play
   * developer console.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken The token returned by the previous request.
   * @opt_param int maxResults The maximum number of resource configurations to
   * return in the response, used for paging. For any response, the actual number
   * of resources returned may be less than the specified maxResults.
   * @return Google_Service_GamesConfiguration_AchievementConfigurationListResponse
   */
  public function listAchievementConfigurations($applicationId, $optParams = array())
  {
    $params = array('applicationId' => $applicationId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_GamesConfiguration_AchievementConfigurationListResponse");
  }

  /**
   * Update the metadata of the achievement configuration with the given ID. This
   * method supports patch semantics. (achievementConfigurations.patch)
   *
   * @param string $achievementId The ID of the achievement used by this method.
   * @param Google_AchievementConfiguration $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_GamesConfiguration_AchievementConfiguration
   */
  public function patch($achievementId, Google_Service_GamesConfiguration_AchievementConfiguration $postBody, $optParams = array())
  {
    $params = array('achievementId' => $achievementId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_GamesConfiguration_AchievementConfiguration");
  }

  /**
   * Update the metadata of the achievement configuration with the given ID.
   * (achievementConfigurations.update)
   *
   * @param string $achievementId The ID of the achievement used by this method.
   * @param Google_AchievementConfiguration $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_GamesConfiguration_AchievementConfiguration
   */
  public function update($achievementId, Google_Service_GamesConfiguration_AchievementConfiguration $postBody, $optParams = array())
  {
    $params = array('achievementId' => $achievementId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_GamesConfiguration_AchievementConfiguration");
  }
}

/**
 * The "imageConfigurations" collection of methods.
 * Typical usage is:
 *  <code>
 *   $gamesConfigurationService = new Google_Service_GamesConfiguration(...);
 *   $imageConfigurations = $gamesConfigurationService->imageConfigurations;
 *  </code>
 */
class Google_Service_GamesConfiguration_ImageConfigurations_Resource extends Google_Service_Resource
{

  /**
   * Uploads an image for a resource with the given ID and image type.
   * (imageConfigurations.upload)
   *
   * @param string $resourceId The ID of the resource used by this method.
   * @param string $imageType Selects which image in a resource for this method.
   * @param array $optParams Optional parameters.
   * @return Google_Service_GamesConfiguration_ImageConfiguration
   */
  public function upload($resourceId, $imageType, $optParams = array())
  {
    $params = array('resourceId' => $resourceId, 'imageType' => $imageType);
    $params = array_merge($params, $optParams);
    return $this->call('upload', array($params), "Google_Service_GamesConfiguration_ImageConfiguration");
  }
}

/**
 * The "leaderboardConfigurations" collection of methods.
 * Typical usage is:
 *  <code>
 *   $gamesConfigurationService = new Google_Service_GamesConfiguration(...);
 *   $leaderboardConfigurations = $gamesConfigurationService->leaderboardConfigurations;
 *  </code>
 */
class Google_Service_GamesConfiguration_LeaderboardConfigurations_Resource extends Google_Service_Resource
{

  /**
   * Delete the leaderboard configuration with the given ID.
   * (leaderboardConfigurations.delete)
   *
   * @param string $leaderboardId The ID of the leaderboard.
   * @param array $optParams Optional parameters.
   */
  public function delete($leaderboardId, $optParams = array())
  {
    $params = array('leaderboardId' => $leaderboardId);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Retrieves the metadata of the leaderboard configuration with the given ID.
   * (leaderboardConfigurations.get)
   *
   * @param string $leaderboardId The ID of the leaderboard.
   * @param array $optParams Optional parameters.
   * @return Google_Service_GamesConfiguration_LeaderboardConfiguration
   */
  public function get($leaderboardId, $optParams = array())
  {
    $params = array('leaderboardId' => $leaderboardId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_GamesConfiguration_LeaderboardConfiguration");
  }

  /**
   * Insert a new leaderboard configuration in this application.
   * (leaderboardConfigurations.insert)
   *
   * @param string $applicationId The application ID from the Google Play
   * developer console.
   * @param Google_LeaderboardConfiguration $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_GamesConfiguration_LeaderboardConfiguration
   */
  public function insert($applicationId, Google_Service_GamesConfiguration_LeaderboardConfiguration $postBody, $optParams = array())
  {
    $params = array('applicationId' => $applicationId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_GamesConfiguration_LeaderboardConfiguration");
  }

  /**
   * Returns a list of the leaderboard configurations in this application.
   * (leaderboardConfigurations.listLeaderboardConfigurations)
   *
   * @param string $applicationId The application ID from the Google Play
   * developer console.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken The token returned by the previous request.
   * @opt_param int maxResults The maximum number of resource configurations to
   * return in the response, used for paging. For any response, the actual number
   * of resources returned may be less than the specified maxResults.
   * @return Google_Service_GamesConfiguration_LeaderboardConfigurationListResponse
   */
  public function listLeaderboardConfigurations($applicationId, $optParams = array())
  {
    $params = array('applicationId' => $applicationId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_GamesConfiguration_LeaderboardConfigurationListResponse");
  }

  /**
   * Update the metadata of the leaderboard configuration with the given ID. This
   * method supports patch semantics. (leaderboardConfigurations.patch)
   *
   * @param string $leaderboardId The ID of the leaderboard.
   * @param Google_LeaderboardConfiguration $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_GamesConfiguration_LeaderboardConfiguration
   */
  public function patch($leaderboardId, Google_Service_GamesConfiguration_LeaderboardConfiguration $postBody, $optParams = array())
  {
    $params = array('leaderboardId' => $leaderboardId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_GamesConfiguration_LeaderboardConfiguration");
  }

  /**
   * Update the metadata of the leaderboard configuration with the given ID.
   * (leaderboardConfigurations.update)
   *
   * @param string $leaderboardId The ID of the leaderboard.
   * @param Google_LeaderboardConfiguration $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_GamesConfiguration_LeaderboardConfiguration
   */
  public function update($leaderboardId, Google_Service_GamesConfiguration_LeaderboardConfiguration $postBody, $optParams = array())
  {
    $params = array('leaderboardId' => $leaderboardId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_GamesConfiguration_LeaderboardConfiguration");
  }
}




class Google_Service_GamesConfiguration_AchievementConfiguration extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $achievementType;
  protected $draftType = 'Google_Service_GamesConfiguration_AchievementConfigurationDetail';
  protected $draftDataType = '';
  public $id;
  public $initialState;
  public $kind;
  protected $publishedType = 'Google_Service_GamesConfiguration_AchievementConfigurationDetail';
  protected $publishedDataType = '';
  public $stepsToUnlock;
  public $token;


  public function setAchievementType($achievementType)
  {
    $this->achievementType = $achievementType;
  }
  public function getAchievementType()
  {
    return $this->achievementType;
  }
  public function setDraft(Google_Service_GamesConfiguration_AchievementConfigurationDetail $draft)
  {
    $this->draft = $draft;
  }
  public function getDraft()
  {
    return $this->draft;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setInitialState($initialState)
  {
    $this->initialState = $initialState;
  }
  public function getInitialState()
  {
    return $this->initialState;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setPublished(Google_Service_GamesConfiguration_AchievementConfigurationDetail $published)
  {
    $this->published = $published;
  }
  public function getPublished()
  {
    return $this->published;
  }
  public function setStepsToUnlock($stepsToUnlock)
  {
    $this->stepsToUnlock = $stepsToUnlock;
  }
  public function getStepsToUnlock()
  {
    return $this->stepsToUnlock;
  }
  public function setToken($token)
  {
    $this->token = $token;
  }
  public function getToken()
  {
    return $this->token;
  }
}

class Google_Service_GamesConfiguration_AchievementConfigurationDetail extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $descriptionType = 'Google_Service_GamesConfiguration_LocalizedStringBundle';
  protected $descriptionDataType = '';
  public $iconUrl;
  public $kind;
  protected $nameType = 'Google_Service_GamesConfiguration_LocalizedStringBundle';
  protected $nameDataType = '';
  public $pointValue;
  public $sortRank;


  public function setDescription(Google_Service_GamesConfiguration_LocalizedStringBundle $description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setIconUrl($iconUrl)
  {
    $this->iconUrl = $iconUrl;
  }
  public function getIconUrl()
  {
    return $this->iconUrl;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setName(Google_Service_GamesConfiguration_LocalizedStringBundle $name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setPointValue($pointValue)
  {
    $this->pointValue = $pointValue;
  }
  public function getPointValue()
  {
    return $this->pointValue;
  }
  public function setSortRank($sortRank)
  {
    $this->sortRank = $sortRank;
  }
  public function getSortRank()
  {
    return $this->sortRank;
  }
}

class Google_Service_GamesConfiguration_AchievementConfigurationListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  protected $itemsType = 'Google_Service_GamesConfiguration_AchievementConfiguration';
  protected $itemsDataType = 'array';
  public $kind;
  public $nextPageToken;


  public function setItems($items)
  {
    $this->items = $items;
  }
  public function getItems()
  {
    return $this->items;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
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

class Google_Service_GamesConfiguration_GamesNumberAffixConfiguration extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $fewType = 'Google_Service_GamesConfiguration_LocalizedStringBundle';
  protected $fewDataType = '';
  protected $manyType = 'Google_Service_GamesConfiguration_LocalizedStringBundle';
  protected $manyDataType = '';
  protected $oneType = 'Google_Service_GamesConfiguration_LocalizedStringBundle';
  protected $oneDataType = '';
  protected $otherType = 'Google_Service_GamesConfiguration_LocalizedStringBundle';
  protected $otherDataType = '';
  protected $twoType = 'Google_Service_GamesConfiguration_LocalizedStringBundle';
  protected $twoDataType = '';
  protected $zeroType = 'Google_Service_GamesConfiguration_LocalizedStringBundle';
  protected $zeroDataType = '';


  public function setFew(Google_Service_GamesConfiguration_LocalizedStringBundle $few)
  {
    $this->few = $few;
  }
  public function getFew()
  {
    return $this->few;
  }
  public function setMany(Google_Service_GamesConfiguration_LocalizedStringBundle $many)
  {
    $this->many = $many;
  }
  public function getMany()
  {
    return $this->many;
  }
  public function setOne(Google_Service_GamesConfiguration_LocalizedStringBundle $one)
  {
    $this->one = $one;
  }
  public function getOne()
  {
    return $this->one;
  }
  public function setOther(Google_Service_GamesConfiguration_LocalizedStringBundle $other)
  {
    $this->other = $other;
  }
  public function getOther()
  {
    return $this->other;
  }
  public function setTwo(Google_Service_GamesConfiguration_LocalizedStringBundle $two)
  {
    $this->two = $two;
  }
  public function getTwo()
  {
    return $this->two;
  }
  public function setZero(Google_Service_GamesConfiguration_LocalizedStringBundle $zero)
  {
    $this->zero = $zero;
  }
  public function getZero()
  {
    return $this->zero;
  }
}

class Google_Service_GamesConfiguration_GamesNumberFormatConfiguration extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $currencyCode;
  public $numDecimalPlaces;
  public $numberFormatType;
  protected $suffixType = 'Google_Service_GamesConfiguration_GamesNumberAffixConfiguration';
  protected $suffixDataType = '';


  public function setCurrencyCode($currencyCode)
  {
    $this->currencyCode = $currencyCode;
  }
  public function getCurrencyCode()
  {
    return $this->currencyCode;
  }
  public function setNumDecimalPlaces($numDecimalPlaces)
  {
    $this->numDecimalPlaces = $numDecimalPlaces;
  }
  public function getNumDecimalPlaces()
  {
    return $this->numDecimalPlaces;
  }
  public function setNumberFormatType($numberFormatType)
  {
    $this->numberFormatType = $numberFormatType;
  }
  public function getNumberFormatType()
  {
    return $this->numberFormatType;
  }
  public function setSuffix(Google_Service_GamesConfiguration_GamesNumberAffixConfiguration $suffix)
  {
    $this->suffix = $suffix;
  }
  public function getSuffix()
  {
    return $this->suffix;
  }
}

class Google_Service_GamesConfiguration_ImageConfiguration extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $imageType;
  public $kind;
  public $resourceId;
  public $url;


  public function setImageType($imageType)
  {
    $this->imageType = $imageType;
  }
  public function getImageType()
  {
    return $this->imageType;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setResourceId($resourceId)
  {
    $this->resourceId = $resourceId;
  }
  public function getResourceId()
  {
    return $this->resourceId;
  }
  public function setUrl($url)
  {
    $this->url = $url;
  }
  public function getUrl()
  {
    return $this->url;
  }
}

class Google_Service_GamesConfiguration_LeaderboardConfiguration extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $draftType = 'Google_Service_GamesConfiguration_LeaderboardConfigurationDetail';
  protected $draftDataType = '';
  public $id;
  public $kind;
  protected $publishedType = 'Google_Service_GamesConfiguration_LeaderboardConfigurationDetail';
  protected $publishedDataType = '';
  public $scoreMax;
  public $scoreMin;
  public $scoreOrder;
  public $token;


  public function setDraft(Google_Service_GamesConfiguration_LeaderboardConfigurationDetail $draft)
  {
    $this->draft = $draft;
  }
  public function getDraft()
  {
    return $this->draft;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setPublished(Google_Service_GamesConfiguration_LeaderboardConfigurationDetail $published)
  {
    $this->published = $published;
  }
  public function getPublished()
  {
    return $this->published;
  }
  public function setScoreMax($scoreMax)
  {
    $this->scoreMax = $scoreMax;
  }
  public function getScoreMax()
  {
    return $this->scoreMax;
  }
  public function setScoreMin($scoreMin)
  {
    $this->scoreMin = $scoreMin;
  }
  public function getScoreMin()
  {
    return $this->scoreMin;
  }
  public function setScoreOrder($scoreOrder)
  {
    $this->scoreOrder = $scoreOrder;
  }
  public function getScoreOrder()
  {
    return $this->scoreOrder;
  }
  public function setToken($token)
  {
    $this->token = $token;
  }
  public function getToken()
  {
    return $this->token;
  }
}

class Google_Service_GamesConfiguration_LeaderboardConfigurationDetail extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $iconUrl;
  public $kind;
  protected $nameType = 'Google_Service_GamesConfiguration_LocalizedStringBundle';
  protected $nameDataType = '';
  protected $scoreFormatType = 'Google_Service_GamesConfiguration_GamesNumberFormatConfiguration';
  protected $scoreFormatDataType = '';
  public $sortRank;


  public function setIconUrl($iconUrl)
  {
    $this->iconUrl = $iconUrl;
  }
  public function getIconUrl()
  {
    return $this->iconUrl;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setName(Google_Service_GamesConfiguration_LocalizedStringBundle $name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setScoreFormat(Google_Service_GamesConfiguration_GamesNumberFormatConfiguration $scoreFormat)
  {
    $this->scoreFormat = $scoreFormat;
  }
  public function getScoreFormat()
  {
    return $this->scoreFormat;
  }
  public function setSortRank($sortRank)
  {
    $this->sortRank = $sortRank;
  }
  public function getSortRank()
  {
    return $this->sortRank;
  }
}

class Google_Service_GamesConfiguration_LeaderboardConfigurationListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  protected $itemsType = 'Google_Service_GamesConfiguration_LeaderboardConfiguration';
  protected $itemsDataType = 'array';
  public $kind;
  public $nextPageToken;


  public function setItems($items)
  {
    $this->items = $items;
  }
  public function getItems()
  {
    return $this->items;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
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

class Google_Service_GamesConfiguration_LocalizedString extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  public $locale;
  public $value;


  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLocale($locale)
  {
    $this->locale = $locale;
  }
  public function getLocale()
  {
    return $this->locale;
  }
  public function setValue($value)
  {
    $this->value = $value;
  }
  public function getValue()
  {
    return $this->value;
  }
}

class Google_Service_GamesConfiguration_LocalizedStringBundle extends Google_Collection
{
  protected $collection_key = 'translations';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  protected $translationsType = 'Google_Service_GamesConfiguration_LocalizedString';
  protected $translationsDataType = 'array';


  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setTranslations($translations)
  {
    $this->translations = $translations;
  }
  public function getTranslations()
  {
    return $this->translations;
  }
}
