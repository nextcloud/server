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
 * Service definition for Manager (v1beta2).
 *
 * <p>
 * The Deployment Manager API allows users to declaratively configure, deploy
 * and run complex solutions on the Google Cloud Platform.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://developers.google.com/deployment-manager/" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_Manager extends Google_Service
{
  /** View and manage your applications deployed on Google App Engine. */
  const APPENGINE_ADMIN =
      "https://www.googleapis.com/auth/appengine.admin";
  /** View and manage your data across Google Cloud Platform services. */
  const CLOUD_PLATFORM =
      "https://www.googleapis.com/auth/cloud-platform";
  /** View your data across Google Cloud Platform services. */
  const CLOUD_PLATFORM_READ_ONLY =
      "https://www.googleapis.com/auth/cloud-platform.read-only";
  /** View and manage your Google Compute Engine resources. */
  const COMPUTE =
      "https://www.googleapis.com/auth/compute";
  /** Manage your data in Google Cloud Storage. */
  const DEVSTORAGE_READ_WRITE =
      "https://www.googleapis.com/auth/devstorage.read_write";
  /** View and manage your Google Cloud Platform management resources and deployment status information. */
  const NDEV_CLOUDMAN =
      "https://www.googleapis.com/auth/ndev.cloudman";
  /** View your Google Cloud Platform management resources and deployment status information. */
  const NDEV_CLOUDMAN_READONLY =
      "https://www.googleapis.com/auth/ndev.cloudman.readonly";

  public $deployments;
  public $templates;
  

  /**
   * Constructs the internal representation of the Manager service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://www.googleapis.com/';
    $this->servicePath = 'manager/v1beta2/projects/';
    $this->version = 'v1beta2';
    $this->serviceName = 'manager';

    $this->deployments = new Google_Service_Manager_Deployments_Resource(
        $this,
        $this->serviceName,
        'deployments',
        array(
          'methods' => array(
            'delete' => array(
              'path' => '{projectId}/regions/{region}/deployments/{deploymentName}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'projectId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'region' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'deploymentName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => '{projectId}/regions/{region}/deployments/{deploymentName}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'projectId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'region' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'deploymentName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => '{projectId}/regions/{region}/deployments',
              'httpMethod' => 'POST',
              'parameters' => array(
                'projectId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'region' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => '{projectId}/regions/{region}/deployments',
              'httpMethod' => 'GET',
              'parameters' => array(
                'projectId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'region' => array(
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
            ),
          )
        )
    );
    $this->templates = new Google_Service_Manager_Templates_Resource(
        $this,
        $this->serviceName,
        'templates',
        array(
          'methods' => array(
            'delete' => array(
              'path' => '{projectId}/templates/{templateName}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'projectId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'templateName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => '{projectId}/templates/{templateName}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'projectId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'templateName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => '{projectId}/templates',
              'httpMethod' => 'POST',
              'parameters' => array(
                'projectId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => '{projectId}/templates',
              'httpMethod' => 'GET',
              'parameters' => array(
                'projectId' => array(
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
            ),
          )
        )
    );
  }
}


/**
 * The "deployments" collection of methods.
 * Typical usage is:
 *  <code>
 *   $managerService = new Google_Service_Manager(...);
 *   $deployments = $managerService->deployments;
 *  </code>
 */
class Google_Service_Manager_Deployments_Resource extends Google_Service_Resource
{

  /**
   * (deployments.delete)
   *
   * @param string $projectId
   * @param string $region
   * @param string $deploymentName
   * @param array $optParams Optional parameters.
   */
  public function delete($projectId, $region, $deploymentName, $optParams = array())
  {
    $params = array('projectId' => $projectId, 'region' => $region, 'deploymentName' => $deploymentName);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * (deployments.get)
   *
   * @param string $projectId
   * @param string $region
   * @param string $deploymentName
   * @param array $optParams Optional parameters.
   * @return Google_Service_Manager_Deployment
   */
  public function get($projectId, $region, $deploymentName, $optParams = array())
  {
    $params = array('projectId' => $projectId, 'region' => $region, 'deploymentName' => $deploymentName);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Manager_Deployment");
  }

  /**
   * (deployments.insert)
   *
   * @param string $projectId
   * @param string $region
   * @param Google_Deployment $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Manager_Deployment
   */
  public function insert($projectId, $region, Google_Service_Manager_Deployment $postBody, $optParams = array())
  {
    $params = array('projectId' => $projectId, 'region' => $region, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Manager_Deployment");
  }

  /**
   * (deployments.listDeployments)
   *
   * @param string $projectId
   * @param string $region
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken Specifies a nextPageToken returned by a previous
   * list request. This token can be used to request the next page of results from
   * a previous list request.
   * @opt_param int maxResults Maximum count of results to be returned. Acceptable
   * values are 0 to 100, inclusive. (Default: 50)
   * @return Google_Service_Manager_DeploymentsListResponse
   */
  public function listDeployments($projectId, $region, $optParams = array())
  {
    $params = array('projectId' => $projectId, 'region' => $region);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Manager_DeploymentsListResponse");
  }
}

/**
 * The "templates" collection of methods.
 * Typical usage is:
 *  <code>
 *   $managerService = new Google_Service_Manager(...);
 *   $templates = $managerService->templates;
 *  </code>
 */
class Google_Service_Manager_Templates_Resource extends Google_Service_Resource
{

  /**
   * (templates.delete)
   *
   * @param string $projectId
   * @param string $templateName
   * @param array $optParams Optional parameters.
   */
  public function delete($projectId, $templateName, $optParams = array())
  {
    $params = array('projectId' => $projectId, 'templateName' => $templateName);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * (templates.get)
   *
   * @param string $projectId
   * @param string $templateName
   * @param array $optParams Optional parameters.
   * @return Google_Service_Manager_Template
   */
  public function get($projectId, $templateName, $optParams = array())
  {
    $params = array('projectId' => $projectId, 'templateName' => $templateName);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Manager_Template");
  }

  /**
   * (templates.insert)
   *
   * @param string $projectId
   * @param Google_Template $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Manager_Template
   */
  public function insert($projectId, Google_Service_Manager_Template $postBody, $optParams = array())
  {
    $params = array('projectId' => $projectId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Manager_Template");
  }

  /**
   * (templates.listTemplates)
   *
   * @param string $projectId
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken Specifies a nextPageToken returned by a previous
   * list request. This token can be used to request the next page of results from
   * a previous list request.
   * @opt_param int maxResults Maximum count of results to be returned. Acceptable
   * values are 0 to 100, inclusive. (Default: 50)
   * @return Google_Service_Manager_TemplatesListResponse
   */
  public function listTemplates($projectId, $optParams = array())
  {
    $params = array('projectId' => $projectId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Manager_TemplatesListResponse");
  }
}




class Google_Service_Manager_AccessConfig extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $name;
  public $natIp;
  public $type;


  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setNatIp($natIp)
  {
    $this->natIp = $natIp;
  }
  public function getNatIp()
  {
    return $this->natIp;
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

class Google_Service_Manager_Action extends Google_Collection
{
  protected $collection_key = 'commands';
  protected $internal_gapi_mappings = array(
  );
  public $commands;
  public $timeoutMs;


  public function setCommands($commands)
  {
    $this->commands = $commands;
  }
  public function getCommands()
  {
    return $this->commands;
  }
  public function setTimeoutMs($timeoutMs)
  {
    $this->timeoutMs = $timeoutMs;
  }
  public function getTimeoutMs()
  {
    return $this->timeoutMs;
  }
}

class Google_Service_Manager_AllowedRule extends Google_Collection
{
  protected $collection_key = 'ports';
  protected $internal_gapi_mappings = array(
        "iPProtocol" => "IPProtocol",
  );
  public $iPProtocol;
  public $ports;


  public function setIPProtocol($iPProtocol)
  {
    $this->iPProtocol = $iPProtocol;
  }
  public function getIPProtocol()
  {
    return $this->iPProtocol;
  }
  public function setPorts($ports)
  {
    $this->ports = $ports;
  }
  public function getPorts()
  {
    return $this->ports;
  }
}

class Google_Service_Manager_AutoscalingModule extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $coolDownPeriodSec;
  public $description;
  public $maxNumReplicas;
  public $minNumReplicas;
  public $signalType;
  public $targetModule;
  public $targetUtilization;


  public function setCoolDownPeriodSec($coolDownPeriodSec)
  {
    $this->coolDownPeriodSec = $coolDownPeriodSec;
  }
  public function getCoolDownPeriodSec()
  {
    return $this->coolDownPeriodSec;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setMaxNumReplicas($maxNumReplicas)
  {
    $this->maxNumReplicas = $maxNumReplicas;
  }
  public function getMaxNumReplicas()
  {
    return $this->maxNumReplicas;
  }
  public function setMinNumReplicas($minNumReplicas)
  {
    $this->minNumReplicas = $minNumReplicas;
  }
  public function getMinNumReplicas()
  {
    return $this->minNumReplicas;
  }
  public function setSignalType($signalType)
  {
    $this->signalType = $signalType;
  }
  public function getSignalType()
  {
    return $this->signalType;
  }
  public function setTargetModule($targetModule)
  {
    $this->targetModule = $targetModule;
  }
  public function getTargetModule()
  {
    return $this->targetModule;
  }
  public function setTargetUtilization($targetUtilization)
  {
    $this->targetUtilization = $targetUtilization;
  }
  public function getTargetUtilization()
  {
    return $this->targetUtilization;
  }
}

class Google_Service_Manager_AutoscalingModuleStatus extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $autoscalingConfigUrl;


  public function setAutoscalingConfigUrl($autoscalingConfigUrl)
  {
    $this->autoscalingConfigUrl = $autoscalingConfigUrl;
  }
  public function getAutoscalingConfigUrl()
  {
    return $this->autoscalingConfigUrl;
  }
}

class Google_Service_Manager_DeployState extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $details;
  public $status;


  public function setDetails($details)
  {
    $this->details = $details;
  }
  public function getDetails()
  {
    return $this->details;
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

class Google_Service_Manager_Deployment extends Google_Collection
{
  protected $collection_key = 'overrides';
  protected $internal_gapi_mappings = array(
  );
  public $creationDate;
  public $description;
  protected $modulesType = 'Google_Service_Manager_ModuleStatus';
  protected $modulesDataType = 'map';
  public $name;
  protected $overridesType = 'Google_Service_Manager_ParamOverride';
  protected $overridesDataType = 'array';
  protected $stateType = 'Google_Service_Manager_DeployState';
  protected $stateDataType = '';
  public $templateName;


  public function setCreationDate($creationDate)
  {
    $this->creationDate = $creationDate;
  }
  public function getCreationDate()
  {
    return $this->creationDate;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setModules($modules)
  {
    $this->modules = $modules;
  }
  public function getModules()
  {
    return $this->modules;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setOverrides($overrides)
  {
    $this->overrides = $overrides;
  }
  public function getOverrides()
  {
    return $this->overrides;
  }
  public function setState(Google_Service_Manager_DeployState $state)
  {
    $this->state = $state;
  }
  public function getState()
  {
    return $this->state;
  }
  public function setTemplateName($templateName)
  {
    $this->templateName = $templateName;
  }
  public function getTemplateName()
  {
    return $this->templateName;
  }
}

class Google_Service_Manager_DeploymentModules extends Google_Model
{
}

class Google_Service_Manager_DeploymentsListResponse extends Google_Collection
{
  protected $collection_key = 'resources';
  protected $internal_gapi_mappings = array(
  );
  public $nextPageToken;
  protected $resourcesType = 'Google_Service_Manager_Deployment';
  protected $resourcesDataType = 'array';


  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  public function setResources($resources)
  {
    $this->resources = $resources;
  }
  public function getResources()
  {
    return $this->resources;
  }
}

class Google_Service_Manager_DiskAttachment extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $deviceName;
  public $index;


  public function setDeviceName($deviceName)
  {
    $this->deviceName = $deviceName;
  }
  public function getDeviceName()
  {
    return $this->deviceName;
  }
  public function setIndex($index)
  {
    $this->index = $index;
  }
  public function getIndex()
  {
    return $this->index;
  }
}

class Google_Service_Manager_EnvVariable extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $hidden;
  public $value;


  public function setHidden($hidden)
  {
    $this->hidden = $hidden;
  }
  public function getHidden()
  {
    return $this->hidden;
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

class Google_Service_Manager_ExistingDisk extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $attachmentType = 'Google_Service_Manager_DiskAttachment';
  protected $attachmentDataType = '';
  public $source;


  public function setAttachment(Google_Service_Manager_DiskAttachment $attachment)
  {
    $this->attachment = $attachment;
  }
  public function getAttachment()
  {
    return $this->attachment;
  }
  public function setSource($source)
  {
    $this->source = $source;
  }
  public function getSource()
  {
    return $this->source;
  }
}

class Google_Service_Manager_FirewallModule extends Google_Collection
{
  protected $collection_key = 'targetTags';
  protected $internal_gapi_mappings = array(
  );
  protected $allowedType = 'Google_Service_Manager_AllowedRule';
  protected $allowedDataType = 'array';
  public $description;
  public $network;
  public $sourceRanges;
  public $sourceTags;
  public $targetTags;


  public function setAllowed($allowed)
  {
    $this->allowed = $allowed;
  }
  public function getAllowed()
  {
    return $this->allowed;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setNetwork($network)
  {
    $this->network = $network;
  }
  public function getNetwork()
  {
    return $this->network;
  }
  public function setSourceRanges($sourceRanges)
  {
    $this->sourceRanges = $sourceRanges;
  }
  public function getSourceRanges()
  {
    return $this->sourceRanges;
  }
  public function setSourceTags($sourceTags)
  {
    $this->sourceTags = $sourceTags;
  }
  public function getSourceTags()
  {
    return $this->sourceTags;
  }
  public function setTargetTags($targetTags)
  {
    $this->targetTags = $targetTags;
  }
  public function getTargetTags()
  {
    return $this->targetTags;
  }
}

class Google_Service_Manager_FirewallModuleStatus extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $firewallUrl;


  public function setFirewallUrl($firewallUrl)
  {
    $this->firewallUrl = $firewallUrl;
  }
  public function getFirewallUrl()
  {
    return $this->firewallUrl;
  }
}

class Google_Service_Manager_HealthCheckModule extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $checkIntervalSec;
  public $description;
  public $healthyThreshold;
  public $host;
  public $path;
  public $port;
  public $timeoutSec;
  public $unhealthyThreshold;


  public function setCheckIntervalSec($checkIntervalSec)
  {
    $this->checkIntervalSec = $checkIntervalSec;
  }
  public function getCheckIntervalSec()
  {
    return $this->checkIntervalSec;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setHealthyThreshold($healthyThreshold)
  {
    $this->healthyThreshold = $healthyThreshold;
  }
  public function getHealthyThreshold()
  {
    return $this->healthyThreshold;
  }
  public function setHost($host)
  {
    $this->host = $host;
  }
  public function getHost()
  {
    return $this->host;
  }
  public function setPath($path)
  {
    $this->path = $path;
  }
  public function getPath()
  {
    return $this->path;
  }
  public function setPort($port)
  {
    $this->port = $port;
  }
  public function getPort()
  {
    return $this->port;
  }
  public function setTimeoutSec($timeoutSec)
  {
    $this->timeoutSec = $timeoutSec;
  }
  public function getTimeoutSec()
  {
    return $this->timeoutSec;
  }
  public function setUnhealthyThreshold($unhealthyThreshold)
  {
    $this->unhealthyThreshold = $unhealthyThreshold;
  }
  public function getUnhealthyThreshold()
  {
    return $this->unhealthyThreshold;
  }
}

class Google_Service_Manager_HealthCheckModuleStatus extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $healthCheckUrl;


  public function setHealthCheckUrl($healthCheckUrl)
  {
    $this->healthCheckUrl = $healthCheckUrl;
  }
  public function getHealthCheckUrl()
  {
    return $this->healthCheckUrl;
  }
}

class Google_Service_Manager_LbModule extends Google_Collection
{
  protected $collection_key = 'targetModules';
  protected $internal_gapi_mappings = array(
  );
  public $description;
  public $healthChecks;
  public $ipAddress;
  public $ipProtocol;
  public $portRange;
  public $sessionAffinity;
  public $targetModules;


  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setHealthChecks($healthChecks)
  {
    $this->healthChecks = $healthChecks;
  }
  public function getHealthChecks()
  {
    return $this->healthChecks;
  }
  public function setIpAddress($ipAddress)
  {
    $this->ipAddress = $ipAddress;
  }
  public function getIpAddress()
  {
    return $this->ipAddress;
  }
  public function setIpProtocol($ipProtocol)
  {
    $this->ipProtocol = $ipProtocol;
  }
  public function getIpProtocol()
  {
    return $this->ipProtocol;
  }
  public function setPortRange($portRange)
  {
    $this->portRange = $portRange;
  }
  public function getPortRange()
  {
    return $this->portRange;
  }
  public function setSessionAffinity($sessionAffinity)
  {
    $this->sessionAffinity = $sessionAffinity;
  }
  public function getSessionAffinity()
  {
    return $this->sessionAffinity;
  }
  public function setTargetModules($targetModules)
  {
    $this->targetModules = $targetModules;
  }
  public function getTargetModules()
  {
    return $this->targetModules;
  }
}

class Google_Service_Manager_LbModuleStatus extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $forwardingRuleUrl;
  public $targetPoolUrl;


  public function setForwardingRuleUrl($forwardingRuleUrl)
  {
    $this->forwardingRuleUrl = $forwardingRuleUrl;
  }
  public function getForwardingRuleUrl()
  {
    return $this->forwardingRuleUrl;
  }
  public function setTargetPoolUrl($targetPoolUrl)
  {
    $this->targetPoolUrl = $targetPoolUrl;
  }
  public function getTargetPoolUrl()
  {
    return $this->targetPoolUrl;
  }
}

class Google_Service_Manager_Metadata extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $fingerPrint;
  protected $itemsType = 'Google_Service_Manager_MetadataItem';
  protected $itemsDataType = 'array';


  public function setFingerPrint($fingerPrint)
  {
    $this->fingerPrint = $fingerPrint;
  }
  public function getFingerPrint()
  {
    return $this->fingerPrint;
  }
  public function setItems($items)
  {
    $this->items = $items;
  }
  public function getItems()
  {
    return $this->items;
  }
}

class Google_Service_Manager_MetadataItem extends Google_Model
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

class Google_Service_Manager_Module extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $autoscalingModuleType = 'Google_Service_Manager_AutoscalingModule';
  protected $autoscalingModuleDataType = '';
  protected $firewallModuleType = 'Google_Service_Manager_FirewallModule';
  protected $firewallModuleDataType = '';
  protected $healthCheckModuleType = 'Google_Service_Manager_HealthCheckModule';
  protected $healthCheckModuleDataType = '';
  protected $lbModuleType = 'Google_Service_Manager_LbModule';
  protected $lbModuleDataType = '';
  protected $networkModuleType = 'Google_Service_Manager_NetworkModule';
  protected $networkModuleDataType = '';
  protected $replicaPoolModuleType = 'Google_Service_Manager_ReplicaPoolModule';
  protected $replicaPoolModuleDataType = '';
  public $type;


  public function setAutoscalingModule(Google_Service_Manager_AutoscalingModule $autoscalingModule)
  {
    $this->autoscalingModule = $autoscalingModule;
  }
  public function getAutoscalingModule()
  {
    return $this->autoscalingModule;
  }
  public function setFirewallModule(Google_Service_Manager_FirewallModule $firewallModule)
  {
    $this->firewallModule = $firewallModule;
  }
  public function getFirewallModule()
  {
    return $this->firewallModule;
  }
  public function setHealthCheckModule(Google_Service_Manager_HealthCheckModule $healthCheckModule)
  {
    $this->healthCheckModule = $healthCheckModule;
  }
  public function getHealthCheckModule()
  {
    return $this->healthCheckModule;
  }
  public function setLbModule(Google_Service_Manager_LbModule $lbModule)
  {
    $this->lbModule = $lbModule;
  }
  public function getLbModule()
  {
    return $this->lbModule;
  }
  public function setNetworkModule(Google_Service_Manager_NetworkModule $networkModule)
  {
    $this->networkModule = $networkModule;
  }
  public function getNetworkModule()
  {
    return $this->networkModule;
  }
  public function setReplicaPoolModule(Google_Service_Manager_ReplicaPoolModule $replicaPoolModule)
  {
    $this->replicaPoolModule = $replicaPoolModule;
  }
  public function getReplicaPoolModule()
  {
    return $this->replicaPoolModule;
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

class Google_Service_Manager_ModuleStatus extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $autoscalingModuleStatusType = 'Google_Service_Manager_AutoscalingModuleStatus';
  protected $autoscalingModuleStatusDataType = '';
  protected $firewallModuleStatusType = 'Google_Service_Manager_FirewallModuleStatus';
  protected $firewallModuleStatusDataType = '';
  protected $healthCheckModuleStatusType = 'Google_Service_Manager_HealthCheckModuleStatus';
  protected $healthCheckModuleStatusDataType = '';
  protected $lbModuleStatusType = 'Google_Service_Manager_LbModuleStatus';
  protected $lbModuleStatusDataType = '';
  protected $networkModuleStatusType = 'Google_Service_Manager_NetworkModuleStatus';
  protected $networkModuleStatusDataType = '';
  protected $replicaPoolModuleStatusType = 'Google_Service_Manager_ReplicaPoolModuleStatus';
  protected $replicaPoolModuleStatusDataType = '';
  protected $stateType = 'Google_Service_Manager_DeployState';
  protected $stateDataType = '';
  public $type;


  public function setAutoscalingModuleStatus(Google_Service_Manager_AutoscalingModuleStatus $autoscalingModuleStatus)
  {
    $this->autoscalingModuleStatus = $autoscalingModuleStatus;
  }
  public function getAutoscalingModuleStatus()
  {
    return $this->autoscalingModuleStatus;
  }
  public function setFirewallModuleStatus(Google_Service_Manager_FirewallModuleStatus $firewallModuleStatus)
  {
    $this->firewallModuleStatus = $firewallModuleStatus;
  }
  public function getFirewallModuleStatus()
  {
    return $this->firewallModuleStatus;
  }
  public function setHealthCheckModuleStatus(Google_Service_Manager_HealthCheckModuleStatus $healthCheckModuleStatus)
  {
    $this->healthCheckModuleStatus = $healthCheckModuleStatus;
  }
  public function getHealthCheckModuleStatus()
  {
    return $this->healthCheckModuleStatus;
  }
  public function setLbModuleStatus(Google_Service_Manager_LbModuleStatus $lbModuleStatus)
  {
    $this->lbModuleStatus = $lbModuleStatus;
  }
  public function getLbModuleStatus()
  {
    return $this->lbModuleStatus;
  }
  public function setNetworkModuleStatus(Google_Service_Manager_NetworkModuleStatus $networkModuleStatus)
  {
    $this->networkModuleStatus = $networkModuleStatus;
  }
  public function getNetworkModuleStatus()
  {
    return $this->networkModuleStatus;
  }
  public function setReplicaPoolModuleStatus(Google_Service_Manager_ReplicaPoolModuleStatus $replicaPoolModuleStatus)
  {
    $this->replicaPoolModuleStatus = $replicaPoolModuleStatus;
  }
  public function getReplicaPoolModuleStatus()
  {
    return $this->replicaPoolModuleStatus;
  }
  public function setState(Google_Service_Manager_DeployState $state)
  {
    $this->state = $state;
  }
  public function getState()
  {
    return $this->state;
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

class Google_Service_Manager_NetworkInterface extends Google_Collection
{
  protected $collection_key = 'accessConfigs';
  protected $internal_gapi_mappings = array(
  );
  protected $accessConfigsType = 'Google_Service_Manager_AccessConfig';
  protected $accessConfigsDataType = 'array';
  public $name;
  public $network;
  public $networkIp;


  public function setAccessConfigs($accessConfigs)
  {
    $this->accessConfigs = $accessConfigs;
  }
  public function getAccessConfigs()
  {
    return $this->accessConfigs;
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
  public function setNetworkIp($networkIp)
  {
    $this->networkIp = $networkIp;
  }
  public function getNetworkIp()
  {
    return $this->networkIp;
  }
}

class Google_Service_Manager_NetworkModule extends Google_Model
{
  protected $internal_gapi_mappings = array(
        "iPv4Range" => "IPv4Range",
  );
  public $iPv4Range;
  public $description;
  public $gatewayIPv4;


  public function setIPv4Range($iPv4Range)
  {
    $this->iPv4Range = $iPv4Range;
  }
  public function getIPv4Range()
  {
    return $this->iPv4Range;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setGatewayIPv4($gatewayIPv4)
  {
    $this->gatewayIPv4 = $gatewayIPv4;
  }
  public function getGatewayIPv4()
  {
    return $this->gatewayIPv4;
  }
}

class Google_Service_Manager_NetworkModuleStatus extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $networkUrl;


  public function setNetworkUrl($networkUrl)
  {
    $this->networkUrl = $networkUrl;
  }
  public function getNetworkUrl()
  {
    return $this->networkUrl;
  }
}

class Google_Service_Manager_NewDisk extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $attachmentType = 'Google_Service_Manager_DiskAttachment';
  protected $attachmentDataType = '';
  public $autoDelete;
  public $boot;
  protected $initializeParamsType = 'Google_Service_Manager_NewDiskInitializeParams';
  protected $initializeParamsDataType = '';


  public function setAttachment(Google_Service_Manager_DiskAttachment $attachment)
  {
    $this->attachment = $attachment;
  }
  public function getAttachment()
  {
    return $this->attachment;
  }
  public function setAutoDelete($autoDelete)
  {
    $this->autoDelete = $autoDelete;
  }
  public function getAutoDelete()
  {
    return $this->autoDelete;
  }
  public function setBoot($boot)
  {
    $this->boot = $boot;
  }
  public function getBoot()
  {
    return $this->boot;
  }
  public function setInitializeParams(Google_Service_Manager_NewDiskInitializeParams $initializeParams)
  {
    $this->initializeParams = $initializeParams;
  }
  public function getInitializeParams()
  {
    return $this->initializeParams;
  }
}

class Google_Service_Manager_NewDiskInitializeParams extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $diskSizeGb;
  public $diskType;
  public $sourceImage;


  public function setDiskSizeGb($diskSizeGb)
  {
    $this->diskSizeGb = $diskSizeGb;
  }
  public function getDiskSizeGb()
  {
    return $this->diskSizeGb;
  }
  public function setDiskType($diskType)
  {
    $this->diskType = $diskType;
  }
  public function getDiskType()
  {
    return $this->diskType;
  }
  public function setSourceImage($sourceImage)
  {
    $this->sourceImage = $sourceImage;
  }
  public function getSourceImage()
  {
    return $this->sourceImage;
  }
}

class Google_Service_Manager_ParamOverride extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $path;
  public $value;


  public function setPath($path)
  {
    $this->path = $path;
  }
  public function getPath()
  {
    return $this->path;
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

class Google_Service_Manager_ReplicaPoolModule extends Google_Collection
{
  protected $collection_key = 'healthChecks';
  protected $internal_gapi_mappings = array(
  );
  protected $envVariablesType = 'Google_Service_Manager_EnvVariable';
  protected $envVariablesDataType = 'map';
  public $healthChecks;
  public $numReplicas;
  protected $replicaPoolParamsType = 'Google_Service_Manager_ReplicaPoolParams';
  protected $replicaPoolParamsDataType = '';
  public $resourceView;


  public function setEnvVariables($envVariables)
  {
    $this->envVariables = $envVariables;
  }
  public function getEnvVariables()
  {
    return $this->envVariables;
  }
  public function setHealthChecks($healthChecks)
  {
    $this->healthChecks = $healthChecks;
  }
  public function getHealthChecks()
  {
    return $this->healthChecks;
  }
  public function setNumReplicas($numReplicas)
  {
    $this->numReplicas = $numReplicas;
  }
  public function getNumReplicas()
  {
    return $this->numReplicas;
  }
  public function setReplicaPoolParams(Google_Service_Manager_ReplicaPoolParams $replicaPoolParams)
  {
    $this->replicaPoolParams = $replicaPoolParams;
  }
  public function getReplicaPoolParams()
  {
    return $this->replicaPoolParams;
  }
  public function setResourceView($resourceView)
  {
    $this->resourceView = $resourceView;
  }
  public function getResourceView()
  {
    return $this->resourceView;
  }
}

class Google_Service_Manager_ReplicaPoolModuleEnvVariables extends Google_Model
{
}

class Google_Service_Manager_ReplicaPoolModuleStatus extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $replicaPoolUrl;
  public $resourceViewUrl;


  public function setReplicaPoolUrl($replicaPoolUrl)
  {
    $this->replicaPoolUrl = $replicaPoolUrl;
  }
  public function getReplicaPoolUrl()
  {
    return $this->replicaPoolUrl;
  }
  public function setResourceViewUrl($resourceViewUrl)
  {
    $this->resourceViewUrl = $resourceViewUrl;
  }
  public function getResourceViewUrl()
  {
    return $this->resourceViewUrl;
  }
}

class Google_Service_Manager_ReplicaPoolParams extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $v1beta1Type = 'Google_Service_Manager_ReplicaPoolParamsV1Beta1';
  protected $v1beta1DataType = '';


  public function setV1beta1(Google_Service_Manager_ReplicaPoolParamsV1Beta1 $v1beta1)
  {
    $this->v1beta1 = $v1beta1;
  }
  public function getV1beta1()
  {
    return $this->v1beta1;
  }
}

class Google_Service_Manager_ReplicaPoolParamsV1Beta1 extends Google_Collection
{
  protected $collection_key = 'serviceAccounts';
  protected $internal_gapi_mappings = array(
  );
  public $autoRestart;
  public $baseInstanceName;
  public $canIpForward;
  public $description;
  protected $disksToAttachType = 'Google_Service_Manager_ExistingDisk';
  protected $disksToAttachDataType = 'array';
  protected $disksToCreateType = 'Google_Service_Manager_NewDisk';
  protected $disksToCreateDataType = 'array';
  public $initAction;
  public $machineType;
  protected $metadataType = 'Google_Service_Manager_Metadata';
  protected $metadataDataType = '';
  protected $networkInterfacesType = 'Google_Service_Manager_NetworkInterface';
  protected $networkInterfacesDataType = 'array';
  public $onHostMaintenance;
  protected $serviceAccountsType = 'Google_Service_Manager_ServiceAccount';
  protected $serviceAccountsDataType = 'array';
  protected $tagsType = 'Google_Service_Manager_Tag';
  protected $tagsDataType = '';
  public $zone;


  public function setAutoRestart($autoRestart)
  {
    $this->autoRestart = $autoRestart;
  }
  public function getAutoRestart()
  {
    return $this->autoRestart;
  }
  public function setBaseInstanceName($baseInstanceName)
  {
    $this->baseInstanceName = $baseInstanceName;
  }
  public function getBaseInstanceName()
  {
    return $this->baseInstanceName;
  }
  public function setCanIpForward($canIpForward)
  {
    $this->canIpForward = $canIpForward;
  }
  public function getCanIpForward()
  {
    return $this->canIpForward;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setDisksToAttach($disksToAttach)
  {
    $this->disksToAttach = $disksToAttach;
  }
  public function getDisksToAttach()
  {
    return $this->disksToAttach;
  }
  public function setDisksToCreate($disksToCreate)
  {
    $this->disksToCreate = $disksToCreate;
  }
  public function getDisksToCreate()
  {
    return $this->disksToCreate;
  }
  public function setInitAction($initAction)
  {
    $this->initAction = $initAction;
  }
  public function getInitAction()
  {
    return $this->initAction;
  }
  public function setMachineType($machineType)
  {
    $this->machineType = $machineType;
  }
  public function getMachineType()
  {
    return $this->machineType;
  }
  public function setMetadata(Google_Service_Manager_Metadata $metadata)
  {
    $this->metadata = $metadata;
  }
  public function getMetadata()
  {
    return $this->metadata;
  }
  public function setNetworkInterfaces($networkInterfaces)
  {
    $this->networkInterfaces = $networkInterfaces;
  }
  public function getNetworkInterfaces()
  {
    return $this->networkInterfaces;
  }
  public function setOnHostMaintenance($onHostMaintenance)
  {
    $this->onHostMaintenance = $onHostMaintenance;
  }
  public function getOnHostMaintenance()
  {
    return $this->onHostMaintenance;
  }
  public function setServiceAccounts($serviceAccounts)
  {
    $this->serviceAccounts = $serviceAccounts;
  }
  public function getServiceAccounts()
  {
    return $this->serviceAccounts;
  }
  public function setTags(Google_Service_Manager_Tag $tags)
  {
    $this->tags = $tags;
  }
  public function getTags()
  {
    return $this->tags;
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

class Google_Service_Manager_ServiceAccount extends Google_Collection
{
  protected $collection_key = 'scopes';
  protected $internal_gapi_mappings = array(
  );
  public $email;
  public $scopes;


  public function setEmail($email)
  {
    $this->email = $email;
  }
  public function getEmail()
  {
    return $this->email;
  }
  public function setScopes($scopes)
  {
    $this->scopes = $scopes;
  }
  public function getScopes()
  {
    return $this->scopes;
  }
}

class Google_Service_Manager_Tag extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $fingerPrint;
  public $items;


  public function setFingerPrint($fingerPrint)
  {
    $this->fingerPrint = $fingerPrint;
  }
  public function getFingerPrint()
  {
    return $this->fingerPrint;
  }
  public function setItems($items)
  {
    $this->items = $items;
  }
  public function getItems()
  {
    return $this->items;
  }
}

class Google_Service_Manager_Template extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $actionsType = 'Google_Service_Manager_Action';
  protected $actionsDataType = 'map';
  public $description;
  protected $modulesType = 'Google_Service_Manager_Module';
  protected $modulesDataType = 'map';
  public $name;


  public function setActions($actions)
  {
    $this->actions = $actions;
  }
  public function getActions()
  {
    return $this->actions;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setModules($modules)
  {
    $this->modules = $modules;
  }
  public function getModules()
  {
    return $this->modules;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
}

class Google_Service_Manager_TemplateActions extends Google_Model
{
}

class Google_Service_Manager_TemplateModules extends Google_Model
{
}

class Google_Service_Manager_TemplatesListResponse extends Google_Collection
{
  protected $collection_key = 'resources';
  protected $internal_gapi_mappings = array(
  );
  public $nextPageToken;
  protected $resourcesType = 'Google_Service_Manager_Template';
  protected $resourcesDataType = 'array';


  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  public function setResources($resources)
  {
    $this->resources = $resources;
  }
  public function getResources()
  {
    return $this->resources;
  }
}
