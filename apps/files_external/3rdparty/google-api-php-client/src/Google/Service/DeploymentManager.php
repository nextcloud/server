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
 * Service definition for DeploymentManager (v2).
 *
 * <p>
 * The Deployment Manager API allows users to declaratively configure, deploy
 * and run complex solutions on the Google Cloud Platform.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://cloud.google.com/deployment-manager/" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_DeploymentManager extends Google_Service
{
  /** View and manage your data across Google Cloud Platform services. */
  const CLOUD_PLATFORM =
      "https://www.googleapis.com/auth/cloud-platform";
  /** View your data across Google Cloud Platform services. */
  const CLOUD_PLATFORM_READ_ONLY =
      "https://www.googleapis.com/auth/cloud-platform.read-only";
  /** View and manage your Google Cloud Platform management resources and deployment status information. */
  const NDEV_CLOUDMAN =
      "https://www.googleapis.com/auth/ndev.cloudman";
  /** View your Google Cloud Platform management resources and deployment status information. */
  const NDEV_CLOUDMAN_READONLY =
      "https://www.googleapis.com/auth/ndev.cloudman.readonly";

  public $deployments;
  public $manifests;
  public $operations;
  public $resources;
  public $types;
  

  /**
   * Constructs the internal representation of the DeploymentManager service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://www.googleapis.com/';
    $this->servicePath = 'deploymentmanager/v2/projects/';
    $this->version = 'v2';
    $this->serviceName = 'deploymentmanager';

    $this->deployments = new Google_Service_DeploymentManager_Deployments_Resource(
        $this,
        $this->serviceName,
        'deployments',
        array(
          'methods' => array(
            'cancelPreview' => array(
              'path' => '{project}/global/deployments/{deployment}/cancelPreview',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'deployment' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'delete' => array(
              'path' => '{project}/global/deployments/{deployment}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'deployment' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => '{project}/global/deployments/{deployment}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'deployment' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => '{project}/global/deployments',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'preview' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
              ),
            ),'list' => array(
              'path' => '{project}/global/deployments',
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
            ),'patch' => array(
              'path' => '{project}/global/deployments/{deployment}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'deployment' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'preview' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'deletePolicy' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'createPolicy' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'stop' => array(
              'path' => '{project}/global/deployments/{deployment}/stop',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'deployment' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => '{project}/global/deployments/{deployment}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'deployment' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'preview' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'deletePolicy' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'createPolicy' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->manifests = new Google_Service_DeploymentManager_Manifests_Resource(
        $this,
        $this->serviceName,
        'manifests',
        array(
          'methods' => array(
            'get' => array(
              'path' => '{project}/global/deployments/{deployment}/manifests/{manifest}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'deployment' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'manifest' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => '{project}/global/deployments/{deployment}/manifests',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'deployment' => array(
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
    $this->operations = new Google_Service_DeploymentManager_Operations_Resource(
        $this,
        $this->serviceName,
        'operations',
        array(
          'methods' => array(
            'get' => array(
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
    $this->resources = new Google_Service_DeploymentManager_Resources_Resource(
        $this,
        $this->serviceName,
        'resources',
        array(
          'methods' => array(
            'get' => array(
              'path' => '{project}/global/deployments/{deployment}/resources/{resource}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'deployment' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'resource' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => '{project}/global/deployments/{deployment}/resources',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'deployment' => array(
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
    $this->types = new Google_Service_DeploymentManager_Types_Resource(
        $this,
        $this->serviceName,
        'types',
        array(
          'methods' => array(
            'list' => array(
              'path' => '{project}/global/types',
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
  }
}


/**
 * The "deployments" collection of methods.
 * Typical usage is:
 *  <code>
 *   $deploymentmanagerService = new Google_Service_DeploymentManager(...);
 *   $deployments = $deploymentmanagerService->deployments;
 *  </code>
 */
class Google_Service_DeploymentManager_Deployments_Resource extends Google_Service_Resource
{

  /**
   * Cancels and removes the preview currently associated with the deployment.
   * (deployments.cancelPreview)
   *
   * @param string $project The project ID for this request.
   * @param string $deployment The name of the deployment for this request.
   * @param Google_DeploymentsCancelPreviewRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_DeploymentManager_Operation
   */
  public function cancelPreview($project, $deployment, Google_Service_DeploymentManager_DeploymentsCancelPreviewRequest $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'deployment' => $deployment, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('cancelPreview', array($params), "Google_Service_DeploymentManager_Operation");
  }

  /**
   * Deletes a deployment and all of the resources in the deployment.
   * (deployments.delete)
   *
   * @param string $project The project ID for this request.
   * @param string $deployment The name of the deployment for this request.
   * @param array $optParams Optional parameters.
   * @return Google_Service_DeploymentManager_Operation
   */
  public function delete($project, $deployment, $optParams = array())
  {
    $params = array('project' => $project, 'deployment' => $deployment);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params), "Google_Service_DeploymentManager_Operation");
  }

  /**
   * Gets information about a specific deployment. (deployments.get)
   *
   * @param string $project The project ID for this request.
   * @param string $deployment The name of the deployment for this request.
   * @param array $optParams Optional parameters.
   * @return Google_Service_DeploymentManager_Deployment
   */
  public function get($project, $deployment, $optParams = array())
  {
    $params = array('project' => $project, 'deployment' => $deployment);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_DeploymentManager_Deployment");
  }

  /**
   * Creates a deployment and all of the resources described by the deployment
   * manifest. (deployments.insert)
   *
   * @param string $project The project ID for this request.
   * @param Google_Deployment $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool preview If set to true, creates a deployment and creates
   * "shell" resources but does not actually instantiate these resources. This
   * allows you to preview what your deployment looks like. After previewing a
   * deployment, you can deploy your resources by making a request with the
   * update() method or you can use the cancelPreview() method to cancel the
   * preview altogether. Note that the deployment will still exist after you
   * cancel the preview and you must separately delete this deployment if you want
   * to remove it.
   * @return Google_Service_DeploymentManager_Operation
   */
  public function insert($project, Google_Service_DeploymentManager_Deployment $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_DeploymentManager_Operation");
  }

  /**
   * Lists all deployments for a given project. (deployments.listDeployments)
   *
   * @param string $project The project ID for this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter Sets a filter expression for filtering listed
   * resources, in the form filter={expression}. Your {expression} must be in the
   * format: FIELD_NAME COMPARISON_STRING LITERAL_STRING.
   *
   * The FIELD_NAME is the name of the field you want to compare. Only atomic
   * field types are supported (string, number, boolean). The COMPARISON_STRING
   * must be either eq (equals) or ne (not equals). The LITERAL_STRING is the
   * string value to filter to. The literal value must be valid for the type of
   * field (string, number, boolean). For string fields, the literal value is
   * interpreted as a regular expression using RE2 syntax. The literal value must
   * match the entire field.
   *
   * For example, filter=name ne example-instance.
   * @opt_param string pageToken Specifies a page token to use. Use this parameter
   * if you want to list the next page of results. Set pageToken to the
   * nextPageToken returned by a previous list request.
   * @opt_param string maxResults Maximum count of results to be returned.
   * @return Google_Service_DeploymentManager_DeploymentsListResponse
   */
  public function listDeployments($project, $optParams = array())
  {
    $params = array('project' => $project);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_DeploymentManager_DeploymentsListResponse");
  }

  /**
   * Updates a deployment and all of the resources described by the deployment
   * manifest. This method supports patch semantics. (deployments.patch)
   *
   * @param string $project The project ID for this request.
   * @param string $deployment The name of the deployment for this request.
   * @param Google_Deployment $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool preview If set to true, updates the deployment and creates
   * and updates the "shell" resources but does not actually alter or instantiate
   * these resources. This allows you to preview what your deployment looks like.
   * You can use this intent to preview how an update would affect your
   * deployment. You must provide a target.config with a configuration if this is
   * set to true. After previewing a deployment, you can deploy your resources by
   * making a request with the update() or you can cancelPreview() to remove the
   * preview altogether. Note that the deployment will still exist after you
   * cancel the preview and you must separately delete this deployment if you want
   * to remove it.
   * @opt_param string deletePolicy Sets the policy to use for deleting resources.
   * @opt_param string createPolicy Sets the policy to use for creating new
   * resources.
   * @return Google_Service_DeploymentManager_Operation
   */
  public function patch($project, $deployment, Google_Service_DeploymentManager_Deployment $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'deployment' => $deployment, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_DeploymentManager_Operation");
  }

  /**
   * Stops an ongoing operation. This does not roll back any work that has already
   * been completed, but prevents any new work from being started.
   * (deployments.stop)
   *
   * @param string $project The project ID for this request.
   * @param string $deployment The name of the deployment for this request.
   * @param Google_DeploymentsStopRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_DeploymentManager_Operation
   */
  public function stop($project, $deployment, Google_Service_DeploymentManager_DeploymentsStopRequest $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'deployment' => $deployment, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('stop', array($params), "Google_Service_DeploymentManager_Operation");
  }

  /**
   * Updates a deployment and all of the resources described by the deployment
   * manifest. (deployments.update)
   *
   * @param string $project The project ID for this request.
   * @param string $deployment The name of the deployment for this request.
   * @param Google_Deployment $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool preview If set to true, updates the deployment and creates
   * and updates the "shell" resources but does not actually alter or instantiate
   * these resources. This allows you to preview what your deployment looks like.
   * You can use this intent to preview how an update would affect your
   * deployment. You must provide a target.config with a configuration if this is
   * set to true. After previewing a deployment, you can deploy your resources by
   * making a request with the update() or you can cancelPreview() to remove the
   * preview altogether. Note that the deployment will still exist after you
   * cancel the preview and you must separately delete this deployment if you want
   * to remove it.
   * @opt_param string deletePolicy Sets the policy to use for deleting resources.
   * @opt_param string createPolicy Sets the policy to use for creating new
   * resources.
   * @return Google_Service_DeploymentManager_Operation
   */
  public function update($project, $deployment, Google_Service_DeploymentManager_Deployment $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'deployment' => $deployment, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_DeploymentManager_Operation");
  }
}

/**
 * The "manifests" collection of methods.
 * Typical usage is:
 *  <code>
 *   $deploymentmanagerService = new Google_Service_DeploymentManager(...);
 *   $manifests = $deploymentmanagerService->manifests;
 *  </code>
 */
class Google_Service_DeploymentManager_Manifests_Resource extends Google_Service_Resource
{

  /**
   * Gets information about a specific manifest. (manifests.get)
   *
   * @param string $project The project ID for this request.
   * @param string $deployment The name of the deployment for this request.
   * @param string $manifest The name of the manifest for this request.
   * @param array $optParams Optional parameters.
   * @return Google_Service_DeploymentManager_Manifest
   */
  public function get($project, $deployment, $manifest, $optParams = array())
  {
    $params = array('project' => $project, 'deployment' => $deployment, 'manifest' => $manifest);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_DeploymentManager_Manifest");
  }

  /**
   * Lists all manifests for a given deployment. (manifests.listManifests)
   *
   * @param string $project The project ID for this request.
   * @param string $deployment The name of the deployment for this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter Sets a filter expression for filtering listed
   * resources, in the form filter={expression}. Your {expression} must be in the
   * format: FIELD_NAME COMPARISON_STRING LITERAL_STRING.
   *
   * The FIELD_NAME is the name of the field you want to compare. Only atomic
   * field types are supported (string, number, boolean). The COMPARISON_STRING
   * must be either eq (equals) or ne (not equals). The LITERAL_STRING is the
   * string value to filter to. The literal value must be valid for the type of
   * field (string, number, boolean). For string fields, the literal value is
   * interpreted as a regular expression using RE2 syntax. The literal value must
   * match the entire field.
   *
   * For example, filter=name ne example-instance.
   * @opt_param string pageToken Specifies a page token to use. Use this parameter
   * if you want to list the next page of results. Set pageToken to the
   * nextPageToken returned by a previous list request.
   * @opt_param string maxResults Maximum count of results to be returned.
   * @return Google_Service_DeploymentManager_ManifestsListResponse
   */
  public function listManifests($project, $deployment, $optParams = array())
  {
    $params = array('project' => $project, 'deployment' => $deployment);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_DeploymentManager_ManifestsListResponse");
  }
}

/**
 * The "operations" collection of methods.
 * Typical usage is:
 *  <code>
 *   $deploymentmanagerService = new Google_Service_DeploymentManager(...);
 *   $operations = $deploymentmanagerService->operations;
 *  </code>
 */
class Google_Service_DeploymentManager_Operations_Resource extends Google_Service_Resource
{

  /**
   * Gets information about a specific operation. (operations.get)
   *
   * @param string $project The project ID for this request.
   * @param string $operation The name of the operation for this request.
   * @param array $optParams Optional parameters.
   * @return Google_Service_DeploymentManager_Operation
   */
  public function get($project, $operation, $optParams = array())
  {
    $params = array('project' => $project, 'operation' => $operation);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_DeploymentManager_Operation");
  }

  /**
   * Lists all operations for a project. (operations.listOperations)
   *
   * @param string $project The project ID for this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter Sets a filter expression for filtering listed
   * resources, in the form filter={expression}. Your {expression} must be in the
   * format: FIELD_NAME COMPARISON_STRING LITERAL_STRING.
   *
   * The FIELD_NAME is the name of the field you want to compare. Only atomic
   * field types are supported (string, number, boolean). The COMPARISON_STRING
   * must be either eq (equals) or ne (not equals). The LITERAL_STRING is the
   * string value to filter to. The literal value must be valid for the type of
   * field (string, number, boolean). For string fields, the literal value is
   * interpreted as a regular expression using RE2 syntax. The literal value must
   * match the entire field.
   *
   * For example, filter=name ne example-instance.
   * @opt_param string pageToken Specifies a page token to use. Use this parameter
   * if you want to list the next page of results. Set pageToken to the
   * nextPageToken returned by a previous list request.
   * @opt_param string maxResults Maximum count of results to be returned.
   * @return Google_Service_DeploymentManager_OperationsListResponse
   */
  public function listOperations($project, $optParams = array())
  {
    $params = array('project' => $project);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_DeploymentManager_OperationsListResponse");
  }
}

/**
 * The "resources" collection of methods.
 * Typical usage is:
 *  <code>
 *   $deploymentmanagerService = new Google_Service_DeploymentManager(...);
 *   $resources = $deploymentmanagerService->resources;
 *  </code>
 */
class Google_Service_DeploymentManager_Resources_Resource extends Google_Service_Resource
{

  /**
   * Gets information about a single resource. (resources.get)
   *
   * @param string $project The project ID for this request.
   * @param string $deployment The name of the deployment for this request.
   * @param string $resource The name of the resource for this request.
   * @param array $optParams Optional parameters.
   * @return Google_Service_DeploymentManager_DeploymentmanagerResource
   */
  public function get($project, $deployment, $resource, $optParams = array())
  {
    $params = array('project' => $project, 'deployment' => $deployment, 'resource' => $resource);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_DeploymentManager_DeploymentmanagerResource");
  }

  /**
   * Lists all resources in a given deployment. (resources.listResources)
   *
   * @param string $project The project ID for this request.
   * @param string $deployment The name of the deployment for this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter Sets a filter expression for filtering listed
   * resources, in the form filter={expression}. Your {expression} must be in the
   * format: FIELD_NAME COMPARISON_STRING LITERAL_STRING.
   *
   * The FIELD_NAME is the name of the field you want to compare. Only atomic
   * field types are supported (string, number, boolean). The COMPARISON_STRING
   * must be either eq (equals) or ne (not equals). The LITERAL_STRING is the
   * string value to filter to. The literal value must be valid for the type of
   * field (string, number, boolean). For string fields, the literal value is
   * interpreted as a regular expression using RE2 syntax. The literal value must
   * match the entire field.
   *
   * For example, filter=name ne example-instance.
   * @opt_param string pageToken Specifies a page token to use. Use this parameter
   * if you want to list the next page of results. Set pageToken to the
   * nextPageToken returned by a previous list request.
   * @opt_param string maxResults Maximum count of results to be returned.
   * @return Google_Service_DeploymentManager_ResourcesListResponse
   */
  public function listResources($project, $deployment, $optParams = array())
  {
    $params = array('project' => $project, 'deployment' => $deployment);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_DeploymentManager_ResourcesListResponse");
  }
}

/**
 * The "types" collection of methods.
 * Typical usage is:
 *  <code>
 *   $deploymentmanagerService = new Google_Service_DeploymentManager(...);
 *   $types = $deploymentmanagerService->types;
 *  </code>
 */
class Google_Service_DeploymentManager_Types_Resource extends Google_Service_Resource
{

  /**
   * Lists all resource types for Deployment Manager. (types.listTypes)
   *
   * @param string $project The project ID for this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter Sets a filter expression for filtering listed
   * resources, in the form filter={expression}. Your {expression} must be in the
   * format: FIELD_NAME COMPARISON_STRING LITERAL_STRING.
   *
   * The FIELD_NAME is the name of the field you want to compare. Only atomic
   * field types are supported (string, number, boolean). The COMPARISON_STRING
   * must be either eq (equals) or ne (not equals). The LITERAL_STRING is the
   * string value to filter to. The literal value must be valid for the type of
   * field (string, number, boolean). For string fields, the literal value is
   * interpreted as a regular expression using RE2 syntax. The literal value must
   * match the entire field.
   *
   * For example, filter=name ne example-instance.
   * @opt_param string pageToken Specifies a page token to use. Use this parameter
   * if you want to list the next page of results. Set pageToken to the
   * nextPageToken returned by a previous list request.
   * @opt_param string maxResults Maximum count of results to be returned.
   * @return Google_Service_DeploymentManager_TypesListResponse
   */
  public function listTypes($project, $optParams = array())
  {
    $params = array('project' => $project);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_DeploymentManager_TypesListResponse");
  }
}




class Google_Service_DeploymentManager_ConfigFile extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $content;


  public function setContent($content)
  {
    $this->content = $content;
  }
  public function getContent()
  {
    return $this->content;
  }
}

class Google_Service_DeploymentManager_Deployment extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $description;
  public $fingerprint;
  public $id;
  public $insertTime;
  public $manifest;
  public $name;
  protected $operationType = 'Google_Service_DeploymentManager_Operation';
  protected $operationDataType = '';
  protected $targetType = 'Google_Service_DeploymentManager_TargetConfiguration';
  protected $targetDataType = '';
  protected $updateType = 'Google_Service_DeploymentManager_DeploymentUpdate';
  protected $updateDataType = '';


  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
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
  public function setInsertTime($insertTime)
  {
    $this->insertTime = $insertTime;
  }
  public function getInsertTime()
  {
    return $this->insertTime;
  }
  public function setManifest($manifest)
  {
    $this->manifest = $manifest;
  }
  public function getManifest()
  {
    return $this->manifest;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setOperation(Google_Service_DeploymentManager_Operation $operation)
  {
    $this->operation = $operation;
  }
  public function getOperation()
  {
    return $this->operation;
  }
  public function setTarget(Google_Service_DeploymentManager_TargetConfiguration $target)
  {
    $this->target = $target;
  }
  public function getTarget()
  {
    return $this->target;
  }
  public function setUpdate(Google_Service_DeploymentManager_DeploymentUpdate $update)
  {
    $this->update = $update;
  }
  public function getUpdate()
  {
    return $this->update;
  }
}

class Google_Service_DeploymentManager_DeploymentUpdate extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $manifest;


  public function setManifest($manifest)
  {
    $this->manifest = $manifest;
  }
  public function getManifest()
  {
    return $this->manifest;
  }
}

class Google_Service_DeploymentManager_DeploymentmanagerResource extends Google_Collection
{
  protected $collection_key = 'warnings';
  protected $internal_gapi_mappings = array(
  );
  public $finalProperties;
  public $id;
  public $insertTime;
  public $manifest;
  public $name;
  public $properties;
  public $type;
  protected $updateType = 'Google_Service_DeploymentManager_ResourceUpdate';
  protected $updateDataType = '';
  public $updateTime;
  public $url;
  protected $warningsType = 'Google_Service_DeploymentManager_DeploymentmanagerResourceWarnings';
  protected $warningsDataType = 'array';


  public function setFinalProperties($finalProperties)
  {
    $this->finalProperties = $finalProperties;
  }
  public function getFinalProperties()
  {
    return $this->finalProperties;
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
  public function setManifest($manifest)
  {
    $this->manifest = $manifest;
  }
  public function getManifest()
  {
    return $this->manifest;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setProperties($properties)
  {
    $this->properties = $properties;
  }
  public function getProperties()
  {
    return $this->properties;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
  public function setUpdate(Google_Service_DeploymentManager_ResourceUpdate $update)
  {
    $this->update = $update;
  }
  public function getUpdate()
  {
    return $this->update;
  }
  public function setUpdateTime($updateTime)
  {
    $this->updateTime = $updateTime;
  }
  public function getUpdateTime()
  {
    return $this->updateTime;
  }
  public function setUrl($url)
  {
    $this->url = $url;
  }
  public function getUrl()
  {
    return $this->url;
  }
  public function setWarnings($warnings)
  {
    $this->warnings = $warnings;
  }
  public function getWarnings()
  {
    return $this->warnings;
  }
}

class Google_Service_DeploymentManager_DeploymentmanagerResourceWarnings extends Google_Collection
{
  protected $collection_key = 'data';
  protected $internal_gapi_mappings = array(
  );
  public $code;
  protected $dataType = 'Google_Service_DeploymentManager_DeploymentmanagerResourceWarningsData';
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

class Google_Service_DeploymentManager_DeploymentmanagerResourceWarningsData extends Google_Model
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

class Google_Service_DeploymentManager_DeploymentsCancelPreviewRequest extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $fingerprint;


  public function setFingerprint($fingerprint)
  {
    $this->fingerprint = $fingerprint;
  }
  public function getFingerprint()
  {
    return $this->fingerprint;
  }
}

class Google_Service_DeploymentManager_DeploymentsListResponse extends Google_Collection
{
  protected $collection_key = 'deployments';
  protected $internal_gapi_mappings = array(
  );
  protected $deploymentsType = 'Google_Service_DeploymentManager_Deployment';
  protected $deploymentsDataType = 'array';
  public $nextPageToken;


  public function setDeployments($deployments)
  {
    $this->deployments = $deployments;
  }
  public function getDeployments()
  {
    return $this->deployments;
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

class Google_Service_DeploymentManager_DeploymentsStopRequest extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $fingerprint;


  public function setFingerprint($fingerprint)
  {
    $this->fingerprint = $fingerprint;
  }
  public function getFingerprint()
  {
    return $this->fingerprint;
  }
}

class Google_Service_DeploymentManager_ImportFile extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $content;
  public $name;


  public function setContent($content)
  {
    $this->content = $content;
  }
  public function getContent()
  {
    return $this->content;
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

class Google_Service_DeploymentManager_Manifest extends Google_Collection
{
  protected $collection_key = 'imports';
  protected $internal_gapi_mappings = array(
  );
  protected $configType = 'Google_Service_DeploymentManager_ConfigFile';
  protected $configDataType = '';
  public $expandedConfig;
  public $id;
  protected $importsType = 'Google_Service_DeploymentManager_ImportFile';
  protected $importsDataType = 'array';
  public $insertTime;
  public $layout;
  public $name;
  public $selfLink;


  public function setConfig(Google_Service_DeploymentManager_ConfigFile $config)
  {
    $this->config = $config;
  }
  public function getConfig()
  {
    return $this->config;
  }
  public function setExpandedConfig($expandedConfig)
  {
    $this->expandedConfig = $expandedConfig;
  }
  public function getExpandedConfig()
  {
    return $this->expandedConfig;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setImports($imports)
  {
    $this->imports = $imports;
  }
  public function getImports()
  {
    return $this->imports;
  }
  public function setInsertTime($insertTime)
  {
    $this->insertTime = $insertTime;
  }
  public function getInsertTime()
  {
    return $this->insertTime;
  }
  public function setLayout($layout)
  {
    $this->layout = $layout;
  }
  public function getLayout()
  {
    return $this->layout;
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

class Google_Service_DeploymentManager_ManifestsListResponse extends Google_Collection
{
  protected $collection_key = 'manifests';
  protected $internal_gapi_mappings = array(
  );
  protected $manifestsType = 'Google_Service_DeploymentManager_Manifest';
  protected $manifestsDataType = 'array';
  public $nextPageToken;


  public function setManifests($manifests)
  {
    $this->manifests = $manifests;
  }
  public function getManifests()
  {
    return $this->manifests;
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

class Google_Service_DeploymentManager_Operation extends Google_Collection
{
  protected $collection_key = 'warnings';
  protected $internal_gapi_mappings = array(
  );
  public $clientOperationId;
  public $creationTimestamp;
  public $endTime;
  protected $errorType = 'Google_Service_DeploymentManager_OperationError';
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
  protected $warningsType = 'Google_Service_DeploymentManager_OperationWarnings';
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
  public function setError(Google_Service_DeploymentManager_OperationError $error)
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

class Google_Service_DeploymentManager_OperationError extends Google_Collection
{
  protected $collection_key = 'errors';
  protected $internal_gapi_mappings = array(
  );
  protected $errorsType = 'Google_Service_DeploymentManager_OperationErrorErrors';
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

class Google_Service_DeploymentManager_OperationErrorErrors extends Google_Model
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

class Google_Service_DeploymentManager_OperationWarnings extends Google_Collection
{
  protected $collection_key = 'data';
  protected $internal_gapi_mappings = array(
  );
  public $code;
  protected $dataType = 'Google_Service_DeploymentManager_OperationWarningsData';
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

class Google_Service_DeploymentManager_OperationWarningsData extends Google_Model
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

class Google_Service_DeploymentManager_OperationsListResponse extends Google_Collection
{
  protected $collection_key = 'operations';
  protected $internal_gapi_mappings = array(
  );
  public $nextPageToken;
  protected $operationsType = 'Google_Service_DeploymentManager_Operation';
  protected $operationsDataType = 'array';


  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  public function setOperations($operations)
  {
    $this->operations = $operations;
  }
  public function getOperations()
  {
    return $this->operations;
  }
}

class Google_Service_DeploymentManager_ResourceUpdate extends Google_Collection
{
  protected $collection_key = 'warnings';
  protected $internal_gapi_mappings = array(
  );
  protected $errorType = 'Google_Service_DeploymentManager_ResourceUpdateError';
  protected $errorDataType = '';
  public $finalProperties;
  public $intent;
  public $manifest;
  public $properties;
  public $state;
  protected $warningsType = 'Google_Service_DeploymentManager_ResourceUpdateWarnings';
  protected $warningsDataType = 'array';


  public function setError(Google_Service_DeploymentManager_ResourceUpdateError $error)
  {
    $this->error = $error;
  }
  public function getError()
  {
    return $this->error;
  }
  public function setFinalProperties($finalProperties)
  {
    $this->finalProperties = $finalProperties;
  }
  public function getFinalProperties()
  {
    return $this->finalProperties;
  }
  public function setIntent($intent)
  {
    $this->intent = $intent;
  }
  public function getIntent()
  {
    return $this->intent;
  }
  public function setManifest($manifest)
  {
    $this->manifest = $manifest;
  }
  public function getManifest()
  {
    return $this->manifest;
  }
  public function setProperties($properties)
  {
    $this->properties = $properties;
  }
  public function getProperties()
  {
    return $this->properties;
  }
  public function setState($state)
  {
    $this->state = $state;
  }
  public function getState()
  {
    return $this->state;
  }
  public function setWarnings($warnings)
  {
    $this->warnings = $warnings;
  }
  public function getWarnings()
  {
    return $this->warnings;
  }
}

class Google_Service_DeploymentManager_ResourceUpdateError extends Google_Collection
{
  protected $collection_key = 'errors';
  protected $internal_gapi_mappings = array(
  );
  protected $errorsType = 'Google_Service_DeploymentManager_ResourceUpdateErrorErrors';
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

class Google_Service_DeploymentManager_ResourceUpdateErrorErrors extends Google_Model
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

class Google_Service_DeploymentManager_ResourceUpdateWarnings extends Google_Collection
{
  protected $collection_key = 'data';
  protected $internal_gapi_mappings = array(
  );
  public $code;
  protected $dataType = 'Google_Service_DeploymentManager_ResourceUpdateWarningsData';
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

class Google_Service_DeploymentManager_ResourceUpdateWarningsData extends Google_Model
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

class Google_Service_DeploymentManager_ResourcesListResponse extends Google_Collection
{
  protected $collection_key = 'resources';
  protected $internal_gapi_mappings = array(
  );
  public $nextPageToken;
  protected $resourcesType = 'Google_Service_DeploymentManager_DeploymentmanagerResource';
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

class Google_Service_DeploymentManager_TargetConfiguration extends Google_Collection
{
  protected $collection_key = 'imports';
  protected $internal_gapi_mappings = array(
  );
  protected $configType = 'Google_Service_DeploymentManager_ConfigFile';
  protected $configDataType = '';
  protected $importsType = 'Google_Service_DeploymentManager_ImportFile';
  protected $importsDataType = 'array';


  public function setConfig(Google_Service_DeploymentManager_ConfigFile $config)
  {
    $this->config = $config;
  }
  public function getConfig()
  {
    return $this->config;
  }
  public function setImports($imports)
  {
    $this->imports = $imports;
  }
  public function getImports()
  {
    return $this->imports;
  }
}

class Google_Service_DeploymentManager_Type extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $id;
  public $insertTime;
  public $name;
  public $selfLink;


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

class Google_Service_DeploymentManager_TypesListResponse extends Google_Collection
{
  protected $collection_key = 'types';
  protected $internal_gapi_mappings = array(
  );
  public $nextPageToken;
  protected $typesType = 'Google_Service_DeploymentManager_Type';
  protected $typesDataType = 'array';


  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  public function setTypes($types)
  {
    $this->types = $types;
  }
  public function getTypes()
  {
    return $this->types;
  }
}
