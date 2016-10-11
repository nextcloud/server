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
 * Service definition for Dns (v1).
 *
 * <p>
 * The Google Cloud DNS API provides services for configuring and serving
 * authoritative DNS records.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://developers.google.com/cloud-dns" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_Dns extends Google_Service
{
  /** View and manage your data across Google Cloud Platform services. */
  const CLOUD_PLATFORM =
      "https://www.googleapis.com/auth/cloud-platform";
  /** View your data across Google Cloud Platform services. */
  const CLOUD_PLATFORM_READ_ONLY =
      "https://www.googleapis.com/auth/cloud-platform.read-only";
  /** View your DNS records hosted by Google Cloud DNS. */
  const NDEV_CLOUDDNS_READONLY =
      "https://www.googleapis.com/auth/ndev.clouddns.readonly";
  /** View and manage your DNS records hosted by Google Cloud DNS. */
  const NDEV_CLOUDDNS_READWRITE =
      "https://www.googleapis.com/auth/ndev.clouddns.readwrite";

  public $changes;
  public $managedZones;
  public $projects;
  public $resourceRecordSets;
  

  /**
   * Constructs the internal representation of the Dns service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://www.googleapis.com/';
    $this->servicePath = 'dns/v1/projects/';
    $this->version = 'v1';
    $this->serviceName = 'dns';

    $this->changes = new Google_Service_Dns_Changes_Resource(
        $this,
        $this->serviceName,
        'changes',
        array(
          'methods' => array(
            'create' => array(
              'path' => '{project}/managedZones/{managedZone}/changes',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'managedZone' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => '{project}/managedZones/{managedZone}/changes/{changeId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'managedZone' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'changeId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => '{project}/managedZones/{managedZone}/changes',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'managedZone' => array(
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
                'sortBy' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->managedZones = new Google_Service_Dns_ManagedZones_Resource(
        $this,
        $this->serviceName,
        'managedZones',
        array(
          'methods' => array(
            'create' => array(
              'path' => '{project}/managedZones',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'delete' => array(
              'path' => '{project}/managedZones/{managedZone}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'managedZone' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => '{project}/managedZones/{managedZone}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'managedZone' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => '{project}/managedZones',
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
                'dnsName' => array(
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
    $this->projects = new Google_Service_Dns_Projects_Resource(
        $this,
        $this->serviceName,
        'projects',
        array(
          'methods' => array(
            'get' => array(
              'path' => '{project}',
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
    $this->resourceRecordSets = new Google_Service_Dns_ResourceRecordSets_Resource(
        $this,
        $this->serviceName,
        'resourceRecordSets',
        array(
          'methods' => array(
            'list' => array(
              'path' => '{project}/managedZones/{managedZone}/rrsets',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'managedZone' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'name' => array(
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
                'type' => array(
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
 * The "changes" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dnsService = new Google_Service_Dns(...);
 *   $changes = $dnsService->changes;
 *  </code>
 */
class Google_Service_Dns_Changes_Resource extends Google_Service_Resource
{

  /**
   * Atomically update the ResourceRecordSet collection. (changes.create)
   *
   * @param string $project Identifies the project addressed by this request.
   * @param string $managedZone Identifies the managed zone addressed by this
   * request. Can be the managed zone name or id.
   * @param Google_Change $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dns_Change
   */
  public function create($project, $managedZone, Google_Service_Dns_Change $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'managedZone' => $managedZone, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('create', array($params), "Google_Service_Dns_Change");
  }

  /**
   * Fetch the representation of an existing Change. (changes.get)
   *
   * @param string $project Identifies the project addressed by this request.
   * @param string $managedZone Identifies the managed zone addressed by this
   * request. Can be the managed zone name or id.
   * @param string $changeId The identifier of the requested change, from a
   * previous ResourceRecordSetsChangeResponse.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dns_Change
   */
  public function get($project, $managedZone, $changeId, $optParams = array())
  {
    $params = array('project' => $project, 'managedZone' => $managedZone, 'changeId' => $changeId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dns_Change");
  }

  /**
   * Enumerate Changes to a ResourceRecordSet collection. (changes.listChanges)
   *
   * @param string $project Identifies the project addressed by this request.
   * @param string $managedZone Identifies the managed zone addressed by this
   * request. Can be the managed zone name or id.
   * @param array $optParams Optional parameters.
   *
   * @opt_param int maxResults Optional. Maximum number of results to be returned.
   * If unspecified, the server will decide how many results to return.
   * @opt_param string pageToken Optional. A tag returned by a previous list
   * request that was truncated. Use this parameter to continue a previous list
   * request.
   * @opt_param string sortBy Sorting criterion. The only supported value is
   * change sequence.
   * @opt_param string sortOrder Sorting order direction: 'ascending' or
   * 'descending'.
   * @return Google_Service_Dns_ChangesListResponse
   */
  public function listChanges($project, $managedZone, $optParams = array())
  {
    $params = array('project' => $project, 'managedZone' => $managedZone);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dns_ChangesListResponse");
  }
}

/**
 * The "managedZones" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dnsService = new Google_Service_Dns(...);
 *   $managedZones = $dnsService->managedZones;
 *  </code>
 */
class Google_Service_Dns_ManagedZones_Resource extends Google_Service_Resource
{

  /**
   * Create a new ManagedZone. (managedZones.create)
   *
   * @param string $project Identifies the project addressed by this request.
   * @param Google_ManagedZone $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dns_ManagedZone
   */
  public function create($project, Google_Service_Dns_ManagedZone $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('create', array($params), "Google_Service_Dns_ManagedZone");
  }

  /**
   * Delete a previously created ManagedZone. (managedZones.delete)
   *
   * @param string $project Identifies the project addressed by this request.
   * @param string $managedZone Identifies the managed zone addressed by this
   * request. Can be the managed zone name or id.
   * @param array $optParams Optional parameters.
   */
  public function delete($project, $managedZone, $optParams = array())
  {
    $params = array('project' => $project, 'managedZone' => $managedZone);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Fetch the representation of an existing ManagedZone. (managedZones.get)
   *
   * @param string $project Identifies the project addressed by this request.
   * @param string $managedZone Identifies the managed zone addressed by this
   * request. Can be the managed zone name or id.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dns_ManagedZone
   */
  public function get($project, $managedZone, $optParams = array())
  {
    $params = array('project' => $project, 'managedZone' => $managedZone);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dns_ManagedZone");
  }

  /**
   * Enumerate ManagedZones that have been created but not yet deleted.
   * (managedZones.listManagedZones)
   *
   * @param string $project Identifies the project addressed by this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken Optional. A tag returned by a previous list
   * request that was truncated. Use this parameter to continue a previous list
   * request.
   * @opt_param string dnsName Restricts the list to return only zones with this
   * domain name.
   * @opt_param int maxResults Optional. Maximum number of results to be returned.
   * If unspecified, the server will decide how many results to return.
   * @return Google_Service_Dns_ManagedZonesListResponse
   */
  public function listManagedZones($project, $optParams = array())
  {
    $params = array('project' => $project);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dns_ManagedZonesListResponse");
  }
}

/**
 * The "projects" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dnsService = new Google_Service_Dns(...);
 *   $projects = $dnsService->projects;
 *  </code>
 */
class Google_Service_Dns_Projects_Resource extends Google_Service_Resource
{

  /**
   * Fetch the representation of an existing Project. (projects.get)
   *
   * @param string $project Identifies the project addressed by this request.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dns_Project
   */
  public function get($project, $optParams = array())
  {
    $params = array('project' => $project);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dns_Project");
  }
}

/**
 * The "resourceRecordSets" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dnsService = new Google_Service_Dns(...);
 *   $resourceRecordSets = $dnsService->resourceRecordSets;
 *  </code>
 */
class Google_Service_Dns_ResourceRecordSets_Resource extends Google_Service_Resource
{

  /**
   * Enumerate ResourceRecordSets that have been created but not yet deleted.
   * (resourceRecordSets.listResourceRecordSets)
   *
   * @param string $project Identifies the project addressed by this request.
   * @param string $managedZone Identifies the managed zone addressed by this
   * request. Can be the managed zone name or id.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string name Restricts the list to return only records with this
   * fully qualified domain name.
   * @opt_param int maxResults Optional. Maximum number of results to be returned.
   * If unspecified, the server will decide how many results to return.
   * @opt_param string pageToken Optional. A tag returned by a previous list
   * request that was truncated. Use this parameter to continue a previous list
   * request.
   * @opt_param string type Restricts the list to return only records of this
   * type. If present, the "name" parameter must also be present.
   * @return Google_Service_Dns_ResourceRecordSetsListResponse
   */
  public function listResourceRecordSets($project, $managedZone, $optParams = array())
  {
    $params = array('project' => $project, 'managedZone' => $managedZone);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dns_ResourceRecordSetsListResponse");
  }
}




class Google_Service_Dns_Change extends Google_Collection
{
  protected $collection_key = 'deletions';
  protected $internal_gapi_mappings = array(
  );
  protected $additionsType = 'Google_Service_Dns_ResourceRecordSet';
  protected $additionsDataType = 'array';
  protected $deletionsType = 'Google_Service_Dns_ResourceRecordSet';
  protected $deletionsDataType = 'array';
  public $id;
  public $kind;
  public $startTime;
  public $status;


  public function setAdditions($additions)
  {
    $this->additions = $additions;
  }
  public function getAdditions()
  {
    return $this->additions;
  }
  public function setDeletions($deletions)
  {
    $this->deletions = $deletions;
  }
  public function getDeletions()
  {
    return $this->deletions;
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
}

class Google_Service_Dns_ChangesListResponse extends Google_Collection
{
  protected $collection_key = 'changes';
  protected $internal_gapi_mappings = array(
  );
  protected $changesType = 'Google_Service_Dns_Change';
  protected $changesDataType = 'array';
  public $kind;
  public $nextPageToken;


  public function setChanges($changes)
  {
    $this->changes = $changes;
  }
  public function getChanges()
  {
    return $this->changes;
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

class Google_Service_Dns_ManagedZone extends Google_Collection
{
  protected $collection_key = 'nameServers';
  protected $internal_gapi_mappings = array(
  );
  public $creationTime;
  public $description;
  public $dnsName;
  public $id;
  public $kind;
  public $name;
  public $nameServerSet;
  public $nameServers;


  public function setCreationTime($creationTime)
  {
    $this->creationTime = $creationTime;
  }
  public function getCreationTime()
  {
    return $this->creationTime;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setDnsName($dnsName)
  {
    $this->dnsName = $dnsName;
  }
  public function getDnsName()
  {
    return $this->dnsName;
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
  public function setNameServerSet($nameServerSet)
  {
    $this->nameServerSet = $nameServerSet;
  }
  public function getNameServerSet()
  {
    return $this->nameServerSet;
  }
  public function setNameServers($nameServers)
  {
    $this->nameServers = $nameServers;
  }
  public function getNameServers()
  {
    return $this->nameServers;
  }
}

class Google_Service_Dns_ManagedZonesListResponse extends Google_Collection
{
  protected $collection_key = 'managedZones';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  protected $managedZonesType = 'Google_Service_Dns_ManagedZone';
  protected $managedZonesDataType = 'array';
  public $nextPageToken;


  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setManagedZones($managedZones)
  {
    $this->managedZones = $managedZones;
  }
  public function getManagedZones()
  {
    return $this->managedZones;
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

class Google_Service_Dns_Project extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $id;
  public $kind;
  public $number;
  protected $quotaType = 'Google_Service_Dns_Quota';
  protected $quotaDataType = '';


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
  public function setNumber($number)
  {
    $this->number = $number;
  }
  public function getNumber()
  {
    return $this->number;
  }
  public function setQuota(Google_Service_Dns_Quota $quota)
  {
    $this->quota = $quota;
  }
  public function getQuota()
  {
    return $this->quota;
  }
}

class Google_Service_Dns_Quota extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  public $managedZones;
  public $resourceRecordsPerRrset;
  public $rrsetAdditionsPerChange;
  public $rrsetDeletionsPerChange;
  public $rrsetsPerManagedZone;
  public $totalRrdataSizePerChange;


  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setManagedZones($managedZones)
  {
    $this->managedZones = $managedZones;
  }
  public function getManagedZones()
  {
    return $this->managedZones;
  }
  public function setResourceRecordsPerRrset($resourceRecordsPerRrset)
  {
    $this->resourceRecordsPerRrset = $resourceRecordsPerRrset;
  }
  public function getResourceRecordsPerRrset()
  {
    return $this->resourceRecordsPerRrset;
  }
  public function setRrsetAdditionsPerChange($rrsetAdditionsPerChange)
  {
    $this->rrsetAdditionsPerChange = $rrsetAdditionsPerChange;
  }
  public function getRrsetAdditionsPerChange()
  {
    return $this->rrsetAdditionsPerChange;
  }
  public function setRrsetDeletionsPerChange($rrsetDeletionsPerChange)
  {
    $this->rrsetDeletionsPerChange = $rrsetDeletionsPerChange;
  }
  public function getRrsetDeletionsPerChange()
  {
    return $this->rrsetDeletionsPerChange;
  }
  public function setRrsetsPerManagedZone($rrsetsPerManagedZone)
  {
    $this->rrsetsPerManagedZone = $rrsetsPerManagedZone;
  }
  public function getRrsetsPerManagedZone()
  {
    return $this->rrsetsPerManagedZone;
  }
  public function setTotalRrdataSizePerChange($totalRrdataSizePerChange)
  {
    $this->totalRrdataSizePerChange = $totalRrdataSizePerChange;
  }
  public function getTotalRrdataSizePerChange()
  {
    return $this->totalRrdataSizePerChange;
  }
}

class Google_Service_Dns_ResourceRecordSet extends Google_Collection
{
  protected $collection_key = 'rrdatas';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  public $name;
  public $rrdatas;
  public $ttl;
  public $type;


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
  public function setRrdatas($rrdatas)
  {
    $this->rrdatas = $rrdatas;
  }
  public function getRrdatas()
  {
    return $this->rrdatas;
  }
  public function setTtl($ttl)
  {
    $this->ttl = $ttl;
  }
  public function getTtl()
  {
    return $this->ttl;
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

class Google_Service_Dns_ResourceRecordSetsListResponse extends Google_Collection
{
  protected $collection_key = 'rrsets';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  public $nextPageToken;
  protected $rrsetsType = 'Google_Service_Dns_ResourceRecordSet';
  protected $rrsetsDataType = 'array';


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
  public function setRrsets($rrsets)
  {
    $this->rrsets = $rrsets;
  }
  public function getRrsets()
  {
    return $this->rrsets;
  }
}
