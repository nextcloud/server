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
 * Service definition for Computeaccounts (alpha).
 *
 * <p>
 * API for the Google Compute Accounts service.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://cloud.google.com/compute/docs/access/user-accounts/api/latest/" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_Computeaccounts extends Google_Service
{
  /** View and manage your data across Google Cloud Platform services. */
  const CLOUD_PLATFORM =
      "https://www.googleapis.com/auth/cloud-platform";
  /** New Service: https://www.googleapis.com/auth/computeaccounts. */
  const COMPUTEACCOUNTS =
      "https://www.googleapis.com/auth/computeaccounts";
  /** New Service: https://www.googleapis.com/auth/computeaccounts.readonly. */
  const COMPUTEACCOUNTS_READONLY =
      "https://www.googleapis.com/auth/computeaccounts.readonly";

  public $globalAccountsOperations;
  public $groups;
  public $linux;
  public $users;
  

  /**
   * Constructs the internal representation of the Computeaccounts service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->servicePath = 'computeaccounts/alpha/projects/';
    $this->version = 'alpha';
    $this->serviceName = 'computeaccounts';

    $this->globalAccountsOperations = new Google_Service_Computeaccounts_GlobalAccountsOperations_Resource(
        $this,
        $this->serviceName,
        'globalAccountsOperations',
        array(
          'methods' => array(
            'delete' => array(
              'path' => '{project}/global/operations/{operation}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'project' => array(
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
            ),'get' => array(
              'path' => '{project}/global/operations/{operation}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
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
              'path' => '{project}/global/operations',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
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
    $this->groups = new Google_Service_Computeaccounts_Groups_Resource(
        $this,
        $this->serviceName,
        'groups',
        array(
          'methods' => array(
            'addMember' => array(
              'path' => '{project}/global/groups/{groupName}/addMember',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'groupName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'delete' => array(
              'path' => '{project}/global/groups/{groupName}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'groupName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => '{project}/global/groups/{groupName}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'groupName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => '{project}/global/groups',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => '{project}/global/groups',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
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
            ),'removeMember' => array(
              'path' => '{project}/global/groups/{groupName}/removeMember',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'groupName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->linux = new Google_Service_Computeaccounts_Linux_Resource(
        $this,
        $this->serviceName,
        'linux',
        array(
          'methods' => array(
            'getAuthorizedKeysView' => array(
              'path' => '{project}/zones/{zone}/authorizedKeysView/{user}',
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
                'user' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'instance' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'getLinuxAccountViews' => array(
              'path' => '{project}/zones/{zone}/linuxAccountViews',
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
                'instance' => array(
                  'location' => 'query',
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
                'filter' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'user' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->users = new Google_Service_Computeaccounts_Users_Resource(
        $this,
        $this->serviceName,
        'users',
        array(
          'methods' => array(
            'addPublicKey' => array(
              'path' => '{project}/global/users/{user}/addPublicKey',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'user' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'delete' => array(
              'path' => '{project}/global/users/{user}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'user' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => '{project}/global/users/{user}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'user' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => '{project}/global/users',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => '{project}/global/users',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
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
            ),'removePublicKey' => array(
              'path' => '{project}/global/users/{user}/removePublicKey',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'user' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'fingerprint' => array(
                  'location' => 'query',
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
 * The "globalAccountsOperations" collection of methods.
 * Typical usage is:
 *  <code>
 *   $computeaccountsService = new Google_Service_Computeaccounts(...);
 *   $globalAccountsOperations = $computeaccountsService->globalAccountsOperations;
 *  </code>
 */
class Google_Service_Computeaccounts_GlobalAccountsOperations_Resource extends Google_Service_Resource
{

  /**
   * Deletes the specified operation resource. (globalAccountsOperations.delete)
   *
   * @param string $project Project ID for this request.
   * @param string $operation Name of the operation resource to delete.
   * @param array $optParams Optional parameters.
   */
  public function delete($project, $operation, $optParams = array())
  {
    $params = array('project' => $project, 'operation' => $operation);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Retrieves the specified operation resource. (globalAccountsOperations.get)
   *
   * @param string $project Project ID for this request.
   * @param string $operation Name of the operation resource to return.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Computeaccounts_Operation
   */
  public function get($project, $operation, $optParams = array())
  {
    $params = array('project' => $project, 'operation' => $operation);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Computeaccounts_Operation");
  }

  /**
   * Retrieves the list of operation resources contained within the specified
   * project. (globalAccountsOperations.listGlobalAccountsOperations)
   *
   * @param string $project Project ID for this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter Optional. Filter expression for filtering listed
   * resources.
   * @opt_param string pageToken Optional. Tag returned by a previous list request
   * truncated by maxResults. Used to continue a previous list request.
   * @opt_param string maxResults Optional. Maximum count of results to be
   * returned. Maximum value is 500 and default value is 500.
   * @return Google_Service_Computeaccounts_OperationList
   */
  public function listGlobalAccountsOperations($project, $optParams = array())
  {
    $params = array('project' => $project);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Computeaccounts_OperationList");
  }
}

/**
 * The "groups" collection of methods.
 * Typical usage is:
 *  <code>
 *   $computeaccountsService = new Google_Service_Computeaccounts(...);
 *   $groups = $computeaccountsService->groups;
 *  </code>
 */
class Google_Service_Computeaccounts_Groups_Resource extends Google_Service_Resource
{

  /**
   * Adds users to the specified group. (groups.addMember)
   *
   * @param string $project Project ID for this request.
   * @param string $groupName Name of the group for this request.
   * @param Google_GroupsAddMemberRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Computeaccounts_Operation
   */
  public function addMember($project, $groupName, Google_Service_Computeaccounts_GroupsAddMemberRequest $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'groupName' => $groupName, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('addMember', array($params), "Google_Service_Computeaccounts_Operation");
  }

  /**
   * Deletes the specified group resource. (groups.delete)
   *
   * @param string $project Project ID for this request.
   * @param string $groupName Name of the group resource to delete.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Computeaccounts_Operation
   */
  public function delete($project, $groupName, $optParams = array())
  {
    $params = array('project' => $project, 'groupName' => $groupName);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params), "Google_Service_Computeaccounts_Operation");
  }

  /**
   * Returns the specified group resource. (groups.get)
   *
   * @param string $project Project ID for this request.
   * @param string $groupName Name of the group resource to return.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Computeaccounts_Group
   */
  public function get($project, $groupName, $optParams = array())
  {
    $params = array('project' => $project, 'groupName' => $groupName);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Computeaccounts_Group");
  }

  /**
   * Creates a group resource in the specified project using the data included in
   * the request. (groups.insert)
   *
   * @param string $project Project ID for this request.
   * @param Google_Group $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Computeaccounts_Operation
   */
  public function insert($project, Google_Service_Computeaccounts_Group $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Computeaccounts_Operation");
  }

  /**
   * Retrieves the list of groups contained within the specified project.
   * (groups.listGroups)
   *
   * @param string $project Project ID for this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter Optional. Filter expression for filtering listed
   * resources.
   * @opt_param string pageToken Optional. Tag returned by a previous list request
   * truncated by maxResults. Used to continue a previous list request.
   * @opt_param string maxResults Optional. Maximum count of results to be
   * returned. Maximum value is 500 and default value is 500.
   * @return Google_Service_Computeaccounts_GroupList
   */
  public function listGroups($project, $optParams = array())
  {
    $params = array('project' => $project);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Computeaccounts_GroupList");
  }

  /**
   * Removes users from the specified group. (groups.removeMember)
   *
   * @param string $project Project ID for this request.
   * @param string $groupName Name of the group for this request.
   * @param Google_GroupsRemoveMemberRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Computeaccounts_Operation
   */
  public function removeMember($project, $groupName, Google_Service_Computeaccounts_GroupsRemoveMemberRequest $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'groupName' => $groupName, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('removeMember', array($params), "Google_Service_Computeaccounts_Operation");
  }
}

/**
 * The "linux" collection of methods.
 * Typical usage is:
 *  <code>
 *   $computeaccountsService = new Google_Service_Computeaccounts(...);
 *   $linux = $computeaccountsService->linux;
 *  </code>
 */
class Google_Service_Computeaccounts_Linux_Resource extends Google_Service_Resource
{

  /**
   * Returns the AuthorizedKeysView of the specified user.
   * (linux.getAuthorizedKeysView)
   *
   * @param string $project Project ID for this request.
   * @param string $zone Name of the zone for this request.
   * @param string $user Username of the AuthorizedKeysView to return.
   * @param string $instance The fully-qualified URL of the instance requesting
   * the view.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Computeaccounts_LinuxGetAuthorizedKeysViewResponse
   */
  public function getAuthorizedKeysView($project, $zone, $user, $instance, $optParams = array())
  {
    $params = array('project' => $project, 'zone' => $zone, 'user' => $user, 'instance' => $instance);
    $params = array_merge($params, $optParams);
    return $this->call('getAuthorizedKeysView', array($params), "Google_Service_Computeaccounts_LinuxGetAuthorizedKeysViewResponse");
  }

  /**
   * Retrieves the Linux views for an instance contained within the specified
   * project. (linux.getLinuxAccountViews)
   *
   * @param string $project Project ID for this request.
   * @param string $zone Name of the zone for this request.
   * @param string $instance The fully-qualified URL of the instance requesting
   * the views.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken Optional. Tag returned by a previous list request
   * truncated by maxResults. Used to continue a previous list request.
   * @opt_param string maxResults Optional. Maximum count of results to be
   * returned. Maximum value is 500 and default value is 500.
   * @opt_param string filter Optional. Filter expression for filtering listed
   * resources.
   * @opt_param string user If provided, the user whose login is triggering an
   * immediate refresh of the views.
   * @return Google_Service_Computeaccounts_LinuxGetLinuxAccountViewsResponse
   */
  public function getLinuxAccountViews($project, $zone, $instance, $optParams = array())
  {
    $params = array('project' => $project, 'zone' => $zone, 'instance' => $instance);
    $params = array_merge($params, $optParams);
    return $this->call('getLinuxAccountViews', array($params), "Google_Service_Computeaccounts_LinuxGetLinuxAccountViewsResponse");
  }
}

/**
 * The "users" collection of methods.
 * Typical usage is:
 *  <code>
 *   $computeaccountsService = new Google_Service_Computeaccounts(...);
 *   $users = $computeaccountsService->users;
 *  </code>
 */
class Google_Service_Computeaccounts_Users_Resource extends Google_Service_Resource
{

  /**
   * Adds a public key to the specified user using the data included in the
   * request. (users.addPublicKey)
   *
   * @param string $project Project ID for this request.
   * @param string $user Name of the user for this request.
   * @param Google_PublicKey $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Computeaccounts_Operation
   */
  public function addPublicKey($project, $user, Google_Service_Computeaccounts_PublicKey $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'user' => $user, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('addPublicKey', array($params), "Google_Service_Computeaccounts_Operation");
  }

  /**
   * Deletes the specified user resource. (users.delete)
   *
   * @param string $project Project ID for this request.
   * @param string $user Name of the user resource to delete.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Computeaccounts_Operation
   */
  public function delete($project, $user, $optParams = array())
  {
    $params = array('project' => $project, 'user' => $user);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params), "Google_Service_Computeaccounts_Operation");
  }

  /**
   * Returns the specified user resource. (users.get)
   *
   * @param string $project Project ID for this request.
   * @param string $user Name of the user resource to return.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Computeaccounts_User
   */
  public function get($project, $user, $optParams = array())
  {
    $params = array('project' => $project, 'user' => $user);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Computeaccounts_User");
  }

  /**
   * Creates a user resource in the specified project using the data included in
   * the request. (users.insert)
   *
   * @param string $project Project ID for this request.
   * @param Google_User $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Computeaccounts_Operation
   */
  public function insert($project, Google_Service_Computeaccounts_User $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Computeaccounts_Operation");
  }

  /**
   * Retrieves the list of users contained within the specified project.
   * (users.listUsers)
   *
   * @param string $project Project ID for this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter Optional. Filter expression for filtering listed
   * resources.
   * @opt_param string pageToken Optional. Tag returned by a previous list request
   * truncated by maxResults. Used to continue a previous list request.
   * @opt_param string maxResults Optional. Maximum count of results to be
   * returned. Maximum value is 500 and default value is 500.
   * @return Google_Service_Computeaccounts_UserList
   */
  public function listUsers($project, $optParams = array())
  {
    $params = array('project' => $project);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Computeaccounts_UserList");
  }

  /**
   * Removes the specified public key from the user. (users.removePublicKey)
   *
   * @param string $project Project ID for this request.
   * @param string $user Name of the user for this request.
   * @param string $fingerprint The fingerprint of the public key to delete.
   * Public keys are identified by their fingerprint, which is defined by RFC4716
   * to be the MD5 digest of the public key.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Computeaccounts_Operation
   */
  public function removePublicKey($project, $user, $fingerprint, $optParams = array())
  {
    $params = array('project' => $project, 'user' => $user, 'fingerprint' => $fingerprint);
    $params = array_merge($params, $optParams);
    return $this->call('removePublicKey', array($params), "Google_Service_Computeaccounts_Operation");
  }
}




class Google_Service_Computeaccounts_AuthorizedKeysView extends Google_Collection
{
  protected $collection_key = 'keys';
  protected $internal_gapi_mappings = array(
  );
  public $keys;


  public function setKeys($keys)
  {
    $this->keys = $keys;
  }
  public function getKeys()
  {
    return $this->keys;
  }
}

class Google_Service_Computeaccounts_Group extends Google_Collection
{
  protected $collection_key = 'members';
  protected $internal_gapi_mappings = array(
  );
  public $creationTimestamp;
  public $description;
  public $id;
  public $kind;
  public $members;
  public $name;
  public $selfLink;


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
  public function setMembers($members)
  {
    $this->members = $members;
  }
  public function getMembers()
  {
    return $this->members;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
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

class Google_Service_Computeaccounts_GroupList extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $id;
  protected $itemsType = 'Google_Service_Computeaccounts_Group';
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

class Google_Service_Computeaccounts_GroupsAddMemberRequest extends Google_Collection
{
  protected $collection_key = 'users';
  protected $internal_gapi_mappings = array(
  );
  public $users;


  public function setUsers($users)
  {
    $this->users = $users;
  }
  public function getUsers()
  {
    return $this->users;
  }
}

class Google_Service_Computeaccounts_GroupsRemoveMemberRequest extends Google_Collection
{
  protected $collection_key = 'users';
  protected $internal_gapi_mappings = array(
  );
  public $users;


  public function setUsers($users)
  {
    $this->users = $users;
  }
  public function getUsers()
  {
    return $this->users;
  }
}

class Google_Service_Computeaccounts_LinuxAccountViews extends Google_Collection
{
  protected $collection_key = 'userViews';
  protected $internal_gapi_mappings = array(
  );
  protected $groupViewsType = 'Google_Service_Computeaccounts_LinuxGroupView';
  protected $groupViewsDataType = 'array';
  public $kind;
  protected $userViewsType = 'Google_Service_Computeaccounts_LinuxUserView';
  protected $userViewsDataType = 'array';


  public function setGroupViews($groupViews)
  {
    $this->groupViews = $groupViews;
  }
  public function getGroupViews()
  {
    return $this->groupViews;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setUserViews($userViews)
  {
    $this->userViews = $userViews;
  }
  public function getUserViews()
  {
    return $this->userViews;
  }
}

class Google_Service_Computeaccounts_LinuxGetAuthorizedKeysViewResponse extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $resourceType = 'Google_Service_Computeaccounts_AuthorizedKeysView';
  protected $resourceDataType = '';


  public function setResource(Google_Service_Computeaccounts_AuthorizedKeysView $resource)
  {
    $this->resource = $resource;
  }
  public function getResource()
  {
    return $this->resource;
  }
}

class Google_Service_Computeaccounts_LinuxGetLinuxAccountViewsResponse extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $resourceType = 'Google_Service_Computeaccounts_LinuxAccountViews';
  protected $resourceDataType = '';


  public function setResource(Google_Service_Computeaccounts_LinuxAccountViews $resource)
  {
    $this->resource = $resource;
  }
  public function getResource()
  {
    return $this->resource;
  }
}

class Google_Service_Computeaccounts_LinuxGroupView extends Google_Collection
{
  protected $collection_key = 'members';
  protected $internal_gapi_mappings = array(
  );
  public $gid;
  public $groupName;
  public $members;


  public function setGid($gid)
  {
    $this->gid = $gid;
  }
  public function getGid()
  {
    return $this->gid;
  }
  public function setGroupName($groupName)
  {
    $this->groupName = $groupName;
  }
  public function getGroupName()
  {
    return $this->groupName;
  }
  public function setMembers($members)
  {
    $this->members = $members;
  }
  public function getMembers()
  {
    return $this->members;
  }
}

class Google_Service_Computeaccounts_LinuxUserView extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $gecos;
  public $gid;
  public $homeDirectory;
  public $shell;
  public $uid;
  public $username;


  public function setGecos($gecos)
  {
    $this->gecos = $gecos;
  }
  public function getGecos()
  {
    return $this->gecos;
  }
  public function setGid($gid)
  {
    $this->gid = $gid;
  }
  public function getGid()
  {
    return $this->gid;
  }
  public function setHomeDirectory($homeDirectory)
  {
    $this->homeDirectory = $homeDirectory;
  }
  public function getHomeDirectory()
  {
    return $this->homeDirectory;
  }
  public function setShell($shell)
  {
    $this->shell = $shell;
  }
  public function getShell()
  {
    return $this->shell;
  }
  public function setUid($uid)
  {
    $this->uid = $uid;
  }
  public function getUid()
  {
    return $this->uid;
  }
  public function setUsername($username)
  {
    $this->username = $username;
  }
  public function getUsername()
  {
    return $this->username;
  }
}

class Google_Service_Computeaccounts_Operation extends Google_Collection
{
  protected $collection_key = 'warnings';
  protected $internal_gapi_mappings = array(
  );
  public $clientOperationId;
  public $creationTimestamp;
  public $endTime;
  protected $errorType = 'Google_Service_Computeaccounts_OperationError';
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
  protected $warningsType = 'Google_Service_Computeaccounts_OperationWarnings';
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
  public function setError(Google_Service_Computeaccounts_OperationError $error)
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

class Google_Service_Computeaccounts_OperationError extends Google_Collection
{
  protected $collection_key = 'errors';
  protected $internal_gapi_mappings = array(
  );
  protected $errorsType = 'Google_Service_Computeaccounts_OperationErrorErrors';
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

class Google_Service_Computeaccounts_OperationErrorErrors extends Google_Model
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

class Google_Service_Computeaccounts_OperationList extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $id;
  protected $itemsType = 'Google_Service_Computeaccounts_Operation';
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

class Google_Service_Computeaccounts_OperationWarnings extends Google_Collection
{
  protected $collection_key = 'data';
  protected $internal_gapi_mappings = array(
  );
  public $code;
  protected $dataType = 'Google_Service_Computeaccounts_OperationWarningsData';
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

class Google_Service_Computeaccounts_OperationWarningsData extends Google_Model
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

class Google_Service_Computeaccounts_PublicKey extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $creationTimestamp;
  public $description;
  public $expirationTimestamp;
  public $fingerprint;
  public $key;


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
  public function setExpirationTimestamp($expirationTimestamp)
  {
    $this->expirationTimestamp = $expirationTimestamp;
  }
  public function getExpirationTimestamp()
  {
    return $this->expirationTimestamp;
  }
  public function setFingerprint($fingerprint)
  {
    $this->fingerprint = $fingerprint;
  }
  public function getFingerprint()
  {
    return $this->fingerprint;
  }
  public function setKey($key)
  {
    $this->key = $key;
  }
  public function getKey()
  {
    return $this->key;
  }
}

class Google_Service_Computeaccounts_User extends Google_Collection
{
  protected $collection_key = 'publicKeys';
  protected $internal_gapi_mappings = array(
  );
  public $creationTimestamp;
  public $description;
  public $groups;
  public $id;
  public $kind;
  public $name;
  public $owner;
  protected $publicKeysType = 'Google_Service_Computeaccounts_PublicKey';
  protected $publicKeysDataType = 'array';
  public $selfLink;


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
  public function setGroups($groups)
  {
    $this->groups = $groups;
  }
  public function getGroups()
  {
    return $this->groups;
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
  public function setOwner($owner)
  {
    $this->owner = $owner;
  }
  public function getOwner()
  {
    return $this->owner;
  }
  public function setPublicKeys($publicKeys)
  {
    $this->publicKeys = $publicKeys;
  }
  public function getPublicKeys()
  {
    return $this->publicKeys;
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

class Google_Service_Computeaccounts_UserList extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $id;
  protected $itemsType = 'Google_Service_Computeaccounts_User';
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
