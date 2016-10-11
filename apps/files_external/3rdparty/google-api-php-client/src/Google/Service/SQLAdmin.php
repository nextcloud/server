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
 * Service definition for SQLAdmin (v1beta4).
 *
 * <p>
 * API for Cloud SQL database instance management.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://cloud.google.com/sql/docs/reference/latest" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_SQLAdmin extends Google_Service
{
  /** View and manage your data across Google Cloud Platform services. */
  const CLOUD_PLATFORM =
      "https://www.googleapis.com/auth/cloud-platform";
  /** Manage your Google SQL Service instances. */
  const SQLSERVICE_ADMIN =
      "https://www.googleapis.com/auth/sqlservice.admin";

  public $backupRuns;
  public $databases;
  public $flags;
  public $instances;
  public $operations;
  public $sslCerts;
  public $tiers;
  public $users;
  

  /**
   * Constructs the internal representation of the SQLAdmin service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://www.googleapis.com/';
    $this->servicePath = 'sql/v1beta4/';
    $this->version = 'v1beta4';
    $this->serviceName = 'sqladmin';

    $this->backupRuns = new Google_Service_SQLAdmin_BackupRuns_Resource(
        $this,
        $this->serviceName,
        'backupRuns',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'projects/{project}/instances/{instance}/backupRuns/{id}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'instance' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'projects/{project}/instances/{instance}/backupRuns/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'instance' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'projects/{project}/instances/{instance}/backupRuns',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'instance' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
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
    $this->databases = new Google_Service_SQLAdmin_Databases_Resource(
        $this,
        $this->serviceName,
        'databases',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'projects/{project}/instances/{instance}/databases/{database}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'instance' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'database' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'projects/{project}/instances/{instance}/databases/{database}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'instance' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'database' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'projects/{project}/instances/{instance}/databases',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'instance' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'projects/{project}/instances/{instance}/databases',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'instance' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'patch' => array(
              'path' => 'projects/{project}/instances/{instance}/databases/{database}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'instance' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'database' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'projects/{project}/instances/{instance}/databases/{database}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'instance' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'database' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->flags = new Google_Service_SQLAdmin_Flags_Resource(
        $this,
        $this->serviceName,
        'flags',
        array(
          'methods' => array(
            'list' => array(
              'path' => 'flags',
              'httpMethod' => 'GET',
              'parameters' => array(),
            ),
          )
        )
    );
    $this->instances = new Google_Service_SQLAdmin_Instances_Resource(
        $this,
        $this->serviceName,
        'instances',
        array(
          'methods' => array(
            'clone' => array(
              'path' => 'projects/{project}/instances/{instance}/clone',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'instance' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'delete' => array(
              'path' => 'projects/{project}/instances/{instance}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'instance' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'export' => array(
              'path' => 'projects/{project}/instances/{instance}/export',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'instance' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'failover' => array(
              'path' => 'projects/{project}/instances/{instance}/failover',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'instance' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'projects/{project}/instances/{instance}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'instance' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'import' => array(
              'path' => 'projects/{project}/instances/{instance}/import',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'instance' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'projects/{project}/instances',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'projects/{project}/instances',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
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
              'path' => 'projects/{project}/instances/{instance}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'instance' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'promoteReplica' => array(
              'path' => 'projects/{project}/instances/{instance}/promoteReplica',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'instance' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'resetSslConfig' => array(
              'path' => 'projects/{project}/instances/{instance}/resetSslConfig',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'instance' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'restart' => array(
              'path' => 'projects/{project}/instances/{instance}/restart',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'instance' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'restoreBackup' => array(
              'path' => 'projects/{project}/instances/{instance}/restoreBackup',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'instance' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'startReplica' => array(
              'path' => 'projects/{project}/instances/{instance}/startReplica',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'instance' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'stopReplica' => array(
              'path' => 'projects/{project}/instances/{instance}/stopReplica',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'instance' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'projects/{project}/instances/{instance}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'instance' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->operations = new Google_Service_SQLAdmin_Operations_Resource(
        $this,
        $this->serviceName,
        'operations',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'projects/{project}/operations/{operation}',
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
              'path' => 'projects/{project}/operations',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'instance' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
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
    $this->sslCerts = new Google_Service_SQLAdmin_SslCerts_Resource(
        $this,
        $this->serviceName,
        'sslCerts',
        array(
          'methods' => array(
            'createEphemeral' => array(
              'path' => 'projects/{project}/instances/{instance}/createEphemeral',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'instance' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'delete' => array(
              'path' => 'projects/{project}/instances/{instance}/sslCerts/{sha1Fingerprint}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'instance' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'sha1Fingerprint' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'projects/{project}/instances/{instance}/sslCerts/{sha1Fingerprint}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'instance' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'sha1Fingerprint' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'projects/{project}/instances/{instance}/sslCerts',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'instance' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'projects/{project}/instances/{instance}/sslCerts',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'instance' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->tiers = new Google_Service_SQLAdmin_Tiers_Resource(
        $this,
        $this->serviceName,
        'tiers',
        array(
          'methods' => array(
            'list' => array(
              'path' => 'projects/{project}/tiers',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->users = new Google_Service_SQLAdmin_Users_Resource(
        $this,
        $this->serviceName,
        'users',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'projects/{project}/instances/{instance}/users',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'instance' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'host' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'name' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'projects/{project}/instances/{instance}/users',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'instance' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'projects/{project}/instances/{instance}/users',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'instance' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'projects/{project}/instances/{instance}/users',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'instance' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'host' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'name' => array(
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
 * The "backupRuns" collection of methods.
 * Typical usage is:
 *  <code>
 *   $sqladminService = new Google_Service_SQLAdmin(...);
 *   $backupRuns = $sqladminService->backupRuns;
 *  </code>
 */
class Google_Service_SQLAdmin_BackupRuns_Resource extends Google_Service_Resource
{

  /**
   * Deletes the backup taken by a backup run. (backupRuns.delete)
   *
   * @param string $project Project ID of the project that contains the instance.
   * @param string $instance Cloud SQL instance ID. This does not include the
   * project ID.
   * @param string $id The ID of the Backup Run to delete. To find a Backup Run
   * ID, use the list method.
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_Operation
   */
  public function delete($project, $instance, $id, $optParams = array())
  {
    $params = array('project' => $project, 'instance' => $instance, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params), "Google_Service_SQLAdmin_Operation");
  }

  /**
   * Retrieves a resource containing information about a backup run.
   * (backupRuns.get)
   *
   * @param string $project Project ID of the project that contains the instance.
   * @param string $instance Cloud SQL instance ID. This does not include the
   * project ID.
   * @param string $id The ID of this Backup Run.
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_BackupRun
   */
  public function get($project, $instance, $id, $optParams = array())
  {
    $params = array('project' => $project, 'instance' => $instance, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_SQLAdmin_BackupRun");
  }

  /**
   * Lists all backup runs associated with a given instance and configuration in
   * the reverse chronological order of the enqueued time.
   * (backupRuns.listBackupRuns)
   *
   * @param string $project Project ID of the project that contains the instance.
   * @param string $instance Cloud SQL instance ID. This does not include the
   * project ID.
   * @param array $optParams Optional parameters.
   *
   * @opt_param int maxResults Maximum number of backup runs per response.
   * @opt_param string pageToken A previously-returned page token representing
   * part of the larger set of results to view.
   * @return Google_Service_SQLAdmin_BackupRunsListResponse
   */
  public function listBackupRuns($project, $instance, $optParams = array())
  {
    $params = array('project' => $project, 'instance' => $instance);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_SQLAdmin_BackupRunsListResponse");
  }
}

/**
 * The "databases" collection of methods.
 * Typical usage is:
 *  <code>
 *   $sqladminService = new Google_Service_SQLAdmin(...);
 *   $databases = $sqladminService->databases;
 *  </code>
 */
class Google_Service_SQLAdmin_Databases_Resource extends Google_Service_Resource
{

  /**
   * Deletes a resource containing information about a database inside a Cloud SQL
   * instance. (databases.delete)
   *
   * @param string $project Project ID of the project that contains the instance.
   * @param string $instance Database instance ID. This does not include the
   * project ID.
   * @param string $database Name of the database to be deleted in the instance.
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_Operation
   */
  public function delete($project, $instance, $database, $optParams = array())
  {
    $params = array('project' => $project, 'instance' => $instance, 'database' => $database);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params), "Google_Service_SQLAdmin_Operation");
  }

  /**
   * Retrieves a resource containing information about a database inside a Cloud
   * SQL instance. (databases.get)
   *
   * @param string $project Project ID of the project that contains the instance.
   * @param string $instance Database instance ID. This does not include the
   * project ID.
   * @param string $database Name of the database in the instance.
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_Database
   */
  public function get($project, $instance, $database, $optParams = array())
  {
    $params = array('project' => $project, 'instance' => $instance, 'database' => $database);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_SQLAdmin_Database");
  }

  /**
   * Inserts a resource containing information about a database inside a Cloud SQL
   * instance. (databases.insert)
   *
   * @param string $project Project ID of the project that contains the instance.
   * @param string $instance Database instance ID. This does not include the
   * project ID.
   * @param Google_Database $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_Operation
   */
  public function insert($project, $instance, Google_Service_SQLAdmin_Database $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'instance' => $instance, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_SQLAdmin_Operation");
  }

  /**
   * Lists databases in the specified Cloud SQL instance.
   * (databases.listDatabases)
   *
   * @param string $project Project ID of the project for which to list Cloud SQL
   * instances.
   * @param string $instance Cloud SQL instance ID. This does not include the
   * project ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_DatabasesListResponse
   */
  public function listDatabases($project, $instance, $optParams = array())
  {
    $params = array('project' => $project, 'instance' => $instance);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_SQLAdmin_DatabasesListResponse");
  }

  /**
   * Updates a resource containing information about a database inside a Cloud SQL
   * instance. This method supports patch semantics. (databases.patch)
   *
   * @param string $project Project ID of the project that contains the instance.
   * @param string $instance Database instance ID. This does not include the
   * project ID.
   * @param string $database Name of the database to be updated in the instance.
   * @param Google_Database $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_Operation
   */
  public function patch($project, $instance, $database, Google_Service_SQLAdmin_Database $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'instance' => $instance, 'database' => $database, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_SQLAdmin_Operation");
  }

  /**
   * Updates a resource containing information about a database inside a Cloud SQL
   * instance. (databases.update)
   *
   * @param string $project Project ID of the project that contains the instance.
   * @param string $instance Database instance ID. This does not include the
   * project ID.
   * @param string $database Name of the database to be updated in the instance.
   * @param Google_Database $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_Operation
   */
  public function update($project, $instance, $database, Google_Service_SQLAdmin_Database $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'instance' => $instance, 'database' => $database, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_SQLAdmin_Operation");
  }
}

/**
 * The "flags" collection of methods.
 * Typical usage is:
 *  <code>
 *   $sqladminService = new Google_Service_SQLAdmin(...);
 *   $flags = $sqladminService->flags;
 *  </code>
 */
class Google_Service_SQLAdmin_Flags_Resource extends Google_Service_Resource
{

  /**
   * List all available database flags for Google Cloud SQL instances.
   * (flags.listFlags)
   *
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_FlagsListResponse
   */
  public function listFlags($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_SQLAdmin_FlagsListResponse");
  }
}

/**
 * The "instances" collection of methods.
 * Typical usage is:
 *  <code>
 *   $sqladminService = new Google_Service_SQLAdmin(...);
 *   $instances = $sqladminService->instances;
 *  </code>
 */
class Google_Service_SQLAdmin_Instances_Resource extends Google_Service_Resource
{

  /**
   * Creates a Cloud SQL instance as a clone of the source instance.
   * (instances.cloneInstances)
   *
   * @param string $project Project ID of the source as well as the clone Cloud
   * SQL instance.
   * @param string $instance The ID of the Cloud SQL instance to be cloned
   * (source). This does not include the project ID.
   * @param Google_InstancesCloneRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_Operation
   */
  public function cloneInstances($project, $instance, Google_Service_SQLAdmin_InstancesCloneRequest $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'instance' => $instance, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('clone', array($params), "Google_Service_SQLAdmin_Operation");
  }

  /**
   * Deletes a Cloud SQL instance. (instances.delete)
   *
   * @param string $project Project ID of the project that contains the instance
   * to be deleted.
   * @param string $instance Cloud SQL instance ID. This does not include the
   * project ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_Operation
   */
  public function delete($project, $instance, $optParams = array())
  {
    $params = array('project' => $project, 'instance' => $instance);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params), "Google_Service_SQLAdmin_Operation");
  }

  /**
   * Exports data from a Cloud SQL instance to a Google Cloud Storage bucket as a
   * MySQL dump file. (instances.export)
   *
   * @param string $project Project ID of the project that contains the instance
   * to be exported.
   * @param string $instance Cloud SQL instance ID. This does not include the
   * project ID.
   * @param Google_InstancesExportRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_Operation
   */
  public function export($project, $instance, Google_Service_SQLAdmin_InstancesExportRequest $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'instance' => $instance, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('export', array($params), "Google_Service_SQLAdmin_Operation");
  }

  /**
   * Failover the instance to its failover replica instance. (instances.failover)
   *
   * @param string $project ID of the project that contains the read replica.
   * @param string $instance Cloud SQL instance ID. This does not include the
   * project ID.
   * @param Google_InstancesFailoverRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_Operation
   */
  public function failover($project, $instance, Google_Service_SQLAdmin_InstancesFailoverRequest $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'instance' => $instance, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('failover', array($params), "Google_Service_SQLAdmin_Operation");
  }

  /**
   * Retrieves a resource containing information about a Cloud SQL instance.
   * (instances.get)
   *
   * @param string $project Project ID of the project that contains the instance.
   * @param string $instance Database instance ID. This does not include the
   * project ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_DatabaseInstance
   */
  public function get($project, $instance, $optParams = array())
  {
    $params = array('project' => $project, 'instance' => $instance);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_SQLAdmin_DatabaseInstance");
  }

  /**
   * Imports data into a Cloud SQL instance from a MySQL dump file in Google Cloud
   * Storage. (instances.import)
   *
   * @param string $project Project ID of the project that contains the instance.
   * @param string $instance Cloud SQL instance ID. This does not include the
   * project ID.
   * @param Google_InstancesImportRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_Operation
   */
  public function import($project, $instance, Google_Service_SQLAdmin_InstancesImportRequest $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'instance' => $instance, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('import', array($params), "Google_Service_SQLAdmin_Operation");
  }

  /**
   * Creates a new Cloud SQL instance. (instances.insert)
   *
   * @param string $project Project ID of the project to which the newly created
   * Cloud SQL instances should belong.
   * @param Google_DatabaseInstance $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_Operation
   */
  public function insert($project, Google_Service_SQLAdmin_DatabaseInstance $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_SQLAdmin_Operation");
  }

  /**
   * Lists instances under a given project in the alphabetical order of the
   * instance name. (instances.listInstances)
   *
   * @param string $project Project ID of the project for which to list Cloud SQL
   * instances.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken A previously-returned page token representing
   * part of the larger set of results to view.
   * @opt_param string maxResults The maximum number of results to return per
   * response.
   * @return Google_Service_SQLAdmin_InstancesListResponse
   */
  public function listInstances($project, $optParams = array())
  {
    $params = array('project' => $project);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_SQLAdmin_InstancesListResponse");
  }

  /**
   * Updates settings of a Cloud SQL instance. Caution: This is not a partial
   * update, so you must include values for all the settings that you want to
   * retain. For partial updates, use patch.. This method supports patch
   * semantics. (instances.patch)
   *
   * @param string $project Project ID of the project that contains the instance.
   * @param string $instance Cloud SQL instance ID. This does not include the
   * project ID.
   * @param Google_DatabaseInstance $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_Operation
   */
  public function patch($project, $instance, Google_Service_SQLAdmin_DatabaseInstance $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'instance' => $instance, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_SQLAdmin_Operation");
  }

  /**
   * Promotes the read replica instance to be a stand-alone Cloud SQL instance.
   * (instances.promoteReplica)
   *
   * @param string $project ID of the project that contains the read replica.
   * @param string $instance Cloud SQL read replica instance name.
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_Operation
   */
  public function promoteReplica($project, $instance, $optParams = array())
  {
    $params = array('project' => $project, 'instance' => $instance);
    $params = array_merge($params, $optParams);
    return $this->call('promoteReplica', array($params), "Google_Service_SQLAdmin_Operation");
  }

  /**
   * Deletes all client certificates and generates a new server SSL certificate
   * for the instance. The changes will not take effect until the instance is
   * restarted. Existing instances without a server certificate will need to call
   * this once to set a server certificate. (instances.resetSslConfig)
   *
   * @param string $project Project ID of the project that contains the instance.
   * @param string $instance Cloud SQL instance ID. This does not include the
   * project ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_Operation
   */
  public function resetSslConfig($project, $instance, $optParams = array())
  {
    $params = array('project' => $project, 'instance' => $instance);
    $params = array_merge($params, $optParams);
    return $this->call('resetSslConfig', array($params), "Google_Service_SQLAdmin_Operation");
  }

  /**
   * Restarts a Cloud SQL instance. (instances.restart)
   *
   * @param string $project Project ID of the project that contains the instance
   * to be restarted.
   * @param string $instance Cloud SQL instance ID. This does not include the
   * project ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_Operation
   */
  public function restart($project, $instance, $optParams = array())
  {
    $params = array('project' => $project, 'instance' => $instance);
    $params = array_merge($params, $optParams);
    return $this->call('restart', array($params), "Google_Service_SQLAdmin_Operation");
  }

  /**
   * Restores a backup of a Cloud SQL instance. (instances.restoreBackup)
   *
   * @param string $project Project ID of the project that contains the instance.
   * @param string $instance Cloud SQL instance ID. This does not include the
   * project ID.
   * @param Google_InstancesRestoreBackupRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_Operation
   */
  public function restoreBackup($project, $instance, Google_Service_SQLAdmin_InstancesRestoreBackupRequest $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'instance' => $instance, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('restoreBackup', array($params), "Google_Service_SQLAdmin_Operation");
  }

  /**
   * Starts the replication in the read replica instance. (instances.startReplica)
   *
   * @param string $project ID of the project that contains the read replica.
   * @param string $instance Cloud SQL read replica instance name.
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_Operation
   */
  public function startReplica($project, $instance, $optParams = array())
  {
    $params = array('project' => $project, 'instance' => $instance);
    $params = array_merge($params, $optParams);
    return $this->call('startReplica', array($params), "Google_Service_SQLAdmin_Operation");
  }

  /**
   * Stops the replication in the read replica instance. (instances.stopReplica)
   *
   * @param string $project ID of the project that contains the read replica.
   * @param string $instance Cloud SQL read replica instance name.
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_Operation
   */
  public function stopReplica($project, $instance, $optParams = array())
  {
    $params = array('project' => $project, 'instance' => $instance);
    $params = array_merge($params, $optParams);
    return $this->call('stopReplica', array($params), "Google_Service_SQLAdmin_Operation");
  }

  /**
   * Updates settings of a Cloud SQL instance. Caution: This is not a partial
   * update, so you must include values for all the settings that you want to
   * retain. For partial updates, use patch. (instances.update)
   *
   * @param string $project Project ID of the project that contains the instance.
   * @param string $instance Cloud SQL instance ID. This does not include the
   * project ID.
   * @param Google_DatabaseInstance $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_Operation
   */
  public function update($project, $instance, Google_Service_SQLAdmin_DatabaseInstance $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'instance' => $instance, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_SQLAdmin_Operation");
  }
}

/**
 * The "operations" collection of methods.
 * Typical usage is:
 *  <code>
 *   $sqladminService = new Google_Service_SQLAdmin(...);
 *   $operations = $sqladminService->operations;
 *  </code>
 */
class Google_Service_SQLAdmin_Operations_Resource extends Google_Service_Resource
{

  /**
   * Retrieves an instance operation that has been performed on an instance.
   * (operations.get)
   *
   * @param string $project Project ID of the project that contains the instance.
   * @param string $operation Instance operation ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_Operation
   */
  public function get($project, $operation, $optParams = array())
  {
    $params = array('project' => $project, 'operation' => $operation);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_SQLAdmin_Operation");
  }

  /**
   * Lists all instance operations that have been performed on the given Cloud SQL
   * instance in the reverse chronological order of the start time.
   * (operations.listOperations)
   *
   * @param string $project Project ID of the project that contains the instance.
   * @param string $instance Cloud SQL instance ID. This does not include the
   * project ID.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string maxResults Maximum number of operations per response.
   * @opt_param string pageToken A previously-returned page token representing
   * part of the larger set of results to view.
   * @return Google_Service_SQLAdmin_OperationsListResponse
   */
  public function listOperations($project, $instance, $optParams = array())
  {
    $params = array('project' => $project, 'instance' => $instance);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_SQLAdmin_OperationsListResponse");
  }
}

/**
 * The "sslCerts" collection of methods.
 * Typical usage is:
 *  <code>
 *   $sqladminService = new Google_Service_SQLAdmin(...);
 *   $sslCerts = $sqladminService->sslCerts;
 *  </code>
 */
class Google_Service_SQLAdmin_SslCerts_Resource extends Google_Service_Resource
{

  /**
   * Generates a short-lived X509 certificate containing the provided public key
   * and signed by a private key specific to the target instance. Users may use
   * the certificate to authenticate as themselves when connecting to the
   * database. (sslCerts.createEphemeral)
   *
   * @param string $project Project ID of the Cloud SQL project.
   * @param string $instance Cloud SQL instance ID. This does not include the
   * project ID.
   * @param Google_SslCertsCreateEphemeralRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_SslCert
   */
  public function createEphemeral($project, $instance, Google_Service_SQLAdmin_SslCertsCreateEphemeralRequest $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'instance' => $instance, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('createEphemeral', array($params), "Google_Service_SQLAdmin_SslCert");
  }

  /**
   * Deletes the SSL certificate. The change will not take effect until the
   * instance is restarted. (sslCerts.delete)
   *
   * @param string $project Project ID of the project that contains the instance
   * to be deleted.
   * @param string $instance Cloud SQL instance ID. This does not include the
   * project ID.
   * @param string $sha1Fingerprint Sha1 FingerPrint.
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_Operation
   */
  public function delete($project, $instance, $sha1Fingerprint, $optParams = array())
  {
    $params = array('project' => $project, 'instance' => $instance, 'sha1Fingerprint' => $sha1Fingerprint);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params), "Google_Service_SQLAdmin_Operation");
  }

  /**
   * Retrieves a particular SSL certificate. Does not include the private key
   * (required for usage). The private key must be saved from the response to
   * initial creation. (sslCerts.get)
   *
   * @param string $project Project ID of the project that contains the instance.
   * @param string $instance Cloud SQL instance ID. This does not include the
   * project ID.
   * @param string $sha1Fingerprint Sha1 FingerPrint.
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_SslCert
   */
  public function get($project, $instance, $sha1Fingerprint, $optParams = array())
  {
    $params = array('project' => $project, 'instance' => $instance, 'sha1Fingerprint' => $sha1Fingerprint);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_SQLAdmin_SslCert");
  }

  /**
   * Creates an SSL certificate and returns it along with the private key and
   * server certificate authority. The new certificate will not be usable until
   * the instance is restarted. (sslCerts.insert)
   *
   * @param string $project Project ID of the project to which the newly created
   * Cloud SQL instances should belong.
   * @param string $instance Cloud SQL instance ID. This does not include the
   * project ID.
   * @param Google_SslCertsInsertRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_SslCertsInsertResponse
   */
  public function insert($project, $instance, Google_Service_SQLAdmin_SslCertsInsertRequest $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'instance' => $instance, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_SQLAdmin_SslCertsInsertResponse");
  }

  /**
   * Lists all of the current SSL certificates for the instance.
   * (sslCerts.listSslCerts)
   *
   * @param string $project Project ID of the project for which to list Cloud SQL
   * instances.
   * @param string $instance Cloud SQL instance ID. This does not include the
   * project ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_SslCertsListResponse
   */
  public function listSslCerts($project, $instance, $optParams = array())
  {
    $params = array('project' => $project, 'instance' => $instance);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_SQLAdmin_SslCertsListResponse");
  }
}

/**
 * The "tiers" collection of methods.
 * Typical usage is:
 *  <code>
 *   $sqladminService = new Google_Service_SQLAdmin(...);
 *   $tiers = $sqladminService->tiers;
 *  </code>
 */
class Google_Service_SQLAdmin_Tiers_Resource extends Google_Service_Resource
{

  /**
   * Lists all available service tiers for Google Cloud SQL, for example D1, D2.
   * For related information, see Pricing. (tiers.listTiers)
   *
   * @param string $project Project ID of the project for which to list tiers.
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_TiersListResponse
   */
  public function listTiers($project, $optParams = array())
  {
    $params = array('project' => $project);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_SQLAdmin_TiersListResponse");
  }
}

/**
 * The "users" collection of methods.
 * Typical usage is:
 *  <code>
 *   $sqladminService = new Google_Service_SQLAdmin(...);
 *   $users = $sqladminService->users;
 *  </code>
 */
class Google_Service_SQLAdmin_Users_Resource extends Google_Service_Resource
{

  /**
   * Deletes a user from a Cloud SQL instance. (users.delete)
   *
   * @param string $project Project ID of the project that contains the instance.
   * @param string $instance Database instance ID. This does not include the
   * project ID.
   * @param string $host Host of the user in the instance.
   * @param string $name Name of the user in the instance.
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_Operation
   */
  public function delete($project, $instance, $host, $name, $optParams = array())
  {
    $params = array('project' => $project, 'instance' => $instance, 'host' => $host, 'name' => $name);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params), "Google_Service_SQLAdmin_Operation");
  }

  /**
   * Creates a new user in a Cloud SQL instance. (users.insert)
   *
   * @param string $project Project ID of the project that contains the instance.
   * @param string $instance Database instance ID. This does not include the
   * project ID.
   * @param Google_User $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_Operation
   */
  public function insert($project, $instance, Google_Service_SQLAdmin_User $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'instance' => $instance, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_SQLAdmin_Operation");
  }

  /**
   * Lists users in the specified Cloud SQL instance. (users.listUsers)
   *
   * @param string $project Project ID of the project that contains the instance.
   * @param string $instance Database instance ID. This does not include the
   * project ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_UsersListResponse
   */
  public function listUsers($project, $instance, $optParams = array())
  {
    $params = array('project' => $project, 'instance' => $instance);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_SQLAdmin_UsersListResponse");
  }

  /**
   * Updates an existing user in a Cloud SQL instance. (users.update)
   *
   * @param string $project Project ID of the project that contains the instance.
   * @param string $instance Database instance ID. This does not include the
   * project ID.
   * @param string $host Host of the user in the instance.
   * @param string $name Name of the user in the instance.
   * @param Google_User $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_SQLAdmin_Operation
   */
  public function update($project, $instance, $host, $name, Google_Service_SQLAdmin_User $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'instance' => $instance, 'host' => $host, 'name' => $name, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_SQLAdmin_Operation");
  }
}




class Google_Service_SQLAdmin_AclEntry extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $expirationTime;
  public $kind;
  public $name;
  public $value;


  public function setExpirationTime($expirationTime)
  {
    $this->expirationTime = $expirationTime;
  }
  public function getExpirationTime()
  {
    return $this->expirationTime;
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
  public function setValue($value)
  {
    $this->value = $value;
  }
  public function getValue()
  {
    return $this->value;
  }
}

class Google_Service_SQLAdmin_BackupConfiguration extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $binaryLogEnabled;
  public $enabled;
  public $kind;
  public $startTime;


  public function setBinaryLogEnabled($binaryLogEnabled)
  {
    $this->binaryLogEnabled = $binaryLogEnabled;
  }
  public function getBinaryLogEnabled()
  {
    return $this->binaryLogEnabled;
  }
  public function setEnabled($enabled)
  {
    $this->enabled = $enabled;
  }
  public function getEnabled()
  {
    return $this->enabled;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setStartTime($startTime)
  {
    $this->startTime = $startTime;
  }
  public function getStartTime()
  {
    return $this->startTime;
  }
}

class Google_Service_SQLAdmin_BackupRun extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $endTime;
  public $enqueuedTime;
  protected $errorType = 'Google_Service_SQLAdmin_OperationError';
  protected $errorDataType = '';
  public $id;
  public $instance;
  public $kind;
  public $selfLink;
  public $startTime;
  public $status;
  public $windowStartTime;


  public function setEndTime($endTime)
  {
    $this->endTime = $endTime;
  }
  public function getEndTime()
  {
    return $this->endTime;
  }
  public function setEnqueuedTime($enqueuedTime)
  {
    $this->enqueuedTime = $enqueuedTime;
  }
  public function getEnqueuedTime()
  {
    return $this->enqueuedTime;
  }
  public function setError(Google_Service_SQLAdmin_OperationError $error)
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
  public function setInstance($instance)
  {
    $this->instance = $instance;
  }
  public function getInstance()
  {
    return $this->instance;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
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
  public function setWindowStartTime($windowStartTime)
  {
    $this->windowStartTime = $windowStartTime;
  }
  public function getWindowStartTime()
  {
    return $this->windowStartTime;
  }
}

class Google_Service_SQLAdmin_BackupRunsListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  protected $itemsType = 'Google_Service_SQLAdmin_BackupRun';
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

class Google_Service_SQLAdmin_BinLogCoordinates extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $binLogFileName;
  public $binLogPosition;
  public $kind;


  public function setBinLogFileName($binLogFileName)
  {
    $this->binLogFileName = $binLogFileName;
  }
  public function getBinLogFileName()
  {
    return $this->binLogFileName;
  }
  public function setBinLogPosition($binLogPosition)
  {
    $this->binLogPosition = $binLogPosition;
  }
  public function getBinLogPosition()
  {
    return $this->binLogPosition;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
}

class Google_Service_SQLAdmin_CloneContext extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $binLogCoordinatesType = 'Google_Service_SQLAdmin_BinLogCoordinates';
  protected $binLogCoordinatesDataType = '';
  public $destinationInstanceName;
  public $kind;


  public function setBinLogCoordinates(Google_Service_SQLAdmin_BinLogCoordinates $binLogCoordinates)
  {
    $this->binLogCoordinates = $binLogCoordinates;
  }
  public function getBinLogCoordinates()
  {
    return $this->binLogCoordinates;
  }
  public function setDestinationInstanceName($destinationInstanceName)
  {
    $this->destinationInstanceName = $destinationInstanceName;
  }
  public function getDestinationInstanceName()
  {
    return $this->destinationInstanceName;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
}

class Google_Service_SQLAdmin_Database extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $charset;
  public $collation;
  public $etag;
  public $instance;
  public $kind;
  public $name;
  public $project;
  public $selfLink;


  public function setCharset($charset)
  {
    $this->charset = $charset;
  }
  public function getCharset()
  {
    return $this->charset;
  }
  public function setCollation($collation)
  {
    $this->collation = $collation;
  }
  public function getCollation()
  {
    return $this->collation;
  }
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setInstance($instance)
  {
    $this->instance = $instance;
  }
  public function getInstance()
  {
    return $this->instance;
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
  public function setProject($project)
  {
    $this->project = $project;
  }
  public function getProject()
  {
    return $this->project;
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

class Google_Service_SQLAdmin_DatabaseFlags extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $name;
  public $value;


  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
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

class Google_Service_SQLAdmin_DatabaseInstance extends Google_Collection
{
  protected $collection_key = 'replicaNames';
  protected $internal_gapi_mappings = array(
  );
  public $currentDiskSize;
  public $databaseVersion;
  public $etag;
  public $instanceType;
  protected $ipAddressesType = 'Google_Service_SQLAdmin_IpMapping';
  protected $ipAddressesDataType = 'array';
  public $ipv6Address;
  public $kind;
  public $masterInstanceName;
  public $maxDiskSize;
  public $name;
  protected $onPremisesConfigurationType = 'Google_Service_SQLAdmin_OnPremisesConfiguration';
  protected $onPremisesConfigurationDataType = '';
  public $project;
  public $region;
  protected $replicaConfigurationType = 'Google_Service_SQLAdmin_ReplicaConfiguration';
  protected $replicaConfigurationDataType = '';
  public $replicaNames;
  public $selfLink;
  protected $serverCaCertType = 'Google_Service_SQLAdmin_SslCert';
  protected $serverCaCertDataType = '';
  public $serviceAccountEmailAddress;
  protected $settingsType = 'Google_Service_SQLAdmin_Settings';
  protected $settingsDataType = '';
  public $state;


  public function setCurrentDiskSize($currentDiskSize)
  {
    $this->currentDiskSize = $currentDiskSize;
  }
  public function getCurrentDiskSize()
  {
    return $this->currentDiskSize;
  }
  public function setDatabaseVersion($databaseVersion)
  {
    $this->databaseVersion = $databaseVersion;
  }
  public function getDatabaseVersion()
  {
    return $this->databaseVersion;
  }
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setInstanceType($instanceType)
  {
    $this->instanceType = $instanceType;
  }
  public function getInstanceType()
  {
    return $this->instanceType;
  }
  public function setIpAddresses($ipAddresses)
  {
    $this->ipAddresses = $ipAddresses;
  }
  public function getIpAddresses()
  {
    return $this->ipAddresses;
  }
  public function setIpv6Address($ipv6Address)
  {
    $this->ipv6Address = $ipv6Address;
  }
  public function getIpv6Address()
  {
    return $this->ipv6Address;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setMasterInstanceName($masterInstanceName)
  {
    $this->masterInstanceName = $masterInstanceName;
  }
  public function getMasterInstanceName()
  {
    return $this->masterInstanceName;
  }
  public function setMaxDiskSize($maxDiskSize)
  {
    $this->maxDiskSize = $maxDiskSize;
  }
  public function getMaxDiskSize()
  {
    return $this->maxDiskSize;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setOnPremisesConfiguration(Google_Service_SQLAdmin_OnPremisesConfiguration $onPremisesConfiguration)
  {
    $this->onPremisesConfiguration = $onPremisesConfiguration;
  }
  public function getOnPremisesConfiguration()
  {
    return $this->onPremisesConfiguration;
  }
  public function setProject($project)
  {
    $this->project = $project;
  }
  public function getProject()
  {
    return $this->project;
  }
  public function setRegion($region)
  {
    $this->region = $region;
  }
  public function getRegion()
  {
    return $this->region;
  }
  public function setReplicaConfiguration(Google_Service_SQLAdmin_ReplicaConfiguration $replicaConfiguration)
  {
    $this->replicaConfiguration = $replicaConfiguration;
  }
  public function getReplicaConfiguration()
  {
    return $this->replicaConfiguration;
  }
  public function setReplicaNames($replicaNames)
  {
    $this->replicaNames = $replicaNames;
  }
  public function getReplicaNames()
  {
    return $this->replicaNames;
  }
  public function setSelfLink($selfLink)
  {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink()
  {
    return $this->selfLink;
  }
  public function setServerCaCert(Google_Service_SQLAdmin_SslCert $serverCaCert)
  {
    $this->serverCaCert = $serverCaCert;
  }
  public function getServerCaCert()
  {
    return $this->serverCaCert;
  }
  public function setServiceAccountEmailAddress($serviceAccountEmailAddress)
  {
    $this->serviceAccountEmailAddress = $serviceAccountEmailAddress;
  }
  public function getServiceAccountEmailAddress()
  {
    return $this->serviceAccountEmailAddress;
  }
  public function setSettings(Google_Service_SQLAdmin_Settings $settings)
  {
    $this->settings = $settings;
  }
  public function getSettings()
  {
    return $this->settings;
  }
  public function setState($state)
  {
    $this->state = $state;
  }
  public function getState()
  {
    return $this->state;
  }
}

class Google_Service_SQLAdmin_DatabasesListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  protected $itemsType = 'Google_Service_SQLAdmin_Database';
  protected $itemsDataType = 'array';
  public $kind;


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
}

class Google_Service_SQLAdmin_ExportContext extends Google_Collection
{
  protected $collection_key = 'databases';
  protected $internal_gapi_mappings = array(
  );
  protected $csvExportOptionsType = 'Google_Service_SQLAdmin_ExportContextCsvExportOptions';
  protected $csvExportOptionsDataType = '';
  public $databases;
  public $fileType;
  public $kind;
  protected $sqlExportOptionsType = 'Google_Service_SQLAdmin_ExportContextSqlExportOptions';
  protected $sqlExportOptionsDataType = '';
  public $uri;


  public function setCsvExportOptions(Google_Service_SQLAdmin_ExportContextCsvExportOptions $csvExportOptions)
  {
    $this->csvExportOptions = $csvExportOptions;
  }
  public function getCsvExportOptions()
  {
    return $this->csvExportOptions;
  }
  public function setDatabases($databases)
  {
    $this->databases = $databases;
  }
  public function getDatabases()
  {
    return $this->databases;
  }
  public function setFileType($fileType)
  {
    $this->fileType = $fileType;
  }
  public function getFileType()
  {
    return $this->fileType;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setSqlExportOptions(Google_Service_SQLAdmin_ExportContextSqlExportOptions $sqlExportOptions)
  {
    $this->sqlExportOptions = $sqlExportOptions;
  }
  public function getSqlExportOptions()
  {
    return $this->sqlExportOptions;
  }
  public function setUri($uri)
  {
    $this->uri = $uri;
  }
  public function getUri()
  {
    return $this->uri;
  }
}

class Google_Service_SQLAdmin_ExportContextCsvExportOptions extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $selectQuery;


  public function setSelectQuery($selectQuery)
  {
    $this->selectQuery = $selectQuery;
  }
  public function getSelectQuery()
  {
    return $this->selectQuery;
  }
}

class Google_Service_SQLAdmin_ExportContextSqlExportOptions extends Google_Collection
{
  protected $collection_key = 'tables';
  protected $internal_gapi_mappings = array(
  );
  public $schemaOnly;
  public $tables;


  public function setSchemaOnly($schemaOnly)
  {
    $this->schemaOnly = $schemaOnly;
  }
  public function getSchemaOnly()
  {
    return $this->schemaOnly;
  }
  public function setTables($tables)
  {
    $this->tables = $tables;
  }
  public function getTables()
  {
    return $this->tables;
  }
}

class Google_Service_SQLAdmin_FailoverContext extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  public $settingsVersion;


  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setSettingsVersion($settingsVersion)
  {
    $this->settingsVersion = $settingsVersion;
  }
  public function getSettingsVersion()
  {
    return $this->settingsVersion;
  }
}

class Google_Service_SQLAdmin_Flag extends Google_Collection
{
  protected $collection_key = 'appliesTo';
  protected $internal_gapi_mappings = array(
  );
  public $allowedStringValues;
  public $appliesTo;
  public $kind;
  public $maxValue;
  public $minValue;
  public $name;
  public $type;


  public function setAllowedStringValues($allowedStringValues)
  {
    $this->allowedStringValues = $allowedStringValues;
  }
  public function getAllowedStringValues()
  {
    return $this->allowedStringValues;
  }
  public function setAppliesTo($appliesTo)
  {
    $this->appliesTo = $appliesTo;
  }
  public function getAppliesTo()
  {
    return $this->appliesTo;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setMaxValue($maxValue)
  {
    $this->maxValue = $maxValue;
  }
  public function getMaxValue()
  {
    return $this->maxValue;
  }
  public function setMinValue($minValue)
  {
    $this->minValue = $minValue;
  }
  public function getMinValue()
  {
    return $this->minValue;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
}

class Google_Service_SQLAdmin_FlagsListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  protected $itemsType = 'Google_Service_SQLAdmin_Flag';
  protected $itemsDataType = 'array';
  public $kind;


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
}

class Google_Service_SQLAdmin_ImportContext extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $csvImportOptionsType = 'Google_Service_SQLAdmin_ImportContextCsvImportOptions';
  protected $csvImportOptionsDataType = '';
  public $database;
  public $fileType;
  public $kind;
  public $uri;


  public function setCsvImportOptions(Google_Service_SQLAdmin_ImportContextCsvImportOptions $csvImportOptions)
  {
    $this->csvImportOptions = $csvImportOptions;
  }
  public function getCsvImportOptions()
  {
    return $this->csvImportOptions;
  }
  public function setDatabase($database)
  {
    $this->database = $database;
  }
  public function getDatabase()
  {
    return $this->database;
  }
  public function setFileType($fileType)
  {
    $this->fileType = $fileType;
  }
  public function getFileType()
  {
    return $this->fileType;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setUri($uri)
  {
    $this->uri = $uri;
  }
  public function getUri()
  {
    return $this->uri;
  }
}

class Google_Service_SQLAdmin_ImportContextCsvImportOptions extends Google_Collection
{
  protected $collection_key = 'columns';
  protected $internal_gapi_mappings = array(
  );
  public $columns;
  public $table;


  public function setColumns($columns)
  {
    $this->columns = $columns;
  }
  public function getColumns()
  {
    return $this->columns;
  }
  public function setTable($table)
  {
    $this->table = $table;
  }
  public function getTable()
  {
    return $this->table;
  }
}

class Google_Service_SQLAdmin_InstancesCloneRequest extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $cloneContextType = 'Google_Service_SQLAdmin_CloneContext';
  protected $cloneContextDataType = '';


  public function setCloneContext(Google_Service_SQLAdmin_CloneContext $cloneContext)
  {
    $this->cloneContext = $cloneContext;
  }
  public function getCloneContext()
  {
    return $this->cloneContext;
  }
}

class Google_Service_SQLAdmin_InstancesExportRequest extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $exportContextType = 'Google_Service_SQLAdmin_ExportContext';
  protected $exportContextDataType = '';


  public function setExportContext(Google_Service_SQLAdmin_ExportContext $exportContext)
  {
    $this->exportContext = $exportContext;
  }
  public function getExportContext()
  {
    return $this->exportContext;
  }
}

class Google_Service_SQLAdmin_InstancesFailoverRequest extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $failoverContextType = 'Google_Service_SQLAdmin_FailoverContext';
  protected $failoverContextDataType = '';


  public function setFailoverContext(Google_Service_SQLAdmin_FailoverContext $failoverContext)
  {
    $this->failoverContext = $failoverContext;
  }
  public function getFailoverContext()
  {
    return $this->failoverContext;
  }
}

class Google_Service_SQLAdmin_InstancesImportRequest extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $importContextType = 'Google_Service_SQLAdmin_ImportContext';
  protected $importContextDataType = '';


  public function setImportContext(Google_Service_SQLAdmin_ImportContext $importContext)
  {
    $this->importContext = $importContext;
  }
  public function getImportContext()
  {
    return $this->importContext;
  }
}

class Google_Service_SQLAdmin_InstancesListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  protected $itemsType = 'Google_Service_SQLAdmin_DatabaseInstance';
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

class Google_Service_SQLAdmin_InstancesRestoreBackupRequest extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $restoreBackupContextType = 'Google_Service_SQLAdmin_RestoreBackupContext';
  protected $restoreBackupContextDataType = '';


  public function setRestoreBackupContext(Google_Service_SQLAdmin_RestoreBackupContext $restoreBackupContext)
  {
    $this->restoreBackupContext = $restoreBackupContext;
  }
  public function getRestoreBackupContext()
  {
    return $this->restoreBackupContext;
  }
}

class Google_Service_SQLAdmin_IpConfiguration extends Google_Collection
{
  protected $collection_key = 'authorizedNetworks';
  protected $internal_gapi_mappings = array(
  );
  protected $authorizedNetworksType = 'Google_Service_SQLAdmin_AclEntry';
  protected $authorizedNetworksDataType = 'array';
  public $ipv4Enabled;
  public $requireSsl;


  public function setAuthorizedNetworks($authorizedNetworks)
  {
    $this->authorizedNetworks = $authorizedNetworks;
  }
  public function getAuthorizedNetworks()
  {
    return $this->authorizedNetworks;
  }
  public function setIpv4Enabled($ipv4Enabled)
  {
    $this->ipv4Enabled = $ipv4Enabled;
  }
  public function getIpv4Enabled()
  {
    return $this->ipv4Enabled;
  }
  public function setRequireSsl($requireSsl)
  {
    $this->requireSsl = $requireSsl;
  }
  public function getRequireSsl()
  {
    return $this->requireSsl;
  }
}

class Google_Service_SQLAdmin_IpMapping extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $ipAddress;
  public $timeToRetire;


  public function setIpAddress($ipAddress)
  {
    $this->ipAddress = $ipAddress;
  }
  public function getIpAddress()
  {
    return $this->ipAddress;
  }
  public function setTimeToRetire($timeToRetire)
  {
    $this->timeToRetire = $timeToRetire;
  }
  public function getTimeToRetire()
  {
    return $this->timeToRetire;
  }
}

class Google_Service_SQLAdmin_LocationPreference extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $followGaeApplication;
  public $kind;
  public $zone;


  public function setFollowGaeApplication($followGaeApplication)
  {
    $this->followGaeApplication = $followGaeApplication;
  }
  public function getFollowGaeApplication()
  {
    return $this->followGaeApplication;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
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

class Google_Service_SQLAdmin_MySqlReplicaConfiguration extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $caCertificate;
  public $clientCertificate;
  public $clientKey;
  public $connectRetryInterval;
  public $dumpFilePath;
  public $kind;
  public $masterHeartbeatPeriod;
  public $password;
  public $sslCipher;
  public $username;
  public $verifyServerCertificate;


  public function setCaCertificate($caCertificate)
  {
    $this->caCertificate = $caCertificate;
  }
  public function getCaCertificate()
  {
    return $this->caCertificate;
  }
  public function setClientCertificate($clientCertificate)
  {
    $this->clientCertificate = $clientCertificate;
  }
  public function getClientCertificate()
  {
    return $this->clientCertificate;
  }
  public function setClientKey($clientKey)
  {
    $this->clientKey = $clientKey;
  }
  public function getClientKey()
  {
    return $this->clientKey;
  }
  public function setConnectRetryInterval($connectRetryInterval)
  {
    $this->connectRetryInterval = $connectRetryInterval;
  }
  public function getConnectRetryInterval()
  {
    return $this->connectRetryInterval;
  }
  public function setDumpFilePath($dumpFilePath)
  {
    $this->dumpFilePath = $dumpFilePath;
  }
  public function getDumpFilePath()
  {
    return $this->dumpFilePath;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setMasterHeartbeatPeriod($masterHeartbeatPeriod)
  {
    $this->masterHeartbeatPeriod = $masterHeartbeatPeriod;
  }
  public function getMasterHeartbeatPeriod()
  {
    return $this->masterHeartbeatPeriod;
  }
  public function setPassword($password)
  {
    $this->password = $password;
  }
  public function getPassword()
  {
    return $this->password;
  }
  public function setSslCipher($sslCipher)
  {
    $this->sslCipher = $sslCipher;
  }
  public function getSslCipher()
  {
    return $this->sslCipher;
  }
  public function setUsername($username)
  {
    $this->username = $username;
  }
  public function getUsername()
  {
    return $this->username;
  }
  public function setVerifyServerCertificate($verifyServerCertificate)
  {
    $this->verifyServerCertificate = $verifyServerCertificate;
  }
  public function getVerifyServerCertificate()
  {
    return $this->verifyServerCertificate;
  }
}

class Google_Service_SQLAdmin_OnPremisesConfiguration extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $hostPort;
  public $kind;


  public function setHostPort($hostPort)
  {
    $this->hostPort = $hostPort;
  }
  public function getHostPort()
  {
    return $this->hostPort;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
}

class Google_Service_SQLAdmin_Operation extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $endTime;
  protected $errorType = 'Google_Service_SQLAdmin_OperationErrors';
  protected $errorDataType = '';
  protected $exportContextType = 'Google_Service_SQLAdmin_ExportContext';
  protected $exportContextDataType = '';
  protected $importContextType = 'Google_Service_SQLAdmin_ImportContext';
  protected $importContextDataType = '';
  public $insertTime;
  public $kind;
  public $name;
  public $operationType;
  public $selfLink;
  public $startTime;
  public $status;
  public $targetId;
  public $targetLink;
  public $targetProject;
  public $user;


  public function setEndTime($endTime)
  {
    $this->endTime = $endTime;
  }
  public function getEndTime()
  {
    return $this->endTime;
  }
  public function setError(Google_Service_SQLAdmin_OperationErrors $error)
  {
    $this->error = $error;
  }
  public function getError()
  {
    return $this->error;
  }
  public function setExportContext(Google_Service_SQLAdmin_ExportContext $exportContext)
  {
    $this->exportContext = $exportContext;
  }
  public function getExportContext()
  {
    return $this->exportContext;
  }
  public function setImportContext(Google_Service_SQLAdmin_ImportContext $importContext)
  {
    $this->importContext = $importContext;
  }
  public function getImportContext()
  {
    return $this->importContext;
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
  public function setTargetProject($targetProject)
  {
    $this->targetProject = $targetProject;
  }
  public function getTargetProject()
  {
    return $this->targetProject;
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

class Google_Service_SQLAdmin_OperationError extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $code;
  public $kind;
  public $message;


  public function setCode($code)
  {
    $this->code = $code;
  }
  public function getCode()
  {
    return $this->code;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
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

class Google_Service_SQLAdmin_OperationErrors extends Google_Collection
{
  protected $collection_key = 'errors';
  protected $internal_gapi_mappings = array(
  );
  protected $errorsType = 'Google_Service_SQLAdmin_OperationError';
  protected $errorsDataType = 'array';
  public $kind;


  public function setErrors($errors)
  {
    $this->errors = $errors;
  }
  public function getErrors()
  {
    return $this->errors;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
}

class Google_Service_SQLAdmin_OperationsListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  protected $itemsType = 'Google_Service_SQLAdmin_Operation';
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

class Google_Service_SQLAdmin_ReplicaConfiguration extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $failoverTarget;
  public $kind;
  protected $mysqlReplicaConfigurationType = 'Google_Service_SQLAdmin_MySqlReplicaConfiguration';
  protected $mysqlReplicaConfigurationDataType = '';


  public function setFailoverTarget($failoverTarget)
  {
    $this->failoverTarget = $failoverTarget;
  }
  public function getFailoverTarget()
  {
    return $this->failoverTarget;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setMysqlReplicaConfiguration(Google_Service_SQLAdmin_MySqlReplicaConfiguration $mysqlReplicaConfiguration)
  {
    $this->mysqlReplicaConfiguration = $mysqlReplicaConfiguration;
  }
  public function getMysqlReplicaConfiguration()
  {
    return $this->mysqlReplicaConfiguration;
  }
}

class Google_Service_SQLAdmin_RestoreBackupContext extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $backupRunId;
  public $instanceId;
  public $kind;


  public function setBackupRunId($backupRunId)
  {
    $this->backupRunId = $backupRunId;
  }
  public function getBackupRunId()
  {
    return $this->backupRunId;
  }
  public function setInstanceId($instanceId)
  {
    $this->instanceId = $instanceId;
  }
  public function getInstanceId()
  {
    return $this->instanceId;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
}

class Google_Service_SQLAdmin_Settings extends Google_Collection
{
  protected $collection_key = 'databaseFlags';
  protected $internal_gapi_mappings = array(
  );
  public $activationPolicy;
  public $authorizedGaeApplications;
  protected $backupConfigurationType = 'Google_Service_SQLAdmin_BackupConfiguration';
  protected $backupConfigurationDataType = '';
  public $crashSafeReplicationEnabled;
  public $dataDiskSizeGb;
  protected $databaseFlagsType = 'Google_Service_SQLAdmin_DatabaseFlags';
  protected $databaseFlagsDataType = 'array';
  public $databaseReplicationEnabled;
  protected $ipConfigurationType = 'Google_Service_SQLAdmin_IpConfiguration';
  protected $ipConfigurationDataType = '';
  public $kind;
  protected $locationPreferenceType = 'Google_Service_SQLAdmin_LocationPreference';
  protected $locationPreferenceDataType = '';
  public $pricingPlan;
  public $replicationType;
  public $settingsVersion;
  public $tier;


  public function setActivationPolicy($activationPolicy)
  {
    $this->activationPolicy = $activationPolicy;
  }
  public function getActivationPolicy()
  {
    return $this->activationPolicy;
  }
  public function setAuthorizedGaeApplications($authorizedGaeApplications)
  {
    $this->authorizedGaeApplications = $authorizedGaeApplications;
  }
  public function getAuthorizedGaeApplications()
  {
    return $this->authorizedGaeApplications;
  }
  public function setBackupConfiguration(Google_Service_SQLAdmin_BackupConfiguration $backupConfiguration)
  {
    $this->backupConfiguration = $backupConfiguration;
  }
  public function getBackupConfiguration()
  {
    return $this->backupConfiguration;
  }
  public function setCrashSafeReplicationEnabled($crashSafeReplicationEnabled)
  {
    $this->crashSafeReplicationEnabled = $crashSafeReplicationEnabled;
  }
  public function getCrashSafeReplicationEnabled()
  {
    return $this->crashSafeReplicationEnabled;
  }
  public function setDataDiskSizeGb($dataDiskSizeGb)
  {
    $this->dataDiskSizeGb = $dataDiskSizeGb;
  }
  public function getDataDiskSizeGb()
  {
    return $this->dataDiskSizeGb;
  }
  public function setDatabaseFlags($databaseFlags)
  {
    $this->databaseFlags = $databaseFlags;
  }
  public function getDatabaseFlags()
  {
    return $this->databaseFlags;
  }
  public function setDatabaseReplicationEnabled($databaseReplicationEnabled)
  {
    $this->databaseReplicationEnabled = $databaseReplicationEnabled;
  }
  public function getDatabaseReplicationEnabled()
  {
    return $this->databaseReplicationEnabled;
  }
  public function setIpConfiguration(Google_Service_SQLAdmin_IpConfiguration $ipConfiguration)
  {
    $this->ipConfiguration = $ipConfiguration;
  }
  public function getIpConfiguration()
  {
    return $this->ipConfiguration;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLocationPreference(Google_Service_SQLAdmin_LocationPreference $locationPreference)
  {
    $this->locationPreference = $locationPreference;
  }
  public function getLocationPreference()
  {
    return $this->locationPreference;
  }
  public function setPricingPlan($pricingPlan)
  {
    $this->pricingPlan = $pricingPlan;
  }
  public function getPricingPlan()
  {
    return $this->pricingPlan;
  }
  public function setReplicationType($replicationType)
  {
    $this->replicationType = $replicationType;
  }
  public function getReplicationType()
  {
    return $this->replicationType;
  }
  public function setSettingsVersion($settingsVersion)
  {
    $this->settingsVersion = $settingsVersion;
  }
  public function getSettingsVersion()
  {
    return $this->settingsVersion;
  }
  public function setTier($tier)
  {
    $this->tier = $tier;
  }
  public function getTier()
  {
    return $this->tier;
  }
}

class Google_Service_SQLAdmin_SslCert extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $cert;
  public $certSerialNumber;
  public $commonName;
  public $createTime;
  public $expirationTime;
  public $instance;
  public $kind;
  public $selfLink;
  public $sha1Fingerprint;


  public function setCert($cert)
  {
    $this->cert = $cert;
  }
  public function getCert()
  {
    return $this->cert;
  }
  public function setCertSerialNumber($certSerialNumber)
  {
    $this->certSerialNumber = $certSerialNumber;
  }
  public function getCertSerialNumber()
  {
    return $this->certSerialNumber;
  }
  public function setCommonName($commonName)
  {
    $this->commonName = $commonName;
  }
  public function getCommonName()
  {
    return $this->commonName;
  }
  public function setCreateTime($createTime)
  {
    $this->createTime = $createTime;
  }
  public function getCreateTime()
  {
    return $this->createTime;
  }
  public function setExpirationTime($expirationTime)
  {
    $this->expirationTime = $expirationTime;
  }
  public function getExpirationTime()
  {
    return $this->expirationTime;
  }
  public function setInstance($instance)
  {
    $this->instance = $instance;
  }
  public function getInstance()
  {
    return $this->instance;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setSelfLink($selfLink)
  {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink()
  {
    return $this->selfLink;
  }
  public function setSha1Fingerprint($sha1Fingerprint)
  {
    $this->sha1Fingerprint = $sha1Fingerprint;
  }
  public function getSha1Fingerprint()
  {
    return $this->sha1Fingerprint;
  }
}

class Google_Service_SQLAdmin_SslCertDetail extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $certInfoType = 'Google_Service_SQLAdmin_SslCert';
  protected $certInfoDataType = '';
  public $certPrivateKey;


  public function setCertInfo(Google_Service_SQLAdmin_SslCert $certInfo)
  {
    $this->certInfo = $certInfo;
  }
  public function getCertInfo()
  {
    return $this->certInfo;
  }
  public function setCertPrivateKey($certPrivateKey)
  {
    $this->certPrivateKey = $certPrivateKey;
  }
  public function getCertPrivateKey()
  {
    return $this->certPrivateKey;
  }
}

class Google_Service_SQLAdmin_SslCertsCreateEphemeralRequest extends Google_Model
{
  protected $internal_gapi_mappings = array(
        "publicKey" => "public_key",
  );
  public $publicKey;


  public function setPublicKey($publicKey)
  {
    $this->publicKey = $publicKey;
  }
  public function getPublicKey()
  {
    return $this->publicKey;
  }
}

class Google_Service_SQLAdmin_SslCertsInsertRequest extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $commonName;


  public function setCommonName($commonName)
  {
    $this->commonName = $commonName;
  }
  public function getCommonName()
  {
    return $this->commonName;
  }
}

class Google_Service_SQLAdmin_SslCertsInsertResponse extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $clientCertType = 'Google_Service_SQLAdmin_SslCertDetail';
  protected $clientCertDataType = '';
  public $kind;
  protected $serverCaCertType = 'Google_Service_SQLAdmin_SslCert';
  protected $serverCaCertDataType = '';


  public function setClientCert(Google_Service_SQLAdmin_SslCertDetail $clientCert)
  {
    $this->clientCert = $clientCert;
  }
  public function getClientCert()
  {
    return $this->clientCert;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setServerCaCert(Google_Service_SQLAdmin_SslCert $serverCaCert)
  {
    $this->serverCaCert = $serverCaCert;
  }
  public function getServerCaCert()
  {
    return $this->serverCaCert;
  }
}

class Google_Service_SQLAdmin_SslCertsListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  protected $itemsType = 'Google_Service_SQLAdmin_SslCert';
  protected $itemsDataType = 'array';
  public $kind;


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
}

class Google_Service_SQLAdmin_Tier extends Google_Collection
{
  protected $collection_key = 'region';
  protected $internal_gapi_mappings = array(
        "diskQuota" => "DiskQuota",
        "rAM" => "RAM",
  );
  public $diskQuota;
  public $rAM;
  public $kind;
  public $region;
  public $tier;


  public function setDiskQuota($diskQuota)
  {
    $this->diskQuota = $diskQuota;
  }
  public function getDiskQuota()
  {
    return $this->diskQuota;
  }
  public function setRAM($rAM)
  {
    $this->rAM = $rAM;
  }
  public function getRAM()
  {
    return $this->rAM;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setRegion($region)
  {
    $this->region = $region;
  }
  public function getRegion()
  {
    return $this->region;
  }
  public function setTier($tier)
  {
    $this->tier = $tier;
  }
  public function getTier()
  {
    return $this->tier;
  }
}

class Google_Service_SQLAdmin_TiersListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  protected $itemsType = 'Google_Service_SQLAdmin_Tier';
  protected $itemsDataType = 'array';
  public $kind;


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
}

class Google_Service_SQLAdmin_User extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $host;
  public $instance;
  public $kind;
  public $name;
  public $password;
  public $project;


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setHost($host)
  {
    $this->host = $host;
  }
  public function getHost()
  {
    return $this->host;
  }
  public function setInstance($instance)
  {
    $this->instance = $instance;
  }
  public function getInstance()
  {
    return $this->instance;
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
  public function setPassword($password)
  {
    $this->password = $password;
  }
  public function getPassword()
  {
    return $this->password;
  }
  public function setProject($project)
  {
    $this->project = $project;
  }
  public function getProject()
  {
    return $this->project;
  }
}

class Google_Service_SQLAdmin_UsersListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  protected $itemsType = 'Google_Service_SQLAdmin_User';
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
