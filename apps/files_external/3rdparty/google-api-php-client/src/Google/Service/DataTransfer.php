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
 * Service definition for DataTransfer (datatransfer_v1).
 *
 * <p>
 * Admin Data Transfer API lets you transfer user data from one user to another.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://developers.google.com/admin-sdk/data-transfer/" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_DataTransfer extends Google_Service
{
  /** View and manage data transfers between users in your organization. */
  const ADMIN_DATATRANSFER =
      "https://www.googleapis.com/auth/admin.datatransfer";
  /** View data transfers between users in your organization. */
  const ADMIN_DATATRANSFER_READONLY =
      "https://www.googleapis.com/auth/admin.datatransfer.readonly";

  public $applications;
  public $transfers;
  

  /**
   * Constructs the internal representation of the DataTransfer service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://www.googleapis.com/';
    $this->servicePath = 'admin/datatransfer/v1/';
    $this->version = 'datatransfer_v1';
    $this->serviceName = 'admin';

    $this->applications = new Google_Service_DataTransfer_Applications_Resource(
        $this,
        $this->serviceName,
        'applications',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'applications/{applicationId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'applicationId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'applications',
              'httpMethod' => 'GET',
              'parameters' => array(
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'customerId' => array(
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
    $this->transfers = new Google_Service_DataTransfer_Transfers_Resource(
        $this,
        $this->serviceName,
        'transfers',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'transfers/{dataTransferId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'dataTransferId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'transfers',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),'list' => array(
              'path' => 'transfers',
              'httpMethod' => 'GET',
              'parameters' => array(
                'status' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'newOwnerUserId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'oldOwnerUserId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'customerId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
  }
}


/**
 * The "applications" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adminService = new Google_Service_DataTransfer(...);
 *   $applications = $adminService->applications;
 *  </code>
 */
class Google_Service_DataTransfer_Applications_Resource extends Google_Service_Resource
{

  /**
   * Retrieves information about an application for the given application ID.
   * (applications.get)
   *
   * @param string $applicationId ID of the application resource to be retrieved.
   * @param array $optParams Optional parameters.
   * @return Google_Service_DataTransfer_Application
   */
  public function get($applicationId, $optParams = array())
  {
    $params = array('applicationId' => $applicationId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_DataTransfer_Application");
  }

  /**
   * Lists the applications available for data transfer for a customer.
   * (applications.listApplications)
   *
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken Token to specify next page in the list.
   * @opt_param string customerId Immutable ID of the Google Apps account.
   * @opt_param string maxResults Maximum number of results to return. Default is
   * 100.
   * @return Google_Service_DataTransfer_ApplicationsListResponse
   */
  public function listApplications($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_DataTransfer_ApplicationsListResponse");
  }
}

/**
 * The "transfers" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adminService = new Google_Service_DataTransfer(...);
 *   $transfers = $adminService->transfers;
 *  </code>
 */
class Google_Service_DataTransfer_Transfers_Resource extends Google_Service_Resource
{

  /**
   * Retrieves a data transfer request by its resource ID. (transfers.get)
   *
   * @param string $dataTransferId ID of the resource to be retrieved. This is
   * returned in the response from the insert method.
   * @param array $optParams Optional parameters.
   * @return Google_Service_DataTransfer_DataTransfer
   */
  public function get($dataTransferId, $optParams = array())
  {
    $params = array('dataTransferId' => $dataTransferId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_DataTransfer_DataTransfer");
  }

  /**
   * Inserts a data transfer request. (transfers.insert)
   *
   * @param Google_DataTransfer $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_DataTransfer_DataTransfer
   */
  public function insert(Google_Service_DataTransfer_DataTransfer $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_DataTransfer_DataTransfer");
  }

  /**
   * Lists the transfers for a customer by source user, destination user, or
   * status. (transfers.listTransfers)
   *
   * @param array $optParams Optional parameters.
   *
   * @opt_param string status Status of the transfer.
   * @opt_param int maxResults Maximum number of results to return. Default is
   * 100.
   * @opt_param string newOwnerUserId Destination user's profile ID.
   * @opt_param string oldOwnerUserId Source user's profile ID.
   * @opt_param string pageToken Token to specify the next page in the list.
   * @opt_param string customerId Immutable ID of the Google Apps account.
   * @return Google_Service_DataTransfer_DataTransfersListResponse
   */
  public function listTransfers($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_DataTransfer_DataTransfersListResponse");
  }
}




class Google_Service_DataTransfer_Application extends Google_Collection
{
  protected $collection_key = 'transferParams';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $id;
  public $kind;
  public $name;
  protected $transferParamsType = 'Google_Service_DataTransfer_ApplicationTransferParam';
  protected $transferParamsDataType = 'array';


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
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
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setTransferParams($transferParams)
  {
    $this->transferParams = $transferParams;
  }
  public function getTransferParams()
  {
    return $this->transferParams;
  }
}

class Google_Service_DataTransfer_ApplicationDataTransfer extends Google_Collection
{
  protected $collection_key = 'applicationTransferParams';
  protected $internal_gapi_mappings = array(
  );
  public $applicationId;
  protected $applicationTransferParamsType = 'Google_Service_DataTransfer_ApplicationTransferParam';
  protected $applicationTransferParamsDataType = 'array';
  public $applicationTransferStatus;


  public function setApplicationId($applicationId)
  {
    $this->applicationId = $applicationId;
  }
  public function getApplicationId()
  {
    return $this->applicationId;
  }
  public function setApplicationTransferParams($applicationTransferParams)
  {
    $this->applicationTransferParams = $applicationTransferParams;
  }
  public function getApplicationTransferParams()
  {
    return $this->applicationTransferParams;
  }
  public function setApplicationTransferStatus($applicationTransferStatus)
  {
    $this->applicationTransferStatus = $applicationTransferStatus;
  }
  public function getApplicationTransferStatus()
  {
    return $this->applicationTransferStatus;
  }
}

class Google_Service_DataTransfer_ApplicationTransferParam extends Google_Collection
{
  protected $collection_key = 'value';
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

class Google_Service_DataTransfer_ApplicationsListResponse extends Google_Collection
{
  protected $collection_key = 'applications';
  protected $internal_gapi_mappings = array(
  );
  protected $applicationsType = 'Google_Service_DataTransfer_Application';
  protected $applicationsDataType = 'array';
  public $etag;
  public $kind;
  public $nextPageToken;


  public function setApplications($applications)
  {
    $this->applications = $applications;
  }
  public function getApplications()
  {
    return $this->applications;
  }
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
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

class Google_Service_DataTransfer_DataTransfer extends Google_Collection
{
  protected $collection_key = 'applicationDataTransfers';
  protected $internal_gapi_mappings = array(
  );
  protected $applicationDataTransfersType = 'Google_Service_DataTransfer_ApplicationDataTransfer';
  protected $applicationDataTransfersDataType = 'array';
  public $etag;
  public $id;
  public $kind;
  public $newOwnerUserId;
  public $oldOwnerUserId;
  public $overallTransferStatusCode;
  public $requestTime;


  public function setApplicationDataTransfers($applicationDataTransfers)
  {
    $this->applicationDataTransfers = $applicationDataTransfers;
  }
  public function getApplicationDataTransfers()
  {
    return $this->applicationDataTransfers;
  }
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
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
  public function setNewOwnerUserId($newOwnerUserId)
  {
    $this->newOwnerUserId = $newOwnerUserId;
  }
  public function getNewOwnerUserId()
  {
    return $this->newOwnerUserId;
  }
  public function setOldOwnerUserId($oldOwnerUserId)
  {
    $this->oldOwnerUserId = $oldOwnerUserId;
  }
  public function getOldOwnerUserId()
  {
    return $this->oldOwnerUserId;
  }
  public function setOverallTransferStatusCode($overallTransferStatusCode)
  {
    $this->overallTransferStatusCode = $overallTransferStatusCode;
  }
  public function getOverallTransferStatusCode()
  {
    return $this->overallTransferStatusCode;
  }
  public function setRequestTime($requestTime)
  {
    $this->requestTime = $requestTime;
  }
  public function getRequestTime()
  {
    return $this->requestTime;
  }
}

class Google_Service_DataTransfer_DataTransfersListResponse extends Google_Collection
{
  protected $collection_key = 'dataTransfers';
  protected $internal_gapi_mappings = array(
  );
  protected $dataTransfersType = 'Google_Service_DataTransfer_DataTransfer';
  protected $dataTransfersDataType = 'array';
  public $etag;
  public $kind;
  public $nextPageToken;


  public function setDataTransfers($dataTransfers)
  {
    $this->dataTransfers = $dataTransfers;
  }
  public function getDataTransfers()
  {
    return $this->dataTransfers;
  }
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
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
