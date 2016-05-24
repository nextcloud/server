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
 * Service definition for Taskqueue (v1beta2).
 *
 * <p>
 * Lets you access a Google App Engine Pull Task Queue over REST.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://developers.google.com/appengine/docs/python/taskqueue/rest" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_Taskqueue extends Google_Service
{
  /** Manage your Tasks and Taskqueues. */
  const TASKQUEUE =
      "https://www.googleapis.com/auth/taskqueue";
  /** Consume Tasks from your Taskqueues. */
  const TASKQUEUE_CONSUMER =
      "https://www.googleapis.com/auth/taskqueue.consumer";

  public $taskqueues;
  public $tasks;
  

  /**
   * Constructs the internal representation of the Taskqueue service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://www.googleapis.com/';
    $this->servicePath = 'taskqueue/v1beta2/projects/';
    $this->version = 'v1beta2';
    $this->serviceName = 'taskqueue';

    $this->taskqueues = new Google_Service_Taskqueue_Taskqueues_Resource(
        $this,
        $this->serviceName,
        'taskqueues',
        array(
          'methods' => array(
            'get' => array(
              'path' => '{project}/taskqueues/{taskqueue}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'taskqueue' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'getStats' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
              ),
            ),
          )
        )
    );
    $this->tasks = new Google_Service_Taskqueue_Tasks_Resource(
        $this,
        $this->serviceName,
        'tasks',
        array(
          'methods' => array(
            'delete' => array(
              'path' => '{project}/taskqueues/{taskqueue}/tasks/{task}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'taskqueue' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'task' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => '{project}/taskqueues/{taskqueue}/tasks/{task}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'taskqueue' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'task' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => '{project}/taskqueues/{taskqueue}/tasks',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'taskqueue' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'lease' => array(
              'path' => '{project}/taskqueues/{taskqueue}/tasks/lease',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'taskqueue' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'numTasks' => array(
                  'location' => 'query',
                  'type' => 'integer',
                  'required' => true,
                ),
                'leaseSecs' => array(
                  'location' => 'query',
                  'type' => 'integer',
                  'required' => true,
                ),
                'groupByTag' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'tag' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'list' => array(
              'path' => '{project}/taskqueues/{taskqueue}/tasks',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'taskqueue' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'patch' => array(
              'path' => '{project}/taskqueues/{taskqueue}/tasks/{task}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'taskqueue' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'task' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'newLeaseSeconds' => array(
                  'location' => 'query',
                  'type' => 'integer',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => '{project}/taskqueues/{taskqueue}/tasks/{task}',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'taskqueue' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'task' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'newLeaseSeconds' => array(
                  'location' => 'query',
                  'type' => 'integer',
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
 * The "taskqueues" collection of methods.
 * Typical usage is:
 *  <code>
 *   $taskqueueService = new Google_Service_Taskqueue(...);
 *   $taskqueues = $taskqueueService->taskqueues;
 *  </code>
 */
class Google_Service_Taskqueue_Taskqueues_Resource extends Google_Service_Resource
{

  /**
   * Get detailed information about a TaskQueue. (taskqueues.get)
   *
   * @param string $project The project under which the queue lies.
   * @param string $taskqueue The id of the taskqueue to get the properties of.
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool getStats Whether to get stats. Optional.
   * @return Google_Service_Taskqueue_TaskQueue
   */
  public function get($project, $taskqueue, $optParams = array())
  {
    $params = array('project' => $project, 'taskqueue' => $taskqueue);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Taskqueue_TaskQueue");
  }
}

/**
 * The "tasks" collection of methods.
 * Typical usage is:
 *  <code>
 *   $taskqueueService = new Google_Service_Taskqueue(...);
 *   $tasks = $taskqueueService->tasks;
 *  </code>
 */
class Google_Service_Taskqueue_Tasks_Resource extends Google_Service_Resource
{

  /**
   * Delete a task from a TaskQueue. (tasks.delete)
   *
   * @param string $project The project under which the queue lies.
   * @param string $taskqueue The taskqueue to delete a task from.
   * @param string $task The id of the task to delete.
   * @param array $optParams Optional parameters.
   */
  public function delete($project, $taskqueue, $task, $optParams = array())
  {
    $params = array('project' => $project, 'taskqueue' => $taskqueue, 'task' => $task);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Get a particular task from a TaskQueue. (tasks.get)
   *
   * @param string $project The project under which the queue lies.
   * @param string $taskqueue The taskqueue in which the task belongs.
   * @param string $task The task to get properties of.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Taskqueue_Task
   */
  public function get($project, $taskqueue, $task, $optParams = array())
  {
    $params = array('project' => $project, 'taskqueue' => $taskqueue, 'task' => $task);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Taskqueue_Task");
  }

  /**
   * Insert a new task in a TaskQueue (tasks.insert)
   *
   * @param string $project The project under which the queue lies
   * @param string $taskqueue The taskqueue to insert the task into
   * @param Google_Task $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Taskqueue_Task
   */
  public function insert($project, $taskqueue, Google_Service_Taskqueue_Task $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'taskqueue' => $taskqueue, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Taskqueue_Task");
  }

  /**
   * Lease 1 or more tasks from a TaskQueue. (tasks.lease)
   *
   * @param string $project The project under which the queue lies.
   * @param string $taskqueue The taskqueue to lease a task from.
   * @param int $numTasks The number of tasks to lease.
   * @param int $leaseSecs The lease in seconds.
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool groupByTag When true, all returned tasks will have the same
   * tag
   * @opt_param string tag The tag allowed for tasks in the response. Must only be
   * specified if group_by_tag is true. If group_by_tag is true and tag is not
   * specified the tag will be that of the oldest task by eta, i.e. the first
   * available tag
   * @return Google_Service_Taskqueue_Tasks
   */
  public function lease($project, $taskqueue, $numTasks, $leaseSecs, $optParams = array())
  {
    $params = array('project' => $project, 'taskqueue' => $taskqueue, 'numTasks' => $numTasks, 'leaseSecs' => $leaseSecs);
    $params = array_merge($params, $optParams);
    return $this->call('lease', array($params), "Google_Service_Taskqueue_Tasks");
  }

  /**
   * List Tasks in a TaskQueue (tasks.listTasks)
   *
   * @param string $project The project under which the queue lies.
   * @param string $taskqueue The id of the taskqueue to list tasks from.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Taskqueue_Tasks2
   */
  public function listTasks($project, $taskqueue, $optParams = array())
  {
    $params = array('project' => $project, 'taskqueue' => $taskqueue);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Taskqueue_Tasks2");
  }

  /**
   * Update tasks that are leased out of a TaskQueue. This method supports patch
   * semantics. (tasks.patch)
   *
   * @param string $project The project under which the queue lies.
   * @param string $taskqueue
   * @param string $task
   * @param int $newLeaseSeconds The new lease in seconds.
   * @param Google_Task $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Taskqueue_Task
   */
  public function patch($project, $taskqueue, $task, $newLeaseSeconds, Google_Service_Taskqueue_Task $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'taskqueue' => $taskqueue, 'task' => $task, 'newLeaseSeconds' => $newLeaseSeconds, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Taskqueue_Task");
  }

  /**
   * Update tasks that are leased out of a TaskQueue. (tasks.update)
   *
   * @param string $project The project under which the queue lies.
   * @param string $taskqueue
   * @param string $task
   * @param int $newLeaseSeconds The new lease in seconds.
   * @param Google_Task $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Taskqueue_Task
   */
  public function update($project, $taskqueue, $task, $newLeaseSeconds, Google_Service_Taskqueue_Task $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'taskqueue' => $taskqueue, 'task' => $task, 'newLeaseSeconds' => $newLeaseSeconds, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Taskqueue_Task");
  }
}




class Google_Service_Taskqueue_Task extends Google_Model
{
  protected $internal_gapi_mappings = array(
        "retryCount" => "retry_count",
  );
  public $enqueueTimestamp;
  public $id;
  public $kind;
  public $leaseTimestamp;
  public $payloadBase64;
  public $queueName;
  public $retryCount;
  public $tag;


  public function setEnqueueTimestamp($enqueueTimestamp)
  {
    $this->enqueueTimestamp = $enqueueTimestamp;
  }
  public function getEnqueueTimestamp()
  {
    return $this->enqueueTimestamp;
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
  public function setLeaseTimestamp($leaseTimestamp)
  {
    $this->leaseTimestamp = $leaseTimestamp;
  }
  public function getLeaseTimestamp()
  {
    return $this->leaseTimestamp;
  }
  public function setPayloadBase64($payloadBase64)
  {
    $this->payloadBase64 = $payloadBase64;
  }
  public function getPayloadBase64()
  {
    return $this->payloadBase64;
  }
  public function setQueueName($queueName)
  {
    $this->queueName = $queueName;
  }
  public function getQueueName()
  {
    return $this->queueName;
  }
  public function setRetryCount($retryCount)
  {
    $this->retryCount = $retryCount;
  }
  public function getRetryCount()
  {
    return $this->retryCount;
  }
  public function setTag($tag)
  {
    $this->tag = $tag;
  }
  public function getTag()
  {
    return $this->tag;
  }
}

class Google_Service_Taskqueue_TaskQueue extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $aclType = 'Google_Service_Taskqueue_TaskQueueAcl';
  protected $aclDataType = '';
  public $id;
  public $kind;
  public $maxLeases;
  protected $statsType = 'Google_Service_Taskqueue_TaskQueueStats';
  protected $statsDataType = '';


  public function setAcl(Google_Service_Taskqueue_TaskQueueAcl $acl)
  {
    $this->acl = $acl;
  }
  public function getAcl()
  {
    return $this->acl;
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
  public function setMaxLeases($maxLeases)
  {
    $this->maxLeases = $maxLeases;
  }
  public function getMaxLeases()
  {
    return $this->maxLeases;
  }
  public function setStats(Google_Service_Taskqueue_TaskQueueStats $stats)
  {
    $this->stats = $stats;
  }
  public function getStats()
  {
    return $this->stats;
  }
}

class Google_Service_Taskqueue_TaskQueueAcl extends Google_Collection
{
  protected $collection_key = 'producerEmails';
  protected $internal_gapi_mappings = array(
  );
  public $adminEmails;
  public $consumerEmails;
  public $producerEmails;


  public function setAdminEmails($adminEmails)
  {
    $this->adminEmails = $adminEmails;
  }
  public function getAdminEmails()
  {
    return $this->adminEmails;
  }
  public function setConsumerEmails($consumerEmails)
  {
    $this->consumerEmails = $consumerEmails;
  }
  public function getConsumerEmails()
  {
    return $this->consumerEmails;
  }
  public function setProducerEmails($producerEmails)
  {
    $this->producerEmails = $producerEmails;
  }
  public function getProducerEmails()
  {
    return $this->producerEmails;
  }
}

class Google_Service_Taskqueue_TaskQueueStats extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $leasedLastHour;
  public $leasedLastMinute;
  public $oldestTask;
  public $totalTasks;


  public function setLeasedLastHour($leasedLastHour)
  {
    $this->leasedLastHour = $leasedLastHour;
  }
  public function getLeasedLastHour()
  {
    return $this->leasedLastHour;
  }
  public function setLeasedLastMinute($leasedLastMinute)
  {
    $this->leasedLastMinute = $leasedLastMinute;
  }
  public function getLeasedLastMinute()
  {
    return $this->leasedLastMinute;
  }
  public function setOldestTask($oldestTask)
  {
    $this->oldestTask = $oldestTask;
  }
  public function getOldestTask()
  {
    return $this->oldestTask;
  }
  public function setTotalTasks($totalTasks)
  {
    $this->totalTasks = $totalTasks;
  }
  public function getTotalTasks()
  {
    return $this->totalTasks;
  }
}

class Google_Service_Taskqueue_Tasks extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  protected $itemsType = 'Google_Service_Taskqueue_Task';
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

class Google_Service_Taskqueue_Tasks2 extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  protected $itemsType = 'Google_Service_Taskqueue_Task';
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
