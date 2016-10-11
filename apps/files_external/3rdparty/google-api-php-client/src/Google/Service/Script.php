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
 * Service definition for Script (v1).
 *
 * <p>
 * An API for executing Google Apps Script projects.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://developers.google.com/apps-script/execution/rest/v1/run" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_Script extends Google_Service
{
  /** View and manage your mail. */
  const MAIL_GOOGLE_COM =
      "https://mail.google.com/";
  /** Manage your calendars. */
  const WWW_GOOGLE_COM_CALENDAR_FEEDS =
      "https://www.google.com/calendar/feeds";
  /** Manage your contacts. */
  const WWW_GOOGLE_COM_M8_FEEDS =
      "https://www.google.com/m8/feeds";
  /** View and manage the provisioning of groups on your domain. */
  const ADMIN_DIRECTORY_GROUP =
      "https://www.googleapis.com/auth/admin.directory.group";
  /** View and manage the provisioning of users on your domain. */
  const ADMIN_DIRECTORY_USER =
      "https://www.googleapis.com/auth/admin.directory.user";
  /** View and manage the files in your Google Drive. */
  const DRIVE =
      "https://www.googleapis.com/auth/drive";
  /** View and manage your forms in Google Drive. */
  const FORMS =
      "https://www.googleapis.com/auth/forms";
  /** View and manage forms that this application has been installed in. */
  const FORMS_CURRENTONLY =
      "https://www.googleapis.com/auth/forms.currentonly";
  /** View and manage your Google Groups. */
  const GROUPS =
      "https://www.googleapis.com/auth/groups";
  /** View your email address. */
  const USERINFO_EMAIL =
      "https://www.googleapis.com/auth/userinfo.email";

  public $scripts;
  

  /**
   * Constructs the internal representation of the Script service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://script.googleapis.com/';
    $this->servicePath = '';
    $this->version = 'v1';
    $this->serviceName = 'script';

    $this->scripts = new Google_Service_Script_Scripts_Resource(
        $this,
        $this->serviceName,
        'scripts',
        array(
          'methods' => array(
            'run' => array(
              'path' => 'v1/scripts/{scriptId}:run',
              'httpMethod' => 'POST',
              'parameters' => array(
                'scriptId' => array(
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
 * The "scripts" collection of methods.
 * Typical usage is:
 *  <code>
 *   $scriptService = new Google_Service_Script(...);
 *   $scripts = $scriptService->scripts;
 *  </code>
 */
class Google_Service_Script_Scripts_Resource extends Google_Service_Resource
{

  /**
   * Runs a function in an Apps Script project that has been deployed for use with
   * the Apps Script Execution API. This method requires authorization with an
   * OAuth 2.0 token that includes at least one of the scopes listed in the
   * [Authentication](#authentication) section; script projects that do not
   * require authorization cannot be executed through this API. To find the
   * correct scopes to include in the authentication token, open the project in
   * the script editor, then select **File > Project properties** and click the
   * **Scopes** tab. (scripts.run)
   *
   * @param string $scriptId The project key of the script to be executed. To find
   * the project key, open the project in the script editor, then select **File >
   * Project properties**.
   * @param Google_ExecutionRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Script_Operation
   */
  public function run($scriptId, Google_Service_Script_ExecutionRequest $postBody, $optParams = array())
  {
    $params = array('scriptId' => $scriptId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('run', array($params), "Google_Service_Script_Operation");
  }
}




class Google_Service_Script_ExecutionError extends Google_Collection
{
  protected $collection_key = 'scriptStackTraceElements';
  protected $internal_gapi_mappings = array(
  );
  public $errorMessage;
  public $errorType;
  protected $scriptStackTraceElementsType = 'Google_Service_Script_ScriptStackTraceElement';
  protected $scriptStackTraceElementsDataType = 'array';


  public function setErrorMessage($errorMessage)
  {
    $this->errorMessage = $errorMessage;
  }
  public function getErrorMessage()
  {
    return $this->errorMessage;
  }
  public function setErrorType($errorType)
  {
    $this->errorType = $errorType;
  }
  public function getErrorType()
  {
    return $this->errorType;
  }
  public function setScriptStackTraceElements($scriptStackTraceElements)
  {
    $this->scriptStackTraceElements = $scriptStackTraceElements;
  }
  public function getScriptStackTraceElements()
  {
    return $this->scriptStackTraceElements;
  }
}

class Google_Service_Script_ExecutionRequest extends Google_Collection
{
  protected $collection_key = 'parameters';
  protected $internal_gapi_mappings = array(
  );
  public $devMode;
  public $function;
  public $parameters;
  public $sessionState;


  public function setDevMode($devMode)
  {
    $this->devMode = $devMode;
  }
  public function getDevMode()
  {
    return $this->devMode;
  }
  public function setFunction($function)
  {
    $this->function = $function;
  }
  public function getFunction()
  {
    return $this->function;
  }
  public function setParameters($parameters)
  {
    $this->parameters = $parameters;
  }
  public function getParameters()
  {
    return $this->parameters;
  }
  public function setSessionState($sessionState)
  {
    $this->sessionState = $sessionState;
  }
  public function getSessionState()
  {
    return $this->sessionState;
  }
}

class Google_Service_Script_ExecutionResponse extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $result;


  public function setResult($result)
  {
    $this->result = $result;
  }
  public function getResult()
  {
    return $this->result;
  }
}

class Google_Service_Script_Operation extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $done;
  protected $errorType = 'Google_Service_Script_Status';
  protected $errorDataType = '';
  public $metadata;
  public $name;
  public $response;


  public function setDone($done)
  {
    $this->done = $done;
  }
  public function getDone()
  {
    return $this->done;
  }
  public function setError(Google_Service_Script_Status $error)
  {
    $this->error = $error;
  }
  public function getError()
  {
    return $this->error;
  }
  public function setMetadata($metadata)
  {
    $this->metadata = $metadata;
  }
  public function getMetadata()
  {
    return $this->metadata;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setResponse($response)
  {
    $this->response = $response;
  }
  public function getResponse()
  {
    return $this->response;
  }
}

class Google_Service_Script_OperationMetadata extends Google_Model
{
}

class Google_Service_Script_OperationResponse extends Google_Model
{
}

class Google_Service_Script_ScriptStackTraceElement extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $function;
  public $lineNumber;


  public function setFunction($function)
  {
    $this->function = $function;
  }
  public function getFunction()
  {
    return $this->function;
  }
  public function setLineNumber($lineNumber)
  {
    $this->lineNumber = $lineNumber;
  }
  public function getLineNumber()
  {
    return $this->lineNumber;
  }
}

class Google_Service_Script_Status extends Google_Collection
{
  protected $collection_key = 'details';
  protected $internal_gapi_mappings = array(
  );
  public $code;
  public $details;
  public $message;


  public function setCode($code)
  {
    $this->code = $code;
  }
  public function getCode()
  {
    return $this->code;
  }
  public function setDetails($details)
  {
    $this->details = $details;
  }
  public function getDetails()
  {
    return $this->details;
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

class Google_Service_Script_StatusDetails extends Google_Model
{
}
