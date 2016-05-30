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
 * Service definition for Pubsub (v1beta1).
 *
 * <p>
 * Provides reliable, many-to-many, asynchronous messaging between applications.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://developers.google.com/pubsub/v1beta1" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_Pubsub extends Google_Service
{
  /** View and manage your data across Google Cloud Platform services. */
  const CLOUD_PLATFORM =
      "https://www.googleapis.com/auth/cloud-platform";
  /** View and manage Pub/Sub topics and subscriptions. */
  const PUBSUB =
      "https://www.googleapis.com/auth/pubsub";

  public $subscriptions;
  public $topics;
  

  /**
   * Constructs the internal representation of the Pubsub service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://www.googleapis.com/';
    $this->servicePath = 'pubsub/v1beta1/';
    $this->version = 'v1beta1';
    $this->serviceName = 'pubsub';

    $this->subscriptions = new Google_Service_Pubsub_Subscriptions_Resource(
        $this,
        $this->serviceName,
        'subscriptions',
        array(
          'methods' => array(
            'acknowledge' => array(
              'path' => 'subscriptions/acknowledge',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),'create' => array(
              'path' => 'subscriptions',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),'delete' => array(
              'path' => 'subscriptions/{+subscription}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'subscription' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'subscriptions/{+subscription}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'subscription' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'subscriptions',
              'httpMethod' => 'GET',
              'parameters' => array(
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'query' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'modifyAckDeadline' => array(
              'path' => 'subscriptions/modifyAckDeadline',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),'modifyPushConfig' => array(
              'path' => 'subscriptions/modifyPushConfig',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),'pull' => array(
              'path' => 'subscriptions/pull',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),'pullBatch' => array(
              'path' => 'subscriptions/pullBatch',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),
          )
        )
    );
    $this->topics = new Google_Service_Pubsub_Topics_Resource(
        $this,
        $this->serviceName,
        'topics',
        array(
          'methods' => array(
            'create' => array(
              'path' => 'topics',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),'delete' => array(
              'path' => 'topics/{+topic}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'topic' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'topics/{+topic}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'topic' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'topics',
              'httpMethod' => 'GET',
              'parameters' => array(
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'query' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'publish' => array(
              'path' => 'topics/publish',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),'publishBatch' => array(
              'path' => 'topics/publishBatch',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),
          )
        )
    );
  }
}


/**
 * The "subscriptions" collection of methods.
 * Typical usage is:
 *  <code>
 *   $pubsubService = new Google_Service_Pubsub(...);
 *   $subscriptions = $pubsubService->subscriptions;
 *  </code>
 */
class Google_Service_Pubsub_Subscriptions_Resource extends Google_Service_Resource
{

  /**
   * Acknowledges a particular received message: the Pub/Sub system can remove the
   * given message from the subscription. Acknowledging a message whose Ack
   * deadline has expired may succeed, but the message could have been already
   * redelivered. Acknowledging a message more than once will not result in an
   * error. This is only used for messages received via pull.
   * (subscriptions.acknowledge)
   *
   * @param Google_AcknowledgeRequest $postBody
   * @param array $optParams Optional parameters.
   */
  public function acknowledge(Google_Service_Pubsub_AcknowledgeRequest $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('acknowledge', array($params));
  }

  /**
   * Creates a subscription on a given topic for a given subscriber. If the
   * subscription already exists, returns ALREADY_EXISTS. If the corresponding
   * topic doesn't exist, returns NOT_FOUND.
   *
   * If the name is not provided in the request, the server will assign a random
   * name for this subscription on the same project as the topic.
   * (subscriptions.create)
   *
   * @param Google_Subscription $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Pubsub_Subscription
   */
  public function create(Google_Service_Pubsub_Subscription $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('create', array($params), "Google_Service_Pubsub_Subscription");
  }

  /**
   * Deletes an existing subscription. All pending messages in the subscription
   * are immediately dropped. Calls to Pull after deletion will return NOT_FOUND.
   * (subscriptions.delete)
   *
   * @param string $subscription The subscription to delete.
   * @param array $optParams Optional parameters.
   */
  public function delete($subscription, $optParams = array())
  {
    $params = array('subscription' => $subscription);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Gets the configuration details of a subscription. (subscriptions.get)
   *
   * @param string $subscription The name of the subscription to get.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Pubsub_Subscription
   */
  public function get($subscription, $optParams = array())
  {
    $params = array('subscription' => $subscription);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Pubsub_Subscription");
  }

  /**
   * Lists matching subscriptions. (subscriptions.listSubscriptions)
   *
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken The value obtained in the last
   * ListSubscriptionsResponse for continuation.
   * @opt_param int maxResults Maximum number of subscriptions to return.
   * @opt_param string query A valid label query expression.
   * @return Google_Service_Pubsub_ListSubscriptionsResponse
   */
  public function listSubscriptions($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Pubsub_ListSubscriptionsResponse");
  }

  /**
   * Modifies the Ack deadline for a message received from a pull request.
   * (subscriptions.modifyAckDeadline)
   *
   * @param Google_ModifyAckDeadlineRequest $postBody
   * @param array $optParams Optional parameters.
   */
  public function modifyAckDeadline(Google_Service_Pubsub_ModifyAckDeadlineRequest $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('modifyAckDeadline', array($params));
  }

  /**
   * Modifies the PushConfig for a specified subscription. This method can be used
   * to suspend the flow of messages to an endpoint by clearing the PushConfig
   * field in the request. Messages will be accumulated for delivery even if no
   * push configuration is defined or while the configuration is modified.
   * (subscriptions.modifyPushConfig)
   *
   * @param Google_ModifyPushConfigRequest $postBody
   * @param array $optParams Optional parameters.
   */
  public function modifyPushConfig(Google_Service_Pubsub_ModifyPushConfigRequest $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('modifyPushConfig', array($params));
  }

  /**
   * Pulls a single message from the server. If return_immediately is true, and no
   * messages are available in the subscription, this method returns
   * FAILED_PRECONDITION. The system is free to return an UNAVAILABLE error if no
   * messages are available in a reasonable amount of time (to reduce system
   * load). (subscriptions.pull)
   *
   * @param Google_PullRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Pubsub_PullResponse
   */
  public function pull(Google_Service_Pubsub_PullRequest $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('pull', array($params), "Google_Service_Pubsub_PullResponse");
  }

  /**
   * Pulls messages from the server. Returns an empty list if there are no
   * messages available in the backlog. The system is free to return UNAVAILABLE
   * if there are too many pull requests outstanding for the given subscription.
   * (subscriptions.pullBatch)
   *
   * @param Google_PullBatchRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Pubsub_PullBatchResponse
   */
  public function pullBatch(Google_Service_Pubsub_PullBatchRequest $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('pullBatch', array($params), "Google_Service_Pubsub_PullBatchResponse");
  }
}

/**
 * The "topics" collection of methods.
 * Typical usage is:
 *  <code>
 *   $pubsubService = new Google_Service_Pubsub(...);
 *   $topics = $pubsubService->topics;
 *  </code>
 */
class Google_Service_Pubsub_Topics_Resource extends Google_Service_Resource
{

  /**
   * Creates the given topic with the given name. (topics.create)
   *
   * @param Google_Topic $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Pubsub_Topic
   */
  public function create(Google_Service_Pubsub_Topic $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('create', array($params), "Google_Service_Pubsub_Topic");
  }

  /**
   * Deletes the topic with the given name. Returns NOT_FOUND if the topic does
   * not exist. After a topic is deleted, a new topic may be created with the same
   * name. (topics.delete)
   *
   * @param string $topic Name of the topic to delete.
   * @param array $optParams Optional parameters.
   */
  public function delete($topic, $optParams = array())
  {
    $params = array('topic' => $topic);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Gets the configuration of a topic. Since the topic only has the name
   * attribute, this method is only useful to check the existence of a topic. If
   * other attributes are added in the future, they will be returned here.
   * (topics.get)
   *
   * @param string $topic The name of the topic to get.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Pubsub_Topic
   */
  public function get($topic, $optParams = array())
  {
    $params = array('topic' => $topic);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Pubsub_Topic");
  }

  /**
   * Lists matching topics. (topics.listTopics)
   *
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken The value obtained in the last ListTopicsResponse
   * for continuation.
   * @opt_param int maxResults Maximum number of topics to return.
   * @opt_param string query A valid label query expression.
   * @return Google_Service_Pubsub_ListTopicsResponse
   */
  public function listTopics($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Pubsub_ListTopicsResponse");
  }

  /**
   * Adds a message to the topic. Returns NOT_FOUND if the topic does not exist.
   * (topics.publish)
   *
   * @param Google_PublishRequest $postBody
   * @param array $optParams Optional parameters.
   */
  public function publish(Google_Service_Pubsub_PublishRequest $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('publish', array($params));
  }

  /**
   * Adds one or more messages to the topic. Returns NOT_FOUND if the topic does
   * not exist. (topics.publishBatch)
   *
   * @param Google_PublishBatchRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Pubsub_PublishBatchResponse
   */
  public function publishBatch(Google_Service_Pubsub_PublishBatchRequest $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('publishBatch', array($params), "Google_Service_Pubsub_PublishBatchResponse");
  }
}




class Google_Service_Pubsub_AcknowledgeRequest extends Google_Collection
{
  protected $collection_key = 'ackId';
  protected $internal_gapi_mappings = array(
  );
  public $ackId;
  public $subscription;


  public function setAckId($ackId)
  {
    $this->ackId = $ackId;
  }
  public function getAckId()
  {
    return $this->ackId;
  }
  public function setSubscription($subscription)
  {
    $this->subscription = $subscription;
  }
  public function getSubscription()
  {
    return $this->subscription;
  }
}

class Google_Service_Pubsub_Label extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $key;
  public $numValue;
  public $strValue;


  public function setKey($key)
  {
    $this->key = $key;
  }
  public function getKey()
  {
    return $this->key;
  }
  public function setNumValue($numValue)
  {
    $this->numValue = $numValue;
  }
  public function getNumValue()
  {
    return $this->numValue;
  }
  public function setStrValue($strValue)
  {
    $this->strValue = $strValue;
  }
  public function getStrValue()
  {
    return $this->strValue;
  }
}

class Google_Service_Pubsub_ListSubscriptionsResponse extends Google_Collection
{
  protected $collection_key = 'subscription';
  protected $internal_gapi_mappings = array(
  );
  public $nextPageToken;
  protected $subscriptionType = 'Google_Service_Pubsub_Subscription';
  protected $subscriptionDataType = 'array';


  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  public function setSubscription($subscription)
  {
    $this->subscription = $subscription;
  }
  public function getSubscription()
  {
    return $this->subscription;
  }
}

class Google_Service_Pubsub_ListTopicsResponse extends Google_Collection
{
  protected $collection_key = 'topic';
  protected $internal_gapi_mappings = array(
  );
  public $nextPageToken;
  protected $topicType = 'Google_Service_Pubsub_Topic';
  protected $topicDataType = 'array';


  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  public function setTopic($topic)
  {
    $this->topic = $topic;
  }
  public function getTopic()
  {
    return $this->topic;
  }
}

class Google_Service_Pubsub_ModifyAckDeadlineRequest extends Google_Collection
{
  protected $collection_key = 'ackIds';
  protected $internal_gapi_mappings = array(
  );
  public $ackDeadlineSeconds;
  public $ackId;
  public $ackIds;
  public $subscription;


  public function setAckDeadlineSeconds($ackDeadlineSeconds)
  {
    $this->ackDeadlineSeconds = $ackDeadlineSeconds;
  }
  public function getAckDeadlineSeconds()
  {
    return $this->ackDeadlineSeconds;
  }
  public function setAckId($ackId)
  {
    $this->ackId = $ackId;
  }
  public function getAckId()
  {
    return $this->ackId;
  }
  public function setAckIds($ackIds)
  {
    $this->ackIds = $ackIds;
  }
  public function getAckIds()
  {
    return $this->ackIds;
  }
  public function setSubscription($subscription)
  {
    $this->subscription = $subscription;
  }
  public function getSubscription()
  {
    return $this->subscription;
  }
}

class Google_Service_Pubsub_ModifyPushConfigRequest extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $pushConfigType = 'Google_Service_Pubsub_PushConfig';
  protected $pushConfigDataType = '';
  public $subscription;


  public function setPushConfig(Google_Service_Pubsub_PushConfig $pushConfig)
  {
    $this->pushConfig = $pushConfig;
  }
  public function getPushConfig()
  {
    return $this->pushConfig;
  }
  public function setSubscription($subscription)
  {
    $this->subscription = $subscription;
  }
  public function getSubscription()
  {
    return $this->subscription;
  }
}

class Google_Service_Pubsub_PublishBatchRequest extends Google_Collection
{
  protected $collection_key = 'messages';
  protected $internal_gapi_mappings = array(
  );
  protected $messagesType = 'Google_Service_Pubsub_PubsubMessage';
  protected $messagesDataType = 'array';
  public $topic;


  public function setMessages($messages)
  {
    $this->messages = $messages;
  }
  public function getMessages()
  {
    return $this->messages;
  }
  public function setTopic($topic)
  {
    $this->topic = $topic;
  }
  public function getTopic()
  {
    return $this->topic;
  }
}

class Google_Service_Pubsub_PublishBatchResponse extends Google_Collection
{
  protected $collection_key = 'messageIds';
  protected $internal_gapi_mappings = array(
  );
  public $messageIds;


  public function setMessageIds($messageIds)
  {
    $this->messageIds = $messageIds;
  }
  public function getMessageIds()
  {
    return $this->messageIds;
  }
}

class Google_Service_Pubsub_PublishRequest extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $messageType = 'Google_Service_Pubsub_PubsubMessage';
  protected $messageDataType = '';
  public $topic;


  public function setMessage(Google_Service_Pubsub_PubsubMessage $message)
  {
    $this->message = $message;
  }
  public function getMessage()
  {
    return $this->message;
  }
  public function setTopic($topic)
  {
    $this->topic = $topic;
  }
  public function getTopic()
  {
    return $this->topic;
  }
}

class Google_Service_Pubsub_PubsubEvent extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $deleted;
  protected $messageType = 'Google_Service_Pubsub_PubsubMessage';
  protected $messageDataType = '';
  public $subscription;
  public $truncated;


  public function setDeleted($deleted)
  {
    $this->deleted = $deleted;
  }
  public function getDeleted()
  {
    return $this->deleted;
  }
  public function setMessage(Google_Service_Pubsub_PubsubMessage $message)
  {
    $this->message = $message;
  }
  public function getMessage()
  {
    return $this->message;
  }
  public function setSubscription($subscription)
  {
    $this->subscription = $subscription;
  }
  public function getSubscription()
  {
    return $this->subscription;
  }
  public function setTruncated($truncated)
  {
    $this->truncated = $truncated;
  }
  public function getTruncated()
  {
    return $this->truncated;
  }
}

class Google_Service_Pubsub_PubsubMessage extends Google_Collection
{
  protected $collection_key = 'label';
  protected $internal_gapi_mappings = array(
  );
  public $data;
  protected $labelType = 'Google_Service_Pubsub_Label';
  protected $labelDataType = 'array';
  public $messageId;


  public function setData($data)
  {
    $this->data = $data;
  }
  public function getData()
  {
    return $this->data;
  }
  public function setLabel($label)
  {
    $this->label = $label;
  }
  public function getLabel()
  {
    return $this->label;
  }
  public function setMessageId($messageId)
  {
    $this->messageId = $messageId;
  }
  public function getMessageId()
  {
    return $this->messageId;
  }
}

class Google_Service_Pubsub_PullBatchRequest extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $maxEvents;
  public $returnImmediately;
  public $subscription;


  public function setMaxEvents($maxEvents)
  {
    $this->maxEvents = $maxEvents;
  }
  public function getMaxEvents()
  {
    return $this->maxEvents;
  }
  public function setReturnImmediately($returnImmediately)
  {
    $this->returnImmediately = $returnImmediately;
  }
  public function getReturnImmediately()
  {
    return $this->returnImmediately;
  }
  public function setSubscription($subscription)
  {
    $this->subscription = $subscription;
  }
  public function getSubscription()
  {
    return $this->subscription;
  }
}

class Google_Service_Pubsub_PullBatchResponse extends Google_Collection
{
  protected $collection_key = 'pullResponses';
  protected $internal_gapi_mappings = array(
  );
  protected $pullResponsesType = 'Google_Service_Pubsub_PullResponse';
  protected $pullResponsesDataType = 'array';


  public function setPullResponses($pullResponses)
  {
    $this->pullResponses = $pullResponses;
  }
  public function getPullResponses()
  {
    return $this->pullResponses;
  }
}

class Google_Service_Pubsub_PullRequest extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $returnImmediately;
  public $subscription;


  public function setReturnImmediately($returnImmediately)
  {
    $this->returnImmediately = $returnImmediately;
  }
  public function getReturnImmediately()
  {
    return $this->returnImmediately;
  }
  public function setSubscription($subscription)
  {
    $this->subscription = $subscription;
  }
  public function getSubscription()
  {
    return $this->subscription;
  }
}

class Google_Service_Pubsub_PullResponse extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $ackId;
  protected $pubsubEventType = 'Google_Service_Pubsub_PubsubEvent';
  protected $pubsubEventDataType = '';


  public function setAckId($ackId)
  {
    $this->ackId = $ackId;
  }
  public function getAckId()
  {
    return $this->ackId;
  }
  public function setPubsubEvent(Google_Service_Pubsub_PubsubEvent $pubsubEvent)
  {
    $this->pubsubEvent = $pubsubEvent;
  }
  public function getPubsubEvent()
  {
    return $this->pubsubEvent;
  }
}

class Google_Service_Pubsub_PushConfig extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $pushEndpoint;


  public function setPushEndpoint($pushEndpoint)
  {
    $this->pushEndpoint = $pushEndpoint;
  }
  public function getPushEndpoint()
  {
    return $this->pushEndpoint;
  }
}

class Google_Service_Pubsub_Subscription extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $ackDeadlineSeconds;
  public $name;
  protected $pushConfigType = 'Google_Service_Pubsub_PushConfig';
  protected $pushConfigDataType = '';
  public $topic;


  public function setAckDeadlineSeconds($ackDeadlineSeconds)
  {
    $this->ackDeadlineSeconds = $ackDeadlineSeconds;
  }
  public function getAckDeadlineSeconds()
  {
    return $this->ackDeadlineSeconds;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setPushConfig(Google_Service_Pubsub_PushConfig $pushConfig)
  {
    $this->pushConfig = $pushConfig;
  }
  public function getPushConfig()
  {
    return $this->pushConfig;
  }
  public function setTopic($topic)
  {
    $this->topic = $topic;
  }
  public function getTopic()
  {
    return $this->topic;
  }
}

class Google_Service_Pubsub_Topic extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $name;


  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
}
