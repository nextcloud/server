<?php
/**
 * An abstraction that defines persistent objects associated with a service
 *
 * @copyright 2012-2013 Rackspace Hosting, Inc.
 * See COPYING for licensing information
 *
 * @package phpOpenCloud
 * @version 1.0
 * @author Glen Campbell <glen.campbell@rackspace.com>
 * @author Jamie Hannaford <jamie.hannaford@rackspace.com>
 */

namespace OpenCloud\Common;

/**
 * Represents an object that can be retrieved, created, updated and deleted.
 *
 * This class abstracts much of the common functionality between: 
 *  
 *  * Nova servers;
 *  * Swift containers and objects;
 *  * DBAAS instances;
 *  * Cinder volumes;
 *  * and various other objects that:
 *    * have a URL;
 *    * can be created, updated, deleted, or retrieved;
 *    * use a standard JSON format with a top-level element followed by 
 *      a child object with attributes.
 *
 * In general, you can create a persistent object class by subclassing this
 * class and defining some protected, static variables:
 * 
 *  * $url_resource - the sub-resource value in the URL of the parent. For
 *    example, if the parent URL is `http://something/parent`, then setting this
 *    value to "another" would result in a URL for the persistent object of 
 *    `http://something/parent/another`.
 *
 *  * $json_name - the top-level JSON object name. For example, if the
 *    persistent object is represented by `{"foo": {"attr":value, ...}}`, then
 *    set $json_name to "foo".
 *
 *  * $json_collection_name - optional; this value is the name of a collection
 *    of the persistent objects. If not provided, it defaults to `json_name`
 *    with an appended "s" (e.g., if `json_name` is "foo", then
 *    `json_collection_name` would be "foos"). Set this value if the collection 
 *    name doesn't follow this pattern.
 *
 *  * $json_collection_element - the common pattern for a collection is:
 *    `{"collection": [{"attr":"value",...}, {"attr":"value",...}, ...]}`
 *    That is, each element of the array is a \stdClass object containing the
 *    object's attributes. In rare instances, the objects in the array
 *    are named, and `json_collection_element` contains the name of the
 *    collection objects. For example, in this JSON response:
 *    `{"allowedDomain":[{"allowedDomain":{"name":"foo"}}]}`,
 *    `json_collection_element` would be set to "allowedDomain".
 *
 * The PersistentObject class supports the standard CRUD methods; if these are 
 * not needed (i.e. not supported by  the service), the subclass should redefine 
 * these to call the `noCreate`, `noUpdate`, or `noDelete` methods, which will 
 * trigger an appropriate exception. For example, if an object cannot be created:
 *
 *    function create($params = array()) 
 *    { 
 *       $this->noCreate(); 
 *    }
 */
abstract class PersistentObject extends Base
{
      
    private $service;
    
    private $parent;
    
    protected $id; 

    /**
     * Retrieves the instance from persistent storage
     *
     * @param mixed $service The service object for this resource
     * @param mixed $info    The ID or array/object of data
     */
    public function __construct($service = null, $info = null)
    {
        if ($service instanceof Service) {
            $this->setService($service);
        }
        
        if (property_exists($this, 'metadata')) {
            $this->metadata = new Metadata;
        }
        
        $this->populate($info);
    }
    
    /**
     * Validates properties that have a namespace: prefix
     *
     * If the property prefix: appears in the list of supported extension
     * namespaces, then the property is applied to the object. Otherwise,
     * an exception is thrown.
     *
     * @param string $name the name of the property
     * @param mixed $value the property's value
     * @return void
     * @throws AttributeError
     */
    public function __set($name, $value)
    {
        $this->setProperty($name, $value, $this->getService()->namespaces());
    }
    
    /**
     * Sets the service associated with this resource object.
     * 
     * @param \OpenCloud\Common\Service $service
     */
    public function setService(Service $service)
    {
        $this->service = $service;
        return $this;
    }
    
    /**
     * Returns the service object for this resource; required for making
     * requests, etc. because it has direct access to the Connection.
     * 
     * @return \OpenCloud\Common\Service
     */
    public function getService()
    {
        if (null === $this->service) {
            throw new Exceptions\ServiceValueError(
                'No service defined'
            );
        }
        return $this->service;
    }
    
    /**
     * Legacy shortcut to getService
     * 
     * @return \OpenCloud\Common\Service
     */
    public function service()
    {
        return $this->getService();
    }
    
    /**
     * Set the parent object for this resource.
     * 
     * @param \OpenCloud\Common\PersistentObject $parent
     */
    public function setParent(PersistentObject $parent)
    {
        $this->parent = $parent;
        return $this;
    }
    
    /**
     * Returns the parent.
     * 
     * @return \OpenCloud\Common\PersistentObject
     */
    public function getParent()
    {
        if (null === $this->parent) {
            $this->parent = $this->getService();
        }
        return $this->parent;
    }
    
    /**
     * Legacy shortcut to getParent
     * 
     * @return \OpenCloud\Common\PersistentObject
     */
    public function parent()
    {
        return $this->getParent();
    }
    
    

    
    /**
     * API OPERATIONS (CRUD & CUSTOM)
     */
    
    /**
     * Creates a new object
     *
     * @api
     * @param array $params array of values to set when creating the object
     * @return HttpResponse
     * @throws VolumeCreateError if HTTP status is not Success
     */
    public function create($params = array())
    {
        // set parameters
        if (!empty($params)) {
            $this->populate($params, false);
        }

        // debug
        $this->getLogger()->info('{class}::Create({name})', array(
            'class' => get_class($this), 
            'name'  => $this->Name()
        ));

        // construct the JSON
        $object = $this->createJson();
        $json = json_encode($object);
        $this->checkJsonError();

        $this->getLogger()->info('{class}::Create JSON [{json}]', array(
            'class' => get_class($this), 
            'json'  => $json
        ));
 
        // send the request
        $response = $this->getService()->request(
            $this->createUrl(),
            'POST',
            array('Content-Type' => 'application/json'),
            $json
        );
        
        // check the return code
        // @codeCoverageIgnoreStart
        if ($response->httpStatus() > 204) {
            throw new Exceptions\CreateError(sprintf(
                Lang::translate('Error creating [%s] [%s], status [%d] response [%s]'),
                get_class($this),
                $this->Name(),
                $response->HttpStatus(),
                $response->HttpBody()
            ));
        }

        if ($response->HttpStatus() == "201" && ($location = $response->Header('Location'))) {
            // follow Location header
            $this->refresh(null, $location);
        } else {
            // set values from response
            $object = json_decode($response->httpBody());
            
            if (!$this->checkJsonError()) {
                $top = $this->jsonName();
                if (isset($object->$top)) {
                    $this->populate($object->$top);
                }
            }
        }
        // @codeCoverageIgnoreEnd

        return $response;
    }

    /**
     * Updates an existing object
     *
     * @api
     * @param array $params array of values to set when updating the object
     * @return HttpResponse
     * @throws VolumeCreateError if HTTP status is not Success
     */
    public function update($params = array())
    {
        // set parameters
        if (!empty($params)) {
            $this->populate($params);
        }

        // debug
        $this->getLogger()->info('{class}::Update({name})', array(
            'class' => get_class($this),
            'name'  => $this->Name()   
        ));

        // construct the JSON
        $obj = $this->updateJson($params);
        $json = json_encode($obj);

        $this->checkJsonError();

        $this->getLogger()->info('{class}::Update JSON [{json}]', array(
            'class' => get_class($this), 
            'json'  => $json
        ));

        // send the request
        $response = $this->getService()->Request(
            $this->url(),
            'PUT',
            array(),
            $json
        );

        // check the return code
        // @codeCoverageIgnoreStart
        if ($response->HttpStatus() > 204) {
            throw new Exceptions\UpdateError(sprintf(
                Lang::translate('Error updating [%s] with [%s], status [%d] response [%s]'),
                get_class($this),
                $json,
                $response->HttpStatus(),
                $response->HttpBody()
            ));
        }
        // @codeCoverageIgnoreEnd

        return $response;
    }

    /**
     * Deletes an object
     *
     * @api
     * @return HttpResponse
     * @throws DeleteError if HTTP status is not Success
     */
    public function delete()
    {
        $this->getLogger()->info('{class}::Delete()', array('class' => get_class($this)));

        // send the request
        $response = $this->getService()->request($this->url(), 'DELETE');

        // check the return code
        // @codeCoverageIgnoreStart
        if ($response->HttpStatus() > 204) {
            throw new Exceptions\DeleteError(sprintf(
                Lang::translate('Error deleting [%s] [%s], status [%d] response [%s]'),
                get_class(),
                $this->Name(),
                $response->HttpStatus(),
                $response->HttpBody()
            ));
        }
        // @codeCoverageIgnoreEnd

        return $response;
    }

     /**
     * Returns an object for the Create() method JSON
     * Must be overridden in a child class.
     *
     * @throws CreateError if not overridden
     */
    protected function createJson()
    {
        throw new Exceptions\CreateError(sprintf(
            Lang::translate('[%s] CreateJson() must be overridden'),
            get_class($this)
        ));
    }

    /**
     * Returns an object for the Update() method JSON
     * Must be overridden in a child class.
     *
     * @throws UpdateError if not overridden
     */
    protected function updateJson($params = array())
    {
        throw new Exceptions\UpdateError(sprintf(
            Lang::translate('[%s] UpdateJson() must be overridden'),
            get_class($this)
        ));
    }

    /**
     * throws a CreateError for subclasses that don't support Create
     *
     * @throws CreateError
     */
    protected function noCreate()
    {
        throw new Exceptions\CreateError(sprintf(
            Lang::translate('[%s] does not support Create()'),
            get_class()
        ));
    }

    /**
     * throws a DeleteError for subclasses that don't support Delete
     *
     * @throws DeleteError
     */
    protected function noDelete()
    {
        throw new Exceptions\DeleteError(sprintf(
            Lang::translate('[%s] does not support Delete()'),
            get_class()
        ));
    }

    /**
     * throws a UpdateError for subclasses that don't support Update
     *
     * @throws UpdateError
     */
    protected function noUpdate()
    {
        throw new Exceptions\UpdateError(sprintf(
            Lang::translate('[%s] does not support Update()'),
            get_class()
        ));
    }
    
    /**
     * Returns the default URL of the object
     *
     * This may have to be overridden in subclasses.
     *
     * @param string $subresource optional sub-resource string
     * @param array $qstr optional k/v pairs for query strings
     * @return string
     * @throws UrlError if URL is not defined
     */
    public function url($subresource = null, $queryString = array())
    {
        // find the primary key attribute name
        $primaryKey = $this->primaryKeyField();

        // first, see if we have a [self] link
        $url = $this->findLink('self');

        /**
         * Next, check to see if we have an ID
         * Note that we use Parent() instead of Service(), since the parent
         * object might not be a service.
         */
        if (!$url && $this->$primaryKey) {
            $url = Lang::noslash($this->getParent()->url($this->resourceName())) . '/' . $this->$primaryKey;
        }

        // add the subresource
        if ($url) {
            $url .= $subresource ? "/$subresource" : '';
            if (count($queryString)) {
                $url .= '?' . $this->makeQueryString($queryString);
            }
            return $url;
        }

        // otherwise, we don't have a URL yet
        throw new Exceptions\UrlError(sprintf(
            Lang::translate('%s does not have a URL yet'), 
            get_class($this)
        ));
    }

    /**
     * Waits for the server/instance status to change
     *
     * This function repeatedly polls the system for a change in server
     * status. Once the status reaches the `$terminal` value (or 'ERROR'),
     * then the function returns.
     *
     * The polling interval is set by the constant RAXSDK_POLL_INTERVAL.
     *
     * The function will automatically terminate after RAXSDK_SERVER_MAXTIMEOUT
     * seconds elapse.
     *
     * @api
     * @param string $terminal the terminal state to wait for
     * @param integer $timeout the max time (in seconds) to wait
     * @param callable $callback a callback function that is invoked with
     *      each repetition of the polling sequence. This can be used, for
     *      example, to update a status display or to permit other operations
     *      to continue
     * @return void
     */
    public function waitFor(
        $terminal = 'ACTIVE',
        $timeout = RAXSDK_SERVER_MAXTIMEOUT,
        $callback = NULL,
        $sleep = RAXSDK_POLL_INTERVAL
    ) {
        // find the primary key field
        $primaryKey = $this->PrimaryKeyField();

        // save stats
        $startTime = time();
        
        $states = array('ERROR', $terminal);
        
        while (true) {
            
            $this->refresh($this->$primaryKey);
            
            if ($callback) {
                call_user_func($callback, $this);
            }
            
            if (in_array($this->status(), $states) || (time() - $startTime) > $timeout) {
                return;
            }
            // @codeCoverageIgnoreStart
            sleep($sleep);
        }
    }
    // @codeCoverageIgnoreEnd

    /**
     * Refreshes the object from the origin (useful when the server is
     * changing states)
     *
     * @return void
     * @throws IdRequiredError
     */
    public function refresh($id = null, $url = null)
    {
        $primaryKey = $this->PrimaryKeyField();

        if (!$url) {
            if ($id === null) {
                $id = $this->$primaryKey;
            }

            if (!$id) {
                throw new Exceptions\IdRequiredError(sprintf(
                    Lang::translate('%s has no ID; cannot be refreshed'),
                    get_class())
                );
            }

            // retrieve it
            $this->getLogger()->info(Lang::translate('{class} id [{id}]'), array(
                'class' => get_class($this), 
                'id'    => $id
            ));
            
            $this->$primaryKey = $id;
            $url = $this->url();
        }
        
        // reset status, if available
        if (property_exists($this, 'status')) {
            $this->status = null;
        }

        // perform a GET on the URL
        $response = $this->getService()->Request($url);
        
        // check status codes
        // @codeCoverageIgnoreStart
        if ($response->HttpStatus() == 404) {
            throw new Exceptions\InstanceNotFound(
                sprintf(Lang::translate('%s [%s] not found [%s]'),
                get_class($this),
                $this->$primaryKey,
                $url
            ));
        }

        if ($response->HttpStatus() >= 300) {
            throw new Exceptions\UnknownError(
                sprintf(Lang::translate('Unexpected %s error [%d] [%s]'),
                get_class($this),
                $response->HttpStatus(),
                $response->HttpBody()
            ));
        }

        // check for empty response
        if (!$response->HttpBody()) {
            throw new Exceptions\EmptyResponseError(
                sprintf(Lang::translate('%s::Refresh() unexpected empty response, URL [%s]'),
                get_class($this),
                $url
            ));
        }

        // we're ok, reload the response
        if ($json = $response->HttpBody()) {
 
            $this->getLogger()->info('refresh() JSON [{json}]', array('json' => $json));
            
            $response = json_decode($json);

            if ($this->CheckJsonError()) {
                throw new Exceptions\ServerJsonError(sprintf(
                    Lang::translate('JSON parse error on %s refresh'), 
                    get_class($this)
                ));
            }

            $top = $this->JsonName();
            
            if ($top && isset($response->$top)) {
                $content = $response->$top;
            } else {
                $content = $response;
            }
            
            $this->populate($content);

        }
        // @codeCoverageIgnoreEnd
    }

    
    /**
     * OBJECT INFORMATION
     */
    
    /**
     * Returns the displayable name of the object
     *
     * Can be overridden by child objects; *must* be overridden by child
     * objects if the object does not have a `name` attribute defined.
     *
     * @api
     * @return string
     * @throws NameError if attribute 'name' is not defined
     */
    public function name()
    {
        if (property_exists($this, 'name')) {
            return $this->name;
        } else {
            throw new Exceptions\NameError(sprintf(
                Lang::translate('Name attribute does not exist for [%s]'),
                get_class($this)
            ));
        }
    }

    /**
     * Sends the json string to the /action resource
     *
     * This is used for many purposes, such as rebooting the server,
     * setting the root password, creating images, etc.
     * Since it can only be used on a live server, it checks for a valid ID.
     *
     * @param $object - this will be encoded as json, and we handle all the JSON
     *     error-checking in one place
     * @throws ServerIdError if server ID is not defined
     * @throws ServerActionError on other errors
     * @returns boolean; TRUE if successful, FALSE otherwise
     */
    protected function action($object)
    {
        $primaryKey = $this->primaryKeyField();

        if (!$this->$primaryKey) {
            throw new Exceptions\IdRequiredError(sprintf(
                Lang::translate('%s is not defined'),
                get_class($this)
            ));
        }

        if (!is_object($object)) {
            throw new Exceptions\ServerActionError(sprintf(
                Lang::translate('%s::Action() requires an object as its parameter'),
                get_class($this)
            ));
        }

        // convert the object to json
        $json = json_encode($object);
        $this->getLogger()->info('JSON [{string}]', array('json' => $json));

        $this->checkJsonError();

        // debug - save the request
        $this->getLogger()->info(Lang::translate('{class}::action [{json}]'), array(
            'class' => get_class($this), 
            'json'  => $json
        ));

        // get the URL for the POST message
        $url = $this->url('action');

        // POST the message
        $response = $this->getService()->request($url, 'POST', array(), $json);

        // @codeCoverageIgnoreStart
        if (!is_object($response)) {
            throw new Exceptions\HttpError(sprintf(
                Lang::translate('Invalid response for %s::Action() request'),
                get_class($this)
            ));
        }
        
        // check for errors
        if ($response->HttpStatus() >= 300) {
            throw new Exceptions\ServerActionError(sprintf(
                Lang::translate('%s::Action() [%s] failed; response [%s]'),
                get_class($this),
                $url,
                $response->HttpBody()
            ));
        }
        // @codeCoverageIgnoreStart

        return $response;
    }
    
    /**
     * Execute a custom resource request.
     * 
     * @param string $path
     * @param string $method
     * @param string|array|object $body
     * @return boolean
     * @throws Exceptions\InvalidArgumentError
     * @throws Exceptions\HttpError
     * @throws Exceptions\ServerActionError
     */
    public function customAction($url, $method = 'GET', $body = null)
    {
        if (is_string($body) && (json_decode($body) === null)) {
            throw new Exceptions\InvalidArgumentError(
                'Please provide either a well-formed JSON string, or an object '
                . 'for JSON serialization'
            );
        } else {
            $body = json_encode($body);
        }

        // POST the message
        $response = $this->service()->request($url, $method, array(), $body);

        if (!is_object($response)) {
            throw new Exceptions\HttpError(sprintf(
                Lang::translate('Invalid response for %s::customAction() request'),
                get_class($this)
            ));
        }

        // check for errors
        // @codeCoverageIgnoreStart
        if ($response->HttpStatus() >= 300) {
            throw new Exceptions\ServerActionError(sprintf(
                Lang::translate('%s::customAction() [%s] failed; response [%s]'),
                get_class($this),
                $url,
                $response->HttpBody()
            ));
        }
        // @codeCoverageIgnoreEnd

        $object = json_decode($response->httpBody());
        
        $this->checkJsonError();
        
        return $object;
    }

    /**
     * returns the object's status or `N/A` if not available
     *
     * @api
     * @return string
     */
    public function status()
    {
        return (isset($this->status)) ? $this->status : 'N/A';
    }

    /**
     * returns the object's identifier
     *
     * Can be overridden by a child class if the identifier is not in the
     * `$id` property. Use of this function permits the `$id` attribute to
     * be protected or private to prevent unauthorized overwriting for
     * security.
     *
     * @api
     * @return string
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * checks for `$alias` in extensions and throws an error if not present
     *
     * @throws UnsupportedExtensionError
     */
    public function checkExtension($alias)
    {
        if (!in_array($alias, $this->getService()->namespaces())) {
            throw new Exceptions\UnsupportedExtensionError(sprintf(
                Lang::translate('Extension [%s] is not installed'),
                $alias
            ));
        }
        
        return true;
    }

    /**
     * returns the region associated with the object
     *
     * navigates to the parent service to determine the region.
     *
     * @api
     */
    public function region()
    {
        return $this->getService()->Region();
    }
    
    /**
     * Since each server can have multiple links, this returns the desired one
     *
     * @param string $type - 'self' is most common; use 'bookmark' for
     *      the version-independent one
     * @return string the URL from the links block
     */
    public function findLink($type = 'self')
    {
        if (empty($this->links)) {
            return false;
        }

        foreach ($this->links as $link) {
            if ($link->rel == $type) {
                return $link->href;
            }
        }

        return false;
    }

    /**
     * returns the URL used for Create
     *
     * @return string
     */
    protected function createUrl()
    {
        return $this->getParent()->Url($this->ResourceName());
    }

    /**
     * Returns the primary key field for the object
     *
     * The primary key is usually 'id', but this function is provided so that
     * (in rare cases where it is not 'id'), it can be overridden.
     *
     * @return string
     */
    protected function primaryKeyField()
    {
        return 'id';
    }

    /**
     * Returns the top-level document identifier for the returned response
     * JSON document; must be overridden in child classes
     *
     * For example, a server document is (JSON) `{"server": ...}` and an
     * Instance document is `{"instance": ...}` - this function must return
     * the top level document name (either "server" or "instance", in
     * these examples).
     *
     * @throws DocumentError if not overridden
     */
    public static function jsonName()
    {
        if (isset(static::$json_name)) {
            return static::$json_name;
        }

        throw new Exceptions\DocumentError(sprintf(
            Lang::translate('No JSON object defined for class [%s] in JsonName()'),
            get_class()
        ));
    }

    /**
     * returns the collection JSON element name
     *
     * When an object is returned in a collection, it usually has a top-level
     * object that is an array holding child objects of the object types.
     * This static function returns the name of the top-level element. Usually,
     * that top-level element is simply the JSON name of the resource.'s';
     * however, it can be overridden by specifying the $json_collection_name
     * attribute.
     *
     * @return string
     */
    public static function jsonCollectionName()
    {
        if (isset(static::$json_collection_name)) {
            return static::$json_collection_name;
        } else {
            return static::$json_name . 's';
        }
    }

    /**
     * returns the JSON name for each element in a collection
     *
     * Usually, elements in a collection are anonymous; this function, however,
     * provides for an element level name:
     *
     *  `{ "collection" : [ { "element" : ... } ] }`
     *
     * @return string
     */
    public static function jsonCollectionElement()
    {
        if (isset(static::$json_collection_element)) {
            return static::$json_collection_element;
        }
    }

    /**
     * Returns the resource name for the URL of the object; must be overridden
     * in child classes
     *
     * For example, a server is `/servers/`, a database instance is
     * `/instances/`. Must be overridden in child classes.
     *
     * @throws UrlError
     */
    public static function resourceName()
    {
        if (isset(static::$url_resource)) {
            return static::$url_resource;
        }

        throw new Exceptions\UrlError(sprintf(
            Lang::translate('No URL resource defined for class [%s] in ResourceName()'),
            get_class()
        ));
    }

}