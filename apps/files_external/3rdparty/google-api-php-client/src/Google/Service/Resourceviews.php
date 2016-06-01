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
 * Service definition for Resourceviews (v1beta2).
 *
 * <p>
 * The Resource View API allows users to create and manage logical sets of
 * Google Compute Engine instances.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://developers.google.com/compute/" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_Resourceviews extends Google_Service
{
  /** View and manage your data across Google Cloud Platform services. */
  const CLOUD_PLATFORM =
      "https://www.googleapis.com/auth/cloud-platform";
  /** View your data across Google Cloud Platform services. */
  const CLOUD_PLATFORM_READ_ONLY =
      "https://www.googleapis.com/auth/cloud-platform.read-only";
  /** View and manage your Google Compute Engine resources. */
  const COMPUTE =
      "https://www.googleapis.com/auth/compute";
  /** View your Google Compute Engine resources. */
  const COMPUTE_READONLY =
      "https://www.googleapis.com/auth/compute.readonly";
  /** View and manage your Google Cloud Platform management resources and deployment status information. */
  const NDEV_CLOUDMAN =
      "https://www.googleapis.com/auth/ndev.cloudman";
  /** View your Google Cloud Platform management resources and deployment status information. */
  const NDEV_CLOUDMAN_READONLY =
      "https://www.googleapis.com/auth/ndev.cloudman.readonly";

  public $zoneOperations;
  public $zoneViews;
  

  /**
   * Constructs the internal representation of the Resourceviews service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://www.googleapis.com/';
    $this->servicePath = 'resourceviews/v1beta2/projects/';
    $this->version = 'v1beta2';
    $this->serviceName = 'resourceviews';

    $this->zoneOperations = new Google_Service_Resourceviews_ZoneOperations_Resource(
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
    $this->zoneViews = new Google_Service_Resourceviews_ZoneViews_Resource(
        $this,
        $this->serviceName,
        'zoneViews',
        array(
          'methods' => array(
            'addResources' => array(
              'path' => '{project}/zones/{zone}/resourceViews/{resourceView}/addResources',
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
                'resourceView' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'delete' => array(
              'path' => '{project}/zones/{zone}/resourceViews/{resourceView}',
              'httpMethod' => 'DELETE',
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
                'resourceView' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => '{project}/zones/{zone}/resourceViews/{resourceView}',
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
                'resourceView' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'getService' => array(
              'path' => '{project}/zones/{zone}/resourceViews/{resourceView}/getService',
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
                'resourceView' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'resourceName' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'insert' => array(
              'path' => '{project}/zones/{zone}/resourceViews',
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
              'path' => '{project}/zones/{zone}/resourceViews',
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
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
              ),
            ),'listResources' => array(
              'path' => '{project}/zones/{zone}/resourceViews/{resourceView}/resources',
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
                'resourceView' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'listState' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'format' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'serviceName' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'removeResources' => array(
              'path' => '{project}/zones/{zone}/resourceViews/{resourceView}/removeResources',
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
                'resourceView' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'setService' => array(
              'path' => '{project}/zones/{zone}/resourceViews/{resourceView}/setService',
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
                'resourceView' => array(
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
 * The "zoneOperations" collection of methods.
 * Typical usage is:
 *  <code>
 *   $resourceviewsService = new Google_Service_Resourceviews(...);
 *   $zoneOperations = $resourceviewsService->zoneOperations;
 *  </code>
 */
class Google_Service_Resourceviews_ZoneOperations_Resource extends Google_Service_Resource
{

  /**
   * Retrieves the specified zone-specific operation resource.
   * (zoneOperations.get)
   *
   * @param string $project Name of the project scoping this request.
   * @param string $zone Name of the zone scoping this request.
   * @param string $operation Name of the operation resource to return.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Resourceviews_Operation
   */
  public function get($project, $zone, $operation, $optParams = array())
  {
    $params = array('project' => $project, 'zone' => $zone, 'operation' => $operation);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Resourceviews_Operation");
  }

  /**
   * Retrieves the list of operation resources contained within the specified
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
   * @return Google_Service_Resourceviews_OperationList
   */
  public function listZoneOperations($project, $zone, $optParams = array())
  {
    $params = array('project' => $project, 'zone' => $zone);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Resourceviews_OperationList");
  }
}

/**
 * The "zoneViews" collection of methods.
 * Typical usage is:
 *  <code>
 *   $resourceviewsService = new Google_Service_Resourceviews(...);
 *   $zoneViews = $resourceviewsService->zoneViews;
 *  </code>
 */
class Google_Service_Resourceviews_ZoneViews_Resource extends Google_Service_Resource
{

  /**
   * Add resources to the view. (zoneViews.addResources)
   *
   * @param string $project The project name of the resource view.
   * @param string $zone The zone name of the resource view.
   * @param string $resourceView The name of the resource view.
   * @param Google_ZoneViewsAddResourcesRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Resourceviews_Operation
   */
  public function addResources($project, $zone, $resourceView, Google_Service_Resourceviews_ZoneViewsAddResourcesRequest $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'zone' => $zone, 'resourceView' => $resourceView, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('addResources', array($params), "Google_Service_Resourceviews_Operation");
  }

  /**
   * Delete a resource view. (zoneViews.delete)
   *
   * @param string $project The project name of the resource view.
   * @param string $zone The zone name of the resource view.
   * @param string $resourceView The name of the resource view.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Resourceviews_Operation
   */
  public function delete($project, $zone, $resourceView, $optParams = array())
  {
    $params = array('project' => $project, 'zone' => $zone, 'resourceView' => $resourceView);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params), "Google_Service_Resourceviews_Operation");
  }

  /**
   * Get the information of a zonal resource view. (zoneViews.get)
   *
   * @param string $project The project name of the resource view.
   * @param string $zone The zone name of the resource view.
   * @param string $resourceView The name of the resource view.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Resourceviews_ResourceView
   */
  public function get($project, $zone, $resourceView, $optParams = array())
  {
    $params = array('project' => $project, 'zone' => $zone, 'resourceView' => $resourceView);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Resourceviews_ResourceView");
  }

  /**
   * Get the service information of a resource view or a resource.
   * (zoneViews.getService)
   *
   * @param string $project The project name of the resource view.
   * @param string $zone The zone name of the resource view.
   * @param string $resourceView The name of the resource view.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string resourceName The name of the resource if user wants to get
   * the service information of the resource.
   * @return Google_Service_Resourceviews_ZoneViewsGetServiceResponse
   */
  public function getService($project, $zone, $resourceView, $optParams = array())
  {
    $params = array('project' => $project, 'zone' => $zone, 'resourceView' => $resourceView);
    $params = array_merge($params, $optParams);
    return $this->call('getService', array($params), "Google_Service_Resourceviews_ZoneViewsGetServiceResponse");
  }

  /**
   * Create a resource view. (zoneViews.insert)
   *
   * @param string $project The project name of the resource view.
   * @param string $zone The zone name of the resource view.
   * @param Google_ResourceView $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Resourceviews_Operation
   */
  public function insert($project, $zone, Google_Service_Resourceviews_ResourceView $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'zone' => $zone, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Resourceviews_Operation");
  }

  /**
   * List resource views. (zoneViews.listZoneViews)
   *
   * @param string $project The project name of the resource view.
   * @param string $zone The zone name of the resource view.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken Specifies a nextPageToken returned by a previous
   * list request. This token can be used to request the next page of results from
   * a previous list request.
   * @opt_param int maxResults Maximum count of results to be returned. Acceptable
   * values are 0 to 5000, inclusive. (Default: 5000)
   * @return Google_Service_Resourceviews_ZoneViewsList
   */
  public function listZoneViews($project, $zone, $optParams = array())
  {
    $params = array('project' => $project, 'zone' => $zone);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Resourceviews_ZoneViewsList");
  }

  /**
   * List the resources of the resource view. (zoneViews.listResources)
   *
   * @param string $project The project name of the resource view.
   * @param string $zone The zone name of the resource view.
   * @param string $resourceView The name of the resource view.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string listState The state of the instance to list. By default, it
   * lists all instances.
   * @opt_param string format The requested format of the return value. It can be
   * URL or URL_PORT. A JSON object will be included in the response based on the
   * format. The default format is NONE, which results in no JSON in the response.
   * @opt_param int maxResults Maximum count of results to be returned. Acceptable
   * values are 0 to 5000, inclusive. (Default: 5000)
   * @opt_param string pageToken Specifies a nextPageToken returned by a previous
   * list request. This token can be used to request the next page of results from
   * a previous list request.
   * @opt_param string serviceName The service name to return in the response. It
   * is optional and if it is not set, all the service end points will be
   * returned.
   * @return Google_Service_Resourceviews_ZoneViewsListResourcesResponse
   */
  public function listResources($project, $zone, $resourceView, $optParams = array())
  {
    $params = array('project' => $project, 'zone' => $zone, 'resourceView' => $resourceView);
    $params = array_merge($params, $optParams);
    return $this->call('listResources', array($params), "Google_Service_Resourceviews_ZoneViewsListResourcesResponse");
  }

  /**
   * Remove resources from the view. (zoneViews.removeResources)
   *
   * @param string $project The project name of the resource view.
   * @param string $zone The zone name of the resource view.
   * @param string $resourceView The name of the resource view.
   * @param Google_ZoneViewsRemoveResourcesRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Resourceviews_Operation
   */
  public function removeResources($project, $zone, $resourceView, Google_Service_Resourceviews_ZoneViewsRemoveResourcesRequest $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'zone' => $zone, 'resourceView' => $resourceView, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('removeResources', array($params), "Google_Service_Resourceviews_Operation");
  }

  /**
   * Update the service information of a resource view or a resource.
   * (zoneViews.setService)
   *
   * @param string $project The project name of the resource view.
   * @param string $zone The zone name of the resource view.
   * @param string $resourceView The name of the resource view.
   * @param Google_ZoneViewsSetServiceRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Resourceviews_Operation
   */
  public function setService($project, $zone, $resourceView, Google_Service_Resourceviews_ZoneViewsSetServiceRequest $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'zone' => $zone, 'resourceView' => $resourceView, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('setService', array($params), "Google_Service_Resourceviews_Operation");
  }
}




class Google_Service_Resourceviews_Label extends Google_Model
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

class Google_Service_Resourceviews_ListResourceResponseItem extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $endpoints;
  public $resource;


  public function setEndpoints($endpoints)
  {
    $this->endpoints = $endpoints;
  }
  public function getEndpoints()
  {
    return $this->endpoints;
  }
  public function setResource($resource)
  {
    $this->resource = $resource;
  }
  public function getResource()
  {
    return $this->resource;
  }
}

class Google_Service_Resourceviews_ListResourceResponseItemEndpoints extends Google_Model
{
}

class Google_Service_Resourceviews_Operation extends Google_Collection
{
  protected $collection_key = 'warnings';
  protected $internal_gapi_mappings = array(
  );
  public $clientOperationId;
  public $creationTimestamp;
  public $endTime;
  protected $errorType = 'Google_Service_Resourceviews_OperationError';
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
  protected $warningsType = 'Google_Service_Resourceviews_OperationWarnings';
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
  public function setError(Google_Service_Resourceviews_OperationError $error)
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

class Google_Service_Resourceviews_OperationError extends Google_Collection
{
  protected $collection_key = 'errors';
  protected $internal_gapi_mappings = array(
  );
  protected $errorsType = 'Google_Service_Resourceviews_OperationErrorErrors';
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

class Google_Service_Resourceviews_OperationErrorErrors extends Google_Model
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

class Google_Service_Resourceviews_OperationList extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $id;
  protected $itemsType = 'Google_Service_Resourceviews_Operation';
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

class Google_Service_Resourceviews_OperationWarnings extends Google_Collection
{
  protected $collection_key = 'data';
  protected $internal_gapi_mappings = array(
  );
  public $code;
  protected $dataType = 'Google_Service_Resourceviews_OperationWarningsData';
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

class Google_Service_Resourceviews_OperationWarningsData extends Google_Model
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

class Google_Service_Resourceviews_ResourceView extends Google_Collection
{
  protected $collection_key = 'resources';
  protected $internal_gapi_mappings = array(
  );
  public $creationTimestamp;
  public $description;
  protected $endpointsType = 'Google_Service_Resourceviews_ServiceEndpoint';
  protected $endpointsDataType = 'array';
  public $fingerprint;
  public $id;
  public $kind;
  protected $labelsType = 'Google_Service_Resourceviews_Label';
  protected $labelsDataType = 'array';
  public $name;
  public $network;
  public $resources;
  public $selfLink;
  public $size;


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
  public function setEndpoints($endpoints)
  {
    $this->endpoints = $endpoints;
  }
  public function getEndpoints()
  {
    return $this->endpoints;
  }
  public function setFingerprint($fingerprint)
  {
    $this->fingerprint = $fingerprint;
  }
  public function getFingerprint()
  {
    return $this->fingerprint;
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
  public function setLabels($labels)
  {
    $this->labels = $labels;
  }
  public function getLabels()
  {
    return $this->labels;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setNetwork($network)
  {
    $this->network = $network;
  }
  public function getNetwork()
  {
    return $this->network;
  }
  public function setResources($resources)
  {
    $this->resources = $resources;
  }
  public function getResources()
  {
    return $this->resources;
  }
  public function setSelfLink($selfLink)
  {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink()
  {
    return $this->selfLink;
  }
  public function setSize($size)
  {
    $this->size = $size;
  }
  public function getSize()
  {
    return $this->size;
  }
}

class Google_Service_Resourceviews_ServiceEndpoint extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $name;
  public $port;


  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setPort($port)
  {
    $this->port = $port;
  }
  public function getPort()
  {
    return $this->port;
  }
}

class Google_Service_Resourceviews_ZoneViewsAddResourcesRequest extends Google_Collection
{
  protected $collection_key = 'resources';
  protected $internal_gapi_mappings = array(
  );
  public $resources;


  public function setResources($resources)
  {
    $this->resources = $resources;
  }
  public function getResources()
  {
    return $this->resources;
  }
}

class Google_Service_Resourceviews_ZoneViewsGetServiceResponse extends Google_Collection
{
  protected $collection_key = 'endpoints';
  protected $internal_gapi_mappings = array(
  );
  protected $endpointsType = 'Google_Service_Resourceviews_ServiceEndpoint';
  protected $endpointsDataType = 'array';
  public $fingerprint;


  public function setEndpoints($endpoints)
  {
    $this->endpoints = $endpoints;
  }
  public function getEndpoints()
  {
    return $this->endpoints;
  }
  public function setFingerprint($fingerprint)
  {
    $this->fingerprint = $fingerprint;
  }
  public function getFingerprint()
  {
    return $this->fingerprint;
  }
}

class Google_Service_Resourceviews_ZoneViewsList extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  protected $itemsType = 'Google_Service_Resourceviews_ResourceView';
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

class Google_Service_Resourceviews_ZoneViewsListResourcesResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  protected $itemsType = 'Google_Service_Resourceviews_ListResourceResponseItem';
  protected $itemsDataType = 'array';
  public $network;
  public $nextPageToken;


  public function setItems($items)
  {
    $this->items = $items;
  }
  public function getItems()
  {
    return $this->items;
  }
  public function setNetwork($network)
  {
    $this->network = $network;
  }
  public function getNetwork()
  {
    return $this->network;
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

class Google_Service_Resourceviews_ZoneViewsRemoveResourcesRequest extends Google_Collection
{
  protected $collection_key = 'resources';
  protected $internal_gapi_mappings = array(
  );
  public $resources;


  public function setResources($resources)
  {
    $this->resources = $resources;
  }
  public function getResources()
  {
    return $this->resources;
  }
}

class Google_Service_Resourceviews_ZoneViewsSetServiceRequest extends Google_Collection
{
  protected $collection_key = 'endpoints';
  protected $internal_gapi_mappings = array(
  );
  protected $endpointsType = 'Google_Service_Resourceviews_ServiceEndpoint';
  protected $endpointsDataType = 'array';
  public $fingerprint;
  public $resourceName;


  public function setEndpoints($endpoints)
  {
    $this->endpoints = $endpoints;
  }
  public function getEndpoints()
  {
    return $this->endpoints;
  }
  public function setFingerprint($fingerprint)
  {
    $this->fingerprint = $fingerprint;
  }
  public function getFingerprint()
  {
    return $this->fingerprint;
  }
  public function setResourceName($resourceName)
  {
    $this->resourceName = $resourceName;
  }
  public function getResourceName()
  {
    return $this->resourceName;
  }
}
