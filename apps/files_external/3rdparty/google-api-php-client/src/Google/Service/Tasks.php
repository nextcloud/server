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
 * Service definition for Tasks (v1).
 *
 * <p>
 * Lets you manage your tasks and task lists.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://developers.google.com/google-apps/tasks/firstapp" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_Tasks extends Google_Service
{
  /** Manage your tasks. */
  const TASKS =
      "https://www.googleapis.com/auth/tasks";
  /** View your tasks. */
  const TASKS_READONLY =
      "https://www.googleapis.com/auth/tasks.readonly";

  public $tasklists;
  public $tasks;
  

  /**
   * Constructs the internal representation of the Tasks service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://www.googleapis.com/';
    $this->servicePath = 'tasks/v1/';
    $this->version = 'v1';
    $this->serviceName = 'tasks';

    $this->tasklists = new Google_Service_Tasks_Tasklists_Resource(
        $this,
        $this->serviceName,
        'tasklists',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'users/@me/lists/{tasklist}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'tasklist' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'users/@me/lists/{tasklist}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'tasklist' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'users/@me/lists',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),'list' => array(
              'path' => 'users/@me/lists',
              'httpMethod' => 'GET',
              'parameters' => array(
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'patch' => array(
              'path' => 'users/@me/lists/{tasklist}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'tasklist' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'users/@me/lists/{tasklist}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'tasklist' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->tasks = new Google_Service_Tasks_Tasks_Resource(
        $this,
        $this->serviceName,
        'tasks',
        array(
          'methods' => array(
            'clear' => array(
              'path' => 'lists/{tasklist}/clear',
              'httpMethod' => 'POST',
              'parameters' => array(
                'tasklist' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'delete' => array(
              'path' => 'lists/{tasklist}/tasks/{task}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'tasklist' => array(
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
              'path' => 'lists/{tasklist}/tasks/{task}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'tasklist' => array(
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
              'path' => 'lists/{tasklist}/tasks',
              'httpMethod' => 'POST',
              'parameters' => array(
                'tasklist' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'parent' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'previous' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'list' => array(
              'path' => 'lists/{tasklist}/tasks',
              'httpMethod' => 'GET',
              'parameters' => array(
                'tasklist' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'dueMax' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'showDeleted' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'updatedMin' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'completedMin' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'showCompleted' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'completedMax' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'showHidden' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'dueMin' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'move' => array(
              'path' => 'lists/{tasklist}/tasks/{task}/move',
              'httpMethod' => 'POST',
              'parameters' => array(
                'tasklist' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'task' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'parent' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'previous' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'patch' => array(
              'path' => 'lists/{tasklist}/tasks/{task}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'tasklist' => array(
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
            ),'update' => array(
              'path' => 'lists/{tasklist}/tasks/{task}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'tasklist' => array(
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
            ),
          )
        )
    );
  }
}


/**
 * The "tasklists" collection of methods.
 * Typical usage is:
 *  <code>
 *   $tasksService = new Google_Service_Tasks(...);
 *   $tasklists = $tasksService->tasklists;
 *  </code>
 */
class Google_Service_Tasks_Tasklists_Resource extends Google_Service_Resource
{

  /**
   * Deletes the authenticated user's specified task list. (tasklists.delete)
   *
   * @param string $tasklist Task list identifier.
   * @param array $optParams Optional parameters.
   */
  public function delete($tasklist, $optParams = array())
  {
    $params = array('tasklist' => $tasklist);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Returns the authenticated user's specified task list. (tasklists.get)
   *
   * @param string $tasklist Task list identifier.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Tasks_TaskList
   */
  public function get($tasklist, $optParams = array())
  {
    $params = array('tasklist' => $tasklist);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Tasks_TaskList");
  }

  /**
   * Creates a new task list and adds it to the authenticated user's task lists.
   * (tasklists.insert)
   *
   * @param Google_TaskList $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Tasks_TaskList
   */
  public function insert(Google_Service_Tasks_TaskList $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Tasks_TaskList");
  }

  /**
   * Returns all the authenticated user's task lists. (tasklists.listTasklists)
   *
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken Token specifying the result page to return.
   * Optional.
   * @opt_param string maxResults Maximum number of task lists returned on one
   * page. Optional. The default is 100.
   * @return Google_Service_Tasks_TaskLists
   */
  public function listTasklists($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Tasks_TaskLists");
  }

  /**
   * Updates the authenticated user's specified task list. This method supports
   * patch semantics. (tasklists.patch)
   *
   * @param string $tasklist Task list identifier.
   * @param Google_TaskList $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Tasks_TaskList
   */
  public function patch($tasklist, Google_Service_Tasks_TaskList $postBody, $optParams = array())
  {
    $params = array('tasklist' => $tasklist, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Tasks_TaskList");
  }

  /**
   * Updates the authenticated user's specified task list. (tasklists.update)
   *
   * @param string $tasklist Task list identifier.
   * @param Google_TaskList $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Tasks_TaskList
   */
  public function update($tasklist, Google_Service_Tasks_TaskList $postBody, $optParams = array())
  {
    $params = array('tasklist' => $tasklist, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Tasks_TaskList");
  }
}

/**
 * The "tasks" collection of methods.
 * Typical usage is:
 *  <code>
 *   $tasksService = new Google_Service_Tasks(...);
 *   $tasks = $tasksService->tasks;
 *  </code>
 */
class Google_Service_Tasks_Tasks_Resource extends Google_Service_Resource
{

  /**
   * Clears all completed tasks from the specified task list. The affected tasks
   * will be marked as 'hidden' and no longer be returned by default when
   * retrieving all tasks for a task list. (tasks.clear)
   *
   * @param string $tasklist Task list identifier.
   * @param array $optParams Optional parameters.
   */
  public function clear($tasklist, $optParams = array())
  {
    $params = array('tasklist' => $tasklist);
    $params = array_merge($params, $optParams);
    return $this->call('clear', array($params));
  }

  /**
   * Deletes the specified task from the task list. (tasks.delete)
   *
   * @param string $tasklist Task list identifier.
   * @param string $task Task identifier.
   * @param array $optParams Optional parameters.
   */
  public function delete($tasklist, $task, $optParams = array())
  {
    $params = array('tasklist' => $tasklist, 'task' => $task);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Returns the specified task. (tasks.get)
   *
   * @param string $tasklist Task list identifier.
   * @param string $task Task identifier.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Tasks_Task
   */
  public function get($tasklist, $task, $optParams = array())
  {
    $params = array('tasklist' => $tasklist, 'task' => $task);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Tasks_Task");
  }

  /**
   * Creates a new task on the specified task list. (tasks.insert)
   *
   * @param string $tasklist Task list identifier.
   * @param Google_Task $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string parent Parent task identifier. If the task is created at
   * the top level, this parameter is omitted. Optional.
   * @opt_param string previous Previous sibling task identifier. If the task is
   * created at the first position among its siblings, this parameter is omitted.
   * Optional.
   * @return Google_Service_Tasks_Task
   */
  public function insert($tasklist, Google_Service_Tasks_Task $postBody, $optParams = array())
  {
    $params = array('tasklist' => $tasklist, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Tasks_Task");
  }

  /**
   * Returns all tasks in the specified task list. (tasks.listTasks)
   *
   * @param string $tasklist Task list identifier.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string dueMax Upper bound for a task's due date (as a RFC 3339
   * timestamp) to filter by. Optional. The default is not to filter by due date.
   * @opt_param bool showDeleted Flag indicating whether deleted tasks are
   * returned in the result. Optional. The default is False.
   * @opt_param string updatedMin Lower bound for a task's last modification time
   * (as a RFC 3339 timestamp) to filter by. Optional. The default is not to
   * filter by last modification time.
   * @opt_param string completedMin Lower bound for a task's completion date (as a
   * RFC 3339 timestamp) to filter by. Optional. The default is not to filter by
   * completion date.
   * @opt_param string maxResults Maximum number of task lists returned on one
   * page. Optional. The default is 100.
   * @opt_param bool showCompleted Flag indicating whether completed tasks are
   * returned in the result. Optional. The default is True.
   * @opt_param string pageToken Token specifying the result page to return.
   * Optional.
   * @opt_param string completedMax Upper bound for a task's completion date (as a
   * RFC 3339 timestamp) to filter by. Optional. The default is not to filter by
   * completion date.
   * @opt_param bool showHidden Flag indicating whether hidden tasks are returned
   * in the result. Optional. The default is False.
   * @opt_param string dueMin Lower bound for a task's due date (as a RFC 3339
   * timestamp) to filter by. Optional. The default is not to filter by due date.
   * @return Google_Service_Tasks_Tasks
   */
  public function listTasks($tasklist, $optParams = array())
  {
    $params = array('tasklist' => $tasklist);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Tasks_Tasks");
  }

  /**
   * Moves the specified task to another position in the task list. This can
   * include putting it as a child task under a new parent and/or move it to a
   * different position among its sibling tasks. (tasks.move)
   *
   * @param string $tasklist Task list identifier.
   * @param string $task Task identifier.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string parent New parent task identifier. If the task is moved to
   * the top level, this parameter is omitted. Optional.
   * @opt_param string previous New previous sibling task identifier. If the task
   * is moved to the first position among its siblings, this parameter is omitted.
   * Optional.
   * @return Google_Service_Tasks_Task
   */
  public function move($tasklist, $task, $optParams = array())
  {
    $params = array('tasklist' => $tasklist, 'task' => $task);
    $params = array_merge($params, $optParams);
    return $this->call('move', array($params), "Google_Service_Tasks_Task");
  }

  /**
   * Updates the specified task. This method supports patch semantics.
   * (tasks.patch)
   *
   * @param string $tasklist Task list identifier.
   * @param string $task Task identifier.
   * @param Google_Task $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Tasks_Task
   */
  public function patch($tasklist, $task, Google_Service_Tasks_Task $postBody, $optParams = array())
  {
    $params = array('tasklist' => $tasklist, 'task' => $task, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Tasks_Task");
  }

  /**
   * Updates the specified task. (tasks.update)
   *
   * @param string $tasklist Task list identifier.
   * @param string $task Task identifier.
   * @param Google_Task $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Tasks_Task
   */
  public function update($tasklist, $task, Google_Service_Tasks_Task $postBody, $optParams = array())
  {
    $params = array('tasklist' => $tasklist, 'task' => $task, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Tasks_Task");
  }
}




class Google_Service_Tasks_Task extends Google_Collection
{
  protected $collection_key = 'links';
  protected $internal_gapi_mappings = array(
  );
  public $completed;
  public $deleted;
  public $due;
  public $etag;
  public $hidden;
  public $id;
  public $kind;
  protected $linksType = 'Google_Service_Tasks_TaskLinks';
  protected $linksDataType = 'array';
  public $notes;
  public $parent;
  public $position;
  public $selfLink;
  public $status;
  public $title;
  public $updated;


  public function setCompleted($completed)
  {
    $this->completed = $completed;
  }
  public function getCompleted()
  {
    return $this->completed;
  }
  public function setDeleted($deleted)
  {
    $this->deleted = $deleted;
  }
  public function getDeleted()
  {
    return $this->deleted;
  }
  public function setDue($due)
  {
    $this->due = $due;
  }
  public function getDue()
  {
    return $this->due;
  }
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setHidden($hidden)
  {
    $this->hidden = $hidden;
  }
  public function getHidden()
  {
    return $this->hidden;
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
  public function setLinks($links)
  {
    $this->links = $links;
  }
  public function getLinks()
  {
    return $this->links;
  }
  public function setNotes($notes)
  {
    $this->notes = $notes;
  }
  public function getNotes()
  {
    return $this->notes;
  }
  public function setParent($parent)
  {
    $this->parent = $parent;
  }
  public function getParent()
  {
    return $this->parent;
  }
  public function setPosition($position)
  {
    $this->position = $position;
  }
  public function getPosition()
  {
    return $this->position;
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
  public function setTitle($title)
  {
    $this->title = $title;
  }
  public function getTitle()
  {
    return $this->title;
  }
  public function setUpdated($updated)
  {
    $this->updated = $updated;
  }
  public function getUpdated()
  {
    return $this->updated;
  }
}

class Google_Service_Tasks_TaskLinks extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $description;
  public $link;
  public $type;


  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setLink($link)
  {
    $this->link = $link;
  }
  public function getLink()
  {
    return $this->link;
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

class Google_Service_Tasks_TaskList extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $id;
  public $kind;
  public $selfLink;
  public $title;
  public $updated;


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
  public function setSelfLink($selfLink)
  {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink()
  {
    return $this->selfLink;
  }
  public function setTitle($title)
  {
    $this->title = $title;
  }
  public function getTitle()
  {
    return $this->title;
  }
  public function setUpdated($updated)
  {
    $this->updated = $updated;
  }
  public function getUpdated()
  {
    return $this->updated;
  }
}

class Google_Service_Tasks_TaskLists extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  protected $itemsType = 'Google_Service_Tasks_TaskList';
  protected $itemsDataType = 'array';
  public $kind;
  public $nextPageToken;


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
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
}

class Google_Service_Tasks_Tasks extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  protected $itemsType = 'Google_Service_Tasks_Task';
  protected $itemsDataType = 'array';
  public $kind;
  public $nextPageToken;


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
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
}
