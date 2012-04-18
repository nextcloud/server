<?php
/**
 * This is the PHP Cloud Files API.
 *
 * <code>
 *   # Authenticate to Cloud Files.  The default is to automatically try
 *   # to re-authenticate if an authentication token expires.
 *   #
 *   # NOTE: Some versions of cURL include an outdated certificate authority (CA)
 *   #       file.  This API ships with a newer version obtained directly from
 *   #       cURL's web site (http://curl.haxx.se).  To use the newer CA bundle,
 *   #       call the CF_Authentication instance's 'ssl_use_cabundle()' method.
 *   #
 *   $auth = new CF_Authentication($username, $api_key);
 *   # $auth->ssl_use_cabundle();  # bypass cURL's old CA bundle
 *   $auth->authenticate();
 *
 *   # Establish a connection to the storage system
 *   #
 *   # NOTE: Some versions of cURL include an outdated certificate authority (CA)
 *   #       file.  This API ships with a newer version obtained directly from
 *   #       cURL's web site (http://curl.haxx.se).  To use the newer CA bundle,
 *   #       call the CF_Connection instance's 'ssl_use_cabundle()' method.
 *   #
 *   $conn = new CF_Connection($auth);
 *   # $conn->ssl_use_cabundle();  # bypass cURL's old CA bundle
 *
 *   # Create a remote Container and storage Object
 *   #
 *   $images = $conn->create_container("photos");
 *   $bday = $images->create_object("first_birthday.jpg");
 *
 *   # Upload content from a local file by streaming it.  Note that we use
 *   # a "float" for the file size to overcome PHP's 32-bit integer limit for
 *   # very large files.
 *   #
 *   $fname = "/home/user/photos/birthdays/birthday1.jpg";  # filename to upload
 *   $size = (float) sprintf("%u", filesize($fname));
 *   $fp = open($fname, "r");
 *   $bday->write($fp, $size);
 *
 *   # Or... use a convenience function instead
 *   #
 *   $bday->load_from_filename("/home/user/photos/birthdays/birthday1.jpg");
 *
 *   # Now, publish the "photos" container to serve the images by CDN.
 *   # Use the "$uri" value to put in your web pages or send the link in an
 *   # email message, etc.
 *   #
 *   $uri = $images->make_public();
 *
 *   # Or... print out the Object's public URI
 *   #
 *   print $bday->public_uri();
 * </code>
 *
 * See the included tests directory for additional sample code.
 *
 * Requres PHP 5.x (for Exceptions and OO syntax) and PHP's cURL module.
 *
 * It uses the supporting "cloudfiles_http.php" module for HTTP(s) support and
 * allows for connection re-use and streaming of content into/out of Cloud Files
 * via PHP's cURL module.
 *
 * See COPYING for license information.
 *
 * @author Eric "EJ" Johnson <ej@racklabs.com>
 * @copyright Copyright (c) 2008, Rackspace US, Inc.
 * @package php-cloudfiles
 */

/**
 */
require_once("cloudfiles_exceptions.php");
require("cloudfiles_http.php");
define("DEFAULT_CF_API_VERSION", 1);
define("MAX_CONTAINER_NAME_LEN", 256);
define("MAX_OBJECT_NAME_LEN", 1024);
define("MAX_OBJECT_SIZE", 5*1024*1024*1024+1);
define("US_AUTHURL", "https://auth.api.rackspacecloud.com");
define("UK_AUTHURL", "https://lon.auth.api.rackspacecloud.com");
/**
 * Class for handling Cloud Files Authentication, call it's {@link authenticate()}
 * method to obtain authorized service urls and an authentication token.
 *
 * Example:
 * <code>
 * # Create the authentication instance
 * #
 * $auth = new CF_Authentication("username", "api_key");
 *
 * # NOTE: For UK Customers please specify your AuthURL Manually
 * # There is a Predfined constant to use EX:
 * #
 * # $auth = new CF_Authentication("username, "api_key", NULL, UK_AUTHURL);
 * # Using the UK_AUTHURL keyword will force the api to use the UK AuthUrl.
 * # rather then the US one. The NULL Is passed for legacy purposes and must
 * # be passed to function correctly.
 *
 * # NOTE: Some versions of cURL include an outdated certificate authority (CA)
 * #       file.  This API ships with a newer version obtained directly from
 * #       cURL's web site (http://curl.haxx.se).  To use the newer CA bundle,
 * #       call the CF_Authentication instance's 'ssl_use_cabundle()' method.
 * #
 * # $auth->ssl_use_cabundle(); # bypass cURL's old CA bundle
 *
 * # Perform authentication request
 * #
 * $auth->authenticate();
 * </code>
 *
 * @package php-cloudfiles
 */
class CF_Authentication
{
    public $dbug;
    public $username;
    public $api_key;
    public $auth_host;
    public $account;

    /**
     * Instance variables that are set after successful authentication
     */
    public $storage_url;
    public $cdnm_url;
    public $auth_token;

    /**
     * Class constructor (PHP 5 syntax)
     *
     * @param string $username Mosso username
     * @param string $api_key Mosso API Access Key
     * @param string $account  <i>Account name</i>
     * @param string $auth_host  <i>Authentication service URI</i>
     */
    function __construct($username=NULL, $api_key=NULL, $account=NULL, $auth_host=US_AUTHURL)
    {

        $this->dbug = False;
        $this->username = $username;
        $this->api_key = $api_key;
        $this->account_name = $account;
        $this->auth_host = $auth_host;

        $this->storage_url = NULL;
        $this->cdnm_url = NULL;
        $this->auth_token = NULL;

        $this->cfs_http = new CF_Http(DEFAULT_CF_API_VERSION);
    }

    /**
     * Use the Certificate Authority bundle included with this API
     *
     * Most versions of PHP with cURL support include an outdated Certificate
     * Authority (CA) bundle (the file that lists all valid certificate
     * signing authorities).  The SSL certificates used by the Cloud Files
     * storage system are perfectly valid but have been created/signed by
     * a CA not listed in these outdated cURL distributions.
     *
     * As a work-around, we've included an updated CA bundle obtained
     * directly from cURL's web site (http://curl.haxx.se).  You can direct
     * the API to use this CA bundle by calling this method prior to making
     * any remote calls.  The best place to use this method is right after
     * the CF_Authentication instance has been instantiated.
     *
     * You can specify your own CA bundle by passing in the full pathname
     * to the bundle.  You can use the included CA bundle by leaving the
     * argument blank.
     *
     * @param string $path Specify path to CA bundle (default to included)
     */
    function ssl_use_cabundle($path=NULL)
    {
        $this->cfs_http->ssl_use_cabundle($path);
    }

    /**
     * Attempt to validate Username/API Access Key
     *
     * Attempts to validate credentials with the authentication service.  It
     * either returns <kbd>True</kbd> or throws an Exception.  Accepts a single
     * (optional) argument for the storage system API version.
     *
     * Example:
     * <code>
     * # Create the authentication instance
     * #
     * $auth = new CF_Authentication("username", "api_key");
     *
     * # Perform authentication request
     * #
     * $auth->authenticate();
     * </code>
     *
     * @param string $version API version for Auth service (optional)
     * @return boolean <kbd>True</kbd> if successfully authenticated
     * @throws AuthenticationException invalid credentials
     * @throws InvalidResponseException invalid response
     */
    function authenticate($version=DEFAULT_CF_API_VERSION)
    {
        list($status,$reason,$surl,$curl,$atoken) = 
                $this->cfs_http->authenticate($this->username, $this->api_key,
                $this->account_name, $this->auth_host);

        if ($status == 401) {
            throw new AuthenticationException("Invalid username or access key.");
        }
        if ($status < 200 || $status > 299) {
            throw new InvalidResponseException(
                "Unexpected response (".$status."): ".$reason);
        }

        if (!($surl || $curl) || !$atoken) {
            throw new InvalidResponseException(
                "Expected headers missing from auth service.");
        }
        $this->storage_url = $surl;
        $this->cdnm_url = $curl;
        $this->auth_token = $atoken;
        return True;
    }
	/**
	 * Use Cached Token and Storage URL's rather then grabbing from the Auth System
         *
         * Example:
 	 * <code>
         * #Create an Auth instance
         * $auth = new CF_Authentication();
         * #Pass Cached URL's and Token as Args
	 * $auth->load_cached_credentials("auth_token", "storage_url", "cdn_management_url");
         * </code>
	 * 
	 * @param string $auth_token A Cloud Files Auth Token (Required)
         * @param string $storage_url The Cloud Files Storage URL (Required)
         * @param string $cdnm_url CDN Management URL (Required)
         * @return boolean <kbd>True</kbd> if successful 
	 * @throws SyntaxException If any of the Required Arguments are missing
         */
	function load_cached_credentials($auth_token, $storage_url, $cdnm_url)
    {
        if(!$storage_url || !$cdnm_url)
        {
                throw new SyntaxException("Missing Required Interface URL's!");
                return False;
        }
        if(!$auth_token)
        {
                throw new SyntaxException("Missing Auth Token!");
                return False;
        }

        $this->storage_url = $storage_url;
        $this->cdnm_url    = $cdnm_url;
        $this->auth_token  = $auth_token;
        return True;
    }
	/**
         * Grab Cloud Files info to be Cached for later use with the load_cached_credentials method.
         *
	 * Example:
         * <code>
         * #Create an Auth instance
         * $auth = new CF_Authentication("UserName","API_Key");
         * $auth->authenticate();
         * $array = $auth->export_credentials();
         * </code>
         * 
	 * @return array of url's and an auth token.
         */
    function export_credentials()
    {
        $arr = array();
        $arr['storage_url'] = $this->storage_url;
        $arr['cdnm_url']    = $this->cdnm_url;
        $arr['auth_token']  = $this->auth_token;

        return $arr;
    }


    /**
     * Make sure the CF_Authentication instance has authenticated.
     *
     * Ensures that the instance variables necessary to communicate with
     * Cloud Files have been set from a previous authenticate() call.
     *
     * @return boolean <kbd>True</kbd> if successfully authenticated
     */
    function authenticated()
    {
        if (!($this->storage_url || $this->cdnm_url) || !$this->auth_token) {
            return False;
        }
        return True;
    }

    /**
     * Toggle debugging - set cURL verbose flag
     */
    function setDebug($bool)
    {
        $this->dbug = $bool;
        $this->cfs_http->setDebug($bool);
    }
}

/**
 * Class for establishing connections to the Cloud Files storage system.
 * Connection instances are used to communicate with the storage system at
 * the account level; listing and deleting Containers and returning Container
 * instances.
 *
 * Example:
 * <code>
 * # Create the authentication instance
 * #
 * $auth = new CF_Authentication("username", "api_key");
 *
 * # Perform authentication request
 * #
 * $auth->authenticate();
 *
 * # Create a connection to the storage/cdn system(s) and pass in the
 * # validated CF_Authentication instance.
 * #
 * $conn = new CF_Connection($auth);
 *
 * # NOTE: Some versions of cURL include an outdated certificate authority (CA)
 * #       file.  This API ships with a newer version obtained directly from
 * #       cURL's web site (http://curl.haxx.se).  To use the newer CA bundle,
 * #       call the CF_Authentication instance's 'ssl_use_cabundle()' method.
 * #
 * # $conn->ssl_use_cabundle(); # bypass cURL's old CA bundle
 * </code>
 *
 * @package php-cloudfiles
 */
class CF_Connection
{
    public $dbug;
    public $cfs_http;
    public $cfs_auth;

    /**
     * Pass in a previously authenticated CF_Authentication instance.
     *
     * Example:
     * <code>
     * # Create the authentication instance
     * #
     * $auth = new CF_Authentication("username", "api_key");
     *
     * # Perform authentication request
     * #
     * $auth->authenticate();
     *
     * # Create a connection to the storage/cdn system(s) and pass in the
     * # validated CF_Authentication instance.
     * #
     * $conn = new CF_Connection($auth);
     *
     * # If you are connecting via Rackspace servers and have access
     * # to the servicenet network you can set the $servicenet to True
     * # like this.
     *
     * $conn = new CF_Connection($auth, $servicenet=True);
     *
     * </code>
     *
     * If the environement variable RACKSPACE_SERVICENET is defined it will
     * force to connect via the servicenet.
     *
     * @param obj $cfs_auth previously authenticated CF_Authentication instance
     * @param boolean $servicenet enable/disable access via Rackspace servicenet.
     * @throws AuthenticationException not authenticated
     */
    function __construct($cfs_auth, $servicenet=False)
    {
        if (isset($_ENV['RACKSPACE_SERVICENET']))
            $servicenet=True;
        $this->cfs_http = new CF_Http(DEFAULT_CF_API_VERSION);
        $this->cfs_auth = $cfs_auth;
        if (!$this->cfs_auth->authenticated()) {
            $e = "Need to pass in a previously authenticated ";
            $e .= "CF_Authentication instance.";
            throw new AuthenticationException($e);
        }
        $this->cfs_http->setCFAuth($this->cfs_auth, $servicenet=$servicenet);
        $this->dbug = False;
    }

    /**
     * Toggle debugging of instance and back-end HTTP module
     *
     * @param boolean $bool enable/disable cURL debugging
     */
    function setDebug($bool)
    {
        $this->dbug = (boolean) $bool;
        $this->cfs_http->setDebug($this->dbug);
    }

    /**
     * Close a connection
     *
     * Example:
     * <code>
     *  
     * $conn->close();
     * 
     * </code>
     *
     * Will close all current cUrl active connections.
     * 
     */
    public function close()
    {
        $this->cfs_http->close();
    }
    
    /**
     * Cloud Files account information
     *
     * Return an array of two floats (since PHP only supports 32-bit integers);
     * number of containers on the account and total bytes used for the account.
     *
     * Example:
     * <code>
     * # ... authentication code excluded (see previous examples) ...
     * #
     * $conn = new CF_Authentication($auth);
     *
     * list($quantity, $bytes) = $conn->get_info();
     * print "Number of containers: " . $quantity . "\n";
     * print "Bytes stored in container: " . $bytes . "\n";
     * </code>
     *
     * @return array (number of containers, total bytes stored)
     * @throws InvalidResponseException unexpected response
     */
    function get_info()
    {
        list($status, $reason, $container_count, $total_bytes) =
                $this->cfs_http->head_account();
        #if ($status == 401 && $this->_re_auth()) {
        #    return $this->get_info();
        #}
        if ($status < 200 || $status > 299) {
            throw new InvalidResponseException(
                "Invalid response (".$status."): ".$this->cfs_http->get_error());
        }
        return array($container_count, $total_bytes);
    }

    /**
     * Create a Container
     *
     * Given a Container name, return a Container instance, creating a new
     * remote Container if it does not exit.
     *
     * Example:
     * <code>
     * # ... authentication code excluded (see previous examples) ...
     * #
     * $conn = new CF_Authentication($auth);
     *
     * $images = $conn->create_container("my photos");
     * </code>
     *
     * @param string $container_name container name
     * @return CF_Container
     * @throws SyntaxException invalid name
     * @throws InvalidResponseException unexpected response
     */
    function create_container($container_name=NULL)
    {
        if ($container_name != "0" and !isset($container_name))
            throw new SyntaxException("Container name not set.");
        
        if (!isset($container_name) or $container_name == "") 
            throw new SyntaxException("Container name not set.");

        if (strpos($container_name, "/") !== False) {
            $r = "Container name '".$container_name;
            $r .= "' cannot contain a '/' character.";
            throw new SyntaxException($r);
        }
        if (strlen($container_name) > MAX_CONTAINER_NAME_LEN) {
            throw new SyntaxException(sprintf(
                "Container name exeeds %d bytes.",
                MAX_CONTAINER_NAME_LEN));
        }

        $return_code = $this->cfs_http->create_container($container_name);
        if (!$return_code) {
            throw new InvalidResponseException("Invalid response ("
                . $return_code. "): " . $this->cfs_http->get_error());
        }
        #if ($status == 401 && $this->_re_auth()) {
        #    return $this->create_container($container_name);
        #}
        if ($return_code != 201 && $return_code != 202) {
            throw new InvalidResponseException(
                "Invalid response (".$return_code."): "
                    . $this->cfs_http->get_error());
        }
        return new CF_Container($this->cfs_auth, $this->cfs_http, $container_name);
    }

    /**
     * Delete a Container
     *
     * Given either a Container instance or name, remove the remote Container.
     * The Container must be empty prior to removing it.
     *
     * Example:
     * <code>
     * # ... authentication code excluded (see previous examples) ...
     * #
     * $conn = new CF_Authentication($auth);
     *
     * $conn->delete_container("my photos");
     * </code>
     *
     * @param string|obj $container container name or instance
     * @return boolean <kbd>True</kbd> if successfully deleted
     * @throws SyntaxException missing proper argument
     * @throws InvalidResponseException invalid response
     * @throws NonEmptyContainerException container not empty
     * @throws NoSuchContainerException remote container does not exist
     */
    function delete_container($container=NULL)
    {
        $container_name = NULL;
        
        if (is_object($container)) {
            if (get_class($container) == "CF_Container") {
                $container_name = $container->name;
            }
        }
        if (is_string($container)) {
            $container_name = $container;
        }

        if ($container_name != "0" and !isset($container_name))
            throw new SyntaxException("Must specify container object or name.");

        $return_code = $this->cfs_http->delete_container($container_name);

        if (!$return_code) {
            throw new InvalidResponseException("Failed to obtain http response");
        }
        #if ($status == 401 && $this->_re_auth()) {
        #    return $this->delete_container($container);
        #}
        if ($return_code == 409) {
            throw new NonEmptyContainerException(
                "Container must be empty prior to removing it.");
        }
        if ($return_code == 404) {
            throw new NoSuchContainerException(
                "Specified container did not exist to delete.");
        }
        if ($return_code != 204) {
            throw new InvalidResponseException(
                "Invalid response (".$return_code."): "
                . $this->cfs_http->get_error());
        }
        return True;
    }

    /**
     * Return a Container instance
     *
     * For the given name, return a Container instance if the remote Container
     * exists, otherwise throw a Not Found exception.
     *
     * Example:
     * <code>
     * # ... authentication code excluded (see previous examples) ...
     * #
     * $conn = new CF_Authentication($auth);
     *
     * $images = $conn->get_container("my photos");
     * print "Number of Objects: " . $images->count . "\n";
     * print "Bytes stored in container: " . $images->bytes . "\n";
     * </code>
     *
     * @param string $container_name name of the remote Container
     * @return container CF_Container instance
     * @throws NoSuchContainerException thrown if no remote Container
     * @throws InvalidResponseException unexpected response
     */
    function get_container($container_name=NULL)
    {
        list($status, $reason, $count, $bytes) =
                $this->cfs_http->head_container($container_name);
        #if ($status == 401 && $this->_re_auth()) {
        #    return $this->get_container($container_name);
        #}
        if ($status == 404) {
            throw new NoSuchContainerException("Container not found.");
        }
        if ($status < 200 || $status > 299) {
            throw new InvalidResponseException(
                "Invalid response: ".$this->cfs_http->get_error());
        }
        return new CF_Container($this->cfs_auth, $this->cfs_http,
            $container_name, $count, $bytes);
    }

    /**
     * Return array of Container instances
     *
     * Return an array of CF_Container instances on the account.  The instances
     * will be fully populated with Container attributes (bytes stored and
     * Object count)
     *
     * Example:
     * <code>
     * # ... authentication code excluded (see previous examples) ...
     * #
     * $conn = new CF_Authentication($auth);
     *
     * $clist = $conn->get_containers();
     * foreach ($clist as $cont) {
     *     print "Container name: " . $cont->name . "\n";
     *     print "Number of Objects: " . $cont->count . "\n";
     *     print "Bytes stored in container: " . $cont->bytes . "\n";
     * }
     * </code>
     *
     * @return array An array of CF_Container instances
     * @throws InvalidResponseException unexpected response
     */
    function get_containers($limit=0, $marker=NULL)
    {
        list($status, $reason, $container_info) =
                $this->cfs_http->list_containers_info($limit, $marker);
        #if ($status == 401 && $this->_re_auth()) {
        #    return $this->get_containers();
        #}
        if ($status < 200 || $status > 299) {
            throw new InvalidResponseException(
                "Invalid response: ".$this->cfs_http->get_error());
        }
        $containers = array();
        foreach ($container_info as $name => $info) {
            $containers[] = new CF_Container($this->cfs_auth, $this->cfs_http,
                $info['name'], $info["count"], $info["bytes"], False);
        }
        return $containers;
    }

    /**
     * Return list of remote Containers
     *
     * Return an array of strings containing the names of all remote Containers.
     *
     * Example:
     * <code>
     * # ... authentication code excluded (see previous examples) ...
     * #
     * $conn = new CF_Authentication($auth);
     *
     * $container_list = $conn->list_containers();
     * print_r($container_list);
     * Array
     * (
     *     [0] => "my photos",
     *     [1] => "my docs"
     * )
     * </code>
     *
     * @param integer $limit restrict results to $limit Containers
     * @param string $marker return results greater than $marker
     * @return array list of remote Containers
     * @throws InvalidResponseException unexpected response
     */
    function list_containers($limit=0, $marker=NULL)
    {
        list($status, $reason, $containers) =
            $this->cfs_http->list_containers($limit, $marker);
        #if ($status == 401 && $this->_re_auth()) {
        #    return $this->list_containers($limit, $marker);
        #}
        if ($status < 200 || $status > 299) {
            throw new InvalidResponseException(
                "Invalid response (".$status."): ".$this->cfs_http->get_error());
        }
        return $containers;
    }

    /**
     * Return array of information about remote Containers
     *
     * Return a nested array structure of Container info.
     *
     * Example:
     * <code>
     * # ... authentication code excluded (see previous examples) ...
     * #
     *
     * $container_info = $conn->list_containers_info();
     * print_r($container_info);
     * Array
     * (
     *     ["my photos"] =>
     *         Array
     *         (
     *             ["bytes"] => 78,
     *             ["count"] => 2
     *         )
     *     ["docs"] =>
     *         Array
     *         (
     *             ["bytes"] => 37323,
     *             ["count"] => 12
     *         )
     * )
     * </code>
     *
     * @param integer $limit restrict results to $limit Containers
     * @param string $marker return results greater than $marker
     * @return array nested array structure of Container info
     * @throws InvalidResponseException unexpected response
     */
    function list_containers_info($limit=0, $marker=NULL)
    {
        list($status, $reason, $container_info) = 
                $this->cfs_http->list_containers_info($limit, $marker);
        #if ($status == 401 && $this->_re_auth()) {
        #    return $this->list_containers_info($limit, $marker);
        #}
        if ($status < 200 || $status > 299) {
            throw new InvalidResponseException(
                "Invalid response (".$status."): ".$this->cfs_http->get_error());
        }
        return $container_info;
    }

    /**
     * Return list of Containers that have been published to the CDN.
     *
     * Return an array of strings containing the names of published Containers.
     * Note that this function returns the list of any Container that has
     * ever been CDN-enabled regardless of it's existence in the storage
     * system.
     *
     * Example:
     * <code>
     * # ... authentication code excluded (see previous examples) ...
     * #
     * $conn = new CF_Authentication($auth);
     *
     * $public_containers = $conn->list_public_containers();
     * print_r($public_containers);
     * Array
     * (
     *     [0] => "images",
     *     [1] => "css",
     *     [2] => "javascript"
     * )
     * </code>
     *
     * @param bool $enabled_only Will list all containers ever CDN enabled if     * set to false or only currently enabled CDN containers if set to true.      * Defaults to false.
     * @return array list of published Container names
     * @throws InvalidResponseException unexpected response
     */
    function list_public_containers($enabled_only=False)
    {
        list($status, $reason, $containers) =
                $this->cfs_http->list_cdn_containers($enabled_only);
        #if ($status == 401 && $this->_re_auth()) {
        #    return $this->list_public_containers();
        #}
        if ($status < 200 || $status > 299) {
            throw new InvalidResponseException(
                "Invalid response (".$status."): ".$this->cfs_http->get_error());
        }
        return $containers;
    }

    /**
     * Set a user-supplied callback function to report download progress
     *
     * The callback function is used to report incremental progress of a data
     * download functions (e.g. $container->list_objects(), $obj->read(), etc).
     * The specified function will be periodically called with the number of
     * bytes transferred until the entire download is complete.  This callback
     * function can be useful for implementing "progress bars" for large
     * downloads.
     *
     * The specified callback function should take a single integer parameter.
     *
     * <code>
     * function read_callback($bytes_transferred) {
     *     print ">> downloaded " . $bytes_transferred . " bytes.\n";
     *     # ... do other things ...
     *     return;
     * }
     *
     * $conn = new CF_Connection($auth_obj);
     * $conn->set_read_progress_function("read_callback");
     * print_r($conn->list_containers());
     *
     * # output would look like this:
     * #
     * >> downloaded 10 bytes.
     * >> downloaded 11 bytes.
     * Array
     * (
     *      [0] => fuzzy.txt
     *      [1] => space name
     * )
     * </code>
     *
     * @param string $func_name the name of the user callback function
     */
    function set_read_progress_function($func_name)
    {
        $this->cfs_http->setReadProgressFunc($func_name);
    }

    /**
     * Set a user-supplied callback function to report upload progress
     *
     * The callback function is used to report incremental progress of a data
     * upload functions (e.g. $obj->write() call).  The specified function will
     * be periodically called with the number of bytes transferred until the
     * entire upload is complete.  This callback function can be useful
     * for implementing "progress bars" for large uploads/downloads.
     *
     * The specified callback function should take a single integer parameter.
     *
     * <code>
     * function write_callback($bytes_transferred) {
     *     print ">> uploaded " . $bytes_transferred . " bytes.\n";
     *     # ... do other things ...
     *     return;
     * }
     *
     * $conn = new CF_Connection($auth_obj);
     * $conn->set_write_progress_function("write_callback");
     * $container = $conn->create_container("stuff");
     * $obj = $container->create_object("foo");
     * $obj->write("The callback function will be called during upload.");
     *
     * # output would look like this:
     * # >> uploaded 51 bytes.
     * #
     * </code>
     *
     * @param string $func_name the name of the user callback function
     */
    function set_write_progress_function($func_name)
    {
        $this->cfs_http->setWriteProgressFunc($func_name);
    }

    /**
     * Use the Certificate Authority bundle included with this API
     *
     * Most versions of PHP with cURL support include an outdated Certificate
     * Authority (CA) bundle (the file that lists all valid certificate
     * signing authorities).  The SSL certificates used by the Cloud Files
     * storage system are perfectly valid but have been created/signed by
     * a CA not listed in these outdated cURL distributions.
     *
     * As a work-around, we've included an updated CA bundle obtained
     * directly from cURL's web site (http://curl.haxx.se).  You can direct
     * the API to use this CA bundle by calling this method prior to making
     * any remote calls.  The best place to use this method is right after
     * the CF_Authentication instance has been instantiated.
     *
     * You can specify your own CA bundle by passing in the full pathname
     * to the bundle.  You can use the included CA bundle by leaving the
     * argument blank.
     *
     * @param string $path Specify path to CA bundle (default to included)
     */
    function ssl_use_cabundle($path=NULL)
    {
        $this->cfs_http->ssl_use_cabundle($path);
    }

    #private function _re_auth()
    #{
    #    $new_auth = new CF_Authentication(
    #        $this->cfs_auth->username,
    #        $this->cfs_auth->api_key,
    #        $this->cfs_auth->auth_host,
    #        $this->cfs_auth->account);
    #    $new_auth->authenticate();
    #    $this->cfs_auth = $new_auth;
    #    $this->cfs_http->setCFAuth($this->cfs_auth);
    #    return True;
    #}
}

/**
 * Container operations
 *
 * Containers are storage compartments where you put your data (objects).
 * A container is similar to a directory or folder on a conventional filesystem
 * with the exception that they exist in a flat namespace, you can not create
 * containers inside of containers.
 *
 * You also have the option of marking a Container as "public" so that the
 * Objects stored in the Container are publicly available via the CDN.
 *
 * @package php-cloudfiles
 */
class CF_Container
{
    public $cfs_auth;
    public $cfs_http;
    public $name;
    public $object_count;
    public $bytes_used;

    public $cdn_enabled;
    public $cdn_streaming_uri;
    public $cdn_ssl_uri;
    public $cdn_uri;
    public $cdn_ttl;
    public $cdn_log_retention;
    public $cdn_acl_user_agent;
    public $cdn_acl_referrer;

    /**
     * Class constructor
     *
     * Constructor for Container
     *
     * @param obj $cfs_auth CF_Authentication instance
     * @param obj $cfs_http HTTP connection manager
     * @param string $name name of Container
     * @param int $count number of Objects stored in this Container
     * @param int $bytes number of bytes stored in this Container
     * @throws SyntaxException invalid Container name
     */
    function __construct(&$cfs_auth, &$cfs_http, $name, $count=0,
        $bytes=0, $docdn=True)
    {
        if (strlen($name) > MAX_CONTAINER_NAME_LEN) {
            throw new SyntaxException("Container name exceeds "
                . "maximum allowed length.");
        }
        if (strpos($name, "/") !== False) {
            throw new SyntaxException(
                "Container names cannot contain a '/' character.");
        }
        $this->cfs_auth = $cfs_auth;
        $this->cfs_http = $cfs_http;
        $this->name = $name;
        $this->object_count = $count;
        $this->bytes_used = $bytes;
        $this->cdn_enabled = NULL;
        $this->cdn_uri = NULL;
        $this->cdn_ssl_uri = NULL;
        $this->cdn_streaming_uri = NULL;
        $this->cdn_ttl = NULL;
        $this->cdn_log_retention = NULL;
        $this->cdn_acl_user_agent = NULL;
        $this->cdn_acl_referrer = NULL;
        if ($this->cfs_http->getCDNMUrl() != NULL && $docdn) {
            $this->_cdn_initialize();
        }
    }

    /**
     * String representation of Container
     *
     * Pretty print the Container instance.
     *
     * @return string Container details
     */
    function __toString()
    {
        $me = sprintf("name: %s, count: %.0f, bytes: %.0f",
            $this->name, $this->object_count, $this->bytes_used);
        if ($this->cfs_http->getCDNMUrl() != NULL) {
            $me .= sprintf(", cdn: %s, cdn uri: %s, cdn ttl: %.0f, logs retention: %s",
                $this->is_public() ? "Yes" : "No",
                $this->cdn_uri, $this->cdn_ttl,
                $this->cdn_log_retention ? "Yes" : "No"
                );

            if ($this->cdn_acl_user_agent != NULL) {
                $me .= ", cdn acl user agent: " . $this->cdn_acl_user_agent;
            }

            if ($this->cdn_acl_referrer != NULL) {
                $me .= ", cdn acl referrer: " . $this->cdn_acl_referrer;
            }
            
            
        }
        return $me;
    }

    /**
     * Enable Container content to be served via CDN or modify CDN attributes
     *
     * Either enable this Container's content to be served via CDN or
     * adjust its CDN attributes.  This Container will always return the
     * same CDN-enabled URI each time it is toggled public/private/public.
     *
     * Example:
     * <code>
     * # ... authentication code excluded (see previous examples) ...
     * #
     * $conn = new CF_Authentication($auth);
     *
     * $public_container = $conn->create_container("public");
     *
     * # CDN-enable the container and set it's TTL for a month
     * #
     * $public_container->make_public(86400/2); # 12 hours (86400 seconds/day)
     * </code>
     *
     * @param int $ttl the time in seconds content will be cached in the CDN
     * @returns string the CDN enabled Container's URI
     * @throws CDNNotEnabledException CDN functionality not returned during auth
     * @throws AuthenticationException if auth token is not valid/expired
     * @throws InvalidResponseException unexpected response
     */
    function make_public($ttl=86400)
    {
        if ($this->cfs_http->getCDNMUrl() == NULL) {
            throw new CDNNotEnabledException(
                "Authentication response did not indicate CDN availability");
        }
        if ($this->cdn_uri != NULL) {
            # previously published, assume we're setting new attributes
            list($status, $reason, $cdn_uri, $cdn_ssl_uri) =
                $this->cfs_http->update_cdn_container($this->name,$ttl,
                                                      $this->cdn_log_retention,
                                                      $this->cdn_acl_user_agent,
                                                      $this->cdn_acl_referrer);
            #if ($status == 401 && $this->_re_auth()) {
            #    return $this->make_public($ttl);
            #}
            if ($status == 404) {
                # this instance _thinks_ the container was published, but the
                # cdn management system thinks otherwise - try again with a PUT
                list($status, $reason, $cdn_uri, $cdn_ssl_uri) =
                    $this->cfs_http->add_cdn_container($this->name,$ttl);

            }
        } else {
            # publish it for first time
            list($status, $reason, $cdn_uri, $cdn_ssl_uri) =
                $this->cfs_http->add_cdn_container($this->name,$ttl);
        }
        #if ($status == 401 && $this->_re_auth()) {
        #    return $this->make_public($ttl);
        #}
        if (!in_array($status, array(201,202))) {
            throw new InvalidResponseException(
                "Invalid response (".$status."): ".$this->cfs_http->get_error());
        }
        $this->cdn_enabled = True;
        $this->cdn_ttl = $ttl;
        $this->cdn_ssl_uri = $cdn_ssl_uri;
        $this->cdn_uri = $cdn_uri;
        $this->cdn_log_retention = False;
        $this->cdn_acl_user_agent = "";
        $this->cdn_acl_referrer = "";
        return $this->cdn_uri;
    }
    /**
     * Purge Containers objects from CDN Cache.
     * Example:
     * <code>
     * # ... authentication code excluded (see previous examples) ...
     * #
     * $conn = new CF_Authentication($auth);
     * $container = $conn->get_container("cdn_enabled");
     * $container->purge_from_cdn("user@domain.com");
     * # or
     * $container->purge_from_cdn();
     * # or 
     * $container->purge_from_cdn("user1@domain.com,user2@domain.com");
     * @returns boolean True if successful
     * @throws CDNNotEnabledException if CDN Is not enabled on this connection
     * @throws InvalidResponseException if the response expected is not returned
     */
    function purge_from_cdn($email=null)
    {
        if (!$this->cfs_http->getCDNMUrl()) 
        {
            throw new CDNNotEnabledException(
                "Authentication response did not indicate CDN availability");
        }
        $status = $this->cfs_http->purge_from_cdn($this->name, $email);
        if ($status < 199 or $status > 299) {
            throw new InvalidResponseException(
                "Invalid response (".$status."): ".$this->cfs_http->get_error());
        } 
        return True;
    }
    /**
     * Enable ACL restriction by User Agent for this container.
     *
     * Example:
     * <code>
     * # ... authentication code excluded (see previous examples) ...
     * #
     * $conn = new CF_Authentication($auth);
     *
     * $public_container = $conn->get_container("public");
     *
     * # Enable ACL by Referrer
     * $public_container->acl_referrer("Mozilla");
     * </code>
     *
     * @returns boolean True if successful
     * @throws CDNNotEnabledException CDN functionality not returned during auth
     * @throws AuthenticationException if auth token is not valid/expired
     * @throws InvalidResponseException unexpected response
     */
    function acl_user_agent($cdn_acl_user_agent="") {
        if ($this->cfs_http->getCDNMUrl() == NULL) {
            throw new CDNNotEnabledException(
                "Authentication response did not indicate CDN availability");
        }
        list($status,$reason) =
            $this->cfs_http->update_cdn_container($this->name,
                                                  $this->cdn_ttl,
                                                  $this->cdn_log_retention,
                                                  $cdn_acl_user_agent,
                                                  $this->cdn_acl_referrer
                );
        if (!in_array($status, array(202,404))) {
            throw new InvalidResponseException(
                "Invalid response (".$status."): ".$this->cfs_http->get_error());
        }
        $this->cdn_acl_user_agent = $cdn_acl_user_agent;
        return True;
    }

    /**
     * Enable ACL restriction by referer for this container.
     *
     * Example:
     * <code>
     * # ... authentication code excluded (see previous examples) ...
     * #
     * $conn = new CF_Authentication($auth);
     *
     * $public_container = $conn->get_container("public");
     *
     * # Enable Referrer
     * $public_container->acl_referrer("http://www.example.com/gallery.php");
     * </code>
     *
     * @returns boolean True if successful
     * @throws CDNNotEnabledException CDN functionality not returned during auth
     * @throws AuthenticationException if auth token is not valid/expired
     * @throws InvalidResponseException unexpected response
     */
    function acl_referrer($cdn_acl_referrer="") {
        if ($this->cfs_http->getCDNMUrl() == NULL) {
            throw new CDNNotEnabledException(
                "Authentication response did not indicate CDN availability");
        }
        list($status,$reason) =
            $this->cfs_http->update_cdn_container($this->name,
                                                  $this->cdn_ttl,
                                                  $this->cdn_log_retention,
                                                  $this->cdn_acl_user_agent,
                                                  $cdn_acl_referrer
                );
        if (!in_array($status, array(202,404))) {
            throw new InvalidResponseException(
                "Invalid response (".$status."): ".$this->cfs_http->get_error());
        }
        $this->cdn_acl_referrer = $cdn_acl_referrer;
        return True;
    }
    
    /**
     * Enable log retention for this CDN container.
     *
     * Enable CDN log retention on the container. If enabled logs will
     * be periodically (at unpredictable intervals) compressed and
     * uploaded to a ".CDN_ACCESS_LOGS" container in the form of
     * "container_name.YYYYMMDDHH-XXXX.gz". Requires CDN be enabled on
     * the account.
     * 
     * Example:
     * <code>
     * # ... authentication code excluded (see previous examples) ...
     * #
     * $conn = new CF_Authentication($auth);
     *
     * $public_container = $conn->get_container("public");
     *
     * # Enable logs retention.
     * $public_container->log_retention(True);
     * </code>
     *
     * @returns boolean True if successful
     * @throws CDNNotEnabledException CDN functionality not returned during auth
     * @throws AuthenticationException if auth token is not valid/expired
     * @throws InvalidResponseException unexpected response
     */
    function log_retention($cdn_log_retention=False) {
        if ($this->cfs_http->getCDNMUrl() == NULL) {
            throw new CDNNotEnabledException(
                "Authentication response did not indicate CDN availability");
        }
        list($status,$reason) =
            $this->cfs_http->update_cdn_container($this->name,
                                                  $this->cdn_ttl,
                                                  $cdn_log_retention,
                                                  $this->cdn_acl_user_agent,
                                                  $this->cdn_acl_referrer
                );
        if (!in_array($status, array(202,404))) {
            throw new InvalidResponseException(
                "Invalid response (".$status."): ".$this->cfs_http->get_error());
        }
        $this->cdn_log_retention = $cdn_log_retention;
        return True;
    }
    
    /**
     * Disable the CDN sharing for this container
     *
     * Use this method to disallow distribution into the CDN of this Container's
     * content.
     *
     * NOTE: Any content already cached in the CDN will continue to be served
     *       from its cache until the TTL expiration transpires.  The default
     *       TTL is typically one day, so "privatizing" the Container will take
     *       up to 24 hours before the content is purged from the CDN cache.
     *
     * Example:
     * <code>
     * # ... authentication code excluded (see previous examples) ...
     * #
     * $conn = new CF_Authentication($auth);
     *
     * $public_container = $conn->get_container("public");
     *
     * # Disable CDN accessability
     * # ... still cached up to a month based on previous example
     * #
     * $public_container->make_private();
     * </code>
     *
     * @returns boolean True if successful
     * @throws CDNNotEnabledException CDN functionality not returned during auth
     * @throws AuthenticationException if auth token is not valid/expired
     * @throws InvalidResponseException unexpected response
     */
    function make_private()
    {
        if ($this->cfs_http->getCDNMUrl() == NULL) {
            throw new CDNNotEnabledException(
                "Authentication response did not indicate CDN availability");
        }
        list($status,$reason) = $this->cfs_http->remove_cdn_container($this->name);
        #if ($status == 401 && $this->_re_auth()) {
        #    return $this->make_private();
        #}
        if (!in_array($status, array(202,404))) {
            throw new InvalidResponseException(
                "Invalid response (".$status."): ".$this->cfs_http->get_error());
        }
        $this->cdn_enabled = False;
        $this->cdn_ttl = NULL;
        $this->cdn_uri = NULL;
        $this->cdn_ssl_uri = NULL;
        $this->cdn_streaming_uri - NULL;
        $this->cdn_log_retention = NULL;
        $this->cdn_acl_user_agent = NULL;
        $this->cdn_acl_referrer = NULL;
        return True;
    }

    /**
     * Check if this Container is being publicly served via CDN
     *
     * Use this method to determine if the Container's content is currently
     * available through the CDN.
     *
     * Example:
     * <code>
     * # ... authentication code excluded (see previous examples) ...
     * #
     * $conn = new CF_Authentication($auth);
     *
     * $public_container = $conn->get_container("public");
     *
     * # Display CDN accessability
     * #
     * $public_container->is_public() ? print "Yes" : print "No";
     * </code>
     *
     * @returns boolean True if enabled, False otherwise
     */
    function is_public()
    {
        return $this->cdn_enabled == True ? True : False;
    }

    /**
     * Create a new remote storage Object
     *
     * Return a new Object instance.  If the remote storage Object exists,
     * the instance's attributes are populated.
     *
     * Example:
     * <code>
     * # ... authentication code excluded (see previous examples) ...
     * #
     * $conn = new CF_Authentication($auth);
     *
     * $public_container = $conn->get_container("public");
     *
     * # This creates a local instance of a storage object but only creates
     * # it in the storage system when the object's write() method is called.
     * #
     * $pic = $public_container->create_object("baby.jpg");
     * </code>
     *
     * @param string $obj_name name of storage Object
     * @return obj CF_Object instance
     */
    function create_object($obj_name=NULL)
    {
        return new CF_Object($this, $obj_name);
    }

    /**
     * Return an Object instance for the remote storage Object
     *
     * Given a name, return a Object instance representing the
     * remote storage object.
     *
     * Example:
     * <code>
     * # ... authentication code excluded (see previous examples) ...
     * #
     * $conn = new CF_Authentication($auth);
     *
     * $public_container = $conn->get_container("public");
     *
     * # This call only fetches header information and not the content of
     * # the storage object.  Use the Object's read() or stream() methods
     * # to obtain the object's data.
     * #
     * $pic = $public_container->get_object("baby.jpg");
     * </code>
     *
     * @param string $obj_name name of storage Object
     * @return obj CF_Object instance
     */
    function get_object($obj_name=NULL)
    {
        return new CF_Object($this, $obj_name, True);
    }

    /**
     * Return a list of Objects
     *
     * Return an array of strings listing the Object names in this Container.
     *
     * Example:
     * <code>
     * # ... authentication code excluded (see previous examples) ...
     * #
     * $images = $conn->get_container("my photos");
     *
     * # Grab the list of all storage objects
     * #
     * $all_objects = $images->list_objects();
     *
     * # Grab subsets of all storage objects
     * #
     * $first_ten = $images->list_objects(10);
     * 
     * # Note the use of the previous result's last object name being
     * # used as the 'marker' parameter to fetch the next 10 objects
     * #
     * $next_ten = $images->list_objects(10, $first_ten[count($first_ten)-1]);
     *
     * # Grab images starting with "birthday_party" and default limit/marker
     * # to match all photos with that prefix
     * #
     * $prefixed = $images->list_objects(0, NULL, "birthday");
     *
     * # Assuming you have created the appropriate directory marker Objects,
     * # you can traverse your pseudo-hierarchical containers
     * # with the "path" argument.
     * #
     * $animals = $images->list_objects(0,NULL,NULL,"pictures/animals");
     * $dogs = $images->list_objects(0,NULL,NULL,"pictures/animals/dogs");
     * </code>
     *
     * @param int $limit <i>optional</i> only return $limit names
     * @param int $marker <i>optional</i> subset of names starting at $marker
     * @param string $prefix <i>optional</i> Objects whose names begin with $prefix
     * @param string $path <i>optional</i> only return results under "pathname"
     * @return array array of strings
     * @throws InvalidResponseException unexpected response
     */
    function list_objects($limit=0, $marker=NULL, $prefix=NULL, $path=NULL)
    {
        list($status, $reason, $obj_list) =
            $this->cfs_http->list_objects($this->name, $limit,
                $marker, $prefix, $path);
        #if ($status == 401 && $this->_re_auth()) {
        #    return $this->list_objects($limit, $marker, $prefix, $path);
        #}
        if ($status < 200 || $status > 299) {
            throw new InvalidResponseException(
                "Invalid response (".$status."): ".$this->cfs_http->get_error());
        }
        return $obj_list;
    }

    /**
     * Return an array of Objects
     *
     * Return an array of Object instances in this Container.
     *
     * Example:
     * <code>
     * # ... authentication code excluded (see previous examples) ...
     * #
     * $images = $conn->get_container("my photos");
     *
     * # Grab the list of all storage objects
     * #
     * $all_objects = $images->get_objects();
     *
     * # Grab subsets of all storage objects
     * #
     * $first_ten = $images->get_objects(10);
     *
     * # Note the use of the previous result's last object name being
     * # used as the 'marker' parameter to fetch the next 10 objects
     * #
     * $next_ten = $images->list_objects(10, $first_ten[count($first_ten)-1]);
     *
     * # Grab images starting with "birthday_party" and default limit/marker
     * # to match all photos with that prefix
     * #
     * $prefixed = $images->get_objects(0, NULL, "birthday");
     *
     * # Assuming you have created the appropriate directory marker Objects,
     * # you can traverse your pseudo-hierarchical containers
     * # with the "path" argument.
     * #
     * $animals = $images->get_objects(0,NULL,NULL,"pictures/animals");
     * $dogs = $images->get_objects(0,NULL,NULL,"pictures/animals/dogs");
     * </code>
     *
     * @param int $limit <i>optional</i> only return $limit names
     * @param int $marker <i>optional</i> subset of names starting at $marker
     * @param string $prefix <i>optional</i> Objects whose names begin with $prefix
     * @param string $path <i>optional</i> only return results under "pathname"
     * @return array array of strings
     * @throws InvalidResponseException unexpected response
     */
    function get_objects($limit=0, $marker=NULL, $prefix=NULL, $path=NULL)
    {
        list($status, $reason, $obj_array) =
            $this->cfs_http->get_objects($this->name, $limit,
                $marker, $prefix, $path);
        #if ($status == 401 && $this->_re_auth()) {
        #    return $this->get_objects($limit, $marker, $prefix, $path);
        #}
        if ($status < 200 || $status > 299) {
            throw new InvalidResponseException(
                "Invalid response (".$status."): ".$this->cfs_http->get_error());
        }
        $objects = array();
        foreach ($obj_array as $obj) {
            $tmp = new CF_Object($this, $obj["name"], False, False);
            $tmp->content_type = $obj["content_type"];
            $tmp->content_length = (float) $obj["bytes"];
            $tmp->set_etag($obj["hash"]);
            $tmp->last_modified = $obj["last_modified"];
            $objects[] = $tmp;
        }
        return $objects;
    }

    /**
     * Copy a remote storage Object to a target Container
     *
     * Given an Object instance or name and a target Container instance or name, copy copies the remote Object
     * and all associated metadata.
     *
     * Example:
     * <code>
     * # ... authentication code excluded (see previous examples) ...
     * #
     * $conn = new CF_Authentication($auth);
     *
     * $images = $conn->get_container("my photos");
     *
     * # Copy specific object
     * #
     * $images->copy_object_to("disco_dancing.jpg","container_target");
     * </code>
     *
     * @param obj $obj name or instance of Object to copy
     * @param obj $container_target name or instance of target Container
     * @param string $dest_obj_name name of target object (optional - uses source name if omitted)
     * @param array $metadata metadata array for new object (optional)
     * @param array $headers header fields array for the new object (optional)
     * @return boolean <kbd>true</kbd> if successfully copied
     * @throws SyntaxException invalid Object/Container name
     * @throws NoSuchObjectException remote Object does not exist
     * @throws InvalidResponseException unexpected response
     */
    function copy_object_to($obj,$container_target,$dest_obj_name=NULL,$metadata=NULL,$headers=NULL)
    {
        $obj_name = NULL;
        if (is_object($obj)) {
            if (get_class($obj) == "CF_Object") {
                $obj_name = $obj->name;
            }
        }
        if (is_string($obj)) {
            $obj_name = $obj;
        }
        if (!$obj_name) {
            throw new SyntaxException("Object name not set.");
        }

				if ($dest_obj_name === NULL) {
            $dest_obj_name = $obj_name;
				}

        $container_name_target = NULL;
        if (is_object($container_target)) {
            if (get_class($container_target) == "CF_Container") {
                $container_name_target = $container_target->name;
            }
        }
        if (is_string($container_target)) {
            $container_name_target = $container_target;
        }
        if (!$container_name_target) {
            throw new SyntaxException("Container name target not set.");
        }

        $status = $this->cfs_http->copy_object($obj_name,$dest_obj_name,$this->name,$container_name_target,$metadata,$headers);
        if ($status == 404) {
            $m = "Specified object '".$this->name."/".$obj_name;
            $m.= "' did not exist as source to copy from or '".$container_name_target."' did not exist as target to copy to.";
            throw new NoSuchObjectException($m);
        }
        if ($status < 200 || $status > 299) {
            throw new InvalidResponseException(
                "Invalid response (".$status."): ".$this->cfs_http->get_error());
        }
        return true;
    }

    /**
     * Copy a remote storage Object from a source Container
     *
     * Given an Object instance or name and a source Container instance or name, copy copies the remote Object
     * and all associated metadata.
     *
     * Example:
     * <code>
     * # ... authentication code excluded (see previous examples) ...
     * #
     * $conn = new CF_Authentication($auth);
     *
     * $images = $conn->get_container("my photos");
     *
     * # Copy specific object
     * #
     * $images->copy_object_from("disco_dancing.jpg","container_source");
     * </code>
     *
     * @param obj $obj name or instance of Object to copy
     * @param obj $container_source name or instance of source Container
     * @param string $dest_obj_name name of target object (optional - uses source name if omitted)
     * @param array $metadata metadata array for new object (optional)
     * @param array $headers header fields array for the new object (optional)
     * @return boolean <kbd>true</kbd> if successfully copied
     * @throws SyntaxException invalid Object/Container name
     * @throws NoSuchObjectException remote Object does not exist
     * @throws InvalidResponseException unexpected response
     */
    function copy_object_from($obj,$container_source,$dest_obj_name=NULL,$metadata=NULL,$headers=NULL)
    {
        $obj_name = NULL;
        if (is_object($obj)) {
            if (get_class($obj) == "CF_Object") {
                $obj_name = $obj->name;
            }
        }
        if (is_string($obj)) {
            $obj_name = $obj;
        }
        if (!$obj_name) {
            throw new SyntaxException("Object name not set.");
        }

				if ($dest_obj_name === NULL) {
            $dest_obj_name = $obj_name;
				}

        $container_name_source = NULL;
        if (is_object($container_source)) {
            if (get_class($container_source) == "CF_Container") {
                $container_name_source = $container_source->name;
            }
        }
        if (is_string($container_source)) {
            $container_name_source = $container_source;
        }
        if (!$container_name_source) {
            throw new SyntaxException("Container name source not set.");
        }

        $status = $this->cfs_http->copy_object($obj_name,$dest_obj_name,$container_name_source,$this->name,$metadata,$headers);
        if ($status == 404) {
            $m = "Specified object '".$container_name_source."/".$obj_name;
            $m.= "' did not exist as source to copy from or '".$this->name."/".$obj_name."' did not exist as target to copy to.";
            throw new NoSuchObjectException($m);
        }
        if ($status < 200 || $status > 299) {
            throw new InvalidResponseException(
                "Invalid response (".$status."): ".$this->cfs_http->get_error());
        }
        
        return true;
    }

    /**
     * Move a remote storage Object to a target Container
     *
     * Given an Object instance or name and a target Container instance or name, move copies the remote Object
     * and all associated metadata and deletes the source Object afterwards
     *
     * Example:
     * <code>
     * # ... authentication code excluded (see previous examples) ...
     * #
     * $conn = new CF_Authentication($auth);
     *
     * $images = $conn->get_container("my photos");
     *
     * # Move specific object
     * #
     * $images->move_object_to("disco_dancing.jpg","container_target");
     * </code>
     *
     * @param obj $obj name or instance of Object to move
     * @param obj $container_target name or instance of target Container
     * @param string $dest_obj_name name of target object (optional - uses source name if omitted)
     * @param array $metadata metadata array for new object (optional)
     * @param array $headers header fields array for the new object (optional)
     * @return boolean <kbd>true</kbd> if successfully moved
     * @throws SyntaxException invalid Object/Container name
     * @throws NoSuchObjectException remote Object does not exist
     * @throws InvalidResponseException unexpected response
     */
    function move_object_to($obj,$container_target,$dest_obj_name=NULL,$metadata=NULL,$headers=NULL)
    {
    	$retVal = false;

        if(self::copy_object_to($obj,$container_target,$dest_obj_name,$metadata,$headers)) {
        	$retVal = self::delete_object($obj,$this->name);
        }

        return $retVal;
    }

    /**
     * Move a remote storage Object from a source Container
     *
     * Given an Object instance or name and a source Container instance or name, move copies the remote Object
     * and all associated metadata and deletes the source Object afterwards
     *
     * Example:
     * <code>
     * # ... authentication code excluded (see previous examples) ...
     * #
     * $conn = new CF_Authentication($auth);
     *
     * $images = $conn->get_container("my photos");
     *
     * # Move specific object
     * #
     * $images->move_object_from("disco_dancing.jpg","container_target");
     * </code>
     *
     * @param obj $obj name or instance of Object to move
     * @param obj $container_source name or instance of target Container
     * @param string $dest_obj_name name of target object (optional - uses source name if omitted)
     * @param array $metadata metadata array for new object (optional)
     * @param array $headers header fields array for the new object (optional)
     * @return boolean <kbd>true</kbd> if successfully moved
     * @throws SyntaxException invalid Object/Container name
     * @throws NoSuchObjectException remote Object does not exist
     * @throws InvalidResponseException unexpected response
     */
    function move_object_from($obj,$container_source,$dest_obj_name=NULL,$metadata=NULL,$headers=NULL)
    {
    	$retVal = false;

        if(self::copy_object_from($obj,$container_source,$dest_obj_name,$metadata,$headers)) {
        	$retVal = self::delete_object($obj,$container_source);
        } 	

        return $retVal;
    }

    /**
     * Delete a remote storage Object
     *
     * Given an Object instance or name, permanently remove the remote Object
     * and all associated metadata.
     *
     * Example:
     * <code>
     * # ... authentication code excluded (see previous examples) ...
     * #
     * $conn = new CF_Authentication($auth);
     *
     * $images = $conn->get_container("my photos");
     *
     * # Delete specific object
     * #
     * $images->delete_object("disco_dancing.jpg");
     * </code>
     *
     * @param obj $obj name or instance of Object to delete
     * @param obj $container name or instance of Container in which the object resides (optional)
     * @return boolean <kbd>True</kbd> if successfully removed
     * @throws SyntaxException invalid Object name
     * @throws NoSuchObjectException remote Object does not exist
     * @throws InvalidResponseException unexpected response
     */
    function delete_object($obj,$container=NULL)
    {
        $obj_name = NULL;
        if (is_object($obj)) {
            if (get_class($obj) == "CF_Object") {
                $obj_name = $obj->name;
            }
        }
        if (is_string($obj)) {
            $obj_name = $obj;
        }
        if (!$obj_name) {
            throw new SyntaxException("Object name not set.");
        }

        $container_name = NULL;

        if($container === NULL) {
        	$container_name = $this->name;
        }
        else {
	        if (is_object($container)) {
	            if (get_class($container) == "CF_Container") {
	                $container_name = $container->name;
	            }
	        }
	        if (is_string($container)) {
	            $container_name = $container;
	        }
	        if (!$container_name) {
	            throw new SyntaxException("Container name source not set.");
	        }
        }

        $status = $this->cfs_http->delete_object($container_name, $obj_name);
        #if ($status == 401 && $this->_re_auth()) {
        #    return $this->delete_object($obj);
        #}
        if ($status == 404) {
            $m = "Specified object '".$container_name."/".$obj_name;
            $m.= "' did not exist to delete.";
            throw new NoSuchObjectException($m);
        }
        if ($status != 204) {
            throw new InvalidResponseException(
                "Invalid response (".$status."): ".$this->cfs_http->get_error());
        }
        return True;
    }

    /**
     * Helper function to create "path" elements for a given Object name
     *
     * Given an Object whos name contains '/' path separators, this function
     * will create the "directory marker" Objects of one byte with the
     * Content-Type of "application/directory".
     *
     * It assumes the last element of the full path is the "real" Object
     * and does NOT create a remote storage Object for that last element.
     */
    function create_paths($path_name)
    {
        if ($path_name[0] == '/') {
            $path_name = mb_substr($path_name, 0, 1);
        }
        $elements = explode('/', $path_name, -1);
        $build_path = "";
        foreach ($elements as $idx => $val) {
            if (!$build_path) {
                $build_path = $val;
            } else {
                $build_path .= "/" . $val;
            }
            $obj = new CF_Object($this, $build_path);
            $obj->content_type = "application/directory";
            $obj->write(".", 1);
        }
    }

    /**
     * Internal method to grab CDN/Container info if appropriate to do so
     *
     * @throws InvalidResponseException unexpected response
     */
    private function _cdn_initialize()
    {
        list($status, $reason, $cdn_enabled, $cdn_ssl_uri, $cdn_streaming_uri, $cdn_uri, $cdn_ttl,
             $cdn_log_retention, $cdn_acl_user_agent, $cdn_acl_referrer) =
            $this->cfs_http->head_cdn_container($this->name);
        #if ($status == 401 && $this->_re_auth()) {
        #    return $this->_cdn_initialize();
        #}
        if (!in_array($status, array(204,404))) {
            throw new InvalidResponseException(
                "Invalid response (".$status."): ".$this->cfs_http->get_error());
        }
        $this->cdn_enabled = $cdn_enabled;
        $this->cdn_streaming_uri = $cdn_streaming_uri;
        $this->cdn_ssl_uri = $cdn_ssl_uri;
        $this->cdn_uri = $cdn_uri;
        $this->cdn_ttl = $cdn_ttl;
        $this->cdn_log_retention = $cdn_log_retention;
        $this->cdn_acl_user_agent = $cdn_acl_user_agent;
        $this->cdn_acl_referrer = $cdn_acl_referrer;
    }

    #private function _re_auth()
    #{
    #    $new_auth = new CF_Authentication(
    #        $this->cfs_auth->username,
    #        $this->cfs_auth->api_key,
    #        $this->cfs_auth->auth_host,
    #        $this->cfs_auth->account);
    #    $new_auth->authenticate();
    #    $this->cfs_auth = $new_auth;
    #    $this->cfs_http->setCFAuth($this->cfs_auth);
    #    return True;
    #}
}


/**
 * Object operations
 *
 * An Object is analogous to a file on a conventional filesystem. You can
 * read data from, or write data to your Objects. You can also associate 
 * arbitrary metadata with them.
 *
 * @package php-cloudfiles
 */
class CF_Object
{
    public $container;
    public $name;
    public $last_modified;
    public $content_type;
    public $content_length;
    public $metadata;
    public $headers;
    public $manifest;
    private $etag;

    /**
     * Class constructor
     *
     * @param obj $container CF_Container instance
     * @param string $name name of Object
     * @param boolean $force_exists if set, throw an error if Object doesn't exist
     */
    function __construct(&$container, $name, $force_exists=False, $dohead=True)
    {
        if ($name[0] == "/") {
            $r = "Object name '".$name;
            $r .= "' cannot contain begin with a '/' character.";
            throw new SyntaxException($r);
        }
        if (strlen($name) > MAX_OBJECT_NAME_LEN) {
            throw new SyntaxException("Object name exceeds "
                . "maximum allowed length.");
        }
        $this->container = $container;
        $this->name = $name;
        $this->etag = NULL;
        $this->_etag_override = False;
        $this->last_modified = NULL;
        $this->content_type = NULL;
        $this->content_length = 0;
        $this->metadata = array();
        $this->headers = array();
        $this->manifest = NULL;
        if ($dohead) {
            if (!$this->_initialize() && $force_exists) {
                throw new NoSuchObjectException("No such object '".$name."'");
            }
        }
    }

    /**
     * String representation of Object
     *
     * Pretty print the Object's location and name
     *
     * @return string Object information
     */
    function __toString()
    {
        return $this->container->name . "/" . $this->name;
    }

    /**
     * Internal check to get the proper mimetype.
     *
     * This function would go over the available PHP methods to get
     * the MIME type.
     *
     * By default it will try to use the PHP fileinfo library which is
     * available from PHP 5.3 or as an PECL extension
     * (http://pecl.php.net/package/Fileinfo).
     *
     * It will get the magic file by default from the system wide file
     * which is usually available in /usr/share/magic on Unix or try
     * to use the file specified in the source directory of the API
     * (share directory).
     *
     * if fileinfo is not available it will try to use the internal
     * mime_content_type function.
     * 
     * @param string $handle name of file or buffer to guess the type from
     * @return boolean <kbd>True</kbd> if successful
     * @throws BadContentTypeException
     */
    function _guess_content_type($handle) {
        if ($this->content_type)
            return;
            
//         if (function_exists("finfo_open")) {
//             $local_magic = dirname(__FILE__) . "/share/magic";
//             $finfo = @finfo_open(FILEINFO_MIME, $local_magic);
// 
//             if (!$finfo) 
//                 $finfo = @finfo_open(FILEINFO_MIME);
//                 
//             if ($finfo) {
// 
//                 if (is_file((string)$handle))
//                     $ct = @finfo_file($finfo, $handle);
//                 else 
//                     $ct = @finfo_buffer($finfo, $handle);
// 
//                 /* PHP 5.3 fileinfo display extra information like
//                    charset so we remove everything after the ; since
//                    we are not into that stuff */
//                 if ($ct) {
//                     $extra_content_type_info = strpos($ct, "; ");
//                     if ($extra_content_type_info)
//                         $ct = substr($ct, 0, $extra_content_type_info);
//                 }
// 
//                 if ($ct && $ct != 'application/octet-stream')
//                     $this->content_type = $ct;
// 
//                 @finfo_close($finfo);
//             }
//         }
// 
//         if (!$this->content_type && (string)is_file($handle) && function_exists("mime_content_type")) {
//             $this->content_type = @mime_content_type($handle);
//         }

		//use OC's mimetype detection for files
		if(is_file($handle)){
			$this->content_type=OC_Helper::getMimeType($handle);
		}else{
			$this->content_type=OC_Helper::getStringMimeType($handle);
		}

        if (!$this->content_type) {
            throw new BadContentTypeException("Required Content-Type not set");
        }
        return True;
    }
    
    /**
     * String representation of the Object's public URI
     *
     * A string representing the Object's public URI assuming that it's
     * parent Container is CDN-enabled.
     *
     * Example:
     * <code>
     * # ... authentication/connection/container code excluded
     * # ... see previous examples
     *
     * # Print out the Object's CDN URI (if it has one) in an HTML img-tag
     * #
     * print "<img src='$pic->public_uri()' />\n";
     * </code>
     *
     * @return string Object's public URI or NULL
     */
    function public_uri()
    {
        if ($this->container->cdn_enabled) {
            return $this->container->cdn_uri . "/" . $this->name;
        }
        return NULL;
    }

       /**
     * String representation of the Object's public SSL URI
     *
     * A string representing the Object's public SSL URI assuming that it's
     * parent Container is CDN-enabled.
     *
     * Example:
     * <code>
     * # ... authentication/connection/container code excluded
     * # ... see previous examples
     *
     * # Print out the Object's CDN SSL URI (if it has one) in an HTML img-tag
     * #
     * print "<img src='$pic->public_ssl_uri()' />\n";
     * </code>
     *
     * @return string Object's public SSL URI or NULL
     */
    function public_ssl_uri()
    {
        if ($this->container->cdn_enabled) {
            return $this->container->cdn_ssl_uri . "/" . $this->name;
        }
        return NULL;
    }
    /**
     * String representation of the Object's public Streaming URI
     *
     * A string representing the Object's public Streaming URI assuming that it's
     * parent Container is CDN-enabled.
     *
     * Example:
     * <code>
     * # ... authentication/connection/container code excluded
     * # ... see previous examples
     *
     * # Print out the Object's CDN Streaming URI (if it has one) in an HTML img-tag
     * #
     * print "<img src='$pic->public_streaming_uri()' />\n";
     * </code>
     *
     * @return string Object's public Streaming URI or NULL
     */
    function public_streaming_uri()
    {
        if ($this->container->cdn_enabled) {
            return $this->container->cdn_streaming_uri . "/" . $this->name;
        }
        return NULL;
    }

    /**
     * Read the remote Object's data
     *
     * Returns the Object's data.  This is useful for smaller Objects such
     * as images or office documents.  Object's with larger content should use
     * the stream() method below.
     *
     * Pass in $hdrs array to set specific custom HTTP headers such as
     * If-Match, If-None-Match, If-Modified-Since, Range, etc.
     *
     * Example:
     * <code>
     * # ... authentication/connection/container code excluded
     * # ... see previous examples
     *
     * $my_docs = $conn->get_container("documents");
     * $doc = $my_docs->get_object("README");
     * $data = $doc->read(); # read image content into a string variable
     * print $data;
     *
     * # Or see stream() below for a different example.
     * #
     * </code>
     *
     * @param array $hdrs user-defined headers (Range, If-Match, etc.)
     * @return string Object's data
     * @throws InvalidResponseException unexpected response
     */
    function read($hdrs=array())
    {
        list($status, $reason, $data) =
            $this->container->cfs_http->get_object_to_string($this, $hdrs);
        #if ($status == 401 && $this->_re_auth()) {
        #    return $this->read($hdrs);
        #}
        if (($status < 200) || ($status > 299
                && $status != 412 && $status != 304)) {
            throw new InvalidResponseException("Invalid response (".$status."): "
                . $this->container->cfs_http->get_error());
        }
        return $data;
    }

    /**
     * Streaming read of Object's data
     *
     * Given an open PHP resource (see PHP's fopen() method), fetch the Object's
     * data and write it to the open resource handle.  This is useful for
     * streaming an Object's content to the browser (videos, images) or for
     * fetching content to a local file.
     *
     * Pass in $hdrs array to set specific custom HTTP headers such as
     * If-Match, If-None-Match, If-Modified-Since, Range, etc.
     *
     * Example:
     * <code>
     * # ... authentication/connection/container code excluded
     * # ... see previous examples
     *
     * # Assuming this is a web script to display the README to the
     * # user's browser:
     * #
     * <?php
     * // grab README from storage system
     * //
     * $my_docs = $conn->get_container("documents");
     * $doc = $my_docs->get_object("README");
     *
     * // Hand it back to user's browser with appropriate content-type
     * //
     * header("Content-Type: " . $doc->content_type);
     * $output = fopen("php://output", "w");
     * $doc->stream($output); # stream object content to PHP's output buffer
     * fclose($output);
     * ?>
     *
     * # See read() above for a more simple example.
     * #
     * </code>
     *
     * @param resource $fp open resource for writing data to
     * @param array $hdrs user-defined headers (Range, If-Match, etc.)
     * @return string Object's data
     * @throws InvalidResponseException unexpected response
     */
    function stream(&$fp, $hdrs=array())
    {
        list($status, $reason) = 
                $this->container->cfs_http->get_object_to_stream($this,$fp,$hdrs);
        #if ($status == 401 && $this->_re_auth()) {
        #    return $this->stream($fp, $hdrs);
        #}
        if (($status < 200) || ($status > 299
                && $status != 412 && $status != 304)) {
            throw new InvalidResponseException("Invalid response (".$status."): "
                .$reason);
        }
        return True;
    }

    /**
     * Store new Object metadata
     *
     * Write's an Object's metadata to the remote Object.  This will overwrite
     * an prior Object metadata.
     *
     * Example:
     * <code>
     * # ... authentication/connection/container code excluded
     * # ... see previous examples
     *
     * $my_docs = $conn->get_container("documents");
     * $doc = $my_docs->get_object("README");
     *
     * # Define new metadata for the object
     * #
     * $doc->metadata = array(
     *     "Author" => "EJ",
     *     "Subject" => "How to use the PHP tests",
     *     "Version" => "1.2.2"
     * );
     *
     * # Define additional headers for the object
     * #
     * $doc->headers = array(
     *     "Content-Disposition" => "attachment",
     * );
     *
     * # Push the new metadata up to the storage system
     * #
     * $doc->sync_metadata();
     * </code>
     *
     * @return boolean <kbd>True</kbd> if successful, <kbd>False</kbd> otherwise
     * @throws InvalidResponseException unexpected response
     */
    function sync_metadata()
    {
        if (!empty($this->metadata) || !empty($this->headers) || $this->manifest) {
            $status = $this->container->cfs_http->update_object($this);
            #if ($status == 401 && $this->_re_auth()) {
            #    return $this->sync_metadata();
            #}
            if ($status != 202) {
                throw new InvalidResponseException("Invalid response ("
                    .$status."): ".$this->container->cfs_http->get_error());
            }
            return True;
        }
        return False;
    }
    /**
     * Store new Object manifest
     *
     * Write's an Object's manifest to the remote Object.  This will overwrite
     * an prior Object manifest.
     *
     * Example:
     * <code>
     * # ... authentication/connection/container code excluded
     * # ... see previous examples
     *
     * $my_docs = $conn->get_container("documents");
     * $doc = $my_docs->get_object("README");
     *
     * # Define new manifest for the object
     * #
     * $doc->manifest = "container/prefix";
     *
     * # Push the new manifest up to the storage system
     * #
     * $doc->sync_manifest();
     * </code>
     *
     * @return boolean <kbd>True</kbd> if successful, <kbd>False</kbd> otherwise
     * @throws InvalidResponseException unexpected response
     */

    function sync_manifest()
    {
        return $this->sync_metadata();
    }
    /**
     * Upload Object's data to Cloud Files
     *
     * Write data to the remote Object.  The $data argument can either be a
     * PHP resource open for reading (see PHP's fopen() method) or an in-memory
     * variable.  If passing in a PHP resource, you must also include the $bytes
     * parameter.
     *
     * Example:
     * <code>
     * # ... authentication/connection/container code excluded
     * # ... see previous examples
     *
     * $my_docs = $conn->get_container("documents");
     * $doc = $my_docs->get_object("README");
     *
     * # Upload placeholder text in my README
     * #
     * $doc->write("This is just placeholder text for now...");
     * </code>
     *
     * @param string|resource $data string or open resource
     * @param float $bytes amount of data to upload (required for resources)
     * @param boolean $verify generate, send, and compare MD5 checksums
     * @return boolean <kbd>True</kbd> when data uploaded successfully
     * @throws SyntaxException missing required parameters
     * @throws BadContentTypeException if no Content-Type was/could be set
     * @throws MisMatchedChecksumException $verify is set and checksums unequal
     * @throws InvalidResponseException unexpected response
     */
    function write($data=NULL, $bytes=0, $verify=True)
    {
        if (!$data && !is_string($data)) {
            throw new SyntaxException("Missing data source.");
        }
        if ($bytes > MAX_OBJECT_SIZE) {
            throw new SyntaxException("Bytes exceeds maximum object size.");
        }
        if ($verify) {
            if (!$this->_etag_override) {
                $this->etag = $this->compute_md5sum($data);
            }
        } else {
            $this->etag = NULL;
        }

        $close_fh = False;
        if (!is_resource($data)) {
            # A hack to treat string data as a file handle.  php://memory feels
            # like a better option, but it seems to break on Windows so use
            # a temporary file instead.
            #
            $fp = fopen("php://temp", "wb+");
            #$fp = fopen("php://memory", "wb+");
            fwrite($fp, $data, strlen($data));
            rewind($fp);
            $close_fh = True;
            $this->content_length = (float) strlen($data);
            if ($this->content_length > MAX_OBJECT_SIZE) {
                throw new SyntaxException("Data exceeds maximum object size");
            }
            $ct_data = substr($data, 0, 64);
        } else {
            $this->content_length = $bytes;
            $fp = $data;
            $ct_data = fread($data, 64);
            rewind($data);
        }

        $this->_guess_content_type($ct_data);

        list($status, $reason, $etag) =
                $this->container->cfs_http->put_object($this, $fp);
        #if ($status == 401 && $this->_re_auth()) {
        #    return $this->write($data, $bytes, $verify);
        #}
        if ($status == 412) {
            if ($close_fh) { fclose($fp); }
            throw new SyntaxException("Missing Content-Type header");
        }
        if ($status == 422) {
            if ($close_fh) { fclose($fp); }
            throw new MisMatchedChecksumException(
                "Supplied and computed checksums do not match.");
        }
        if ($status != 201) {
            if ($close_fh) { fclose($fp); }
            throw new InvalidResponseException("Invalid response (".$status."): "
                . $this->container->cfs_http->get_error());
        }
        if (!$verify) {
            $this->etag = $etag;
        }
        if ($close_fh) { fclose($fp); }
        return True;
    }

    /**
     * Upload Object data from local filename
     *
     * This is a convenience function to upload the data from a local file.  A
     * True value for $verify will cause the method to compute the Object's MD5
     * checksum prior to uploading.
     *
     * Example:
     * <code>
     * # ... authentication/connection/container code excluded
     * # ... see previous examples
     *
     * $my_docs = $conn->get_container("documents");
     * $doc = $my_docs->get_object("README");
     *
     * # Upload my local README's content
     * #
     * $doc->load_from_filename("/home/ej/cloudfiles/readme");
     * </code>
     *
     * @param string $filename full path to local file
     * @param boolean $verify enable local/remote MD5 checksum validation
     * @return boolean <kbd>True</kbd> if data uploaded successfully
     * @throws SyntaxException missing required parameters
     * @throws BadContentTypeException if no Content-Type was/could be set
     * @throws MisMatchedChecksumException $verify is set and checksums unequal
     * @throws InvalidResponseException unexpected response
     * @throws IOException error opening file
     */
    function load_from_filename($filename, $verify=True)
    {
        $fp = @fopen($filename, "r");
        if (!$fp) {
            throw new IOException("Could not open file for reading: ".$filename);
        }

        clearstatcache();
        
        $size = (float) sprintf("%u", filesize($filename));
        if ($size > MAX_OBJECT_SIZE) {
            throw new SyntaxException("File size exceeds maximum object size.");
        }

        $this->_guess_content_type($filename);
        
        $this->write($fp, $size, $verify);
        fclose($fp);
        return True;
    }

    /**
     * Save Object's data to local filename
     *
     * Given a local filename, the Object's data will be written to the newly
     * created file.
     *
     * Example:
     * <code>
     * # ... authentication/connection/container code excluded
     * # ... see previous examples
     *
     * # Whoops!  I deleted my local README, let me download/save it
     * #
     * $my_docs = $conn->get_container("documents");
     * $doc = $my_docs->get_object("README");
     *
     * $doc->save_to_filename("/home/ej/cloudfiles/readme.restored");
     * </code>
     *
     * @param string $filename name of local file to write data to
     * @return boolean <kbd>True</kbd> if successful
     * @throws IOException error opening file
     * @throws InvalidResponseException unexpected response
     */
    function save_to_filename($filename)
    {
        $fp = @fopen($filename, "wb");
        if (!$fp) {
            throw new IOException("Could not open file for writing: ".$filename);
        }
        $result = $this->stream($fp);
        fclose($fp);
        return $result;
    }
       /**
     * Purge this Object from CDN Cache.
     * Example:
     * <code>
     * # ... authentication code excluded (see previous examples) ...
     * #
     * $conn = new CF_Authentication($auth);
     * $container = $conn->get_container("cdn_enabled");
     * $obj = $container->get_object("object");
     * $obj->purge_from_cdn("user@domain.com");
     * # or
     * $obj->purge_from_cdn();
     * # or 
     * $obj->purge_from_cdn("user1@domain.com,user2@domain.com");
     * @returns boolean True if successful
     * @throws CDNNotEnabledException if CDN Is not enabled on this connection
     * @throws InvalidResponseException if the response expected is not returned
     */
    function purge_from_cdn($email=null)
    {
        if (!$this->container->cfs_http->getCDNMUrl())
        {
            throw new CDNNotEnabledException(
                "Authentication response did not indicate CDN availability");
        }
        $status = $this->container->cfs_http->purge_from_cdn($this->container->name . "/" . $this->name, $email);
        if ($status < 199 or $status > 299) {
            throw new InvalidResponseException(
                "Invalid response (".$status."): ".$this->container->cfs_http->get_error());
        }
        return True;
    }

    /**
     * Set Object's MD5 checksum
     *
     * Manually set the Object's ETag.  Including the ETag is mandatory for
     * Cloud Files to perform end-to-end verification.  Omitting the ETag forces
     * the user to handle any data integrity checks.
     *
     * @param string $etag MD5 checksum hexidecimal string
     */
    function set_etag($etag)
    {
        $this->etag = $etag;
        $this->_etag_override = True;
    }

    /**
     * Object's MD5 checksum
     *
     * Accessor method for reading Object's private ETag attribute.
     *
     * @return string MD5 checksum hexidecimal string
     */
    function getETag()
    {
        return $this->etag;
    }

    /**
     * Compute the MD5 checksum
     *
     * Calculate the MD5 checksum on either a PHP resource or data.  The argument
     * may either be a local filename, open resource for reading, or a string.
     *
     * <b>WARNING:</b> if you are uploading a big file over a stream
     * it could get very slow to compute the md5 you probably want to
     * set the $verify parameter to False in the write() method and
     * compute yourself the md5 before if you have it.
     *
     * @param filename|obj|string $data filename, open resource, or string
     * @return string MD5 checksum hexidecimal string
     */
    function compute_md5sum(&$data)
    {

        if (function_exists("hash_init") && is_resource($data)) {
            $ctx = hash_init('md5');
            while (!feof($data)) {
                $buffer = fgets($data, 65536);
                hash_update($ctx, $buffer);
            }
            $md5 = hash_final($ctx, false);
            rewind($data);
        } elseif ((string)is_file($data)) {
            $md5 = md5_file($data);
        } else {
            $md5 = md5($data);
        }
        return $md5;
    }

    /**
     * PRIVATE: fetch information about the remote Object if it exists
     */
    private function _initialize()
    {
        list($status, $reason, $etag, $last_modified, $content_type,
            $content_length, $metadata, $manifest, $headers) =
                $this->container->cfs_http->head_object($this);
        #if ($status == 401 && $this->_re_auth()) {
        #    return $this->_initialize();
        #}
        if ($status == 404) {
            return False;
        }
        if ($status < 200 || $status > 299) {
            throw new InvalidResponseException("Invalid response (".$status."): "
                . $this->container->cfs_http->get_error());
        }
        $this->etag = $etag;
        $this->last_modified = $last_modified;
        $this->content_type = $content_type;
        $this->content_length = $content_length;
        $this->metadata = $metadata;
        $this->headers = $headers;
        $this->manifest = $manifest;
        return True;
    }

    #private function _re_auth()
    #{
    #    $new_auth = new CF_Authentication(
    #        $this->cfs_auth->username,
    #        $this->cfs_auth->api_key,
    #        $this->cfs_auth->auth_host,
    #        $this->cfs_auth->account);
    #    $new_auth->authenticate();
    #    $this->container->cfs_auth = $new_auth;
    #    $this->container->cfs_http->setCFAuth($this->cfs_auth);
    #    return True;
    #}
}

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
?>
