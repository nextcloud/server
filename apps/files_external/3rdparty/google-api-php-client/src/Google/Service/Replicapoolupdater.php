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
 * Service definition for Replicapoolupdater (v1beta1).
 *
 * <p>
 * The Google Compute Engine Instance Group Updater API provides services for
 * updating groups of Compute Engine Instances.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://cloud.google.com/compute/docs/instance-groups/manager/#applying_rolling_updates_using_the_updater_service" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_Replicapoolupdater extends Google_Service
{
  /** View and manage your data across Google Cloud Platform services. */
  const CLOUD_PLATFORM =
      "https://www.googleapis.com/auth/cloud-platform";
  /** View your data across Google Cloud Platform services. */
  const CLOUD_PLATFORM_READ_ONLY =
      "https://www.googleapis.com/auth/cloud-platform.read-only";
  /** View and manage replica pools. */
  const REPLICAPOOL =
      "https://www.googleapis.com/auth/replicapool";
  /** View replica pools. */
  const REPLICAPOOL_READONLY =
      "https://www.googleapis.com/auth/replicapool.readonly";

  public $rollingUpdates;
  public $zoneOperations;
  

  /**
   * Constructs the internal representation of the Replicapoolupdater service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://www.googleapis.com/';
    $this->servicePath = 'replicapoolupdater/v1beta1/projects/';
    $this->version = 'v1beta1';
    $this->serviceName = 'replicapoolupdater';

    $this->rollingUpdates = new Google_Service_Replicapoolupdater_RollingUpdates_Resource(
        $this,
        $this->serviceName,
        'rollingUpdates',
        array(
          'methods' => array(
            'cancel' => array(
              'path' => '{project}/zones/{zone}/rollingUpdates/{rollingUpdate}/cancel',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'zone' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'rollingUpdate' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => '{project}/zones/{zone}/rollingUpdates/{rollingUpdate}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'zone' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'rollingUpdate' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => '{project}/zones/{zone}/rollingUpdates',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'zone' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => '{project}/zones/{zone}/rollingUpdates',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'zone' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'filter' => array(
                  'location' => 'query',
                  'type' => 'string',
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
            ),'listInstanceUpdates' => array(
              'path' => '{project}/zones/{zone}/rollingUpdates/{rollingUpdate}/instanceUpdates',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'zone' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'rollingUpdate' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'filter' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'pause' => array(
              'path' => '{project}/zones/{zone}/rollingUpdates/{rollingUpdate}/pause',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'zone' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'rollingUpdate' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'resume' => array(
              'path' => '{project}/zones/{zone}/rollingUpdates/{rollingUpdate}/resume',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'zone' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'rollingUpdate' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'rollback' => array(
              'path' => '{project}/zones/{zone}/rollingUpdates/{rollingUpdate}/rollback',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'zone' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'rollingUpdate' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->zoneOperations = new Google_Service_Replicapoolupdater_ZoneOperations_Resource(
        $this,
        $this->serviceName,
        'zoneOperations',
        array(
          'methods' => array(
            'get' => array(
              'path' => '{project}/zones/{zone}/operations/{operation}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'zone' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'operation' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => '{project}/zones/{zone}/operations',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'zone' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'filter' => array(
                  'location' => 'query',
                  'type' => 'string',
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
            ),
          )
        )
    );
  }
}


/**
 * The "rollingUpdates" collection of methods.
 * Typical usage is:
 *  <code>
 *   $replicapoolupdaterService = new Google_Service_Replicapoolupdater(...);
 *   $rollingUpdates = $replicapoolupdaterService->rollingUpdates;
 *  </code>
 */
class Google_Service_Replicapoolupdater_RollingUpdates_Resource extends Google_Service_Resource
{

  /**
   * Cancels an update. The update must be PAUSED before it can be cancelled. This
   * has no effect if the update is already CANCELLED. (rollingUpdates.cancel)
   *
   * @param string $project The Google Developers Console project name.
   * @param string $zone The name of the zone in which the update's target
   * resides.
   * @param string $rollingUpdate The name of the update.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Replicapoolupdater_Operation
   */
  public function cancel($project, $zone, $rollingUpdate, $optParams = array())
  {
    $params = array('project' => $project, 'zone' => $zone, 'rollingUpdate' => $rollingUpdate);
    $params = array_merge($params, $optParams);
    return $this->call('cancel', array($params), "Google_Service_Replicapoolupdater_Operation");
  }

  /**
   * Returns information about an update. (rollingUpdates.get)
   *
   * @param string $project The Google Developers Console project name.
   * @param string $zone The name of the zone in which the update's target
   * resides.
   * @param string $rollingUpdate The name of the update.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Replicapoolupdater_RollingUpdate
   */
  public function get($project, $zone, $rollingUpdate, $optParams = array())
  {
    $params = array('project' => $project, 'zone' => $zone, 'rollingUpdate' => $rollingUpdate);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Replicapoolupdater_RollingUpdate");
  }

  /**
   * Inserts and starts a new update. (rollingUpdates.insert)
   *
   * @param string $project The Google Developers Console project name.
   * @param string $zone The name of the zone in which the update's target
   * resides.
   * @param Google_RollingUpdate $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Replicapoolupdater_Operation
   */
  public function insert($project, $zone, Google_Service_Replicapoolupdater_RollingUpdate $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'zone' => $zone, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Replicapoolupdater_Operation");
  }

  /**
   * Lists recent updates for a given managed instance group, in reverse
   * chronological order and paginated format. (rollingUpdates.listRollingUpdates)
   *
   * @param string $project The Google Developers Console project name.
   * @param string $zone The name of the zone in which the update's target
   * resides.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter Optional. Filter expression for filtering listed
   * resources.
   * @opt_param string pageToken Optional. Tag returned by a previous list request
   * truncated by maxResults. Used to continue a previous list request.
   * @opt_param string maxResults Optional. Maximum count of results to be
   * returned. Maximum value is 500 and default value is 500.
   * @return Google_Service_Replicapoolupdater_RollingUpdateList
   */
  public function listRollingUpdates($project, $zone, $optParams = array())
  {
    $params = array('project' => $project, 'zone' => $zone);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Replicapoolupdater_RollingUpdateList");
  }

  /**
   * Lists the current status for each instance within a given update.
   * (rollingUpdates.listInstanceUpdates)
   *
   * @param string $project The Google Developers Console project name.
   * @param string $zone The name of the zone in which the update's target
   * resides.
   * @param string $rollingUpdate The name of the update.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string maxResults Optional. Maximum count of results to be
   * returned. Maximum value is 500 and default value is 500.
   * @opt_param string filter Optional. Filter expression for filtering listed
   * resources.
   * @opt_param string pageToken Optional. Tag returned by a previous list request
   * truncated by maxResults. Used to continue a previous list request.
   * @return Google_Service_Replicapoolupdater_InstanceUpdateList
   */
  public function listInstanceUpdates($project, $zone, $rollingUpdate, $optParams = array())
  {
    $params = array('project' => $project, 'zone' => $zone, 'rollingUpdate' => $rollingUpdate);
    $params = array_merge($params, $optParams);
    return $this->call('listInstanceUpdates', array($params), "Google_Service_Replicapoolupdater_InstanceUpdateList");
  }

  /**
   * Pauses the update in state from ROLLING_FORWARD or ROLLING_BACK. Has no
   * effect if invoked when the state of the update is PAUSED.
   * (rollingUpdates.pause)
   *
   * @param string $project The Google Developers Console project name.
   * @param string $zone The name of the zone in which the update's target
   * resides.
   * @param string $rollingUpdate The name of the update.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Replicapoolupdater_Operation
   */
  public function pause($project, $zone, $rollingUpdate, $optParams = array())
  {
    $params = array('project' => $project, 'zone' => $zone, 'rollingUpdate' => $rollingUpdate);
    $params = array_merge($params, $optParams);
    return $this->call('pause', array($params), "Google_Service_Replicapoolupdater_Operation");
  }

  /**
   * Continues an update in PAUSED state. Has no effect if invoked when the state
   * of the update is ROLLED_OUT. (rollingUpdates.resume)
   *
   * @param string $project The Google Developers Console project name.
   * @param string $zone The name of the zone in which the update's target
   * resides.
   * @param string $rollingUpdate The name of the update.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Replicapoolupdater_Operation
   */
  public function resume($project, $zone, $rollingUpdate, $optParams = array())
  {
    $params = array('project' => $project, 'zone' => $zone, 'rollingUpdate' => $rollingUpdate);
    $params = array_merge($params, $optParams);
    return $this->call('resume', array($params), "Google_Service_Replicapoolupdater_Operation");
  }

  /**
   * Rolls back the update in state from ROLLING_FORWARD or PAUSED. Has no effect
   * if invoked when the state of the update is ROLLED_BACK.
   * (rollingUpdates.rollback)
   *
   * @param string $project The Google Developers Console project name.
   * @param string $zone The name of the zone in which the update's target
   * resides.
   * @param string $rollingUpdate The name of the update.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Replicapoolupdater_Operation
   */
  public function rollback($project, $zone, $rollingUpdate, $optParams = array())
  {
    $params = array('project' => $project, 'zone' => $zone, 'rollingUpdate' => $rollingUpdate);
    $params = array_merge($params, $optParams);
    return $this->call('rollback', array($params), "Google_Service_Replicapoolupdater_Operation");
  }
}

/**
 * The "zoneOperations" collection of methods.
 * Typical usage is:
 *  <code>
 *   $replicapoolupdaterService = new Google_Service_Replicapoolupdater(...);
 *   $zoneOperations = $replicapoolupdaterService->zoneOperations;
 *  </code>
 */
class Google_Service_Replicapoolupdater_ZoneOperations_Resource extends Google_Service_Resource
{

  /**
   * Retrieves the specified zone-specific operation resource.
   * (zoneOperations.get)
   *
   * @param string $project Name of the project scoping this request.
   * @param string $zone Name of the zone scoping this request.
   * @param string $operation Name of the operation resource to return.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Replicapoolupdater_Operation
   */
  public function get($project, $zone, $operation, $optParams = array())
  {
    $params = array('project' => $project, 'zone' => $zone, 'operation' => $operation);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Replicapoolupdater_Operation");
  }

  /**
   * Retrieves the list of Operation resources contained within the specified
   * zone. (zoneOperations.listZoneOperations)
   *
   * @param string $project Name of the project scoping this request.
   * @param string $zone Name of the zone scoping this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter Optional. Filter expression for filtering listed
   * resources.
   * @opt_param string pageToken Optional. Tag returned by a previous list request
   * truncated by maxResults. Used to continue a previous list request.
   * @opt_param string maxResults Optional. Maximum count of results to be
   * returned. Maximum value is 500 and default value is 500.
   * @return Google_Service_Replicapoolupdater_OperationList
   */
  public function listZoneOperations($project, $zone, $optParams = array())
  {
    $params = array('project' => $project, 'zone' => $zone);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Replicapoolupdater_OperationList");
  }
}




class Google_Service_Replicapoolupdater_InstanceUpdate extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $errorType = 'Google_Service_Replicapoolupdater_InstanceUpdateError';
  protected $errorDataType = '';
  public $instance;
  public $status;


  public function setError(Google_Service_Replicapoolupdater_InstanceUpdateError $error)
  {
    $this->error = $error;
  }
  public function getError()
  {
    return $this->error;
  }
  public function setInstance($instance)
  {
    $this->instance = $instance;
  }
  public function getInstance()
  {
    return $this->instance;
  }
  public function setStatus($status)
  {
    $this->status = $status;
  }
  public function getStatus()
  {
    return $this->status;
  }
}

class Google_Service_Replicapoolupdater_InstanceUpdateError extends Google_Collection
{
  protected $collection_key = 'errors';
  protected $internal_gapi_mappings = array(
  );
  protected $errorsType = 'Google_Service_Replicapoolupdater_InstanceUpdateErrorErrors';
  protected $errorsDataType = 'array';


  public function setErrors($errors)
  {
    $this->errors = $errors;
  }
  public function getErrors()
  {
    return $this->errors;
  }
}

class Google_Service_Replicapoolupdater_InstanceUpdateErrorErrors extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $code;
  public $location;
  public $message;


  public function setCode($code)
  {
    $this->code = $code;
  }
  public function getCode()
  {
    return $this->code;
  }
  public function setLocation($location)
  {
    $this->location = $location;
  }
  public function getLocation()
  {
    return $this->location;
  }
  public function setMessage($message)
  {
    $this->message = $message;
  }
  public function getMessage()
  {
    return $this->message;
  }
}

class Google_Service_Replicapoolupdater_InstanceUpdateList extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  protected $itemsType = 'Google_Service_Replicapoolupdater_InstanceUpdate';
  protected $itemsDataType = 'array';
  public $kind;
  public $nextPageToken;
  public $selfLink;


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
  public function setSelfLink($selfLink)
  {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink()
  {
    return $this->selfLink;
  }
}

class Google_Service_Replicapoolupdater_Operation extends Google_Collection
{
  protected $collection_key = 'warnings';
  protected $internal_gapi_mappings = array(
  );
  public $clientOperationId;
  public $creationTimestamp;
  public $endTime;
  protected $errorType = 'Google_Service_Replicapoolupdater_OperationError';
  protected $errorDataType = '';
  public $httpErrorMessage;
  public $httpErrorStatusCode;
  public $id;
  public $insertTime;
  public $kind;
  public $name;
  public $operationType;
  public $progress;
  public $region;
  public $selfLink;
  public $startTime;
  public $status;
  public $statusMessage;
  public $targetId;
  public $targetLink;
  public $user;
  protected $warningsType = 'Google_Service_Replicapoolupdater_OperationWarnings';
  protected $warningsDataType = 'array';
  public $zone;


  public function setClientOperationId($clientOperationId)
  {
    $this->clientOperationId = $clientOperationId;
  }
  public function getClientOperationId()
  {
    return $this->clientOperationId;
  }
  public function setCreationTimestamp($creationTimestamp)
  {
    $this->creationTimestamp = $creationTimestamp;
  }
  public function getCreationTimestamp()
  {
    return $this->creationTimestamp;
  }
  public function setEndTime($endTime)
  {
    $this->endTime = $endTime;
  }
  public function getEndTime()
  {
    return $this->endTime;
  }
  public function setError(Google_Service_Replicapoolupdater_OperationError $error)
  {
    $this->error = $error;
  }
  public function getError()
  {
    return $this->error;
  }
  public function setHttpErrorMessage($httpErrorMessage)
  {
    $this->httpErrorMessage = $httpErrorMessage;
  }
  public function getHttpErrorMessage()
  {
    return $this->httpErrorMessage;
  }
  public function setHttpErrorStatusCode($httpErrorStatusCode)
  {
    $this->httpErrorStatusCode = $httpErrorStatusCode;
  }
  public function getHttpErrorStatusCode()
  {
    return $this->httpErrorStatusCode;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setInsertTime($insertTime)
  {
    $this->insertTime = $insertTime;
  }
  public function getInsertTime()
  {
    return $this->insertTime;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setOperationType($operationType)
  {
    $this->operationType = $operationType;
  }
  public function getOperationType()
  {
    return $this->operationType;
  }
  public function setProgress($progress)
  {
    $this->progress = $progress;
  }
  public function getProgress()
  {
    return $this->progress;
  }
  public function setRegion($region)
  {
    $this->region = $region;
  }
  public function getRegion()
  {
    return $this->region;
  }
  public function setSelfLink($selfLink)
  {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink()
  {
    return $this->selfLink;
  }
  public function setStartTime($startTime)
  {
    $this->startTime = $startTime;
  }
  public function getStartTime()
  {
    return $this->startTime;
  }
  public function setStatus($status)
  {
    $this->status = $status;
  }
  public function getStatus()
  {
    return $this->status;
  }
  public function setStatusMessage($statusMessage)
  {
    $this->statusMessage = $statusMessage;
  }
  public function getStatusMessage()
  {
    return $this->statusMessage;
  }
  public function setTargetId($targetId)
  {
    $this->targetId = $targetId;
  }
  public function getTargetId()
  {
    return $this->targetId;
  }
  public function setTargetLink($targetLink)
  {
    $this->targetLink = $targetLink;
  }
  public function getTargetLink()
  {
    return $this->targetLink;
  }
  public function setUser($user)
  {
    $this->user = $user;
  }
  public function getUser()
  {
    return $this->user;
  }
  public function setWarnings($warnings)
  {
    $this->warnings = $warnings;
  }
  public function getWarnings()
  {
    return $this->warnings;
  }
  public function setZone($zone)
  {
    $this->zone = $zone;
  }
  public function getZone()
  {
    return $this->zone;
  }
}

class Google_Service_Replicapoolupdater_OperationError extends Google_Collection
{
  protected $collection_key = 'errors';
  protected $internal_gapi_mappings = array(
  );
  protected $errorsType = 'Google_Service_Replicapoolupdater_OperationErrorErrors';
  protected $errorsDataType = 'array';


  public function setErrors($errors)
  {
    $this->errors = $errors;
  }
  public function getErrors()
  {
    return $this->errors;
  }
}

class Google_Service_Replicapoolupdater_OperationErrorErrors extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $code;
  public $location;
  public $message;


  public function setCode($code)
  {
    $this->code = $code;
  }
  public function getCode()
  {
    return $this->code;
  }
  public function setLocation($location)
  {
    $this->location = $location;
  }
  public function getLocation()
  {
    return $this->location;
  }
  public function setMessage($message)
  {
    $this->message = $message;
  }
  public function getMessage()
  {
    return $this->message;
  }
}

class Google_Service_Replicapoolupdater_OperationList extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $id;
  protected $itemsType = 'Google_Service_Replicapoolupdater_Operation';
  protected $itemsDataType = 'array';
  public $kind;
  public $nextPageToken;
  public $selfLink;


  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
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
  public function setSelfLink($selfLink)
  {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink()
  {
    return $this->selfLink;
  }
}

class Google_Service_Replicapoolupdater_OperationWarnings extends Google_Collection
{
  protected $collection_key = 'data';
  protected $internal_gapi_mappings = array(
  );
  public $code;
  protected $dataType = 'Google_Service_Replicapoolupdater_OperationWarningsData';
  protected $dataDataType = 'array';
  public $message;


  public function setCode($code)
  {
    $this->code = $code;
  }
  public function getCode()
  {
    return $this->code;
  }
  public function setData($data)
  {
    $this->data = $data;
  }
  public function getData()
  {
    return $this->data;
  }
  public function setMessage($message)
  {
    $this->message = $message;
  }
  public function getMessage()
  {
    return $this->message;
  }
}

class Google_Service_Replicapoolupdater_OperationWarningsData extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $key;
  public $value;


  public function setKey($key)
  {
    $this->key = $key;
  }
  public function getKey()
  {
    return $this->key;
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

class Google_Service_Replicapoolupdater_RollingUpdate extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $actionType;
  public $creationTimestamp;
  public $description;
  protected $errorType = 'Google_Service_Replicapoolupdater_RollingUpdateError';
  protected $errorDataType = '';
  public $id;
  public $instanceGroup;
  public $instanceGroupManager;
  public $instanceTemplate;
  public $kind;
  public $oldInstanceTemplate;
  protected $policyType = 'Google_Service_Replicapoolupdater_RollingUpdatePolicy';
  protected $policyDataType = '';
  public $progress;
  public $selfLink;
  public $status;
  public $statusMessage;
  public $user;


  public function setActionType($actionType)
  {
    $this->actionType = $actionType;
  }
  public function getActionType()
  {
    return $this->actionType;
  }
  public function setCreationTimestamp($creationTimestamp)
  {
    $this->creationTimestamp = $creationTimestamp;
  }
  public function getCreationTimestamp()
  {
    return $this->creationTimestamp;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setError(Google_Service_Replicapoolupdater_RollingUpdateError $error)
  {
    $this->error = $error;
  }
  public function getError()
  {
    return $this->error;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setInstanceGroup($instanceGroup)
  {
    $this->instanceGroup = $instanceGroup;
  }
  public function getInstanceGroup()
  {
    return $this->instanceGroup;
  }
  public function setInstanceGroupManager($instanceGroupManager)
  {
    $this->instanceGroupManager = $instanceGroupManager;
  }
  public function getInstanceGroupManager()
  {
    return $this->instanceGroupManager;
  }
  public function setInstanceTemplate($instanceTemplate)
  {
    $this->instanceTemplate = $instanceTemplate;
  }
  public function getInstanceTemplate()
  {
    return $this->instanceTemplate;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setOldInstanceTemplate($oldInstanceTemplate)
  {
    $this->oldInstanceTemplate = $oldInstanceTemplate;
  }
  public function getOldInstanceTemplate()
  {
    return $this->oldInstanceTemplate;
  }
  public function setPolicy(Google_Service_Replicapoolupdater_RollingUpdatePolicy $policy)
  {
    $this->policy = $policy;
  }
  public function getPolicy()
  {
    return $this->policy;
  }
  public function setProgress($progress)
  {
    $this->progress = $progress;
  }
  public function getProgress()
  {
    return $this->progress;
  }
  public function setSelfLink($selfLink)
  {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink()
  {
    return $this->selfLink;
  }
  public function setStatus($status)
  {
    $this->status = $status;
  }
  public function getStatus()
  {
    return $this->status;
  }
  public function setStatusMessage($statusMessage)
  {
    $this->statusMessage = $statusMessage;
  }
  public function getStatusMessage()
  {
    return $this->statusMessage;
  }
  public function setUser($user)
  {
    $this->user = $user;
  }
  public function getUser()
  {
    return $this->user;
  }
}

class Google_Service_Replicapoolupdater_RollingUpdateError extends Google_Collection
{
  protected $collection_key = 'errors';
  protected $internal_gapi_mappings = array(
  );
  protected $errorsType = 'Google_Service_Replicapoolupdater_RollingUpdateErrorErrors';
  protected $errorsDataType = 'array';


  public function setErrors($errors)
  {
    $this->errors = $errors;
  }
  public function getErrors()
  {
    return $this->errors;
  }
}

class Google_Service_Replicapoolupdater_RollingUpdateErrorErrors extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $code;
  public $location;
  public $message;


  public function setCode($code)
  {
    $this->code = $code;
  }
  public function getCode()
  {
    return $this->code;
  }
  public function setLocation($location)
  {
    $this->location = $location;
  }
  public function getLocation()
  {
    return $this->location;
  }
  public function setMessage($message)
  {
    $this->message = $message;
  }
  public function getMessage()
  {
    return $this->message;
  }
}

class Google_Service_Replicapoolupdater_RollingUpdateList extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  protected $itemsType = 'Google_Service_Replicapoolupdater_RollingUpdate';
  protected $itemsDataType = 'array';
  public $kind;
  public $nextPageToken;
  public $selfLink;


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
  public function setSelfLink($selfLink)
  {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink()
  {
    return $this->selfLink;
  }
}

class Google_Service_Replicapoolupdater_RollingUpdatePolicy extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $autoPauseAfterInstances;
  public $instanceStartupTimeoutSec;
  public $maxNumConcurrentInstances;
  public $maxNumFailedInstances;
  public $minInstanceUpdateTimeSec;


  public function setAutoPauseAfterInstances($autoPauseAfterInstances)
  {
    $this->autoPauseAfterInstances = $autoPauseAfterInstances;
  }
  public function getAutoPauseAfterInstances()
  {
    return $this->autoPauseAfterInstances;
  }
  public function setInstanceStartupTimeoutSec($instanceStartupTimeoutSec)
  {
    $this->instanceStartupTimeoutSec = $instanceStartupTimeoutSec;
  }
  public function getInstanceStartupTimeoutSec()
  {
    return $this->instanceStartupTimeoutSec;
  }
  public function setMaxNumConcurrentInstances($maxNumConcurrentInstances)
  {
    $this->maxNumConcurrentInstances = $maxNumConcurrentInstances;
  }
  public function getMaxNumConcurrentInstances()
  {
    return $this->maxNumConcurrentInstances;
  }
  public function setMaxNumFailedInstances($maxNumFailedInstances)
  {
    $this->maxNumFailedInstances = $maxNumFailedInstances;
  }
  public function getMaxNumFailedInstances()
  {
    return $this->maxNumFailedInstances;
  }
  public function setMinInstanceUpdateTimeSec($minInstanceUpdateTimeSec)
  {
    $this->minInstanceUpdateTimeSec = $minInstanceUpdateTimeSec;
  }
  public function getMinInstanceUpdateTimeSec()
  {
    return $this->minInstanceUpdateTimeSec;
  }
}
