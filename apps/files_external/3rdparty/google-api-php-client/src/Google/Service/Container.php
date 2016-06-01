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
 * Service definition for Container (v1).
 *
 * <p>
 * The Google Container Engine API is used for building and managing container
 * based applications, powered by the open source Kubernetes technology.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://cloud.google.com/container-engine/" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_Container extends Google_Service
{
  /** View and manage your data across Google Cloud Platform services. */
  const CLOUD_PLATFORM =
      "https://www.googleapis.com/auth/cloud-platform";

  public $projects_zones;
  public $projects_zones_clusters;
  public $projects_zones_operations;
  

  /**
   * Constructs the internal representation of the Container service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://container.googleapis.com/';
    $this->servicePath = '';
    $this->version = 'v1';
    $this->serviceName = 'container';

    $this->projects_zones = new Google_Service_Container_ProjectsZones_Resource(
        $this,
        $this->serviceName,
        'zones',
        array(
          'methods' => array(
            'getServerconfig' => array(
              'path' => 'v1/projects/{projectId}/zones/{zone}/serverconfig',
              'httpMethod' => 'GET',
              'parameters' => array(
                'projectId' => array(
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
            ),
          )
        )
    );
    $this->projects_zones_clusters = new Google_Service_Container_ProjectsZonesClusters_Resource(
        $this,
        $this->serviceName,
        'clusters',
        array(
          'methods' => array(
            'create' => array(
              'path' => 'v1/projects/{projectId}/zones/{zone}/clusters',
              'httpMethod' => 'POST',
              'parameters' => array(
                'projectId' => array(
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
            ),'delete' => array(
              'path' => 'v1/projects/{projectId}/zones/{zone}/clusters/{clusterId}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'projectId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'zone' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'clusterId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'v1/projects/{projectId}/zones/{zone}/clusters/{clusterId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'projectId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'zone' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'clusterId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'v1/projects/{projectId}/zones/{zone}/clusters',
              'httpMethod' => 'GET',
              'parameters' => array(
                'projectId' => array(
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
            ),'update' => array(
              'path' => 'v1/projects/{projectId}/zones/{zone}/clusters/{clusterId}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'projectId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'zone' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'clusterId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->projects_zones_operations = new Google_Service_Container_ProjectsZonesOperations_Resource(
        $this,
        $this->serviceName,
        'operations',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'v1/projects/{projectId}/zones/{zone}/operations/{operationId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'projectId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'zone' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'operationId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'v1/projects/{projectId}/zones/{zone}/operations',
              'httpMethod' => 'GET',
              'parameters' => array(
                'projectId' => array(
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
            ),
          )
        )
    );
  }
}


/**
 * The "projects" collection of methods.
 * Typical usage is:
 *  <code>
 *   $containerService = new Google_Service_Container(...);
 *   $projects = $containerService->projects;
 *  </code>
 */
class Google_Service_Container_Projects_Resource extends Google_Service_Resource
{
}

/**
 * The "zones" collection of methods.
 * Typical usage is:
 *  <code>
 *   $containerService = new Google_Service_Container(...);
 *   $zones = $containerService->zones;
 *  </code>
 */
class Google_Service_Container_ProjectsZones_Resource extends Google_Service_Resource
{

  /**
   * Returns configuration info about the Container Engine service.
   * (zones.getServerconfig)
   *
   * @param string $projectId The Google Developers Console [project ID or project
   * number](https://developers.google.com/console/help/new/#projectnumber).
   * @param string $zone The name of the Google Compute Engine
   * [zone](/compute/docs/zones#available) to return operations for, or "-" for
   * all zones.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Container_ServerConfig
   */
  public function getServerconfig($projectId, $zone, $optParams = array())
  {
    $params = array('projectId' => $projectId, 'zone' => $zone);
    $params = array_merge($params, $optParams);
    return $this->call('getServerconfig', array($params), "Google_Service_Container_ServerConfig");
  }
}

/**
 * The "clusters" collection of methods.
 * Typical usage is:
 *  <code>
 *   $containerService = new Google_Service_Container(...);
 *   $clusters = $containerService->clusters;
 *  </code>
 */
class Google_Service_Container_ProjectsZonesClusters_Resource extends Google_Service_Resource
{

  /**
   * Creates a cluster, consisting of the specified number and type of Google
   * Compute Engine instances, plus a Kubernetes master endpoint. By default, the
   * cluster is created in the project's [default
   * network](/compute/docs/networking#networks_1). One firewall is added for the
   * cluster. After cluster creation, the cluster creates routes for each node to
   * allow the containers on that node to communicate with all other instances in
   * the cluster. Finally, an entry is added to the project's global metadata
   * indicating which CIDR range is being used by the cluster. (clusters.create)
   *
   * @param string $projectId The Google Developers Console [project ID or project
   * number](https://developers.google.com/console/help/new/#projectnumber).
   * @param string $zone The name of the Google Compute Engine
   * [zone](/compute/docs/zones#available) in which the cluster resides.
   * @param Google_CreateClusterRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Container_Operation
   */
  public function create($projectId, $zone, Google_Service_Container_CreateClusterRequest $postBody, $optParams = array())
  {
    $params = array('projectId' => $projectId, 'zone' => $zone, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('create', array($params), "Google_Service_Container_Operation");
  }

  /**
   * Deletes the cluster, including the Kubernetes endpoint and all worker nodes.
   * Firewalls and routes that were configured during cluster creation are also
   * deleted. (clusters.delete)
   *
   * @param string $projectId The Google Developers Console [project ID or project
   * number](https://developers.google.com/console/help/new/#projectnumber).
   * @param string $zone The name of the Google Compute Engine
   * [zone](/compute/docs/zones#available) in which the cluster resides.
   * @param string $clusterId The name of the cluster to delete.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Container_Operation
   */
  public function delete($projectId, $zone, $clusterId, $optParams = array())
  {
    $params = array('projectId' => $projectId, 'zone' => $zone, 'clusterId' => $clusterId);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params), "Google_Service_Container_Operation");
  }

  /**
   * Gets a specific cluster. (clusters.get)
   *
   * @param string $projectId The Google Developers Console [project ID or project
   * number](https://developers.google.com/console/help/new/#projectnumber).
   * @param string $zone The name of the Google Compute Engine
   * [zone](/compute/docs/zones#available) in which the cluster resides.
   * @param string $clusterId The name of the cluster to retrieve.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Container_Cluster
   */
  public function get($projectId, $zone, $clusterId, $optParams = array())
  {
    $params = array('projectId' => $projectId, 'zone' => $zone, 'clusterId' => $clusterId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Container_Cluster");
  }

  /**
   * Lists all clusters owned by a project in either the specified zone or all
   * zones. (clusters.listProjectsZonesClusters)
   *
   * @param string $projectId The Google Developers Console [project ID or project
   * number](https://developers.google.com/console/help/new/#projectnumber).
   * @param string $zone The name of the Google Compute Engine
   * [zone](/compute/docs/zones#available) in which the cluster resides, or "-"
   * for all zones.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Container_ListClustersResponse
   */
  public function listProjectsZonesClusters($projectId, $zone, $optParams = array())
  {
    $params = array('projectId' => $projectId, 'zone' => $zone);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Container_ListClustersResponse");
  }

  /**
   * Update settings of a specific cluster. (clusters.update)
   *
   * @param string $projectId The Google Developers Console [project ID or project
   * number](https://developers.google.com/console/help/new/#projectnumber).
   * @param string $zone The name of the Google Compute Engine
   * [zone](/compute/docs/zones#available) in which the cluster resides.
   * @param string $clusterId The name of the cluster to upgrade.
   * @param Google_UpdateClusterRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Container_Operation
   */
  public function update($projectId, $zone, $clusterId, Google_Service_Container_UpdateClusterRequest $postBody, $optParams = array())
  {
    $params = array('projectId' => $projectId, 'zone' => $zone, 'clusterId' => $clusterId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Container_Operation");
  }
}
/**
 * The "operations" collection of methods.
 * Typical usage is:
 *  <code>
 *   $containerService = new Google_Service_Container(...);
 *   $operations = $containerService->operations;
 *  </code>
 */
class Google_Service_Container_ProjectsZonesOperations_Resource extends Google_Service_Resource
{

  /**
   * Gets the specified operation. (operations.get)
   *
   * @param string $projectId The Google Developers Console [project ID or project
   * number](https://developers.google.com/console/help/new/#projectnumber).
   * @param string $zone The name of the Google Compute Engine
   * [zone](/compute/docs/zones#available) in which the cluster resides.
   * @param string $operationId The server-assigned `name` of the operation.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Container_Operation
   */
  public function get($projectId, $zone, $operationId, $optParams = array())
  {
    $params = array('projectId' => $projectId, 'zone' => $zone, 'operationId' => $operationId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Container_Operation");
  }

  /**
   * Lists all operations in a project in a specific zone or all zones.
   * (operations.listProjectsZonesOperations)
   *
   * @param string $projectId The Google Developers Console [project ID or project
   * number](https://developers.google.com/console/help/new/#projectnumber).
   * @param string $zone The name of the Google Compute Engine
   * [zone](/compute/docs/zones#available) to return operations for, or "-" for
   * all zones.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Container_ListOperationsResponse
   */
  public function listProjectsZonesOperations($projectId, $zone, $optParams = array())
  {
    $params = array('projectId' => $projectId, 'zone' => $zone);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Container_ListOperationsResponse");
  }
}




class Google_Service_Container_Cluster extends Google_Collection
{
  protected $collection_key = 'instanceGroupUrls';
  protected $internal_gapi_mappings = array(
  );
  public $clusterIpv4Cidr;
  public $createTime;
  public $currentMasterVersion;
  public $currentNodeVersion;
  public $description;
  public $endpoint;
  public $initialClusterVersion;
  public $initialNodeCount;
  public $instanceGroupUrls;
  public $loggingService;
  protected $masterAuthType = 'Google_Service_Container_MasterAuth';
  protected $masterAuthDataType = '';
  public $monitoringService;
  public $name;
  public $network;
  protected $nodeConfigType = 'Google_Service_Container_NodeConfig';
  protected $nodeConfigDataType = '';
  public $nodeIpv4CidrSize;
  public $selfLink;
  public $servicesIpv4Cidr;
  public $status;
  public $statusMessage;
  public $zone;


  public function setClusterIpv4Cidr($clusterIpv4Cidr)
  {
    $this->clusterIpv4Cidr = $clusterIpv4Cidr;
  }
  public function getClusterIpv4Cidr()
  {
    return $this->clusterIpv4Cidr;
  }
  public function setCreateTime($createTime)
  {
    $this->createTime = $createTime;
  }
  public function getCreateTime()
  {
    return $this->createTime;
  }
  public function setCurrentMasterVersion($currentMasterVersion)
  {
    $this->currentMasterVersion = $currentMasterVersion;
  }
  public function getCurrentMasterVersion()
  {
    return $this->currentMasterVersion;
  }
  public function setCurrentNodeVersion($currentNodeVersion)
  {
    $this->currentNodeVersion = $currentNodeVersion;
  }
  public function getCurrentNodeVersion()
  {
    return $this->currentNodeVersion;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setEndpoint($endpoint)
  {
    $this->endpoint = $endpoint;
  }
  public function getEndpoint()
  {
    return $this->endpoint;
  }
  public function setInitialClusterVersion($initialClusterVersion)
  {
    $this->initialClusterVersion = $initialClusterVersion;
  }
  public function getInitialClusterVersion()
  {
    return $this->initialClusterVersion;
  }
  public function setInitialNodeCount($initialNodeCount)
  {
    $this->initialNodeCount = $initialNodeCount;
  }
  public function getInitialNodeCount()
  {
    return $this->initialNodeCount;
  }
  public function setInstanceGroupUrls($instanceGroupUrls)
  {
    $this->instanceGroupUrls = $instanceGroupUrls;
  }
  public function getInstanceGroupUrls()
  {
    return $this->instanceGroupUrls;
  }
  public function setLoggingService($loggingService)
  {
    $this->loggingService = $loggingService;
  }
  public function getLoggingService()
  {
    return $this->loggingService;
  }
  public function setMasterAuth(Google_Service_Container_MasterAuth $masterAuth)
  {
    $this->masterAuth = $masterAuth;
  }
  public function getMasterAuth()
  {
    return $this->masterAuth;
  }
  public function setMonitoringService($monitoringService)
  {
    $this->monitoringService = $monitoringService;
  }
  public function getMonitoringService()
  {
    return $this->monitoringService;
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
  public function setNodeConfig(Google_Service_Container_NodeConfig $nodeConfig)
  {
    $this->nodeConfig = $nodeConfig;
  }
  public function getNodeConfig()
  {
    return $this->nodeConfig;
  }
  public function setNodeIpv4CidrSize($nodeIpv4CidrSize)
  {
    $this->nodeIpv4CidrSize = $nodeIpv4CidrSize;
  }
  public function getNodeIpv4CidrSize()
  {
    return $this->nodeIpv4CidrSize;
  }
  public function setSelfLink($selfLink)
  {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink()
  {
    return $this->selfLink;
  }
  public function setServicesIpv4Cidr($servicesIpv4Cidr)
  {
    $this->servicesIpv4Cidr = $servicesIpv4Cidr;
  }
  public function getServicesIpv4Cidr()
  {
    return $this->servicesIpv4Cidr;
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
  public function setZone($zone)
  {
    $this->zone = $zone;
  }
  public function getZone()
  {
    return $this->zone;
  }
}

class Google_Service_Container_ClusterUpdate extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $desiredNodeVersion;


  public function setDesiredNodeVersion($desiredNodeVersion)
  {
    $this->desiredNodeVersion = $desiredNodeVersion;
  }
  public function getDesiredNodeVersion()
  {
    return $this->desiredNodeVersion;
  }
}

class Google_Service_Container_CreateClusterRequest extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $clusterType = 'Google_Service_Container_Cluster';
  protected $clusterDataType = '';


  public function setCluster(Google_Service_Container_Cluster $cluster)
  {
    $this->cluster = $cluster;
  }
  public function getCluster()
  {
    return $this->cluster;
  }
}

class Google_Service_Container_ListClustersResponse extends Google_Collection
{
  protected $collection_key = 'clusters';
  protected $internal_gapi_mappings = array(
  );
  protected $clustersType = 'Google_Service_Container_Cluster';
  protected $clustersDataType = 'array';


  public function setClusters($clusters)
  {
    $this->clusters = $clusters;
  }
  public function getClusters()
  {
    return $this->clusters;
  }
}

class Google_Service_Container_ListOperationsResponse extends Google_Collection
{
  protected $collection_key = 'operations';
  protected $internal_gapi_mappings = array(
  );
  protected $operationsType = 'Google_Service_Container_Operation';
  protected $operationsDataType = 'array';


  public function setOperations($operations)
  {
    $this->operations = $operations;
  }
  public function getOperations()
  {
    return $this->operations;
  }
}

class Google_Service_Container_MasterAuth extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $clientCertificate;
  public $clientKey;
  public $clusterCaCertificate;
  public $password;
  public $username;


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
  public function setClusterCaCertificate($clusterCaCertificate)
  {
    $this->clusterCaCertificate = $clusterCaCertificate;
  }
  public function getClusterCaCertificate()
  {
    return $this->clusterCaCertificate;
  }
  public function setPassword($password)
  {
    $this->password = $password;
  }
  public function getPassword()
  {
    return $this->password;
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

class Google_Service_Container_NodeConfig extends Google_Collection
{
  protected $collection_key = 'oauthScopes';
  protected $internal_gapi_mappings = array(
  );
  public $diskSizeGb;
  public $machineType;
  public $oauthScopes;


  public function setDiskSizeGb($diskSizeGb)
  {
    $this->diskSizeGb = $diskSizeGb;
  }
  public function getDiskSizeGb()
  {
    return $this->diskSizeGb;
  }
  public function setMachineType($machineType)
  {
    $this->machineType = $machineType;
  }
  public function getMachineType()
  {
    return $this->machineType;
  }
  public function setOauthScopes($oauthScopes)
  {
    $this->oauthScopes = $oauthScopes;
  }
  public function getOauthScopes()
  {
    return $this->oauthScopes;
  }
}

class Google_Service_Container_Operation extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $name;
  public $operationType;
  public $selfLink;
  public $status;
  public $statusMessage;
  public $targetLink;
  public $zone;


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
  public function setTargetLink($targetLink)
  {
    $this->targetLink = $targetLink;
  }
  public function getTargetLink()
  {
    return $this->targetLink;
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

class Google_Service_Container_ServerConfig extends Google_Collection
{
  protected $collection_key = 'validNodeVersions';
  protected $internal_gapi_mappings = array(
  );
  public $defaultClusterVersion;
  public $validNodeVersions;


  public function setDefaultClusterVersion($defaultClusterVersion)
  {
    $this->defaultClusterVersion = $defaultClusterVersion;
  }
  public function getDefaultClusterVersion()
  {
    return $this->defaultClusterVersion;
  }
  public function setValidNodeVersions($validNodeVersions)
  {
    $this->validNodeVersions = $validNodeVersions;
  }
  public function getValidNodeVersions()
  {
    return $this->validNodeVersions;
  }
}

class Google_Service_Container_UpdateClusterRequest extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $updateType = 'Google_Service_Container_ClusterUpdate';
  protected $updateDataType = '';


  public function setUpdate(Google_Service_Container_ClusterUpdate $update)
  {
    $this->update = $update;
  }
  public function getUpdate()
  {
    return $this->update;
  }
}
