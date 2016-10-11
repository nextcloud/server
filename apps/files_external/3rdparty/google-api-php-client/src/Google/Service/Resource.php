<?php
/**
 * Copyright 2010 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

if (!class_exists('Google_Client')) {
  require_once dirname(__FILE__) . '/../autoload.php';
}

/**
 * Implements the actual methods/resources of the discovered Google API using magic function
 * calling overloading (__call()), which on call will see if the method name (plus.activities.list)
 * is available in this service, and if so construct an apiHttpRequest representing it.
 *
 */
class Google_Service_Resource
{
  // Valid query parameters that work, but don't appear in discovery.
  private $stackParameters = array(
      'alt' => array('type' => 'string', 'location' => 'query'),
      'fields' => array('type' => 'string', 'location' => 'query'),
      'trace' => array('type' => 'string', 'location' => 'query'),
      'userIp' => array('type' => 'string', 'location' => 'query'),
      'quotaUser' => array('type' => 'string', 'location' => 'query'),
      'data' => array('type' => 'string', 'location' => 'body'),
      'mimeType' => array('type' => 'string', 'location' => 'header'),
      'uploadType' => array('type' => 'string', 'location' => 'query'),
      'mediaUpload' => array('type' => 'complex', 'location' => 'query'),
      'prettyPrint' => array('type' => 'string', 'location' => 'query'),
  );

  /** @var string $rootUrl */
  private $rootUrl;

  /** @var Google_Client $client */
  private $client;

  /** @var string $serviceName */
  private $serviceName;

  /** @var string $servicePath */
  private $servicePath;

  /** @var string $resourceName */
  private $resourceName;

  /** @var array $methods */
  private $methods;

  public function __construct($service, $serviceName, $resourceName, $resource)
  {
    $this->rootUrl = $service->rootUrl;
    $this->client = $service->getClient();
    $this->servicePath = $service->servicePath;
    $this->serviceName = $serviceName;
    $this->resourceName = $resourceName;
    $this->methods = is_array($resource) && isset($resource['methods']) ?
        $resource['methods'] :
        array($resourceName => $resource);
  }

  /**
   * TODO: This function needs simplifying.
   * @param $name
   * @param $arguments
   * @param $expected_class - optional, the expected class name
   * @return Google_Http_Request|expected_class
   * @throws Google_Exception
   */
  public function call($name, $arguments, $expected_class = null)
  {
    if (! isset($this->methods[$name])) {
      $this->client->getLogger()->error(
          'Service method unknown',
          array(
              'service' => $this->serviceName,
              'resource' => $this->resourceName,
              'method' => $name
          )
      );

      throw new Google_Exception(
          "Unknown function: " .
          "{$this->serviceName}->{$this->resourceName}->{$name}()"
      );
    }
    $method = $this->methods[$name];
    $parameters = $arguments[0];

    // postBody is a special case since it's not defined in the discovery
    // document as parameter, but we abuse the param entry for storing it.
    $postBody = null;
    if (isset($parameters['postBody'])) {
      if ($parameters['postBody'] instanceof Google_Model) {
        // In the cases the post body is an existing object, we want
        // to use the smart method to create a simple object for
        // for JSONification.
        $parameters['postBody'] = $parameters['postBody']->toSimpleObject();
      } else if (is_object($parameters['postBody'])) {
        // If the post body is another kind of object, we will try and
        // wrangle it into a sensible format.
        $parameters['postBody'] =
            $this->convertToArrayAndStripNulls($parameters['postBody']);
      }
      $postBody = json_encode($parameters['postBody']);
      if ($postBody === false && $parameters['postBody'] !== false) {
        throw new Google_Exception("JSON encoding failed. Ensure all strings in the request are UTF-8 encoded.");
      }
      unset($parameters['postBody']);
    }

    // TODO: optParams here probably should have been
    // handled already - this may well be redundant code.
    if (isset($parameters['optParams'])) {
      $optParams = $parameters['optParams'];
      unset($parameters['optParams']);
      $parameters = array_merge($parameters, $optParams);
    }

    if (!isset($method['parameters'])) {
      $method['parameters'] = array();
    }

    $method['parameters'] = array_merge(
        $this->stackParameters,
        $method['parameters']
    );
    foreach ($parameters as $key => $val) {
      if ($key != 'postBody' && ! isset($method['parameters'][$key])) {
        $this->client->getLogger()->error(
            'Service parameter unknown',
            array(
                'service' => $this->serviceName,
                'resource' => $this->resourceName,
                'method' => $name,
                'parameter' => $key
            )
        );
        throw new Google_Exception("($name) unknown parameter: '$key'");
      }
    }

    foreach ($method['parameters'] as $paramName => $paramSpec) {
      if (isset($paramSpec['required']) &&
          $paramSpec['required'] &&
          ! isset($parameters[$paramName])
      ) {
        $this->client->getLogger()->error(
            'Service parameter missing',
            array(
                'service' => $this->serviceName,
                'resource' => $this->resourceName,
                'method' => $name,
                'parameter' => $paramName
            )
        );
        throw new Google_Exception("($name) missing required param: '$paramName'");
      }
      if (isset($parameters[$paramName])) {
        $value = $parameters[$paramName];
        $parameters[$paramName] = $paramSpec;
        $parameters[$paramName]['value'] = $value;
        unset($parameters[$paramName]['required']);
      } else {
        // Ensure we don't pass nulls.
        unset($parameters[$paramName]);
      }
    }

    $this->client->getLogger()->info(
        'Service Call',
        array(
            'service' => $this->serviceName,
            'resource' => $this->resourceName,
            'method' => $name,
            'arguments' => $parameters,
        )
    );

    $url = Google_Http_REST::createRequestUri(
        $this->servicePath,
        $method['path'],
        $parameters
    );
    $httpRequest = new Google_Http_Request(
        $url,
        $method['httpMethod'],
        null,
        $postBody
    );

    if ($this->rootUrl) {
      $httpRequest->setBaseComponent($this->rootUrl);
    } else {
      $httpRequest->setBaseComponent($this->client->getBasePath());
    }

    if ($postBody) {
      $contentTypeHeader = array();
      $contentTypeHeader['content-type'] = 'application/json; charset=UTF-8';
      $httpRequest->setRequestHeaders($contentTypeHeader);
      $httpRequest->setPostBody($postBody);
    }

    $httpRequest = $this->client->getAuth()->sign($httpRequest);
    $httpRequest->setExpectedClass($expected_class);

    if (isset($parameters['data']) &&
        ($parameters['uploadType']['value'] == 'media' || $parameters['uploadType']['value'] == 'multipart')) {
      // If we are doing a simple media upload, trigger that as a convenience.
      $mfu = new Google_Http_MediaFileUpload(
          $this->client,
          $httpRequest,
          isset($parameters['mimeType']) ? $parameters['mimeType']['value'] : 'application/octet-stream',
          $parameters['data']['value']
      );
    }

    if (isset($parameters['alt']) && $parameters['alt']['value'] == 'media') {
      $httpRequest->enableExpectedRaw();
    }

    if ($this->client->shouldDefer()) {
      // If we are in batch or upload mode, return the raw request.
      return $httpRequest;
    }

    return $this->client->execute($httpRequest);
  }

  protected function convertToArrayAndStripNulls($o)
  {
    $o = (array) $o;
    foreach ($o as $k => $v) {
      if ($v === null) {
        unset($o[$k]);
      } elseif (is_object($v) || is_array($v)) {
        $o[$k] = $this->convertToArrayAndStripNulls($o[$k]);
      }
    }
    return $o;
  }
}
