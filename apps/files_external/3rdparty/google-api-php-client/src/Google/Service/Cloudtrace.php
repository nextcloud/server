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
 * Service definition for Cloudtrace (v1).
 *
 * <p>
 * The Google Cloud Trace API provides services for reading and writing runtime
 * trace data for Cloud applications.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://cloud.google.com/tools/cloud-trace" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_Cloudtrace extends Google_Service
{
  /** View and manage your data across Google Cloud Platform services. */
  const CLOUD_PLATFORM =
      "https://www.googleapis.com/auth/cloud-platform";

  public $projects;
  public $projects_traces;
  public $v1;
  

  /**
   * Constructs the internal representation of the Cloudtrace service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://cloudtrace.googleapis.com/';
    $this->servicePath = '';
    $this->version = 'v1';
    $this->serviceName = 'cloudtrace';

    $this->projects = new Google_Service_Cloudtrace_Projects_Resource(
        $this,
        $this->serviceName,
        'projects',
        array(
          'methods' => array(
            'patchTraces' => array(
              'path' => 'v1/projects/{projectId}/traces',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'projectId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->projects_traces = new Google_Service_Cloudtrace_ProjectsTraces_Resource(
        $this,
        $this->serviceName,
        'traces',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'v1/projects/{projectId}/traces/{traceId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'projectId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'traceId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'v1/projects/{projectId}/traces',
              'httpMethod' => 'GET',
              'parameters' => array(
                'projectId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'orderBy' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'pageSize' => array(
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
                'startTime' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'endTime' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'view' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->v1 = new Google_Service_Cloudtrace_V1_Resource(
        $this,
        $this->serviceName,
        'v1',
        array(
          'methods' => array(
            'getDiscovery' => array(
              'path' => 'v1/discovery',
              'httpMethod' => 'GET',
              'parameters' => array(
                'labels' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'version' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'args' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'format' => array(
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
 * The "projects" collection of methods.
 * Typical usage is:
 *  <code>
 *   $cloudtraceService = new Google_Service_Cloudtrace(...);
 *   $projects = $cloudtraceService->projects;
 *  </code>
 */
class Google_Service_Cloudtrace_Projects_Resource extends Google_Service_Resource
{

  /**
   * Updates the existing traces specified by PatchTracesRequest and inserts the
   * new traces. Any existing trace or span fields included in an update are
   * overwritten by the update, and any additional fields in an update are merged
   * with the existing trace data. (projects.patchTraces)
   *
   * @param string $projectId The project id of the trace to patch.
   * @param Google_Traces $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Cloudtrace_Empty
   */
  public function patchTraces($projectId, Google_Service_Cloudtrace_Traces $postBody, $optParams = array())
  {
    $params = array('projectId' => $projectId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patchTraces', array($params), "Google_Service_Cloudtrace_Empty");
  }
}

/**
 * The "traces" collection of methods.
 * Typical usage is:
 *  <code>
 *   $cloudtraceService = new Google_Service_Cloudtrace(...);
 *   $traces = $cloudtraceService->traces;
 *  </code>
 */
class Google_Service_Cloudtrace_ProjectsTraces_Resource extends Google_Service_Resource
{

  /**
   * Gets one trace by id. (traces.get)
   *
   * @param string $projectId The project id of the trace to return.
   * @param string $traceId The trace id of the trace to return.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Cloudtrace_Trace
   */
  public function get($projectId, $traceId, $optParams = array())
  {
    $params = array('projectId' => $projectId, 'traceId' => $traceId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Cloudtrace_Trace");
  }

  /**
   * List traces matching the filter expression. (traces.listProjectsTraces)
   *
   * @param string $projectId The stringified-version of the project id.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string orderBy The trace field used to establish the order of
   * traces returned by the ListTraces method. Possible options are: trace_id name
   * (name field of root span) duration (different between end_time and start_time
   * fields of root span) start (start_time field of root span) Descending order
   * can be specified by appending "desc" to the sort field: name desc Only one
   * sort field is permitted, though this may change in the future.
   * @opt_param int pageSize Maximum number of topics to return. If not specified
   * or <= 0, the implementation will select a reasonable value. The implemenation
   * may always return fewer than the requested page_size.
   * @opt_param string filter An optional filter for the request.
   * @opt_param string pageToken The token identifying the page of results to
   * return from the ListTraces method. If present, this value is should be taken
   * from the next_page_token field of a previous ListTracesResponse.
   * @opt_param string startTime End of the time interval (inclusive).
   * @opt_param string endTime Start of the time interval (exclusive).
   * @opt_param string view ViewType specifies the projection of the result.
   * @return Google_Service_Cloudtrace_ListTracesResponse
   */
  public function listProjectsTraces($projectId, $optParams = array())
  {
    $params = array('projectId' => $projectId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Cloudtrace_ListTracesResponse");
  }
}

/**
 * The "v1" collection of methods.
 * Typical usage is:
 *  <code>
 *   $cloudtraceService = new Google_Service_Cloudtrace(...);
 *   $v1 = $cloudtraceService->v1;
 *  </code>
 */
class Google_Service_Cloudtrace_V1_Resource extends Google_Service_Resource
{

  /**
   * Returns a discovery document in the specified `format`. The typeurl in the
   * returned google.protobuf.Any value depends on the requested format.
   * (v1.getDiscovery)
   *
   * @param array $optParams Optional parameters.
   *
   * @opt_param string labels A list of labels (like visibility) influencing the
   * scope of the requested doc.
   * @opt_param string version The API version of the requested discovery doc.
   * @opt_param string args Any additional arguments.
   * @opt_param string format The format requested for discovery.
   */
  public function getDiscovery($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('getDiscovery', array($params));
  }
}




class Google_Service_Cloudtrace_Empty extends Google_Model
{
}

class Google_Service_Cloudtrace_ListTracesResponse extends Google_Collection
{
  protected $collection_key = 'traces';
  protected $internal_gapi_mappings = array(
  );
  public $nextPageToken;
  protected $tracesType = 'Google_Service_Cloudtrace_Trace';
  protected $tracesDataType = 'array';


  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  public function setTraces($traces)
  {
    $this->traces = $traces;
  }
  public function getTraces()
  {
    return $this->traces;
  }
}

class Google_Service_Cloudtrace_Trace extends Google_Collection
{
  protected $collection_key = 'spans';
  protected $internal_gapi_mappings = array(
  );
  public $projectId;
  protected $spansType = 'Google_Service_Cloudtrace_TraceSpan';
  protected $spansDataType = 'array';
  public $traceId;


  public function setProjectId($projectId)
  {
    $this->projectId = $projectId;
  }
  public function getProjectId()
  {
    return $this->projectId;
  }
  public function setSpans($spans)
  {
    $this->spans = $spans;
  }
  public function getSpans()
  {
    return $this->spans;
  }
  public function setTraceId($traceId)
  {
    $this->traceId = $traceId;
  }
  public function getTraceId()
  {
    return $this->traceId;
  }
}

class Google_Service_Cloudtrace_TraceSpan extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $endTime;
  public $kind;
  public $labels;
  public $name;
  public $parentSpanId;
  public $spanId;
  public $startTime;


  public function setEndTime($endTime)
  {
    $this->endTime = $endTime;
  }
  public function getEndTime()
  {
    return $this->endTime;
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
  public function setParentSpanId($parentSpanId)
  {
    $this->parentSpanId = $parentSpanId;
  }
  public function getParentSpanId()
  {
    return $this->parentSpanId;
  }
  public function setSpanId($spanId)
  {
    $this->spanId = $spanId;
  }
  public function getSpanId()
  {
    return $this->spanId;
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

class Google_Service_Cloudtrace_TraceSpanLabels extends Google_Model
{
}

class Google_Service_Cloudtrace_Traces extends Google_Collection
{
  protected $collection_key = 'traces';
  protected $internal_gapi_mappings = array(
  );
  protected $tracesType = 'Google_Service_Cloudtrace_Trace';
  protected $tracesDataType = 'array';


  public function setTraces($traces)
  {
    $this->traces = $traces;
  }
  public function getTraces()
  {
    return $this->traces;
  }
}
