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
 * Service definition for Cloudbilling (v1).
 *
 * <p>
 * Retrieves Google Developers Console billing accounts and associates them with
 * projects.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://cloud.google.com/billing/" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_Cloudbilling extends Google_Service
{
  /** View and manage your data across Google Cloud Platform services. */
  const CLOUD_PLATFORM =
      "https://www.googleapis.com/auth/cloud-platform";

  public $billingAccounts;
  public $billingAccounts_projects;
  public $projects;
  

  /**
   * Constructs the internal representation of the Cloudbilling service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://cloudbilling.googleapis.com/';
    $this->servicePath = '';
    $this->version = 'v1';
    $this->serviceName = 'cloudbilling';

    $this->billingAccounts = new Google_Service_Cloudbilling_BillingAccounts_Resource(
        $this,
        $this->serviceName,
        'billingAccounts',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'v1/{+name}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'name' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'v1/billingAccounts',
              'httpMethod' => 'GET',
              'parameters' => array(
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'pageSize' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
              ),
            ),
          )
        )
    );
    $this->billingAccounts_projects = new Google_Service_Cloudbilling_BillingAccountsProjects_Resource(
        $this,
        $this->serviceName,
        'projects',
        array(
          'methods' => array(
            'list' => array(
              'path' => 'v1/{+name}/projects',
              'httpMethod' => 'GET',
              'parameters' => array(
                'name' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'pageSize' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
              ),
            ),
          )
        )
    );
    $this->projects = new Google_Service_Cloudbilling_Projects_Resource(
        $this,
        $this->serviceName,
        'projects',
        array(
          'methods' => array(
            'getBillingInfo' => array(
              'path' => 'v1/{+name}/billingInfo',
              'httpMethod' => 'GET',
              'parameters' => array(
                'name' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'updateBillingInfo' => array(
              'path' => 'v1/{+name}/billingInfo',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'name' => array(
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
 * The "billingAccounts" collection of methods.
 * Typical usage is:
 *  <code>
 *   $cloudbillingService = new Google_Service_Cloudbilling(...);
 *   $billingAccounts = $cloudbillingService->billingAccounts;
 *  </code>
 */
class Google_Service_Cloudbilling_BillingAccounts_Resource extends Google_Service_Resource
{

  /**
   * Gets information about a billing account. The current authenticated user must
   * be an [owner of the billing
   * account](https://support.google.com/cloud/answer/4430947).
   * (billingAccounts.get)
   *
   * @param string $name The resource name of the billing account to retrieve. For
   * example, `billingAccounts/012345-567890-ABCDEF`.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Cloudbilling_BillingAccount
   */
  public function get($name, $optParams = array())
  {
    $params = array('name' => $name);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Cloudbilling_BillingAccount");
  }

  /**
   * Lists the billing accounts that the current authenticated user
   * [owns](https://support.google.com/cloud/answer/4430947).
   * (billingAccounts.listBillingAccounts)
   *
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken A token identifying a page of results to return.
   * This should be a `next_page_token` value returned from a previous
   * `ListBillingAccounts` call. If unspecified, the first page of results is
   * returned.
   * @opt_param int pageSize Requested page size. The maximum page size is 100;
   * this is also the default.
   * @return Google_Service_Cloudbilling_ListBillingAccountsResponse
   */
  public function listBillingAccounts($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Cloudbilling_ListBillingAccountsResponse");
  }
}

/**
 * The "projects" collection of methods.
 * Typical usage is:
 *  <code>
 *   $cloudbillingService = new Google_Service_Cloudbilling(...);
 *   $projects = $cloudbillingService->projects;
 *  </code>
 */
class Google_Service_Cloudbilling_BillingAccountsProjects_Resource extends Google_Service_Resource
{

  /**
   * Lists the projects associated with a billing account. The current
   * authenticated user must be an [owner of the billing
   * account](https://support.google.com/cloud/answer/4430947).
   * (projects.listBillingAccountsProjects)
   *
   * @param string $name The resource name of the billing account associated with
   * the projects that you want to list. For example,
   * `billingAccounts/012345-567890-ABCDEF`.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken A token identifying a page of results to be
   * returned. This should be a `next_page_token` value returned from a previous
   * `ListProjectBillingInfo` call. If unspecified, the first page of results is
   * returned.
   * @opt_param int pageSize Requested page size. The maximum page size is 100;
   * this is also the default.
   * @return Google_Service_Cloudbilling_ListProjectBillingInfoResponse
   */
  public function listBillingAccountsProjects($name, $optParams = array())
  {
    $params = array('name' => $name);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Cloudbilling_ListProjectBillingInfoResponse");
  }
}

/**
 * The "projects" collection of methods.
 * Typical usage is:
 *  <code>
 *   $cloudbillingService = new Google_Service_Cloudbilling(...);
 *   $projects = $cloudbillingService->projects;
 *  </code>
 */
class Google_Service_Cloudbilling_Projects_Resource extends Google_Service_Resource
{

  /**
   * Gets the billing information for a project. The current authenticated user
   * must have [permission to view the project](https://cloud.google.com/docs
   * /permissions-overview#h.bgs0oxofvnoo ). (projects.getBillingInfo)
   *
   * @param string $name The resource name of the project for which billing
   * information is retrieved. For example, `projects/tokyo-rain-123`.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Cloudbilling_ProjectBillingInfo
   */
  public function getBillingInfo($name, $optParams = array())
  {
    $params = array('name' => $name);
    $params = array_merge($params, $optParams);
    return $this->call('getBillingInfo', array($params), "Google_Service_Cloudbilling_ProjectBillingInfo");
  }

  /**
   * Sets or updates the billing account associated with a project. You specify
   * the new billing account by setting the `billing_account_name` in the
   * `ProjectBillingInfo` resource to the resource name of a billing account.
   * Associating a project with an open billing account enables billing on the
   * project and allows charges for resource usage. If the project already had a
   * billing account, this method changes the billing account used for resource
   * usage charges. *Note:* Incurred charges that have not yet been reported in
   * the transaction history of the Google Developers Console may be billed to the
   * new billing account, even if the charge occurred before the new billing
   * account was assigned to the project. The current authenticated user must have
   * ownership privileges for both the [project](https://cloud.google.com/docs
   * /permissions-overview#h.bgs0oxofvnoo ) and the [billing
   * account](https://support.google.com/cloud/answer/4430947). You can disable
   * billing on the project by setting the `billing_account_name` field to empty.
   * This action disassociates the current billing account from the project. Any
   * billable activity of your in-use services will stop, and your application
   * could stop functioning as expected. Any unbilled charges to date will be
   * billed to the previously associated account. The current authenticated user
   * must be either an owner of the project or an owner of the billing account for
   * the project. Note that associating a project with a *closed* billing account
   * will have much the same effect as disabling billing on the project: any paid
   * resources used by the project will be shut down. Thus, unless you wish to
   * disable billing, you should always call this method with the name of an
   * *open* billing account. (projects.updateBillingInfo)
   *
   * @param string $name The resource name of the project associated with the
   * billing information that you want to update. For example, `projects/tokyo-
   * rain-123`.
   * @param Google_ProjectBillingInfo $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Cloudbilling_ProjectBillingInfo
   */
  public function updateBillingInfo($name, Google_Service_Cloudbilling_ProjectBillingInfo $postBody, $optParams = array())
  {
    $params = array('name' => $name, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('updateBillingInfo', array($params), "Google_Service_Cloudbilling_ProjectBillingInfo");
  }
}




class Google_Service_Cloudbilling_BillingAccount extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $displayName;
  public $name;
  public $open;


  public function setDisplayName($displayName)
  {
    $this->displayName = $displayName;
  }
  public function getDisplayName()
  {
    return $this->displayName;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setOpen($open)
  {
    $this->open = $open;
  }
  public function getOpen()
  {
    return $this->open;
  }
}

class Google_Service_Cloudbilling_ListBillingAccountsResponse extends Google_Collection
{
  protected $collection_key = 'billingAccounts';
  protected $internal_gapi_mappings = array(
  );
  protected $billingAccountsType = 'Google_Service_Cloudbilling_BillingAccount';
  protected $billingAccountsDataType = 'array';
  public $nextPageToken;


  public function setBillingAccounts($billingAccounts)
  {
    $this->billingAccounts = $billingAccounts;
  }
  public function getBillingAccounts()
  {
    return $this->billingAccounts;
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

class Google_Service_Cloudbilling_ListProjectBillingInfoResponse extends Google_Collection
{
  protected $collection_key = 'projectBillingInfo';
  protected $internal_gapi_mappings = array(
  );
  public $nextPageToken;
  protected $projectBillingInfoType = 'Google_Service_Cloudbilling_ProjectBillingInfo';
  protected $projectBillingInfoDataType = 'array';


  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  public function setProjectBillingInfo($projectBillingInfo)
  {
    $this->projectBillingInfo = $projectBillingInfo;
  }
  public function getProjectBillingInfo()
  {
    return $this->projectBillingInfo;
  }
}

class Google_Service_Cloudbilling_ProjectBillingInfo extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $billingAccountName;
  public $billingEnabled;
  public $name;
  public $projectId;


  public function setBillingAccountName($billingAccountName)
  {
    $this->billingAccountName = $billingAccountName;
  }
  public function getBillingAccountName()
  {
    return $this->billingAccountName;
  }
  public function setBillingEnabled($billingEnabled)
  {
    $this->billingEnabled = $billingEnabled;
  }
  public function getBillingEnabled()
  {
    return $this->billingEnabled;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setProjectId($projectId)
  {
    $this->projectId = $projectId;
  }
  public function getProjectId()
  {
    return $this->projectId;
  }
}
