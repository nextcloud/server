<?php
/*
 * Copyright 2010-2012 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 *  http://aws.amazon.com/apache2.0
 *
 * or in the "license" file accompanying this file. This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */


/*%******************************************************************************************%*/
// EXCEPTIONS

/**
 * Default S3 Exception.
 */
class S3_Exception extends Exception {}


/*%******************************************************************************************%*/
// MAIN CLASS

/**
 * Amazon S3 is a web service that enables you to store data in the cloud. You can then download the data
 * or use the data with other AWS services, such as Amazon Elastic Cloud Computer (EC2).
 *
 * Amazon Simple Storage Service (Amazon S3) is storage for the Internet. You can use Amazon S3 to store
 * and retrieve any amount of data at any time, from anywhere on the web. You can accomplish these tasks
 * using the AWS Management Console, which is a simple and intuitive web interface.
 *
 * To get the most out of Amazon S3, you need to understand a few simple concepts. Amazon S3 stores data
 * as objects in buckets. An object is comprised of a file and optionally any metadata that describes
 * that file.
 *
 * To store an object in Amazon S3, you upload the file you want to store to a bucket. When you upload a
 * file, you can set permissions on the object as well as any metadata.
 *
 * Buckets are the containers for objects. You can have one or more buckets. For each bucket, you can control
 * access to the bucket (who can create, delete, and list objects in the bucket), view access logs for the
 * bucket and its objects, and choose the geographical region where Amazon S3 will store the bucket and its
 * contents.
 *
 * Visit <http://aws.amazon.com/s3/> for more information.
 *
 * @version 2012.01.17
 * @license See the included NOTICE.md file for more information.
 * @copyright See the included NOTICE.md file for more information.
 * @link http://aws.amazon.com/s3/ Amazon Simple Storage Service
 * @link http://aws.amazon.com/documentation/s3/ Amazon Simple Storage Service documentation
 */
class AmazonS3 extends CFRuntime
{
	/*%******************************************************************************************%*/
	// REGIONAL ENDPOINTS

	/**
	 * Specify the queue URL for the US-Standard (Northern Virginia & Washington State) Region.
	 */
	const REGION_US_E1 = 's3.amazonaws.com';

	/**
	 * Specify the queue URL for the US-Standard (Northern Virginia & Washington State) Region.
	 */
	const REGION_VIRGINIA = self::REGION_US_E1;

	/**
	 * Specify the queue URL for the US-Standard (Northern Virginia & Washington State) Region.
	 */
	const REGION_US_STANDARD = self::REGION_US_E1;

	/**
	 * Specify the queue URL for the US-West 1 (Northern California) Region.
	 */
	const REGION_US_W1 = 's3-us-west-1.amazonaws.com';

	/**
	 * Specify the queue URL for the US-West 1 (Northern California) Region.
	 */
	const REGION_CALIFORNIA = self::REGION_US_W1;

	/**
	 * Specify the queue URL for the US-West 2 (Oregon) Region.
	 */
	const REGION_US_W2 = 's3-us-west-2.amazonaws.com';

	/**
	 * Specify the queue URL for the US-West 2 (Oregon) Region.
	 */
	const REGION_OREGON = self::REGION_US_W2;

	/**
	 * Specify the queue URL for the EU (Ireland) Region.
	 */
	const REGION_EU_W1 = 's3-eu-west-1.amazonaws.com';

	/**
	 * Specify the queue URL for the EU (Ireland) Region.
	 */
	const REGION_IRELAND = self::REGION_EU_W1;

	/**
	 * Specify the queue URL for the Asia Pacific (Singapore) Region.
	 */
	const REGION_APAC_SE1 = 's3-ap-southeast-1.amazonaws.com';

	/**
	 * Specify the queue URL for the Asia Pacific (Singapore) Region.
	 */
	const REGION_SINGAPORE = self::REGION_APAC_SE1;

	/**
	 * Specify the queue URL for the Asia Pacific (Japan) Region.
	 */
	const REGION_APAC_NE1 = 's3-ap-northeast-1.amazonaws.com';

	/**
	 * Specify the queue URL for the Asia Pacific (Japan) Region.
	 */
	const REGION_TOKYO = self::REGION_APAC_NE1;

	/**
	 * Specify the queue URL for the South America (Sao Paulo) Region.
	 */
	const REGION_SA_E1 = 's3-sa-east-1.amazonaws.com';

	/**
	 * Specify the queue URL for the South America (Sao Paulo) Region.
	 */
	const REGION_SAO_PAULO = self::REGION_SA_E1;

	/**
	 * Specify the queue URL for the United States GovCloud Region.
	 */
	const REGION_US_GOV1 = 's3-us-gov-west-1.amazonaws.com';

	/**
	 * Specify the queue URL for the United States GovCloud FIPS 140-2 Region.
	 */
	const REGION_US_GOV1_FIPS = 's3-fips-us-gov-west-1.amazonaws.com';

	/**
	 * The default endpoint.
	 */
	const DEFAULT_URL = self::REGION_US_E1;


	/*%******************************************************************************************%*/
	// REGIONAL WEBSITE ENDPOINTS

	/**
	 * Specify the queue URL for the US-Standard (Northern Virginia & Washington State) Website Region.
	 */
	const REGION_US_E1_WEBSITE = 's3-website-us-east-1.amazonaws.com';

	/**
	 * Specify the queue URL for the US-Standard (Northern Virginia & Washington State) Website Region.
	 */
	const REGION_VIRGINIA_WEBSITE = self::REGION_US_E1_WEBSITE;

	/**
	 * Specify the queue URL for the US-Standard (Northern Virginia & Washington State) Website Region.
	 */
	const REGION_US_STANDARD_WEBSITE = self::REGION_US_E1_WEBSITE;

	/**
	 * Specify the queue URL for the US-West 1 (Northern California) Website Region.
	 */
	const REGION_US_W1_WEBSITE = 's3-website-us-west-1.amazonaws.com';

	/**
	 * Specify the queue URL for the US-West 1 (Northern California) Website Region.
	 */
	const REGION_CALIFORNIA_WEBSITE = self::REGION_US_W1_WEBSITE;

	/**
	 * Specify the queue URL for the US-West 2 (Oregon) Website Region.
	 */
	const REGION_US_W2_WEBSITE = 's3-website-us-west-2.amazonaws.com';

	/**
	 * Specify the queue URL for the US-West 2 (Oregon) Website Region.
	 */
	const REGION_OREGON_WEBSITE = self::REGION_US_W2_WEBSITE;

	/**
	 * Specify the queue URL for the EU (Ireland) Website Region.
	 */
	const REGION_EU_W1_WEBSITE = 's3-website-eu-west-1.amazonaws.com';

	/**
	 * Specify the queue URL for the EU (Ireland) Website Region.
	 */
	const REGION_IRELAND_WEBSITE = self::REGION_EU_W1_WEBSITE;

	/**
	 * Specify the queue URL for the Asia Pacific (Singapore) Website Region.
	 */
	const REGION_APAC_SE1_WEBSITE = 's3-website-ap-southeast-1.amazonaws.com';

	/**
	 * Specify the queue URL for the Asia Pacific (Singapore) Website Region.
	 */
	const REGION_SINGAPORE_WEBSITE = self::REGION_APAC_SE1_WEBSITE;

	/**
	 * Specify the queue URL for the Asia Pacific (Japan) Website Region.
	 */
	const REGION_APAC_NE1_WEBSITE = 's3-website-ap-northeast-1.amazonaws.com';

	/**
	 * Specify the queue URL for the Asia Pacific (Japan) Website Region.
	 */
	const REGION_TOKYO_WEBSITE = self::REGION_APAC_NE1_WEBSITE;

	/**
	 * Specify the queue URL for the South America (Sao Paulo) Website Region.
	 */
	const REGION_SA_E1_WEBSITE = 's3-website-sa-east-1.amazonaws.com';

	/**
	 * Specify the queue URL for the South America (Sao Paulo) Website Region.
	 */
	const REGION_SAO_PAULO_WEBSITE = self::REGION_SA_E1_WEBSITE;

	/**
	 * Specify the queue URL for the United States GovCloud Website Region.
	 */
	const REGION_US_GOV1_WEBSITE = 's3-website-us-gov-west-1.amazonaws.com';


	/*%******************************************************************************************%*/
	// ACL

	/**
	 * ACL: Owner-only read/write.
	 */
	const ACL_PRIVATE = 'private';

	/**
	 * ACL: Owner read/write, public read.
	 */
	const ACL_PUBLIC = 'public-read';

	/**
	 * ACL: Public read/write.
	 */
	const ACL_OPEN = 'public-read-write';

	/**
	 * ACL: Owner read/write, authenticated read.
	 */
	const ACL_AUTH_READ = 'authenticated-read';

	/**
	 * ACL: Bucket owner read.
	 */
	const ACL_OWNER_READ = 'bucket-owner-read';

	/**
	 * ACL: Bucket owner full control.
	 */
	const ACL_OWNER_FULL_CONTROL = 'bucket-owner-full-control';


	/*%******************************************************************************************%*/
	// GRANTS

	/**
	 * When applied to a bucket, grants permission to list the bucket. When applied to an object, this
	 * grants permission to read the object data and/or metadata.
	 */
	const GRANT_READ = 'READ';

	/**
	 * When applied to a bucket, grants permission to create, overwrite, and delete any object in the
	 * bucket. This permission is not supported for objects.
	 */
	const GRANT_WRITE = 'WRITE';

	/**
	 * Grants permission to read the ACL for the applicable bucket or object. The owner of a bucket or
	 * object always has this permission implicitly.
	 */
	const GRANT_READ_ACP = 'READ_ACP';

	/**
	 * Gives permission to overwrite the ACP for the applicable bucket or object. The owner of a bucket
	 * or object always has this permission implicitly. Granting this permission is equivalent to granting
	 * FULL_CONTROL because the grant recipient can make any changes to the ACP.
	 */
	const GRANT_WRITE_ACP = 'WRITE_ACP';

	/**
	 * Provides READ, WRITE, READ_ACP, and WRITE_ACP permissions. It does not convey additional rights and
	 * is provided only for convenience.
	 */
	const GRANT_FULL_CONTROL = 'FULL_CONTROL';


	/*%******************************************************************************************%*/
	// USERS

	/**
	 * The "AuthenticatedUsers" group for access control policies.
	 */
	const USERS_AUTH = 'http://acs.amazonaws.com/groups/global/AuthenticatedUsers';

	/**
	 * The "AllUsers" group for access control policies.
	 */
	const USERS_ALL = 'http://acs.amazonaws.com/groups/global/AllUsers';

	/**
	 * The "LogDelivery" group for access control policies.
	 */
	const USERS_LOGGING = 'http://acs.amazonaws.com/groups/s3/LogDelivery';


	/*%******************************************************************************************%*/
	// PATTERNS

	/**
	 * PCRE: Match all items
	 */
	const PCRE_ALL = '/.*/i';


	/*%******************************************************************************************%*/
	// STORAGE

	/**
	 * Standard storage redundancy.
	 */
	const STORAGE_STANDARD = 'STANDARD';

	/**
	 * Reduced storage redundancy.
	 */
	const STORAGE_REDUCED = 'REDUCED_REDUNDANCY';


	/*%******************************************************************************************%*/
	// PROPERTIES

	/**
	 * The request URL.
	 */
	public $request_url;

	/**
	 * The virtual host setting.
	 */
	public $vhost;

	/**
	 * The base XML elements to use for access control policy methods.
	 */
	public $base_acp_xml;

	/**
	 * The base XML elements to use for creating buckets in regions.
	 */
	public $base_location_constraint;

	/**
	 * The base XML elements to use for logging methods.
	 */
	public $base_logging_xml;

	/**
	 * The base XML elements to use for notifications.
	 */
	public $base_notification_xml;

	/**
	 * The base XML elements to use for versioning.
	 */
	public $base_versioning_xml;

	/**
	 * The base XML elements to use for completing a multipart upload.
	 */
	public $complete_mpu_xml;

	/**
	 * The base XML elements to use for website support.
	 */
	public $website_config_xml;

	/**
	 * The base XML elements to use for multi-object delete support.
	 */
	public $multi_object_delete_xml;

	/**
	 * The base XML elements to use for object expiration support.
	 */
	public $object_expiration_xml;

	/**
	 * The DNS vs. Path-style setting.
	 */
	public $path_style = false;

	/**
	 * The state of whether the prefix change is temporary or permanent.
	 */
	public $temporary_prefix = false;


	/*%******************************************************************************************%*/
	// CONSTRUCTOR

	/**
	 * Constructs a new instance of <AmazonS3>.
	 *
	 * @param array $options (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>certificate_authority</code> - <code>boolean</code> - Optional - Determines which Cerificate Authority file to use. A value of boolean <code>false</code> will use the Certificate Authority file available on the system. A value of boolean <code>true</code> will use the Certificate Authority provided by the SDK. Passing a file system path to a Certificate Authority file (chmodded to <code>0755</code>) will use that. Leave this set to <code>false</code> if you're not sure.</li>
	 * 	<li><code>credentials</code> - <code>string</code> - Optional - The name of the credential set to use for authentication.</li>
	 * 	<li><code>default_cache_config</code> - <code>string</code> - Optional - This option allows a preferred storage type to be configured for long-term caching. This can be changed later using the <set_cache_config()> method. Valid values are: <code>apc</code>, <code>xcache</code>, or a file system path such as <code>./cache</code> or <code>/tmp/cache/</code>.</li>
	 * 	<li><code>key</code> - <code>string</code> - Optional - Your AWS key, or a session key. If blank, the default credential set will be used.</li>
	 * 	<li><code>secret</code> - <code>string</code> - Optional - Your AWS secret key, or a session secret key. If blank, the default credential set will be used.</li>
	 * 	<li><code>token</code> - <code>string</code> - Optional - An AWS session token.</li></ul>
	 * @return void
	 */
	public function __construct(array $options = array())
	{
		$this->vhost = null;
		$this->api_version = '2006-03-01';
		$this->hostname = self::DEFAULT_URL;

		$this->base_acp_xml             = '<?xml version="1.0" encoding="UTF-8"?><AccessControlPolicy xmlns="http://s3.amazonaws.com/doc/latest/"></AccessControlPolicy>';
		$this->base_location_constraint = '<?xml version="1.0" encoding="UTF-8"?><CreateBucketConfiguration xmlns="http://s3.amazonaws.com/doc/' . $this->api_version . '/"><LocationConstraint></LocationConstraint></CreateBucketConfiguration>';
		$this->base_logging_xml         = '<?xml version="1.0" encoding="utf-8"?><BucketLoggingStatus xmlns="http://doc.s3.amazonaws.com/' . $this->api_version . '"></BucketLoggingStatus>';
		$this->base_notification_xml    = '<?xml version="1.0" encoding="utf-8"?><NotificationConfiguration></NotificationConfiguration>';
		$this->base_versioning_xml      = '<?xml version="1.0" encoding="utf-8"?><VersioningConfiguration xmlns="http://s3.amazonaws.com/doc/' . $this->api_version . '/"></VersioningConfiguration>';
		$this->complete_mpu_xml         = '<?xml version="1.0" encoding="utf-8"?><CompleteMultipartUpload></CompleteMultipartUpload>';
		$this->website_config_xml       = '<?xml version="1.0" encoding="utf-8"?><WebsiteConfiguration xmlns="http://s3.amazonaws.com/doc/' . $this->api_version . '/"><IndexDocument><Suffix>index.html</Suffix></IndexDocument><ErrorDocument><Key>error.html</Key></ErrorDocument></WebsiteConfiguration>';
		$this->multi_object_delete_xml  = '<?xml version="1.0" encoding="utf-8"?><Delete></Delete>';
		$this->object_expiration_xml    = '<?xml version="1.0" encoding="utf-8"?><LifecycleConfiguration></LifecycleConfiguration>';

		parent::__construct($options);
	}


	/*%******************************************************************************************%*/
	// AUTHENTICATION

	/**
	 * Authenticates a connection to Amazon S3. Do not use directly unless implementing custom methods for
	 * this class.
	 *
	 * @param string $operation (Required) The name of the bucket to operate on (S3 Only).
	 * @param array $payload (Required) An associative array of parameters for authenticating. See inline comments for allowed keys.
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 * @link http://docs.amazonwebservices.com/AmazonS3/latest/dev/S3_Authentication.html REST authentication
	 */
	public function authenticate($operation, $payload)
	{
		/*
		 * Overriding or extending this class? You can pass the following "magic" keys into $opt.
		 *
		 * ## verb, resource, sub_resource and query_string ##
		 * 	<verb> /<resource>?<sub_resource>&<query_string>
		 * 	GET /filename.txt?versions&prefix=abc&max-items=1
		 *
		 * ## versionId, uploadId, partNumber, response-* ##
		 * 	These don't follow the same rules as above, in that the they needs to be signed, while
		 * 	other query_string values do not.
		 *
		 * ## curlopts ##
		 * 	These values get passed directly to the cURL methods in RequestCore.
		 *
		 * ## fileUpload, fileDownload, seekTo ##
		 * 	These are slightly modified and then passed to the cURL methods in RequestCore.
		 *
		 * ## headers ##
		 * 	$opt['headers'] is an array, whose keys are HTTP headers to be sent.
		 *
		 * ## body ##
		 * 	This is the request body that is sent to the server via PUT/POST.
		 *
		 * ## preauth ##
		 * 	This is a hook that tells authenticate() to generate a pre-authenticated URL.
		 *
		 * ## returnCurlHandle ##
		 * 	Tells authenticate() to return the cURL handle for the request instead of executing it.
		 */

		// Rename variables (to overcome inheritence issues)
		$bucket = $operation;
		$opt = $payload;

		// Validate the S3 bucket name
		if (!$this->validate_bucketname_support($bucket))
		{
			// @codeCoverageIgnoreStart
			throw new S3_Exception('S3 does not support "' . $bucket . '" as a valid bucket name. Review "Bucket Restrictions and Limitations" in the S3 Developer Guide for more information.');
			// @codeCoverageIgnoreEnd
		}

		// Die if $opt isn't set.
		if (!$opt) return false;

		$method_arguments = func_get_args();

		// Use the caching flow to determine if we need to do a round-trip to the server.
		if ($this->use_cache_flow)
		{
			// Generate an identifier specific to this particular set of arguments.
			$cache_id = $this->key . '_' . get_class($this) . '_' . $bucket . '_' . sha1(serialize($method_arguments));

			// Instantiate the appropriate caching object.
			$this->cache_object = new $this->cache_class($cache_id, $this->cache_location, $this->cache_expires, $this->cache_compress);

			if ($this->delete_cache)
			{
				$this->use_cache_flow = false;
				$this->delete_cache = false;
				return $this->cache_object->delete();
			}

			// Invoke the cache callback function to determine whether to pull data from the cache or make a fresh request.
			$data = $this->cache_object->response_manager(array($this, 'cache_callback'), $method_arguments);

			if ($this->parse_the_response)
			{
				// Parse the XML body
				$data = $this->parse_callback($data);
			}

			// End!
			return $data;
		}

		// If we haven't already set a resource prefix and the bucket name isn't DNS-valid...
		if ((!$this->resource_prefix && !$this->validate_bucketname_create($bucket)) || $this->path_style)
		{
			// Fall back to the older path-style URI
			$this->set_resource_prefix('/' . $bucket);
			$this->temporary_prefix = true;
		}

		// Determine hostname
		$scheme = $this->use_ssl ? 'https://' : 'http://';
		if ($this->resource_prefix || $this->path_style) // Use bucket-in-path method.
		{
			$hostname = $this->hostname . $this->resource_prefix . (($bucket === '' || $this->resource_prefix === '/' . $bucket) ? '' : ('/' . $bucket));
		}
		else
		{
			$hostname = $this->vhost ? $this->vhost : (($bucket === '') ? $this->hostname : ($bucket . '.') . $this->hostname);
		}

		// Get the UTC timestamp in RFC 2616 format
		$date = gmdate(CFUtilities::DATE_FORMAT_RFC2616, time());

		// Storage for request parameters.
		$resource = '';
		$sub_resource = '';
		$querystringparams = array();
		$signable_querystringparams = array();
		$string_to_sign = '';
		$headers = array(
			'Content-MD5' => '',
			'Content-Type' => 'application/x-www-form-urlencoded',
			'Date' => $date
		);

		/*%******************************************************************************************%*/

		// Do we have an authentication token?
		if ($this->auth_token)
		{
			$headers['X-Amz-Security-Token'] = $this->auth_token;
		}

		// Handle specific resources
		if (isset($opt['resource']))
		{
			$resource .= $opt['resource'];
		}

		// Merge query string values
		if (isset($opt['query_string']))
		{
			$querystringparams = array_merge($querystringparams, $opt['query_string']);
		}
		$query_string = $this->util->to_query_string($querystringparams);

		// Merge the signable query string values. Must be alphabetical.
		$signable_list = array(
			'partNumber',
			'response-cache-control',
			'response-content-disposition',
			'response-content-encoding',
			'response-content-language',
			'response-content-type',
			'response-expires',
			'uploadId',
			'versionId'
		);
		foreach ($signable_list as $item)
		{
			if (isset($opt[$item]))
			{
				$signable_querystringparams[$item] = $opt[$item];
			}
		}
		$signable_query_string = $this->util->to_query_string($signable_querystringparams);

		// Merge the HTTP headers
		if (isset($opt['headers']))
		{
			$headers = array_merge($headers, $opt['headers']);
		}

		// Compile the URI to request
		$conjunction = '?';
		$signable_resource = '/' . str_replace('%2F', '/', rawurlencode($resource));
		$non_signable_resource = '';

		if (isset($opt['sub_resource']))
		{
			$signable_resource .= $conjunction . rawurlencode($opt['sub_resource']);
			$conjunction = '&';
		}
		if ($signable_query_string !== '')
		{
			$signable_query_string = $conjunction . $signable_query_string;
			$conjunction = '&';
		}
		if ($query_string !== '')
		{
			$non_signable_resource .= $conjunction . $query_string;
			$conjunction = '&';
		}
		if (substr($hostname, -1) === substr($signable_resource, 0, 1))
		{
			$signable_resource = ltrim($signable_resource, '/');
		}

		$this->request_url = $scheme . $hostname . $signable_resource . $signable_query_string . $non_signable_resource;

		if (isset($opt['location']))
		{
			$this->request_url = $opt['location'];
		}

		// Gather information to pass along to other classes.
		$helpers = array(
			'utilities' => $this->utilities_class,
			'request' => $this->request_class,
			'response' => $this->response_class,
		);

		// Instantiate the request class
		$request = new $this->request_class($this->request_url, $this->proxy, $helpers, $this->credentials);

		// Update RequestCore settings
		$request->request_class = $this->request_class;
		$request->response_class = $this->response_class;
		$request->ssl_verification = $this->ssl_verification;

		// Pass along registered stream callbacks
		if ($this->registered_streaming_read_callback)
		{
			$request->register_streaming_read_callback($this->registered_streaming_read_callback);
		}

		if ($this->registered_streaming_write_callback)
		{
			$request->register_streaming_write_callback($this->registered_streaming_write_callback);
		}

		// Streaming uploads
		if (isset($opt['fileUpload']))
		{
			if (is_resource($opt['fileUpload']))
			{
				// Determine the length to read from the stream
				$length = null; // From current position until EOF by default, size determined by set_read_stream()

				if (isset($headers['Content-Length']))
				{
					$length = $headers['Content-Length'];
				}
				elseif (isset($opt['seekTo']))
				{
					// Read from seekTo until EOF by default
					$stats = fstat($opt['fileUpload']);

					if ($stats && $stats['size'] >= 0)
					{
						$length = $stats['size'] - (integer) $opt['seekTo'];
					}
				}

				$request->set_read_stream($opt['fileUpload'], $length);

				if ($headers['Content-Type'] === 'application/x-www-form-urlencoded')
				{
					$headers['Content-Type'] = 'application/octet-stream';
				}
			}
			else
			{
				$request->set_read_file($opt['fileUpload']);

				// Determine the length to read from the file
				$length = $request->read_stream_size; // The file size by default

				if (isset($headers['Content-Length']))
				{
					$length = $headers['Content-Length'];
				}
				elseif (isset($opt['seekTo']) && isset($length))
				{
					// Read from seekTo until EOF by default
					$length -= (integer) $opt['seekTo'];
				}

				$request->set_read_stream_size($length);

				// Attempt to guess the correct mime-type
				if ($headers['Content-Type'] === 'application/x-www-form-urlencoded')
				{
					$extension = explode('.', $opt['fileUpload']);
					$extension = array_pop($extension);
					$mime_type = CFMimeTypes::get_mimetype($extension);
					$headers['Content-Type'] = $mime_type;
				}
			}

			$headers['Content-Length'] = $request->read_stream_size;
			$headers['Content-MD5'] = '';
		}

		// Handle streaming file offsets
		if (isset($opt['seekTo']))
		{
			// Pass the seek position to RequestCore
			$request->set_seek_position((integer) $opt['seekTo']);
		}

		// Streaming downloads
		if (isset($opt['fileDownload']))
		{
			if (is_resource($opt['fileDownload']))
			{
				$request->set_write_stream($opt['fileDownload']);
			}
			else
			{
				$request->set_write_file($opt['fileDownload']);
			}
		}

		$curlopts = array();

		// Set custom CURLOPT settings
		if (isset($opt['curlopts']))
		{
			$curlopts = $opt['curlopts'];
		}

		// Debug mode
		if ($this->debug_mode)
		{
			$curlopts[CURLOPT_VERBOSE] = true;
		}

		// Set the curl options.
		if (count($curlopts))
		{
			$request->set_curlopts($curlopts);
		}

		// Do we have a verb?
		if (isset($opt['verb']))
		{
			$request->set_method($opt['verb']);
			$string_to_sign .= $opt['verb'] . "\n";
		}

		// Add headers and content when we have a body
		if (isset($opt['body']))
		{
			$request->set_body($opt['body']);
			$headers['Content-Length'] = strlen($opt['body']);

			if ($headers['Content-Type'] === 'application/x-www-form-urlencoded')
			{
				$headers['Content-Type'] = 'application/octet-stream';
			}

			if (!isset($opt['NoContentMD5']) || $opt['NoContentMD5'] !== true)
			{
				$headers['Content-MD5'] = $this->util->hex_to_base64(md5($opt['body']));
			}
		}

		// Handle query-string authentication
		if (isset($opt['preauth']) && (integer) $opt['preauth'] > 0)
		{
			unset($headers['Date']);
			$headers['Content-Type'] = '';
			$headers['Expires'] = is_int($opt['preauth']) ? $opt['preauth'] : strtotime($opt['preauth']);
		}

		// Sort headers
		uksort($headers, 'strnatcasecmp');

		// Add headers to request and compute the string to sign
		foreach ($headers as $header_key => $header_value)
		{
			// Strip linebreaks from header values as they're illegal and can allow for security issues
			$header_value = str_replace(array("\r", "\n"), '', $header_value);

			// Add the header if it has a value
			if ($header_value !== '')
			{
				$request->add_header($header_key, $header_value);
			}

			// Generate the string to sign
			if (
				strtolower($header_key) === 'content-md5' ||
				strtolower($header_key) === 'content-type' ||
				strtolower($header_key) === 'date' ||
				(strtolower($header_key) === 'expires' && isset($opt['preauth']) && (integer) $opt['preauth'] > 0)
			)
			{
				$string_to_sign .= $header_value . "\n";
			}
			elseif (substr(strtolower($header_key), 0, 6) === 'x-amz-')
			{
				$string_to_sign .= strtolower($header_key) . ':' . $header_value . "\n";
			}
		}

		// Add the signable resource location
		$string_to_sign .= ($this->resource_prefix ? $this->resource_prefix : '');
		$string_to_sign .= (($bucket === '' || $this->resource_prefix === '/' . $bucket) ? '' : ('/' . $bucket)) . $signable_resource . urldecode($signable_query_string);

		// Hash the AWS secret key and generate a signature for the request.
		$signature = base64_encode(hash_hmac('sha1', $string_to_sign, $this->secret_key, true));
		$request->add_header('Authorization', 'AWS ' . $this->key . ':' . $signature);

		// If we're generating a URL, return the URL to the calling method.
		if (isset($opt['preauth']) && (integer) $opt['preauth'] > 0)
		{
			$query_params = array(
				'AWSAccessKeyId' => $this->key,
				'Expires' => $headers['Expires'],
				'Signature' => $signature,
			);

			// If using short-term credentials, add the token to the query string
			if ($this->auth_token)
			{
				$query_params['x-amz-security-token'] = $this->auth_token;
			}

			return $this->request_url . $conjunction . http_build_query($query_params, '', '&');
		}
		elseif (isset($opt['preauth']))
		{
			return $this->request_url;
		}

		/*%******************************************************************************************%*/

		// If our changes were temporary, reset them.
		if ($this->temporary_prefix)
		{
			$this->temporary_prefix = false;
			$this->resource_prefix = null;
		}

		// Manage the (newer) batch request API or the (older) returnCurlHandle setting.
		if ($this->use_batch_flow)
		{
			$handle = $request->prep_request();
			$this->batch_object->add($handle);
			$this->use_batch_flow = false;

			return $handle;
		}
		elseif (isset($opt['returnCurlHandle']) && $opt['returnCurlHandle'] === true)
		{
			return $request->prep_request();
		}

		// Send!
		$request->send_request();

		// Prepare the response
		$headers = $request->get_response_header();
		$headers['x-aws-request-url'] = $this->request_url;
		$headers['x-aws-redirects'] = $this->redirects;
		$headers['x-aws-stringtosign'] = $string_to_sign;
		$headers['x-aws-requestheaders'] = $request->request_headers;

		// Did we have a request body?
		if (isset($opt['body']))
		{
			$headers['x-aws-requestbody'] = $opt['body'];
		}

		$data = new $this->response_class($headers, $this->parse_callback($request->get_response_body()), $request->get_response_code());

		// Did Amazon tell us to redirect? Typically happens for multiple rapid requests EU datacenters.
		// @see: http://docs.amazonwebservices.com/AmazonS3/latest/dev/Redirects.html
		// @codeCoverageIgnoreStart
		if ((integer) $request->get_response_code() === 307) // Temporary redirect to new endpoint.
		{
			$this->redirects++;
			$opt['location'] = $headers['location'];
			$data = $this->authenticate($bucket, $opt);
		}

		// Was it Amazon's fault the request failed? Retry the request until we reach $max_retries.
		elseif ((integer) $request->get_response_code() === 500 || (integer) $request->get_response_code() === 503)
		{
			if ($this->redirects <= $this->max_retries)
			{
				// Exponential backoff
				$delay = (integer) (pow(4, $this->redirects) * 100000);
				usleep($delay);
				$this->redirects++;
				$data = $this->authenticate($bucket, $opt);
			}
		}
		// @codeCoverageIgnoreEnd

		// Return!
		$this->redirects = 0;
		return $data;
	}

	/**
	 * Validates whether or not the specified Amazon S3 bucket name is valid for DNS-style access. This
	 * method is leveraged by any method that creates buckets.
	 *
	 * @param string $bucket (Required) The name of the bucket to validate.
	 * @return boolean Whether or not the specified Amazon S3 bucket name is valid for DNS-style access. A value of <code>true</code> means that the bucket name is valid. A value of <code>false</code> means that the bucket name is invalid.
	 */
	public function validate_bucketname_create($bucket)
	{
		// list_buckets() uses this. Let it pass.
		if ($bucket === '') return true;

		if (
			($bucket === null || $bucket === false) ||                  // Must not be null or false
			preg_match('/[^(a-z0-9\-\.)]/', $bucket) ||                 // Must be in the lowercase Roman alphabet, period or hyphen
			!preg_match('/^([a-z]|\d)/', $bucket) ||                    // Must start with a number or letter
			!(strlen($bucket) >= 3 && strlen($bucket) <= 63) ||         // Must be between 3 and 63 characters long
			(strpos($bucket, '..') !== false) ||                        // Bucket names cannot contain two, adjacent periods
			(strpos($bucket, '-.') !== false) ||                        // Bucket names cannot contain dashes next to periods
			(strpos($bucket, '.-') !== false) ||                        // Bucket names cannot contain dashes next to periods
			preg_match('/(-|\.)$/', $bucket) ||                         // Bucket names should not end with a dash or period
			preg_match('/^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$/', $bucket)    // Must not be formatted as an IP address
		) return false;

		return true;
	}

	/**
	 * Validates whether or not the specified Amazon S3 bucket name is valid for path-style access. This
	 * method is leveraged by any method that reads from buckets.
	 *
	 * @param string $bucket (Required) The name of the bucket to validate.
	 * @return boolean Whether or not the bucket name is valid. A value of <code>true</code> means that the bucket name is valid. A value of <code>false</code> means that the bucket name is invalid.
	 */
	public function validate_bucketname_support($bucket)
	{
		// list_buckets() uses this. Let it pass.
		if ($bucket === '') return true;

		// Validate
		if (
			($bucket === null || $bucket === false) ||                  // Must not be null or false
			preg_match('/[^(a-z0-9_\-\.)]/i', $bucket) ||               // Must be in the Roman alphabet, period, hyphen or underscore
			!preg_match('/^([a-z]|\d)/i', $bucket) ||                   // Must start with a number or letter
			!(strlen($bucket) >= 3 && strlen($bucket) <= 255) ||        // Must be between 3 and 255 characters long
			preg_match('/^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$/', $bucket)    // Must not be formatted as an IP address
		) return false;

		return true;
	}

	/*%******************************************************************************************%*/
	// SETTERS

	/**
	 * Sets the region to use for subsequent Amazon S3 operations. This will also reset any prior use of
	 * <enable_path_style()>.
	 *
	 * @param string $region (Required) The region to use for subsequent Amazon S3 operations. For a complete list of REGION constants, see the <code>AmazonS3</code> Constants page in the API reference.
	 * @return $this A reference to the current instance.
	 */
	public function set_region($region)
	{
		// @codeCoverageIgnoreStart
		$this->set_hostname($region);

		switch ($region)
		{
			case self::REGION_US_E1: // Northern Virginia
				$this->enable_path_style(false);
				break;

			case self::REGION_EU_W1: // Ireland
				$this->enable_path_style(); // Always use path-style access for EU endpoint.
				break;

			default:
				$this->enable_path_style(false);
				break;

		}
		// @codeCoverageIgnoreEnd

		return $this;
	}

	/**
	 * Sets the virtual host to use in place of the default `bucket.s3.amazonaws.com` domain.
	 *
	 * @param string $vhost (Required) The virtual host to use in place of the default `bucket.s3.amazonaws.com` domain.
	 * @return $this A reference to the current instance.
	 * @link http://docs.amazonwebservices.com/AmazonS3/latest/dev/VirtualHosting.html Virtual Hosting of Buckets
	 */
	public function set_vhost($vhost)
	{
		$this->vhost = $vhost;
		return $this;
	}

	/**
	 * Enables the use of the older path-style URI access for all requests.
	 *
	 * @param string $style (Optional) Whether or not to enable path-style URI access for all requests. The default value is <code>true</code>.
	 * @return $this A reference to the current instance.
	 */
	public function enable_path_style($style = true)
	{
		$this->path_style = $style;
		return $this;
	}


	/*%******************************************************************************************%*/
	// BUCKET METHODS

	/**
	 * Creates an Amazon S3 bucket.
	 *
	 * Every object stored in Amazon S3 is contained in a bucket. Buckets partition the namespace of
	 * objects stored in Amazon S3 at the top level. in a bucket, any name can be used for objects.
	 * However, bucket names must be unique across all of Amazon S3.
	 *
	 * @param string $bucket (Required) The name of the bucket to create.
	 * @param string $region (Required) The preferred geographical location for the bucket. [Allowed values: `AmazonS3::REGION_US_E1 `, `AmazonS3::REGION_US_W1`, `AmazonS3::REGION_EU_W1`, `AmazonS3::REGION_APAC_SE1`, `AmazonS3::REGION_APAC_NE1`]
	 * @param string $acl (Optional) The ACL settings for the specified bucket. [Allowed values: <code>AmazonS3::ACL_PRIVATE</code>, <code>AmazonS3::ACL_PUBLIC</code>, <code>AmazonS3::ACL_OPEN</code>, <code>AmazonS3::ACL_AUTH_READ</code>, <code>AmazonS3::ACL_OWNER_READ</code>, <code>AmazonS3::ACL_OWNER_FULL_CONTROL</code>]. The default value is <ACL_PRIVATE>.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 * @link http://docs.amazonwebservices.com/AmazonS3/latest/dev/UsingBucket.html Working with Amazon S3 Buckets
	 */
	public function create_bucket($bucket, $region, $acl = self::ACL_PRIVATE, $opt = null)
	{
		// If the bucket contains uppercase letters...
		if (preg_match('/[A-Z]/', $bucket))
		{
			// Throw a warning
			trigger_error('Since DNS-valid bucket names cannot contain uppercase characters, "' . $bucket . '" has been automatically converted to "' . strtolower($bucket) . '"', E_USER_WARNING);

			// Force the bucketname to lowercase
			$bucket = strtolower($bucket);
		}

		// Validate the S3 bucket name for creation
		if (!$this->validate_bucketname_create($bucket))
		{
			// @codeCoverageIgnoreStart
			throw new S3_Exception('"' . $bucket . '" is not DNS-valid (i.e., <bucketname>.s3.amazonaws.com), and cannot be used as an S3 bucket name. Review "Bucket Restrictions and Limitations" in the S3 Developer Guide for more information.');
			// @codeCoverageIgnoreEnd
		}

		if (!$opt) $opt = array();
		$opt['verb'] = 'PUT';
		$opt['headers'] = array(
			'Content-Type' => 'application/xml',
			'x-amz-acl' => $acl
		);

		// Defaults
		$this->set_region($region); // Also sets path-style
		$xml = simplexml_load_string($this->base_location_constraint);

		switch ($region)
		{
			case self::REGION_US_E1: // Northern Virginia
				$opt['body'] = '';
				break;

			case self::REGION_EU_W1:    // Ireland
				$xml->LocationConstraint = 'EU';
				$opt['body'] = $xml->asXML();
				break;

			default:
				$xml->LocationConstraint = str_replace(array('s3-', '.amazonaws.com'), '', $region);
				$opt['body'] = $xml->asXML();
				break;
		}

		$response = $this->authenticate($bucket, $opt);

		// Make sure we're set back to DNS-style URLs
		$this->enable_path_style(false);

		return $response;
	}

	/**
	 * Gets the region in which the specified Amazon S3 bucket is located.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>preauth</code> - <code>integer|string</code> - Optional - Specifies that a presigned URL for this request should be returned. May be passed as a number of seconds since UNIX Epoch, or any string compatible with <php:strtotime()>.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 */
	public function get_bucket_region($bucket, $opt = null)
	{
		// Add this to our request
		if (!$opt) $opt = array();
		$opt['verb'] = 'GET';
		$opt['sub_resource'] = 'location';

		// Authenticate to S3
		$response = $this->authenticate($bucket, $opt);

		if ($response->isOK())
		{
			// Handle body
			$response->body = (string) $response->body;
		}

		return $response;
	}

	/**
	 * Gets the HTTP headers for the specified Amazon S3 bucket.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>preauth</code> - <code>integer|string</code> - Optional - Specifies that a presigned URL for this request should be returned. May be passed as a number of seconds since UNIX Epoch, or any string compatible with <php:strtotime()>.</li>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 */
	public function get_bucket_headers($bucket, $opt = null)
	{
		if (!$opt) $opt = array();
		$opt['verb'] = 'HEAD';

		return $this->authenticate($bucket, $opt);
	}

	/**
	 * Deletes a bucket from an Amazon S3 account. A bucket must be empty before the bucket itself can be deleted.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param boolean $force (Optional) Whether to force-delete the bucket and all of its contents. The default value is <code>false</code>.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return mixed A <CFResponse> object if the bucket was deleted successfully. Returns boolean <code>false</code> if otherwise.
	 */
	public function delete_bucket($bucket, $force = false, $opt = null)
	{
		// Set default value
		$success = true;

		if ($force)
		{
			// Delete all of the items from the bucket.
			$success = $this->delete_all_object_versions($bucket);
		}

		// As long as we were successful...
		if ($success)
		{
			if (!$opt) $opt = array();
			$opt['verb'] = 'DELETE';

			return $this->authenticate($bucket, $opt);
		}

		// @codeCoverageIgnoreStart
		return false;
		// @codeCoverageIgnoreEnd
	}

	/**
	 * Gets a list of all buckets contained in the caller's Amazon S3 account.
	 *
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>preauth</code> - <code>integer|string</code> - Optional - Specifies that a presigned URL for this request should be returned. May be passed as a number of seconds since UNIX Epoch, or any string compatible with <php:strtotime()>.</li>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 */
	public function list_buckets($opt = null)
	{
		if (!$opt) $opt = array();
		$opt['verb'] = 'GET';

		return $this->authenticate('', $opt);
	}

	/**
	 * Gets the access control list (ACL) settings for the specified Amazon S3 bucket.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>preauth</code> - <code>integer|string</code> - Optional - Specifies that a presigned URL for this request should be returned. May be passed as a number of seconds since UNIX Epoch, or any string compatible with <php:strtotime()>.</li>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 * @link http://docs.amazonwebservices.com/AmazonS3/latest/dev/RESTAccessPolicy.html REST Access Control Policy
	 */
	public function get_bucket_acl($bucket, $opt = null)
	{
		// Add this to our request
		if (!$opt) $opt = array();
		$opt['verb'] = 'GET';
		$opt['sub_resource'] = 'acl';

		// Authenticate to S3
		return $this->authenticate($bucket, $opt);
	}

	/**
	 * Sets the access control list (ACL) settings for the specified Amazon S3 bucket.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param string $acl (Optional) The ACL settings for the specified bucket. [Allowed values: <code>AmazonS3::ACL_PRIVATE</code>, <code>AmazonS3::ACL_PUBLIC</code>, <code>AmazonS3::ACL_OPEN</code>, <code>AmazonS3::ACL_AUTH_READ</code>, <code>AmazonS3::ACL_OWNER_READ</code>, <code>AmazonS3::ACL_OWNER_FULL_CONTROL</code>]. Alternatively, an array of associative arrays. Each associative array contains an `id` and a `permission` key. The default value is <ACL_PRIVATE>.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 * @link http://docs.amazonwebservices.com/AmazonS3/latest/dev/RESTAccessPolicy.html REST Access Control Policy
	 */
	public function set_bucket_acl($bucket, $acl = self::ACL_PRIVATE, $opt = null)
	{
		// Add this to our request
		if (!$opt) $opt = array();
		$opt['verb'] = 'PUT';
		$opt['sub_resource'] = 'acl';
		$opt['headers'] = array(
			'Content-Type' => 'application/xml'
		);

		// Make sure these are defined.
		// @codeCoverageIgnoreStart
		if (!$this->credentials->canonical_id || !$this->credentials->canonical_name)
		{
			// Fetch the data live.
			$canonical = $this->get_canonical_user_id();
			$this->credentials->canonical_id = $canonical['id'];
			$this->credentials->canonical_name = $canonical['display_name'];
		}
		// @codeCoverageIgnoreEnd

		if (is_array($acl))
		{
			$opt['body'] = $this->generate_access_policy($this->credentials->canonical_id, $this->credentials->canonical_name, $acl);
		}
		else
		{
			$opt['body'] = '';
			$opt['headers']['x-amz-acl'] = $acl;
		}

		// Authenticate to S3
		return $this->authenticate($bucket, $opt);
	}


	/*%******************************************************************************************%*/
	// OBJECT METHODS

	/**
	 * Creates an Amazon S3 object. After an Amazon S3 bucket is created, objects can be stored in it.
	 *
	 * Each standard object can hold up to 5 GB of data. When an object is stored in Amazon S3, the data is streamed
	 * to multiple storage servers in multiple data centers. This ensures the data remains available in the
	 * event of internal network or hardware failure.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param string $filename (Required) The file name for the object.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>body</code> - <code>string</code> - Required; Conditional - The data to be stored in the object. Either this parameter or <code>fileUpload</code> must be specified.</li>
	 * 	<li><code>fileUpload</code> - <code>string|resource</code> - Required; Conditional - The URL/path for the file to upload, or an open resource. Either this parameter or <code>body</code> is required.</li>
	 * 	<li><code>acl</code> - <code>string</code> - Optional - The ACL settings for the specified object. [Allowed values: <code>AmazonS3::ACL_PRIVATE</code>, <code>AmazonS3::ACL_PUBLIC</code>, <code>AmazonS3::ACL_OPEN</code>, <code>AmazonS3::ACL_AUTH_READ</code>, <code>AmazonS3::ACL_OWNER_READ</code>, <code>AmazonS3::ACL_OWNER_FULL_CONTROL</code>]. The default value is <code>ACL_PRIVATE</code>.</li>
	 * 	<li><code>contentType</code> - <code>string</code> - Optional - The type of content that is being sent in the body. If a file is being uploaded via <code>fileUpload</code> as a file system path, it will attempt to determine the correct mime-type based on the file extension. The default value is <code>application/octet-stream</code>.</li>
	 * 	<li><code>encryption</code> - <code>string</code> - Optional - The algorithm to use for encrypting the object. [Allowed values: <code>AES256</code>]</li>
	 * 	<li><code>headers</code> - <code>array</code> - Optional - Standard HTTP headers to send along in the request. Accepts an associative array of key-value pairs.</li>
	 * 	<li><code>length</code> - <code>integer</code> - Optional - The size of the object in bytes. For more information, see <a href="http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.13">RFC 2616, section 14.13</a>. The value can also be passed to the <code>header</code> option as <code>Content-Length</code>.</li>
	 * 	<li><code>meta</code> - <code>array</code> - Optional - An associative array of key-value pairs. Represented by <code>x-amz-meta-:</code>. Any header starting with this prefix is considered user metadata. It will be stored with the object and returned when you retrieve the object. The total size of the HTTP request, not including the body, must be less than 4 KB.</li>
	 * 	<li><code>seekTo</code> - <code>integer</code> - Optional - The starting position in bytes within the file/stream to upload from.</li>
	 * 	<li><code>storage</code> - <code>string</code> - Optional - Whether to use Standard or Reduced Redundancy storage. [Allowed values: <code>AmazonS3::STORAGE_STANDARD</code>, <code>AmazonS3::STORAGE_REDUCED</code>]. The default value is <code>STORAGE_STANDARD</code>.</li>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 * @link http://docs.amazonwebservices.com/AmazonS3/latest/dev/RESTAccessPolicy.html REST Access Control Policy
	 */
	public function create_object($bucket, $filename, $opt = null)
	{
		if (!$opt) $opt = array();

		// Add this to our request
		$opt['verb'] = 'PUT';
		$opt['resource'] = $filename;

		// Handle content length. Can also be passed as an HTTP header.
		if (isset($opt['length']))
		{
			$opt['headers']['Content-Length'] = $opt['length'];
			unset($opt['length']);
		}

		// Handle content type. Can also be passed as an HTTP header.
		if (isset($opt['contentType']))
		{
			$opt['headers']['Content-Type'] = $opt['contentType'];
			unset($opt['contentType']);
		}

		// Handle Access Control Lists. Can also be passed as an HTTP header.
		if (isset($opt['acl']))
		{
			$opt['headers']['x-amz-acl'] = $opt['acl'];
			unset($opt['acl']);
		}

		// Handle storage settings. Can also be passed as an HTTP header.
		if (isset($opt['storage']))
		{
			$opt['headers']['x-amz-storage-class'] = $opt['storage'];
			unset($opt['storage']);
		}

		// Handle encryption settings. Can also be passed as an HTTP header.
		if (isset($opt['encryption']))
		{
			$opt['headers']['x-amz-server-side-encryption'] = $opt['encryption'];
			unset($opt['encryption']);
		}

		// Handle meta tags. Can also be passed as an HTTP header.
		if (isset($opt['meta']))
		{
			foreach ($opt['meta'] as $meta_key => $meta_value)
			{
				// e.g., `My Meta Header` is converted to `x-amz-meta-my-meta-header`.
				$opt['headers']['x-amz-meta-' . strtolower(str_replace(' ', '-', $meta_key))] = $meta_value;
			}
			unset($opt['meta']);
		}

		$opt['headers']['Expect'] = '100-continue';

		// Authenticate to S3
		return $this->authenticate($bucket, $opt);
	}

	/**
	 * Gets the contents of an Amazon S3 object in the specified bucket.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param string $filename (Required) The file name for the object.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>etag</code> - <code>string</code> - Optional - The <code>ETag</code> header passed in from a previous request. If specified, request <code>LastModified</code> option must be specified as well. Will trigger a <code>304 Not Modified</code> status code if the file hasn't changed.</li>
	 * 	<li><code>fileDownload</code> - <code>string|resource</code> - Optional - The file system location to download the file to, or an open file resource. Must be a server-writable location.</li>
	 * 	<li><code>headers</code> - <code>array</code> - Optional - Standard HTTP headers to send along in the request. Accepts an associative array of key-value pairs.</li>
	 * 	<li><code>lastmodified</code> - <code>string</code> - Optional - The <code>LastModified</code> header passed in from a previous request. If specified, request <code>ETag</code> option must be specified as well. Will trigger a <code>304 Not Modified</code> status code if the file hasn't changed.</li>
	 * 	<li><code>preauth</code> - <code>integer|string</code> - Optional - Specifies that a presigned URL for this request should be returned. May be passed as a number of seconds since UNIX Epoch, or any string compatible with <php:strtotime()>.</li>
	 * 	<li><code>range</code> - <code>string</code> - Optional - The range of bytes to fetch from the object. Specify this parameter when downloading partial bits or completing incomplete object downloads. The specified range must be notated with a hyphen (e.g., 0-10485759). Defaults to the byte range of the complete Amazon S3 object.</li>
	 * 	<li><code>response</code> - <code>array</code> - Optional - Allows adjustments to specific response headers. Pass an associative array where each key is one of the following: <code>cache-control</code>, <code>content-disposition</code>, <code>content-encoding</code>, <code>content-language</code>, <code>content-type</code>, <code>expires</code>. The <code>expires</code> value should use <php:gmdate()> and be formatted with the <code>DATE_RFC2822</code> constant.</li>
	 * 	<li><code>versionId</code> - <code>string</code> - Optional - The version of the object to retrieve. Version IDs are returned in the <code>x-amz-version-id</code> header of any previous object-related request.</li>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 */
	public function get_object($bucket, $filename, $opt = null)
	{
		if (!$opt) $opt = array();

		// Add this to our request
		$opt['verb'] = 'GET';
		$opt['resource'] = $filename;

		if (!isset($opt['headers']) || !is_array($opt['headers']))
		{
			$opt['headers'] = array();
		}

		if (isset($opt['lastmodified']))
		{
			$opt['headers']['If-Modified-Since'] = $opt['lastmodified'];
		}

		if (isset($opt['etag']))
		{
			$opt['headers']['If-None-Match'] = $opt['etag'];
		}

		// Partial content range
		if (isset($opt['range']))
		{
			$opt['headers']['Range'] = 'bytes=' . $opt['range'];
		}

		// GET responses
		if (isset($opt['response']))
		{
			foreach ($opt['response'] as $key => $value)
			{
				$opt['response-' . $key] = $value;
				unset($opt['response'][$key]);
			}
		}

		// Authenticate to S3
		$this->parse_the_response = false;
		$response = $this->authenticate($bucket, $opt);
		$this->parse_the_response = true;

		return $response;
	}

	/**
	 * Gets the HTTP headers for the specified Amazon S3 object.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param string $filename (Required) The file name for the object.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>versionId</code> - <code>string</code> - Optional - The version of the object to retrieve. Version IDs are returned in the <code>x-amz-version-id</code> header of any previous object-related request.</li>
	 * 	<li><code>preauth</code> - <code>integer|string</code> - Optional - Specifies that a presigned URL for this request should be returned. May be passed as a number of seconds since UNIX Epoch, or any string compatible with <php:strtotime()>.</li>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 */
	public function get_object_headers($bucket, $filename, $opt = null)
	{
		// Add this to our request
		if (!$opt) $opt = array();
		$opt['verb'] = 'HEAD';
		$opt['resource'] = $filename;

		// Authenticate to S3
		return $this->authenticate($bucket, $opt);
	}

	/**
	 * Deletes an Amazon S3 object from the specified bucket.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param string $filename (Required) The file name for the object.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>versionId</code> - <code>string</code> - Optional - The version of the object to delete. Version IDs are returned in the <code>x-amz-version-id</code> header of any previous object-related request.</li>
	 * 	<li><code>MFASerial</code> - <code>string</code> - Optional - The serial number on the back of the Gemalto device. <code>MFASerial</code> and <code>MFAToken</code> must both be set for MFA to work.</li>
	 * 	<li><code>MFAToken</code> - <code>string</code> - Optional - The current token displayed on the Gemalto device. <code>MFASerial</code> and <code>MFAToken</code> must both be set for MFA to work.</li>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 * @link http://aws.amazon.com/mfa/ Multi-Factor Authentication
	 */
	public function delete_object($bucket, $filename, $opt = null)
	{
		// Add this to our request
		if (!$opt) $opt = array();
		$opt['verb'] = 'DELETE';
		$opt['resource'] = $filename;

		// Enable MFA delete?
		// @codeCoverageIgnoreStart
		if (isset($opt['MFASerial']) && isset($opt['MFAToken']))
		{
			$opt['headers'] = array(
				'x-amz-mfa' => ($opt['MFASerial'] . ' ' . $opt['MFAToken'])
			);
		}
		// @codeCoverageIgnoreEnd

		// Authenticate to S3
		return $this->authenticate($bucket, $opt);
	}

	/**
	 * Deletes two or more specified Amazon S3 objects from the specified bucket.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>objects</code> - <code>array</code> - Required - The object references to delete from the bucket. <ul>
	 * 		<li><code>key</code> - <code>string</code> - Required - The name of the object (e.g., the "key") to delete. This should include the entire file path including all "subdirectories".</li>
	 * 		<li><code>version_id</code> - <code>string</code> - Optional - If the object is versioned, include the version ID to delete.</li>
	 * 	</ul></li>
	 * 	<li><code>quiet</code> - <code>boolean</code> - Optional - Whether or not Amazon S3 should use "Quiet" mode for this operation. A value of <code>true</code> will enable Quiet mode. A value of <code>false</code> will use Verbose mode. The default value is <code>false</code>.</li>
	 * 	<li><code>MFASerial</code> - <code>string</code> - Optional - The serial number on the back of the Gemalto device. <code>MFASerial</code> and <code>MFAToken</code> must both be set for MFA to work.</li>
	 * 	<li><code>MFAToken</code> - <code>string</code> - Optional - The current token displayed on the Gemalto device. <code>MFASerial</code> and <code>MFAToken</code> must both be set for MFA to work.</li>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 * @link http://aws.amazon.com/mfa/ Multi-Factor Authentication
	 */
	public function delete_objects($bucket, $opt = null)
	{
		// Add this to our request
		if (!$opt) $opt = array();
		$opt['verb'] = 'POST';
		$opt['sub_resource'] = 'delete';
		$opt['body'] = '';

		// Bail out
		if (!isset($opt['objects']) || !is_array($opt['objects']))
		{
			throw new S3_Exception('The ' . __FUNCTION__ . ' method requires the "objects" option to be set as an array.');
		}

		$xml = new SimpleXMLElement($this->multi_object_delete_xml);

		// Add the objects
		foreach ($opt['objects'] as $object)
		{
			$xobject = $xml->addChild('Object');
			$xobject->addChild('Key', $object['key']);

			if (isset($object['version_id']))
			{
				$xobject->addChild('VersionId', $object['version_id']);
			}
		}

		// Quiet mode?
		if (isset($opt['quiet']))
		{
			$quiet = 'false';
			if (is_bool($opt['quiet'])) // Boolean
			{
				$quiet = $opt['quiet'] ? 'true' : 'false';
			}
			elseif (is_string($opt['quiet'])) // String
			{
				$quiet = ($opt['quiet'] === 'true') ? 'true' : 'false';
			}

			$xml->addChild('Quiet', $quiet);
		}

		// Enable MFA delete?
		// @codeCoverageIgnoreStart
		if (isset($opt['MFASerial']) && isset($opt['MFAToken']))
		{
			$opt['headers'] = array(
				'x-amz-mfa' => ($opt['MFASerial'] . ' ' . $opt['MFAToken'])
			);
		}
		// @codeCoverageIgnoreEnd

		$opt['body'] = $xml->asXML();

		// Authenticate to S3
		return $this->authenticate($bucket, $opt);
	}

	/**
	 * Gets a list of all Amazon S3 objects in the specified bucket.
	 *
	 * NOTE: <strong>This method is paginated</strong>, and will not return more than <code>max-keys</code> keys. If you want to retrieve a list of all keys, you will need to make multiple calls to this function using the <code>marker</code> option to specify the pagination offset (the key of the last processed key--lexically ordered) and the <code>IsTruncated</code> response key to detect when all results have been processed. See: <a href="http://docs.amazonwebservices.com/AmazonS3/latest/API/index.html?RESTBucketGET.html">the S3 REST documentation for get_bucket</a> for more information.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>delimiter</code> - <code>string</code> - Optional - Keys that contain the same string between the prefix and the first occurrence of the delimiter will be rolled up into a single result element in the CommonPrefixes collection.</li>
	 * 	<li><code>marker</code> - <code>string</code> - Optional - Restricts the response to contain results that only occur alphabetically after the value of the marker.</li>
	 * 	<li><code>max-keys</code> - <code>string</code> - Optional - The maximum number of results returned by the method call. The returned list will contain no more results than the specified value, but may return fewer. The default value is 1000.</li>
	 * 	<li><code>preauth</code> - <code>integer|string</code> - Optional - Specifies that a presigned URL for this request should be returned. May be passed as a number of seconds since UNIX Epoch, or any string compatible with <php:strtotime()>.</li>
	 * 	<li><code>prefix</code> - <code>string</code> - Optional - Restricts the response to contain results that begin only with the specified prefix.</li>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 */
	public function list_objects($bucket, $opt = null)
	{
		if (!$opt) $opt = array();

		// Add this to our request
		$opt['verb'] = 'GET';

		foreach (array('delimiter', 'marker', 'max-keys', 'prefix') as $param)
		{
			if (isset($opt[$param]))
			{
				$opt['query_string'][$param] = $opt[$param];
				unset($opt[$param]);
			}
		}

		// Authenticate to S3
		return $this->authenticate($bucket, $opt);
	}

	/**
	 * Copies an Amazon S3 object to a new location, whether in the same Amazon S3 region, bucket, or otherwise.
	 *
	 * @param array $source (Required) The bucket and file name to copy from. The following keys must be set: <ul>
	 * 	<li><code>bucket</code> - <code>string</code> - Required - Specifies the name of the bucket containing the source object.</li>
	 * 	<li><code>filename</code> - <code>string</code> - Required - Specifies the file name of the source object to copy.</li></ul>
	 * @param array $dest (Required) The bucket and file name to copy to. The following keys must be set: <ul>
	 * 	<li><code>bucket</code> - <code>string</code> - Required - Specifies the name of the bucket to copy the object to.</li>
	 * 	<li><code>filename</code> - <code>string</code> - Required - Specifies the file name to copy the object to.</li></ul>
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>acl</code> - <code>string</code> - Optional - The ACL settings for the specified object. [Allowed values: <code>AmazonS3::ACL_PRIVATE</code>, <code>AmazonS3::ACL_PUBLIC</code>, <code>AmazonS3::ACL_OPEN</code>, <code>AmazonS3::ACL_AUTH_READ</code>, <code>AmazonS3::ACL_OWNER_READ</code>, <code>AmazonS3::ACL_OWNER_FULL_CONTROL</code>]. Alternatively, an array of associative arrays. Each associative array contains an <code>id</code> and a <code>permission</code> key. The default value is <code>ACL_PRIVATE</code>.</li>
	 * 	<li><code>encryption</code> - <code>string</code> - Optional - The algorithm to use for encrypting the object. [Allowed values: <code>AES256</code>]</li>
	 * 	<li><code>storage</code> - <code>string</code> - Optional - Whether to use Standard or Reduced Redundancy storage. [Allowed values: <code>AmazonS3::STORAGE_STANDARD</code>, <code>AmazonS3::STORAGE_REDUCED</code>]. The default value is <code>STORAGE_STANDARD</code>.</li>
	 * 	<li><code>versionId</code> - <code>string</code> - Optional - The version of the object to copy. Version IDs are returned in the <code>x-amz-version-id</code> header of any previous object-related request.</li>
	 * 	<li><code>ifMatch</code> - <code>string</code> - Optional - The ETag header from a previous request. Copies the object if its entity tag (ETag) matches the specified tag; otherwise, the request returns a <code>412</code> HTTP status code error (precondition failed). Used in conjunction with <code>ifUnmodifiedSince</code>.</li>
	 * 	<li><code>ifUnmodifiedSince</code> - <code>string</code> - Optional - The LastModified header from a previous request. Copies the object if it hasn't been modified since the specified time; otherwise, the request returns a <code>412</code> HTTP status code error (precondition failed). Used in conjunction with <code>ifMatch</code>.</li>
	 * 	<li><code>ifNoneMatch</code> - <code>string</code> - Optional - The ETag header from a previous request. Copies the object if its entity tag (ETag) is different than the specified ETag; otherwise, the request returns a <code>412</code> HTTP status code error (failed condition). Used in conjunction with <code>ifModifiedSince</code>.</li>
	 * 	<li><code>ifModifiedSince</code> - <code>string</code> - Optional - The LastModified header from a previous request. Copies the object if it has been modified since the specified time; otherwise, the request returns a <code>412</code> HTTP status code error (failed condition). Used in conjunction with <code>ifNoneMatch</code>.</li>
	 * 	<li><code>headers</code> - <code>array</code> - Optional - Standard HTTP headers to send along in the request. Accepts an associative array of key-value pairs.</li>
	 * 	<li><code>meta</code> - <code>array</code> - Optional - Associative array of key-value pairs. Represented by <code>x-amz-meta-:</code> Any header starting with this prefix is considered user metadata. It will be stored with the object and returned when you retrieve the object. The total size of the HTTP request, not including the body, must be less than 4 KB.</li>
	 * 	<li><code>metadataDirective</code> - <code>string</code> - Optional - Accepts either COPY or REPLACE. You will likely never need to use this, as it manages itself with no issues.</li>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 * @link http://docs.amazonwebservices.com/AmazonS3/latest/dev/API/RESTObjectCOPY.html Copying Amazon S3 Objects
	 */
	public function copy_object($source, $dest, $opt = null)
	{
		if (!$opt) $opt = array();
		$batch = array();

		// Add this to our request
		$opt['verb'] = 'PUT';
		$opt['resource'] = $dest['filename'];
		$opt['body'] = '';

		// Handle copy source
		if (isset($source['bucket']) && isset($source['filename']))
		{
			$opt['headers']['x-amz-copy-source'] = '/' . $source['bucket'] . '/' . rawurlencode($source['filename'])
				. (isset($opt['versionId']) ? ('?' . 'versionId=' . rawurlencode($opt['versionId'])) : ''); // Append the versionId to copy, if available
			unset($opt['versionId']);

			// Determine if we need to lookup the pre-existing content-type.
			if (
				(!$this->use_batch_flow && !isset($opt['returnCurlHandle'])) &&
				!in_array(strtolower('content-type'), array_map('strtolower', array_keys($opt['headers'])))
			)
			{
				$response = $this->get_object_headers($source['bucket'], $source['filename']);
				if ($response->isOK())
				{
					$opt['headers']['Content-Type'] = $response->header['content-type'];
				}
			}
		}

		// Handle metadata directive
		$opt['headers']['x-amz-metadata-directive'] = 'COPY';
		if ($source['bucket'] === $dest['bucket'] && $source['filename'] === $dest['filename'])
		{
			$opt['headers']['x-amz-metadata-directive'] = 'REPLACE';
		}
		if (isset($opt['metadataDirective']))
		{
			$opt['headers']['x-amz-metadata-directive'] = $opt['metadataDirective'];
			unset($opt['metadataDirective']);
		}

		// Handle Access Control Lists. Can also pass canned ACLs as an HTTP header.
		if (isset($opt['acl']) && is_array($opt['acl']))
		{
			$batch[] = $this->set_object_acl($dest['bucket'], $dest['filename'], $opt['acl'], array(
				'returnCurlHandle' => true
			));
			unset($opt['acl']);
		}
		elseif (isset($opt['acl']))
		{
			$opt['headers']['x-amz-acl'] = $opt['acl'];
			unset($opt['acl']);
		}

		// Handle storage settings. Can also be passed as an HTTP header.
		if (isset($opt['storage']))
		{
			$opt['headers']['x-amz-storage-class'] = $opt['storage'];
			unset($opt['storage']);
		}

		// Handle encryption settings. Can also be passed as an HTTP header.
		if (isset($opt['encryption']))
		{
			$opt['headers']['x-amz-server-side-encryption'] = $opt['encryption'];
			unset($opt['encryption']);
		}

		// Handle conditional-copy parameters
		if (isset($opt['ifMatch']))
		{
			$opt['headers']['x-amz-copy-source-if-match'] = $opt['ifMatch'];
			unset($opt['ifMatch']);
		}
		if (isset($opt['ifNoneMatch']))
		{
			$opt['headers']['x-amz-copy-source-if-none-match'] = $opt['ifNoneMatch'];
			unset($opt['ifNoneMatch']);
		}
		if (isset($opt['ifUnmodifiedSince']))
		{
			$opt['headers']['x-amz-copy-source-if-unmodified-since'] = $opt['ifUnmodifiedSince'];
			unset($opt['ifUnmodifiedSince']);
		}
		if (isset($opt['ifModifiedSince']))
		{
			$opt['headers']['x-amz-copy-source-if-modified-since'] = $opt['ifModifiedSince'];
			unset($opt['ifModifiedSince']);
		}

		// Handle meta tags. Can also be passed as an HTTP header.
		if (isset($opt['meta']))
		{
			foreach ($opt['meta'] as $meta_key => $meta_value)
			{
				// e.g., `My Meta Header` is converted to `x-amz-meta-my-meta-header`.
				$opt['headers']['x-amz-meta-' . strtolower(str_replace(' ', '-', $meta_key))] = $meta_value;
			}
			unset($opt['meta']);
		}

		// Authenticate to S3
		$response = $this->authenticate($dest['bucket'], $opt);

		// Attempt to reset ACLs
		$http = new RequestCore();
		$http->send_multi_request($batch);

		return $response;
	}

	/**
	 * Updates an Amazon S3 object with new headers or other metadata. To replace the content of the
	 * specified Amazon S3 object, call <create_object()> with the same bucket and file name parameters.
	 *
	 * @param string $bucket (Required) The name of the bucket that contains the source file.
	 * @param string $filename (Required) The source file name that you want to update.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>acl</code> - <code>string</code> - Optional - The ACL settings for the specified object. [Allowed values: <code>AmazonS3::ACL_PRIVATE</code>, <code>AmazonS3::ACL_PUBLIC</code>, <code>AmazonS3::ACL_OPEN</code>, <code>AmazonS3::ACL_AUTH_READ</code>, <code>AmazonS3::ACL_OWNER_READ</code>, <code>AmazonS3::ACL_OWNER_FULL_CONTROL</code>]. The default value is <ACL_PRIVATE>.</li>
	 * 	<li><code>headers</code> - <code>array</code> - Optional - Standard HTTP headers to send along in the request. Accepts an associative array of key-value pairs.</li>
	 * 	<li><code>meta</code> - <code>array</code> - Optional - An associative array of key-value pairs. Any header with the <code>x-amz-meta-</code> prefix is considered user metadata and is stored with the Amazon S3 object. It will be stored with the object and returned when you retrieve the object. The total size of the HTTP request, not including the body, must be less than 4 KB.</li>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 * @link http://docs.amazonwebservices.com/AmazonS3/latest/dev/API/RESTObjectCOPY.html Copying Amazon S3 Objects
	 */
	public function update_object($bucket, $filename, $opt = null)
	{
		if (!$opt) $opt = array();
		$opt['metadataDirective'] = 'REPLACE';

		// Authenticate to S3
		return $this->copy_object(
			array('bucket' => $bucket, 'filename' => $filename),
			array('bucket' => $bucket, 'filename' => $filename),
			$opt
		);
	}


	/*%******************************************************************************************%*/
	// ACCESS CONTROL LISTS

	/**
	 * Gets the access control list (ACL) settings for the specified Amazon S3 object.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param string $filename (Required) The file name for the object.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>versionId</code> - <code>string</code> - Optional - The version of the object to retrieve. Version IDs are returned in the <code>x-amz-version-id</code> header of any previous object-related request.</li>
	 * 	<li><code>preauth</code> - <code>integer|string</code> - Optional - Specifies that a presigned URL for this request should be returned. May be passed as a number of seconds since UNIX Epoch, or any string compatible with <php:strtotime()>.</li>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 * @link http://docs.amazonwebservices.com/AmazonS3/latest/dev/RESTAccessPolicy.html REST Access Control Policy
	 */
	public function get_object_acl($bucket, $filename, $opt = null)
	{
		// Add this to our request
		if (!$opt) $opt = array();
		$opt['verb'] = 'GET';
		$opt['resource'] = $filename;
		$opt['sub_resource'] = 'acl';

		// Authenticate to S3
		return $this->authenticate($bucket, $opt);
	}

	/**
	 * Sets the access control list (ACL) settings for the specified Amazon S3 object.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param string $filename (Required) The file name for the object.
	 * @param string $acl (Optional) The ACL settings for the specified object. Accepts any of the following constants: [Allowed values: <code>AmazonS3::ACL_PRIVATE</code>, <code>AmazonS3::ACL_PUBLIC</code>, <code>AmazonS3::ACL_OPEN</code>, <code>AmazonS3::ACL_AUTH_READ</code>, <code>AmazonS3::ACL_OWNER_READ</code>, <code>AmazonS3::ACL_OWNER_FULL_CONTROL</code>]. Alternatively, an array of associative arrays. Each associative array contains an <code>id</code> and a <code>permission</code> key. The default value is <code>ACL_PRIVATE</code>.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 * @link http://docs.amazonwebservices.com/AmazonS3/latest/dev/RESTAccessPolicy.html REST Access Control Policy
	 */
	public function set_object_acl($bucket, $filename, $acl = self::ACL_PRIVATE, $opt = null)
	{
		// Add this to our request
		if (!$opt) $opt = array();
		$opt['verb'] = 'PUT';
		$opt['resource'] = $filename;
		$opt['sub_resource'] = 'acl';

		// Retrieve the original metadata
		$metadata = $this->get_object_metadata($bucket, $filename);
		if ($metadata && $metadata['ContentType'])
		{
			$opt['headers']['Content-Type'] = $metadata['ContentType'];
		}
		if ($metadata && $metadata['StorageClass'])
		{
			$opt['headers']['x-amz-storage-class'] = $metadata['StorageClass'];
		}

		// Make sure these are defined.
		// @codeCoverageIgnoreStart
		if (!$this->credentials->canonical_id || !$this->credentials->canonical_name)
		{
			// Fetch the data live.
			$canonical = $this->get_canonical_user_id();
			$this->credentials->canonical_id = $canonical['id'];
			$this->credentials->canonical_name = $canonical['display_name'];
		}
		// @codeCoverageIgnoreEnd

		if (is_array($acl))
		{
			$opt['body'] = $this->generate_access_policy($this->credentials->canonical_id, $this->credentials->canonical_name, $acl);
		}
		else
		{
			$opt['body'] = '';
			$opt['headers']['x-amz-acl'] = $acl;
		}

		// Authenticate to S3
		return $this->authenticate($bucket, $opt);
	}

	/**
	 * Generates the XML to be used for the Access Control Policy.
	 *
	 * @param string $canonical_id (Required) The canonical ID for the bucket owner. This is provided as the `id` return value from <get_canonical_user_id()>.
	 * @param string $canonical_name (Required) The canonical display name for the bucket owner. This is provided as the `display_name` value from <get_canonical_user_id()>.
	 * @param array $users (Optional) An array of associative arrays. Each associative array contains an `id` value and a `permission` value.
	 * @return string Access Control Policy XML.
	 * @link http://docs.amazonwebservices.com/AmazonS3/latest/dev/S3_ACLs.html Access Control Lists
	 */
	public function generate_access_policy($canonical_id, $canonical_name, $users)
	{
		$xml = simplexml_load_string($this->base_acp_xml);
		$owner = $xml->addChild('Owner');
		$owner->addChild('ID', $canonical_id);
		$owner->addChild('DisplayName', $canonical_name);
		$acl = $xml->addChild('AccessControlList');

		foreach ($users as $user)
		{
			$grant = $acl->addChild('Grant');
			$grantee = $grant->addChild('Grantee');

			switch ($user['id'])
			{
				// Authorized Users
				case self::USERS_AUTH:
					$grantee->addAttribute('xsi:type', 'Group', 'http://www.w3.org/2001/XMLSchema-instance');
					$grantee->addChild('URI', self::USERS_AUTH);
					break;

				// All Users
				case self::USERS_ALL:
					$grantee->addAttribute('xsi:type', 'Group', 'http://www.w3.org/2001/XMLSchema-instance');
					$grantee->addChild('URI', self::USERS_ALL);
					break;

				// The Logging User
				case self::USERS_LOGGING:
					$grantee->addAttribute('xsi:type', 'Group', 'http://www.w3.org/2001/XMLSchema-instance');
					$grantee->addChild('URI', self::USERS_LOGGING);
					break;

				// Email Address or Canonical Id
				default:
					if (strpos($user['id'], '@'))
					{
						$grantee->addAttribute('xsi:type', 'AmazonCustomerByEmail', 'http://www.w3.org/2001/XMLSchema-instance');
						$grantee->addChild('EmailAddress', $user['id']);
					}
					else
					{
						// Assume Canonical Id
						$grantee->addAttribute('xsi:type', 'CanonicalUser', 'http://www.w3.org/2001/XMLSchema-instance');
						$grantee->addChild('ID', $user['id']);
					}
					break;
			}

			$grant->addChild('Permission', $user['permission']);
		}

		return $xml->asXML();
	}


	/*%******************************************************************************************%*/
	// LOGGING METHODS

	/**
	 * Gets the access logs associated with the specified Amazon S3 bucket.
	 *
	 * @param string $bucket (Required) The name of the bucket to use. Pass a `null` value when using the <set_vhost()> method.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>preauth</code> - <code>integer|string</code> - Optional - Specifies that a presigned URL for this request should be returned. May be passed as a number of seconds since UNIX Epoch, or any string compatible with <php:strtotime()>.</li>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 * @link http://docs.amazonwebservices.com/AmazonS3/latest/dev/ServerLogs.html Server Access Logging
	 */
	public function get_logs($bucket, $opt = null)
	{
		// Add this to our request
		if (!$opt) $opt = array();
		$opt['verb'] = 'GET';
		$opt['sub_resource'] = 'logging';

		// Authenticate to S3
		return $this->authenticate($bucket, $opt);
	}

	/**
	 * Enables access logging for the specified Amazon S3 bucket.
	 *
	 * @param string $bucket (Required) The name of the bucket to enable logging for. Pass a `null` value when using the <set_vhost()> method.
	 * @param string $target_bucket (Required) The name of the bucket to store the logs in.
	 * @param string $target_prefix (Required) The prefix to give to the log file names.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>users</code> - <code>array</code> - Optional - An array of associative arrays specifying any user to give access to. Each associative array contains an <code>id</code> and <code>permission</code> value.</li>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 * @link http://docs.amazonwebservices.com/AmazonS3/latest/dev/LoggingAPI.html Server Access Logging Configuration API
	 */
	public function enable_logging($bucket, $target_bucket, $target_prefix, $opt = null)
	{
		// Add this to our request
		if (!$opt) $opt = array();
		$opt['verb'] = 'PUT';
		$opt['sub_resource'] = 'logging';
		$opt['headers'] = array(
			'Content-Type' => 'application/xml'
		);

		$xml = simplexml_load_string($this->base_logging_xml);
		$LoggingEnabled = $xml->addChild('LoggingEnabled');
		$LoggingEnabled->addChild('TargetBucket', $target_bucket);
		$LoggingEnabled->addChild('TargetPrefix', $target_prefix);
		$TargetGrants = $LoggingEnabled->addChild('TargetGrants');

		if (isset($opt['users']) && is_array($opt['users']))
		{
			foreach ($opt['users'] as $user)
			{
				$grant = $TargetGrants->addChild('Grant');
				$grantee = $grant->addChild('Grantee');

				switch ($user['id'])
				{
					// Authorized Users
					case self::USERS_AUTH:
						$grantee->addAttribute('xsi:type', 'Group', 'http://www.w3.org/2001/XMLSchema-instance');
						$grantee->addChild('URI', self::USERS_AUTH);
						break;

					// All Users
					case self::USERS_ALL:
						$grantee->addAttribute('xsi:type', 'Group', 'http://www.w3.org/2001/XMLSchema-instance');
						$grantee->addChild('URI', self::USERS_ALL);
						break;

					// The Logging User
					case self::USERS_LOGGING:
						$grantee->addAttribute('xsi:type', 'Group', 'http://www.w3.org/2001/XMLSchema-instance');
						$grantee->addChild('URI', self::USERS_LOGGING);
						break;

					// Email Address or Canonical Id
					default:
						if (strpos($user['id'], '@'))
						{
							$grantee->addAttribute('xsi:type', 'AmazonCustomerByEmail', 'http://www.w3.org/2001/XMLSchema-instance');
							$grantee->addChild('EmailAddress', $user['id']);
						}
						else
						{
							// Assume Canonical Id
							$grantee->addAttribute('xsi:type', 'CanonicalUser', 'http://www.w3.org/2001/XMLSchema-instance');
							$grantee->addChild('ID', $user['id']);
						}
						break;
				}

				$grant->addChild('Permission', $user['permission']);
			}
		}

		$opt['body'] = $xml->asXML();

		// Authenticate to S3
		return $this->authenticate($bucket, $opt);
	}

	/**
	 * Disables access logging for the specified Amazon S3 bucket.
	 *
	 * @param string $bucket (Required) The name of the bucket to use. Pass `null` if using <set_vhost()>.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 * @link http://docs.amazonwebservices.com/AmazonS3/latest/dev/LoggingAPI.html Server Access Logging Configuration API
	 */
	public function disable_logging($bucket, $opt = null)
	{
		// Add this to our request
		if (!$opt) $opt = array();
		$opt['verb'] = 'PUT';
		$opt['sub_resource'] = 'logging';
		$opt['headers'] = array(
			'Content-Type' => 'application/xml'
		);
		$opt['body'] = $this->base_logging_xml;

		// Authenticate to S3
		return $this->authenticate($bucket, $opt);
	}


	/*%******************************************************************************************%*/
	// CONVENIENCE METHODS

	/**
	 * Gets whether or not the specified Amazon S3 bucket exists in Amazon S3. This includes buckets
	 * that do not belong to the caller.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @return boolean A value of <code>true</code> if the bucket exists, or a value of <code>false</code> if it does not.
	 */
	public function if_bucket_exists($bucket)
	{
		if ($this->use_batch_flow)
		{
			throw new S3_Exception(__FUNCTION__ . '() cannot be batch requested');
		}

		$header = $this->get_bucket_headers($bucket);
		return (bool) $header->isOK();
	}

	/**
	 * Gets whether or not the specified Amazon S3 object exists in the specified bucket.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param string $filename (Required) The file name for the object.
	 * @return boolean A value of <code>true</code> if the object exists, or a value of <code>false</code> if it does not.
	 */
	public function if_object_exists($bucket, $filename)
	{
		if ($this->use_batch_flow)
		{
			throw new S3_Exception(__FUNCTION__ . '() cannot be batch requested');
		}

		$header = $this->get_object_headers($bucket, $filename);

		if ($header->isOK()) { return true; }
		elseif ($header->status === 404) { return false; }

		// @codeCoverageIgnoreStart
		return null;
		// @codeCoverageIgnoreEnd
	}

	/**
	 * Gets whether or not the specified Amazon S3 bucket has a bucket policy associated with it.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @return boolean A value of <code>true</code> if a bucket policy exists, or a value of <code>false</code> if one does not.
	 */
	public function if_bucket_policy_exists($bucket)
	{
		if ($this->use_batch_flow)
		{
			// @codeCoverageIgnoreStart
			throw new S3_Exception(__FUNCTION__ . '() cannot be batch requested');
			// @codeCoverageIgnoreEnd
		}

		$response = $this->get_bucket_policy($bucket);

		if ($response->isOK()) { return true; }
		elseif ($response->status === 404) { return false; }

		// @codeCoverageIgnoreStart
		return null;
		// @codeCoverageIgnoreEnd
	}

	/**
	 * Gets the number of Amazon S3 objects in the specified bucket.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @return integer The number of Amazon S3 objects in the bucket.
	 */
	public function get_bucket_object_count($bucket)
	{
		if ($this->use_batch_flow)
		{
			// @codeCoverageIgnoreStart
			throw new S3_Exception(__FUNCTION__ . '() cannot be batch requested');
			// @codeCoverageIgnoreEnd
		}

		return count($this->get_object_list($bucket));
	}

	/**
	 * Gets the cumulative file size of the contents of the Amazon S3 bucket.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param boolean $friendly_format (Optional) A value of <code>true</code> will format the return value to 2 decimal points using the largest possible unit (i.e., 3.42 GB). A value of <code>false</code> will format the return value as the raw number of bytes.
	 * @return integer|string The number of bytes as an integer, or the friendly format as a string.
	 */
	public function get_bucket_filesize($bucket, $friendly_format = false)
	{
		if ($this->use_batch_flow)
		{
			throw new S3_Exception(__FUNCTION__ . '() cannot be batch requested');
		}

		$filesize = 0;
		$list = $this->list_objects($bucket);

		foreach ($list->body->Contents as $filename)
		{
			$filesize += (integer) $filename->Size;
		}

		while ((string) $list->body->IsTruncated === 'true')
		{
			$body = (array) $list->body;
			$list = $this->list_objects($bucket, array(
				'marker' => (string) end($body['Contents'])->Key
			));

			foreach ($list->body->Contents as $object)
			{
				$filesize += (integer) $object->Size;
			}
		}

		if ($friendly_format)
		{
			$filesize = $this->util->size_readable($filesize);
		}

		return $filesize;
	}

	/**
	 * Gets the file size of the specified Amazon S3 object.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param string $filename (Required) The file name for the object.
	 * @param boolean $friendly_format (Optional) A value of <code>true</code> will format the return value to 2 decimal points using the largest possible unit (i.e., 3.42 GB). A value of <code>false</code> will format the return value as the raw number of bytes.
	 * @return integer|string The number of bytes as an integer, or the friendly format as a string.
	 */
	public function get_object_filesize($bucket, $filename, $friendly_format = false)
	{
		if ($this->use_batch_flow)
		{
			throw new S3_Exception(__FUNCTION__ . '() cannot be batch requested');
		}

		$object = $this->get_object_headers($bucket, $filename);
		$filesize = (integer) $object->header['content-length'];

		if ($friendly_format)
		{
			$filesize = $this->util->size_readable($filesize);
		}

		return $filesize;
	}

	/**
	 * Changes the content type for an existing Amazon S3 object.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param string $filename (Required) The file name for the object.
	 * @param string $contentType (Required) The content-type to apply to the object.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 */
	public function change_content_type($bucket, $filename, $contentType, $opt = null)
	{
		if (!$opt) $opt = array();

		// Retrieve the original metadata
		$metadata = $this->get_object_metadata($bucket, $filename);
		if ($metadata && $metadata['ACL'])
		{
			$opt['acl'] = $metadata['ACL'];
		}
		if ($metadata && $metadata['StorageClass'])
		{
			$opt['headers']['x-amz-storage-class'] = $metadata['StorageClass'];
		}

		// Merge optional parameters
		$opt = array_merge_recursive(array(
			'headers' => array(
				'Content-Type' => $contentType
			),
			'metadataDirective' => 'COPY'
		), $opt);

		return $this->copy_object(
			array('bucket' => $bucket, 'filename' => $filename),
			array('bucket' => $bucket, 'filename' => $filename),
			$opt
		);
	}

	/**
	 * Changes the storage redundancy for an existing object.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param string $filename (Required) The file name for the object.
	 * @param string $storage (Required) The storage setting to apply to the object. [Allowed values: <code>AmazonS3::STORAGE_STANDARD</code>, <code>AmazonS3::STORAGE_REDUCED</code>]
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 */
	public function change_storage_redundancy($bucket, $filename, $storage, $opt = null)
	{
		if (!$opt) $opt = array();

		// Retrieve the original metadata
		$metadata = $this->get_object_metadata($bucket, $filename);
		if ($metadata && $metadata['ACL'])
		{
			$opt['acl'] = $metadata['ACL'];
		}
		if ($metadata && $metadata['ContentType'])
		{
			$opt['headers']['Content-Type'] = $metadata['ContentType'];
		}

		// Merge optional parameters
		$opt = array_merge(array(
			'storage' => $storage,
			'metadataDirective' => 'COPY',
		), $opt);

		return $this->copy_object(
			array('bucket' => $bucket, 'filename' => $filename),
			array('bucket' => $bucket, 'filename' => $filename),
			$opt
		);
	}

	/**
	 * Gets a simplified list of bucket names on an Amazon S3 account.
	 *
	 * @param string $pcre (Optional) A Perl-Compatible Regular Expression (PCRE) to filter the bucket names against.
	 * @return array The list of matching bucket names. If there are no results, the method will return an empty array.
	 * @link http://php.net/pcre Regular Expressions (Perl-Compatible)
	 */
	public function get_bucket_list($pcre = null)
	{
		if ($this->use_batch_flow)
		{
			throw new S3_Exception(__FUNCTION__ . '() cannot be batch requested');
		}

		// Get a list of buckets.
		$list = $this->list_buckets();
		if ($list = $list->body->query('descendant-or-self::Name'))
		{
			$list = $list->map_string($pcre);
			return $list;
		}

		// @codeCoverageIgnoreStart
		return array();
		// @codeCoverageIgnoreEnd
	}

	/**
	 * Gets a simplified list of Amazon S3 object file names contained in a bucket.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>delimiter</code> - <code>string</code> - Optional - Keys that contain the same string between the prefix and the first occurrence of the delimiter will be rolled up into a single result element in the CommonPrefixes collection.</li>
	 * 	<li><code>marker</code> - <code>string</code> - Optional - Restricts the response to contain results that only occur alphabetically after the value of the marker.</li>
	 * 	<li><code>max-keys</code> - <code>integer</code> - Optional - The maximum number of results returned by the method call. The returned list will contain no more results than the specified value, but may return less. A value of zero is treated as if you did not specify max-keys.</li>
	 * 	<li><code>pcre</code> - <code>string</code> - Optional - A Perl-Compatible Regular Expression (PCRE) to filter the names against. This is applied only AFTER any native Amazon S3 filtering from specified <code>prefix</code>, <code>marker</code>, <code>max-keys</code>, or <code>delimiter</code> values are applied.</li>
	 * 	<li><code>prefix</code> - <code>string</code> - Optional - Restricts the response to contain results that begin only with the specified prefix.</li>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * @return array The list of matching object names. If there are no results, the method will return an empty array.
	 * @link http://php.net/pcre Regular Expressions (Perl-Compatible)
	 */
	public function get_object_list($bucket, $opt = null)
	{
		if ($this->use_batch_flow)
		{
			throw new S3_Exception(__FUNCTION__ . '() cannot be batch requested');
		}

		if (!$opt) $opt = array();
		unset($opt['returnCurlHandle']); // This would cause problems

		// Set some default values
		$pcre = isset($opt['pcre']) ? $opt['pcre'] : null;
		$max_keys = (isset($opt['max-keys']) && is_int($opt['max-keys'])) ? $opt['max-keys'] : null;
		$objects = array();

		if (!$max_keys)
		{
			// No max-keys specified. Get everything.
			do
			{
				$list = $this->list_objects($bucket, $opt);
				if ($keys = $list->body->query('descendant-or-self::Key')->map_string($pcre))
				{
					$objects = array_merge($objects, $keys);
				}

				$body = (array) $list->body;
				$opt = array_merge($opt, array(
					'marker' => (isset($body['Contents']) && is_array($body['Contents'])) ?
						((string) end($body['Contents'])->Key) :
						((string) $list->body->Contents->Key)
				));
			}
			while ((string) $list->body->IsTruncated === 'true');
		}
		else
		{
			// Max-keys specified. Approximate number of loops and make the requests.

			$max_keys = $opt['max-keys'];
			$loops = ceil($max_keys / 1000);

			do
			{
				$list = $this->list_objects($bucket, $opt);
				$keys = $list->body->query('descendant-or-self::Key')->map_string($pcre);

				if ($count = count($keys))
				{
					$objects = array_merge($objects, $keys);

					if ($count < 1000)
					{
						break;
					}
				}

				if ($max_keys > 1000)
				{
					$max_keys -= 1000;
				}

				$body = (array) $list->body;
				$opt = array_merge($opt, array(
					'max-keys' => $max_keys,
					'marker' => (isset($body['Contents']) && is_array($body['Contents'])) ?
						((string) end($body['Contents'])->Key) :
						((string) $list->body->Contents->Key)
				));
			}
			while (--$loops);
		}

		return $objects;
	}

	/**
	 * Deletes all Amazon S3 objects inside the specified bucket.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param string $pcre (Optional) A Perl-Compatible Regular Expression (PCRE) to filter the names against. The default value is <PCRE_ALL>.
	 * @return boolean A value of <code>true</code> means that all objects were successfully deleted. A value of <code>false</code> means that at least one object failed to delete.
	 * @link http://php.net/pcre Regular Expressions (Perl-Compatible)
	 */
	public function delete_all_objects($bucket, $pcre = self::PCRE_ALL)
	{
		// Collect all matches
		$list = $this->get_object_list($bucket, array('pcre' => $pcre));

		// As long as we have at least one match...
		if (count($list) > 0)
		{
			$objects = array();

			foreach ($list as $object)
			{
				$objects[] = array('key' => $object);
			}

			$batch = new CFBatchRequest();
			$batch->use_credentials($this->credentials);

			foreach (array_chunk($objects, 1000) as $object_set)
			{
				$this->batch($batch)->delete_objects($bucket, array(
					'objects' => $object_set
				));
			}

			$responses = $this->batch($batch)->send();
			$is_ok = true;

			foreach ($responses as $response)
			{
				if (!$response->isOK() || isset($response->body->Error))
				{
					$is_ok = false;
				}
			}

			return $is_ok;
		}

		// If there are no matches, return true
		return true;
	}

	/**
	 * Deletes all of the versions of all Amazon S3 objects inside the specified bucket.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param string $pcre (Optional) A Perl-Compatible Regular Expression (PCRE) to filter the names against. The default value is <PCRE_ALL>.
	 * @return boolean A value of <code>true</code> means that all object versions were successfully deleted. A value of <code>false</code> means that at least one object/version failed to delete.
	 * @link http://php.net/pcre Regular Expressions (Perl-Compatible)
	 */
	public function delete_all_object_versions($bucket, $pcre = null)
	{
		// Instantiate
		$versions = $this->list_bucket_object_versions($bucket);

		// Gather all nodes together into a single array
		if ($versions->body->DeleteMarker() && $versions->body->Version())
		{
			$markers = array_merge($versions->body->DeleteMarker()->getArrayCopy(), $versions->body->Version()->getArrayCopy());
		}
		elseif ($versions->body->DeleteMarker())
		{
			$markers = $versions->body->DeleteMarker()->getArrayCopy();
		}
		elseif ($versions->body->Version())
		{
			$markers = $versions->body->Version()->getArrayCopy();
		}
		else
		{
			$markers = array();
		}

		while ((string) $versions->body->IsTruncated === 'true')
		{
			$versions = $this->list_bucket_object_versions($bucket, array(
				'key-marker' => (string) $versions->body->NextKeyMarker
			));

			// Gather all nodes together into a single array
			if ($versions->body->DeleteMarker() && $versions->body->Version())
			{
				$markers = array_merge($markers, $versions->body->DeleteMarker()->getArrayCopy(), $versions->body->Version()->getArrayCopy());
			}
			elseif ($versions->body->DeleteMarker())
			{
				$markers = array_merge($markers, $versions->body->DeleteMarker()->getArrayCopy());
			}
			elseif ($versions->body->Version())
			{
				$markers = array_merge($markers, $versions->body->Version()->getArrayCopy());
			}
		}

		$objects = array();

		// Loop through markers
		foreach ($markers as $marker)
		{
			if ($pcre)
			{
				if (preg_match($pcre, (string) $marker->Key))
				{
					$xx = array('key' => (string) $marker->Key);
					if ((string) $marker->VersionId !== 'null')
					{
						$xx['version_id'] = (string) $marker->VersionId;
					}
					$objects[] = $xx;
					unset($xx);
				}
			}
			else
			{
				$xx = array('key' => (string) $marker->Key);
				if ((string) $marker->VersionId !== 'null')
				{
					$xx['version_id'] = (string) $marker->VersionId;
				}
				$objects[] = $xx;
				unset($xx);
			}
		}

		$batch = new CFBatchRequest();
		$batch->use_credentials($this->credentials);

		foreach (array_chunk($objects, 1000) as $object_set)
		{
			$this->batch($batch)->delete_objects($bucket, array(
				'objects' => $object_set
			));
		}

		$responses = $this->batch($batch)->send();
		$is_ok = true;

		foreach ($responses as $response)
		{
			if (!$response->isOK() || isset($response->body->Error))
			{
				$is_ok = false;
			}
		}

		return $is_ok;
	}

	/**
	 * Gets the collective metadata for the given Amazon S3 object.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param string $filename (Required) The file name for the Amazon S3 object.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>versionId</code> - <code>string</code> - Optional - The version of the object to retrieve. Version IDs are returned in the <code>x-amz-version-id</code> header of any previous object-related request.</li>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return mixed If the object exists, the method returns the collective metadata for the Amazon S3 object. If the object does not exist, the method returns boolean <code>false</code>.
	 */
	public function get_object_metadata($bucket, $filename, $opt = null)
	{
		$batch = new CFBatchRequest();
		$this->batch($batch)->get_object_acl($bucket, $filename); // Get ACL info
		$this->batch($batch)->get_object_headers($bucket, $filename); // Get content-type
		$this->batch($batch)->list_objects($bucket, array( // Get other metadata
			'max-keys' => 1,
			'prefix' => $filename
		));
		$response = $this->batch($batch)->send();

		// Fail if any requests were unsuccessful
		if (!$response->areOK())
		{
			return false;
		}

		$data = array(
			'ACL' => array(),
			'ContentType' => null,
			'ETag' => null,
			'Headers' => null,
			'Key' => null,
			'LastModified' => null,
			'Owner' => array(),
			'Size' => null,
			'StorageClass' => null,
		);

		// Add the content type
		$data['ContentType'] = (string) $response[1]->header['content-type'];

		// Add the other metadata (including storage type)
		$contents = json_decode(json_encode($response[2]->body->query('descendant-or-self::Contents')->first()), true);
		$data = array_merge($data, (is_array($contents) ? $contents : array()));

		// Add ACL info
		$grants = $response[0]->body->query('descendant-or-self::Grant');
		$max = count($grants);

		// Add raw header info
		$data['Headers'] = $response[1]->header;
		foreach (array('_info', 'x-amz-id-2', 'x-amz-request-id', 'cneonction', 'server', 'content-length', 'content-type', 'etag') as $header)
		{
			unset($data['Headers'][$header]);
		}
		ksort($data['Headers']);

		if (count($grants) > 0)
		{
			foreach ($grants as $grant)
			{
				$dgrant = array(
					'id' => (string) $this->util->try_these(array('ID', 'URI'), $grant->Grantee),
					'permission' => (string) $grant->Permission
				);

				$data['ACL'][] = $dgrant;
			}
		}

		return $data;
	}


	/*%******************************************************************************************%*/
	// URLS

	/**
	 * Gets the web-accessible URL for the Amazon S3 object or generates a time-limited signed request for
	 * a private file.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param string $filename (Required) The file name for the Amazon S3 object.
	 * @param integer|string $preauth (Optional) Specifies that a presigned URL for this request should be returned. May be passed as a number of seconds since UNIX Epoch, or any string compatible with <php:strtotime()>.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>https</code> - <code>boolean</code> - Optional - Set to <code>true</code> if you would like the URL be in https mode. Otherwise, the default behavior is always to use http regardless of your SSL settings.
	 * 	<li><code>method</code> - <code>string</code> - Optional - The HTTP method to use for the request. Defaults to a value of <code>GET</code>.</li>
	 * 	<li><code>response</code> - <code>array</code> - Optional - Allows adjustments to specific response headers. Pass an associative array where each key is one of the following: <code>cache-control</code>, <code>content-disposition</code>, <code>content-encoding</code>, <code>content-language</code>, <code>content-type</code>, <code>expires</code>. The <code>expires</code> value should use <php:gmdate()> and be formatted with the <code>DATE_RFC2822</code> constant.</li>
	 * 	<li><code>torrent</code> - <code>boolean</code> - Optional - A value of <code>true</code> will return a URL to a torrent of the Amazon S3 object. A value of <code>false</code> will return a non-torrent URL. Defaults to <code>false</code>.</li>
	 * 	<li><code>versionId</code> - <code>string</code> - Optional - The version of the object. Version IDs are returned in the <code>x-amz-version-id</code> header of any previous object-related request.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return string The file URL, with authentication and/or torrent parameters if requested.
	 * @link http://docs.amazonwebservices.com/AmazonS3/latest/dev/S3_QSAuth.html Using Query String Authentication
	 */
	public function get_object_url($bucket, $filename, $preauth = 0, $opt = null)
	{
		// Add this to our request
		if (!$opt) $opt = array();
		$opt['verb'] = isset($opt['method']) ? $opt['method'] : 'GET';
		$opt['resource'] = $filename;
		$opt['preauth'] = $preauth;

		if (isset($opt['torrent']) && $opt['torrent'])
		{
			$opt['sub_resource'] = 'torrent';
			unset($opt['torrent']);
		}

		// GET responses
		if (isset($opt['response']))
		{
			foreach ($opt['response'] as $key => $value)
			{
				$opt['response-' . $key] = $value;
				unset($opt['response'][$key]);
			}
		}

		// Determine whether or not to use SSL
		$use_ssl = isset($opt['https']) ? (bool) $opt['https'] : false;
		unset($opt['https']);
		$current_use_ssl_setting = $this->use_ssl;

		// Authenticate to S3
		$this->use_ssl = $use_ssl;
		$response = $this->authenticate($bucket, $opt);
		$this->use_ssl = $current_use_ssl_setting;

		return $response;
	}

	/**
	 * Gets the web-accessible URL to a torrent of the Amazon S3 object. The Amazon S3 object's access
	 * control list settings (ACL) MUST be set to <ACL_PUBLIC> for a valid URL to be returned.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param string $filename (Required) The file name for the object.
	 * @param integer|string $preauth (Optional) Specifies that a presigned URL for this request should be returned. May be passed as a number of seconds since UNIX Epoch, or any string compatible with <php:strtotime()>.
	 * @return string The torrent URL, with authentication parameters if requested.
	 * @link http://docs.amazonwebservices.com/AmazonS3/latest/dev/index.html?S3TorrentRetrieve.html Using BitTorrent to Retrieve Objects Stored in Amazon S3
	 */
	public function get_torrent_url($bucket, $filename, $preauth = 0)
	{
		return $this->get_object_url($bucket, $filename, $preauth, array(
			'torrent' => true
		));
	}


	/*%******************************************************************************************%*/
	// VERSIONING

	/**
	 * Enables versioning support for the specified Amazon S3 bucket.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>MFASerial</code> - string (Optional) The serial number on the back of the Gemalto device. <code>MFASerial</code>, <code>MFAToken</code> and <code>MFAStatus</code> must all be set for MFA to work.</li>
	 * 	<li><code>MFAToken</code> - string (Optional) The current token displayed on the Gemalto device. <code>MFASerial</code>, <code>MFAToken</code> and <code>MFAStatus</code> must all be set for MFA to work.</li>
	 * 	<li><code>MFAStatus</code> - string (Optional) The MFA Delete status. Can be <code>Enabled</code> or <code>Disabled</code>. <code>MFASerial</code>, <code>MFAToken</code> and <code>MFAStatus</code> must all be set for MFA to work.</li>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 * @link http://aws.amazon.com/mfa/ Multi-Factor Authentication
	 */
	public function enable_versioning($bucket, $opt = null)
	{
		if (!$opt) $opt = array();

		// Add this to our request
		$opt['verb'] = 'PUT';
		$opt['sub_resource'] = 'versioning';
		$opt['headers'] = array(
			'Content-Type' => 'application/xml'
		);

		$xml = simplexml_load_string($this->base_versioning_xml);
		$xml->addChild('Status', 'Enabled');

		// Enable MFA delete?
		// @codeCoverageIgnoreStart
		if (isset($opt['MFASerial']) && isset($opt['MFAToken']) && isset($opt['MFAStatus']))
		{
			$xml->addChild('MfaDelete', $opt['MFAStatus']);
			$opt['headers']['x-amz-mfa'] = ($opt['MFASerial'] . ' ' . $opt['MFAToken']);
		}
		// @codeCoverageIgnoreEnd

		$opt['body'] = $xml->asXML();

		// Authenticate to S3
		return $this->authenticate($bucket, $opt);
	}

	/**
	 * Disables versioning support for the specified Amazon S3 bucket.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>MFASerial</code> - <code>string</code> - Optional - The serial number on the back of the Gemalto device. <code>MFASerial</code>, <code>MFAToken</code> and <code>MFAStatus</code> must all be set for MFA to work.</li>
	 * 	<li><code>MFAToken</code> - <code>string</code> - Optional - The current token displayed on the Gemalto device. <code>MFASerial</code>, <code>MFAToken</code> and <code>MFAStatus</code> must all be set for MFA to work.</li>
	 * 	<li><code>MFAStatus</code> - <code>string</code> - Optional - The MFA Delete status. Can be <code>Enabled</code> or <code>Disabled</code>. <code>MFASerial</code>, <code>MFAToken</code> and <code>MFAStatus</code> must all be set for MFA to work.</li>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 * @link http://aws.amazon.com/mfa/ Multi-Factor Authentication
	 */
	public function disable_versioning($bucket, $opt = null)
	{
		if (!$opt) $opt = array();

		// Add this to our request
		$opt['verb'] = 'PUT';
		$opt['sub_resource'] = 'versioning';
		$opt['headers'] = array(
			'Content-Type' => 'application/xml'
		);

		$xml = simplexml_load_string($this->base_versioning_xml);
		$xml->addChild('Status', 'Suspended');

		// Enable MFA delete?
		// @codeCoverageIgnoreStart
		if (isset($opt['MFASerial']) && isset($opt['MFAToken']) && isset($opt['MFAStatus']))
		{
			$xml->addChild('MfaDelete', $opt['MFAStatus']);
			$opt['headers']['x-amz-mfa'] = ($opt['MFASerial'] . ' ' . $opt['MFAToken']);
		}
		// @codeCoverageIgnoreEnd

		$opt['body'] = $xml->asXML();

		// Authenticate to S3
		return $this->authenticate($bucket, $opt);
	}

	/**
	 * Gets an Amazon S3 bucket's versioning status.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>preauth</code> - <code>integer|string</code> - Optional - Specifies that a presigned URL for this request should be returned. May be passed as a number of seconds since UNIX Epoch, or any string compatible with <php:strtotime()>.</li>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 */
	public function get_versioning_status($bucket, $opt = null)
	{
		if (!$opt) $opt = array();
		$opt['verb'] = 'GET';
		$opt['sub_resource'] = 'versioning';

		// Authenticate to S3
		return $this->authenticate($bucket, $opt);
	}

	/**
	 * Gets a list of all the versions of Amazon S3 objects in the specified bucket.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>delimiter</code> - <code>string</code> - Optional - Unicode string parameter. Keys that contain the same string between the prefix and the first occurrence of the delimiter will be rolled up into a single result element in the CommonPrefixes collection.</li>
	 * 	<li><code>key-marker</code> - <code>string</code> - Optional - Restricts the response to contain results that only occur alphabetically after the value of the <code>key-marker</code>.</li>
	 * 	<li><code>max-keys</code> - <code>string</code> - Optional - Limits the number of results returned in response to your query. Will return no more than this number of results, but possibly less.</li>
	 * 	<li><code>prefix</code> - <code>string</code> - Optional - Restricts the response to only contain results that begin with the specified prefix.</li>
	 * 	<li><code>version-id-marker</code> - <code>string</code> - Optional - Restricts the response to contain results that only occur alphabetically after the value of the <code>version-id-marker</code>.</li>
	 * 	<li><code>preauth</code> - <code>integer|string</code> - Optional - Specifies that a presigned URL for this request should be returned. May be passed as a number of seconds since UNIX Epoch, or any string compatible with <php:strtotime()>.</li>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 */
	public function list_bucket_object_versions($bucket, $opt = null)
	{
		if (!$opt) $opt = array();
		$opt['verb'] = 'GET';
		$opt['sub_resource'] = 'versions';

		foreach (array('delimiter', 'key-marker', 'max-keys', 'prefix', 'version-id-marker') as $param)
		{
			if (isset($opt[$param]))
			{
				$opt['query_string'][$param] = $opt[$param];
				unset($opt[$param]);
			}
		}

		// Authenticate to S3
		return $this->authenticate($bucket, $opt);
	}


	/*%******************************************************************************************%*/
	// BUCKET POLICIES

	/**
	 * Sets the policy sub-resource for the specified Amazon S3 bucket. The specified policy replaces any
	 * policy the bucket already has.
	 *
	 * To perform this operation, the caller must be authorized to set a policy for the bucket and have
	 * PutPolicy permissions. If the caller does not have PutPolicy permissions for the bucket, Amazon S3
	 * returns a `403 Access Denied` error. If the caller has the correct permissions but has not been
	 * authorized by the bucket owner, Amazon S3 returns a `405 Method Not Allowed` error.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param CFPolicy $policy (Required) The JSON policy to use.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 * @link http://docs.amazonwebservices.com/AmazonS3/latest/dev/AccessPolicyLanguage.html Appendix: The Access Policy Language
	 */
	public function set_bucket_policy($bucket, CFPolicy $policy, $opt = null)
	{
		if (!$opt) $opt = array();
		$opt['verb'] = 'PUT';
		$opt['sub_resource'] = 'policy';
		$opt['body'] = $policy->get_json();

		// Authenticate to S3
		return $this->authenticate($bucket, $opt);
	}

	/**
	 * Gets the policy of the specified Amazon S3 bucket.
	 *
	 * To use this operation, the caller must have GetPolicy permissions for the specified bucket and must be
	 * the bucket owner. If the caller does not have GetPolicy permissions, this method will generate a
	 * `403 Access Denied` error. If the caller has the correct permissions but is not the bucket owner, this
	 * method will generate a `405 Method Not Allowed` error. If the bucket does not have a policy defined for
	 * it, this method will generate a `404 Policy Not Found` error.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 */
	public function get_bucket_policy($bucket, $opt = null)
	{
		if (!$opt) $opt = array();
		$opt['verb'] = 'GET';
		$opt['sub_resource'] = 'policy';

		// Authenticate to S3
		return $this->authenticate($bucket, $opt);
	}

	/**
	 * Deletes the bucket policy for the specified Amazon S3 bucket. To delete the policy, the caller must
	 * be the bucket owner and have `DeletePolicy` permissions for the specified bucket.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response. If you do not have `DeletePolicy` permissions, Amazon S3 returns a `403 Access Denied` error. If you have the correct permissions, but are not the bucket owner, Amazon S3 returns a `405 Method Not Allowed` error. If the bucket doesn't have a policy, Amazon S3 returns a `204 No Content` error.
	 */
	public function delete_bucket_policy($bucket, $opt = null)
	{
		if (!$opt) $opt = array();
		$opt['verb'] = 'DELETE';
		$opt['sub_resource'] = 'policy';

		// Authenticate to S3
		return $this->authenticate($bucket, $opt);
	}


	/*%******************************************************************************************%*/
	// BUCKET NOTIFICATIONS

	/**
	 * Enables notifications of specified events for an Amazon S3 bucket. Currently, the
	 * `s3:ReducedRedundancyLostObject` event is the only event supported for notifications. The
	 * `s3:ReducedRedundancyLostObject` event is triggered when Amazon S3 detects that it has lost all
	 * copies of an Amazon S3 object and can no longer service requests for that object.
	 *
	 * If the bucket owner and Amazon SNS topic owner are the same, the bucket owner has permission to
	 * publish notifications to the topic by default. Otherwise, the owner of the topic must create a
	 * policy to enable the bucket owner to publish to the topic.
	 *
	 * By default, only the bucket owner can configure notifications on a bucket. However, bucket owners
	 * can use bucket policies to grant permission to other users to set this configuration with the
	 * `s3:PutBucketNotification` permission.
	 *
	 * After a PUT operation is called to configure notifications on a bucket, Amazon S3 publishes a test
	 * notification to ensure that the topic exists and that the bucket owner has permission to publish
	 * to the specified topic. If the notification is successfully published to the SNS topic, the PUT
	 * operation updates the bucket configuration and returns the 200 OK responses with a
	 * `x-amz-sns-test-message-id` header containing the message ID of the test notification sent to topic.
	 *
	 * @param string $bucket (Required) The name of the bucket to create bucket notifications for.
	 * @param string $topic_arn (Required) The SNS topic ARN to send notifications to.
	 * @param string $event (Required) The event type to listen for.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 * @link http://docs.amazonwebservices.com/AmazonS3/latest/dev/NotificationHowTo.html Setting Up Notification of Bucket Events
	 */
	public function create_bucket_notification($bucket, $topic_arn, $event, $opt = null)
	{
		if (!$opt) $opt = array();
		$opt['verb'] = 'PUT';
		$opt['sub_resource'] = 'notification';
		$opt['headers'] = array(
			'Content-Type' => 'application/xml'
		);

		$xml = simplexml_load_string($this->base_notification_xml);
		$topic_config = $xml->addChild('TopicConfiguration');
		$topic_config->addChild('Topic', $topic_arn);
		$topic_config->addChild('Event', $event);

		$opt['body'] = $xml->asXML();

		// Authenticate to S3
		return $this->authenticate($bucket, $opt);
	}

	/**
	 * Gets the notification configuration of a bucket. Currently, the `s3:ReducedRedundancyLostObject` event
	 * is the only event supported for notifications. The `s3:ReducedRedundancyLostObject` event is triggered
	 * when Amazon S3 detects that it has lost all replicas of a Reduced Redundancy Storage object and can no
	 * longer service requests for that object.
	 *
	 * If notifications are not enabled on the bucket, the operation returns an empty
	 * `NotificatonConfiguration` element.
	 *
	 * By default, you must be the bucket owner to read the notification configuration of a bucket. However,
	 * the bucket owner can use a bucket policy to grant permission to other users to read this configuration
	 * with the `s3:GetBucketNotification` permission.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 * @link http://docs.amazonwebservices.com/AmazonS3/latest/dev/NotificationHowTo.html Setting Up Notification of Bucket Events
	 */
	public function get_bucket_notifications($bucket, $opt = null)
	{
		if (!$opt) $opt = array();
		$opt['verb'] = 'GET';
		$opt['sub_resource'] = 'notification';

		// Authenticate to S3
		return $this->authenticate($bucket, $opt);
	}

	/**
	 * Empties the list of SNS topics to send notifications to.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 * @link http://docs.amazonwebservices.com/AmazonS3/latest/dev/NotificationHowTo.html Setting Up Notification of Bucket Events
	 */
	public function delete_bucket_notification($bucket, $opt = null)
	{
		if (!$opt) $opt = array();
		$opt['verb'] = 'PUT';
		$opt['sub_resource'] = 'notification';
		$opt['body'] = $this->base_notification_xml;

		// Authenticate to S3
		return $this->authenticate($bucket, $opt);
	}


	/*%******************************************************************************************%*/
	// MULTIPART UPLOAD

	/**
	 * Calculates the correct values for sequentially reading a file for multipart upload. This method should
	 * be used in conjunction with <upload_part()>.
	 *
	 * @param integer $filesize (Required) The size in bytes of the entire file.
	 * @param integer $part_size (Required) The size in bytes of the part of the file to send.
	 * @return array An array containing key-value pairs. The keys are `seekTo` and `length`.
	 */
	public function get_multipart_counts($filesize, $part_size)
	{
		$i = 0;
		$sizecount = $filesize;
		$values = array();

		while ($sizecount > 0)
		{
			$sizecount -= $part_size;
			$values[] = array(
				'seekTo' => ($part_size * $i),
				'length' => (($sizecount > 0) ? $part_size : ($sizecount + $part_size)),
			);
			$i++;
		}

		return $values;
	}

	/**
	 * Initiates a multipart upload and returns an `UploadId`.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param string $filename (Required) The file name for the object.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>acl</code> - <code>string</code> - Optional - The ACL settings for the specified object. [Allowed values: <code>AmazonS3::ACL_PRIVATE</code>, <code>AmazonS3::ACL_PUBLIC</code>, <code>AmazonS3::ACL_OPEN</code>, <code>AmazonS3::ACL_AUTH_READ</code>, <code>AmazonS3::ACL_OWNER_READ</code>, <code>AmazonS3::ACL_OWNER_FULL_CONTROL</code>]. The default value is <code>ACL_PRIVATE</code>.</li>
	 * 	<li><code>contentType</code> - <code>string</code> - Optional - The type of content that is being sent. The default value is <code>application/octet-stream</code>.</li>
	 * 	<li><code>encryption</code> - <code>string</code> - Optional - The algorithm to use for encrypting the object. [Allowed values: <code>AES256</code>]</li>
	 * 	<li><code>headers</code> - <code>array</code> - Optional - Standard HTTP headers to send along in the request. Accepts an associative array of key-value pairs.</li>
	 * 	<li><code>meta</code> - <code>array</code> - Optional - An associative array of key-value pairs. Any header starting with <code>x-amz-meta-:</code> is considered user metadata. It will be stored with the object and returned when you retrieve the object. The total size of the HTTP request, not including the body, must be less than 4 KB.</li>
	 * 	<li><code>storage</code> - <code>string</code> - Optional - Whether to use Standard or Reduced Redundancy storage. [Allowed values: <code>AmazonS3::STORAGE_STANDARD</code>, <code>AmazonS3::STORAGE_REDUCED</code>]. The default value is <code>STORAGE_STANDARD</code>.</li>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 * @link http://docs.amazonwebservices.com/AmazonS3/latest/dev/RESTAccessPolicy.html REST Access Control Policy
	 */
	public function initiate_multipart_upload($bucket, $filename, $opt = null)
	{
		if (!$opt) $opt = array();

		// Add this to our request
		$opt['verb'] = 'POST';
		$opt['resource'] = $filename;
		$opt['sub_resource'] = 'uploads';
		$opt['body'] = '';

		// Handle content type. Can also be passed as an HTTP header.
		if (isset($opt['contentType']))
		{
			$opt['headers']['Content-Type'] = $opt['contentType'];
			unset($opt['contentType']);
		}

		// Set a default content type.
		if (!isset($opt['headers']['Content-Type']))
		{
			$opt['headers']['Content-Type'] = 'application/octet-stream';
		}

		// Handle Access Control Lists. Can also be passed as an HTTP header.
		if (isset($opt['acl']))
		{
			$opt['headers']['x-amz-acl'] = $opt['acl'];
			unset($opt['acl']);
		}

		// Handle storage settings. Can also be passed as an HTTP header.
		if (isset($opt['storage']))
		{
			$opt['headers']['x-amz-storage-class'] = $opt['storage'];
			unset($opt['storage']);
		}

		// Handle encryption settings. Can also be passed as an HTTP header.
		if (isset($opt['encryption']))
		{
			$opt['headers']['x-amz-server-side-encryption'] = $opt['encryption'];
			unset($opt['encryption']);
		}

		// Handle meta tags. Can also be passed as an HTTP header.
		if (isset($opt['meta']))
		{
			foreach ($opt['meta'] as $meta_key => $meta_value)
			{
				// e.g., `My Meta Header` is converted to `x-amz-meta-my-meta-header`.
				$opt['headers']['x-amz-meta-' . strtolower(str_replace(' ', '-', $meta_key))] = $meta_value;
			}
			unset($opt['meta']);
		}

		// Authenticate to S3
		return $this->authenticate($bucket, $opt);
	}

	/**
	 * Uploads a single part of a multipart upload. The part size cannot be smaller than 5 MB
	 * or larger than 5 TB. A multipart upload can have no more than 10,000 parts.
	 *
	 * Amazon S3 charges for storage as well as requests to the service. Smaller part sizes (and more
	 * requests) allow for faster failures and better upload reliability. Larger part sizes (and fewer
	 * requests) costs slightly less but has lower upload reliability.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param string $filename (Required) The file name for the object.
	 * @param string $upload_id (Required) The upload ID identifying the multipart upload whose parts are being listed. The upload ID is retrieved from a call to <initiate_multipart_upload()>.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>fileUpload</code> - <code>string|resource</code> - Required - The URL/path for the file to upload or an open resource.</li>
	 * 	<li><code>partNumber</code> - <code>integer</code> - Required - The part number order of the multipart upload.</li>
	 * 	<li><code>expect</code> - <code>string</code> - Optional - Specifies that the SDK not send the request body until it receives an acknowledgement. If the message is rejected based on the headers, the body of the message is not sent. For more information, see <a href="http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.20">RFC 2616, section 14.20</a>. The value can also be passed to the <code>header</code> option as <code>Expect</code>. [Allowed values: <code>100-continue</code>]</li>
	 * 	<li><code>headers</code> - <code>array</code> - Optional - Standard HTTP headers to send along in the request. Accepts an associative array of key-value pairs.</li>
	 * 	<li><code>length</code> - <code>integer</code> - Optional - The size of the part in bytes. For more information, see <a href="http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.13">RFC 2616, section 14.13</a>. The value can also be passed to the <code>header</code> option as <code>Content-Length</code>.</li>
	 * 	<li><code>md5</code> - <code>string</code> - Optional - The base64 encoded 128-bit MD5 digest of the part data. This header can be used as a message integrity check to verify that the part data is the same data that was originally sent. Although it is optional, we recommend using this mechanism as an end-to-end integrity check. For more information, see <a href="http://www.ietf.org/rfc/rfc1864.txt">RFC 1864</a>. The value can also be passed to the <code>header</code> option as <code>Content-MD5</code>.</li>
	 * 	<li><code>seekTo</code> - <code>integer</code> - Optional - The starting position in bytes for the piece of the file/stream to upload.</li>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 */
	public function upload_part($bucket, $filename, $upload_id, $opt = null)
	{
		if (!$opt) $opt = array();

		// Add this to our request
		$opt['verb'] = 'PUT';
		$opt['resource'] = $filename;
		$opt['uploadId'] = $upload_id;

		if (!isset($opt['fileUpload']) || !isset($opt['partNumber']))
		{
			throw new S3_Exception('The `fileUpload` and `partNumber` options are both required in ' . __FUNCTION__ . '().');
		}

		// Handle expectation. Can also be passed as an HTTP header.
		if (isset($opt['expect']))
		{
			$opt['headers']['Expect'] = $opt['expect'];
			unset($opt['expect']);
		}

		// Handle content length. Can also be passed as an HTTP header.
		if (isset($opt['length']))
		{
			$opt['headers']['Content-Length'] = $opt['length'];
			unset($opt['length']);
		}

		// Handle content md5. Can also be passed as an HTTP header.
		if (isset($opt['md5']))
		{
			$opt['headers']['Content-MD5'] = $opt['md5'];
			unset($opt['md5']);
		}

		$opt['headers']['Expect'] = '100-continue';

		// Authenticate to S3
		return $this->authenticate($bucket, $opt);
	}

	/**
	 * Lists the completed parts of an in-progress multipart upload.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param string $filename (Required) The file name for the object.
	 * @param string $upload_id (Required) The upload ID identifying the multipart upload whose parts are being listed. The upload ID is retrieved from a call to <initiate_multipart_upload()>.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>max-parts</code> - <code>integer</code> - Optional - The maximum number of parts to return in the response body.</li>
	 * 	<li><code>part-number-marker</code> - <code>string</code> - Optional - Restricts the response to contain results that only occur numerically after the value of the <code>part-number-marker</code>.</li>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 */
	public function list_parts($bucket, $filename, $upload_id, $opt = null)
	{
		if (!$opt) $opt = array();

		// Add this to our request
		$opt['verb'] = 'GET';
		$opt['resource'] = $filename;
		$opt['uploadId'] = $upload_id;
		$opt['query_string'] = array();

		foreach (array('max-parts', 'part-number-marker') as $param)
		{
			if (isset($opt[$param]))
			{
				$opt['query_string'][$param] = $opt[$param];
				unset($opt[$param]);
			}
		}

		// Authenticate to S3
		return $this->authenticate($bucket, $opt);
	}

	/**
	 * Aborts an in-progress multipart upload. This operation cannot be reversed.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param string $filename (Required) The file name for the object.
	 * @param string $upload_id (Required) The upload ID identifying the multipart upload whose parts are being listed. The upload ID is retrieved from a call to <initiate_multipart_upload()>.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 */
	public function abort_multipart_upload($bucket, $filename, $upload_id, $opt = null)
	{
		if (!$opt) $opt = array();

		// Add this to our request
		$opt['verb'] = 'DELETE';
		$opt['resource'] = $filename;
		$opt['uploadId'] = $upload_id;

		// Authenticate to S3
		return $this->authenticate($bucket, $opt);
	}

	/**
	 * Completes an in-progress multipart upload. A multipart upload is completed by describing the part
	 * numbers and corresponding ETag values in order, and submitting that data to Amazon S3 as an XML document.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param string $filename (Required) The file name for the object.
	 * @param string $upload_id (Required) The upload ID identifying the multipart upload whose parts are being listed. The upload ID is retrieved from a call to <initiate_multipart_upload()>.
	 * @param string|array|SimpleXMLElement|CFResponse $parts (Required) The completion XML document. This document can be provided in multiple ways; as a string of XML, as a <php:SimpleXMLElement> object representing the XML document, as an indexed array of associative arrays where the keys are <code>PartNumber</code> and <code>ETag</code>, or as a <CFResponse> object returned by <list_parts()>.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 */
	public function complete_multipart_upload($bucket, $filename, $upload_id, $parts, $opt = null)
	{
		if (!$opt) $opt = array();

		// Add this to our request
		$opt['verb'] = 'POST';
		$opt['resource'] = $filename;
		$opt['uploadId'] = $upload_id;
		$opt['headers'] = array(
			'Content-Type' => 'application/xml'
		);

		// Disable Content-MD5 calculation for this operation
		$opt['NoContentMD5'] = true;

		if (is_string($parts))
		{
			// Assume it's the intended XML.
			$opt['body'] = $parts;
		}
		elseif ($parts instanceof SimpleXMLElement)
		{
			// Assume it's a SimpleXMLElement object representing the XML.
			$opt['body'] = $parts->asXML();
		}
		elseif (is_array($parts) || $parts instanceof CFResponse)
		{
			$xml = simplexml_load_string($this->complete_mpu_xml);

			if (is_array($parts))
			{
				// Generate the appropriate XML.
				foreach ($parts as $node)
				{
					$part = $xml->addChild('Part');
					$part->addChild('PartNumber', $node['PartNumber']);
					$part->addChild('ETag', $node['ETag']);
				}

			}
			elseif ($parts instanceof CFResponse)
			{
				// Assume it's a response from list_parts().
				foreach ($parts->body->Part as $node)
				{
					$part = $xml->addChild('Part');
					$part->addChild('PartNumber', (string) $node->PartNumber);
					$part->addChild('ETag', (string) $node->ETag);
				}
			}

			$opt['body'] = $xml->asXML();
		}

		// Authenticate to S3
		return $this->authenticate($bucket, $opt);
	}

	/**
	 * Lists the in-progress multipart uploads.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>delimiter</code> - <code>string</code> - Optional - Keys that contain the same string between the prefix and the first occurrence of the delimiter will be rolled up into a single result element in the CommonPrefixes collection.</li>
	 * 	<li><code>key-marker</code> - <code>string</code> - Optional - Restricts the response to contain results that only occur alphabetically after the value of the <code>key-marker</code>. If used in conjunction with <code>upload-id-marker</code>, the results will be filtered to include keys whose upload ID is alphabetically after the value of <code>upload-id-marker</code>.</li>
	 * 	<li><code>upload-id-marker</code> - <code>string</code> - Optional - Restricts the response to contain results that only occur alphabetically after the value of the <code>upload-id-marker</code>. Must be used in conjunction with <code>key-marker</code>.</li>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 */
	public function list_multipart_uploads($bucket, $opt = null)
	{
		if (!$opt) $opt = array();

		// Add this to our request
		$opt['verb'] = 'GET';
		$opt['sub_resource'] = 'uploads';

		foreach (array('key-marker', 'max-uploads', 'upload-id-marker') as $param)
		{
			if (isset($opt[$param]))
			{
				$opt['query_string'][$param] = $opt[$param];
				unset($opt[$param]);
			}
		}

		// Authenticate to S3
		return $this->authenticate($bucket, $opt);
	}

	/**
	 * Since Amazon S3's standard <copy_object()> operation only supports copying objects that are smaller than
	 * 5 GB, the ability to copy large objects (greater than 5 GB) requires the use of "Multipart Copy".
	 *
	 * Copying large objects requires the developer to initiate a new multipart "upload", copy pieces of the
	 * large object (specifying a range of bytes up to 5 GB from the large source file), then complete the
	 * multipart "upload".
	 *
	 * NOTE: <strong>This is a synchronous operation</strong>, not an <em>asynchronous</em> operation, which means
	 * that Amazon S3 will not return a response for this operation until the copy has completed across the Amazon
	 * S3 server fleet. Copying objects within a single region will complete more quickly than copying objects
	 * <em>across</em> regions. The synchronous nature of this operation is different from other services where
	 * responses are typically returned immediately, even if the operation itself has not yet been completed on
	 * the server-side.
	 *
	 * @param array $source (Required) The bucket and file name to copy from. The following keys must be set: <ul>
	 * 	<li><code>bucket</code> - <code>string</code> - Required - Specifies the name of the bucket containing the source object.</li>
	 * 	<li><code>filename</code> - <code>string</code> - Required - Specifies the file name of the source object to copy.</li></ul>
	 * @param array $dest (Required) The bucket and file name to copy to. The following keys must be set: <ul>
	 * 	<li><code>bucket</code> - <code>string</code> - Required - Specifies the name of the bucket to copy the object to.</li>
	 * 	<li><code>filename</code> - <code>string</code> - Required - Specifies the file name to copy the object to.</li></ul>
	 * @param string $upload_id (Required) The upload ID identifying the multipart upload whose parts are being listed. The upload ID is retrieved from a call to <initiate_multipart_upload()>.
	 * @param integer $part_number (Required) A part number uniquely identifies a part and defines its position within the destination object. When you complete a multipart upload, a complete object is created by concatenating parts in ascending order based on part number. If you copy a new part using the same part number as a previously copied/uploaded part, the previously written part is overwritten.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>ifMatch</code> - <code>string</code> - Optional - The ETag header from a previous request. Copies the object if its entity tag (ETag) matches the specified tag; otherwise, the request returns a <code>412</code> HTTP status code error (precondition failed). Used in conjunction with <code>ifUnmodifiedSince</code>.</li>
	 * 	<li><code>ifUnmodifiedSince</code> - <code>string</code> - Optional - The LastModified header from a previous request. Copies the object if it hasn't been modified since the specified time; otherwise, the request returns a <code>412</code> HTTP status code error (precondition failed). Used in conjunction with <code>ifMatch</code>.</li>
	 * 	<li><code>ifNoneMatch</code> - <code>string</code> - Optional - The ETag header from a previous request. Copies the object if its entity tag (ETag) is different than the specified ETag; otherwise, the request returns a <code>412</code> HTTP status code error (failed condition). Used in conjunction with <code>ifModifiedSince</code>.</li>
	 * 	<li><code>ifModifiedSince</code> - <code>string</code> - Optional - The LastModified header from a previous request. Copies the object if it has been modified since the specified time; otherwise, the request returns a <code>412</code> HTTP status code error (failed condition). Used in conjunction with <code>ifNoneMatch</code>.</li>
	 * 	<li><code>range</code> - <code>string</code> - Optional - The range of bytes to copy from the object. Specify this parameter when copying partial bits. The specified range must be notated with a hyphen (e.g., 0-10485759). Defaults to the byte range of the complete Amazon S3 object.</li>
	 * 	<li><code>versionId</code> - <code>string</code> - Optional - The version of the object to copy. Version IDs are returned in the <code>x-amz-version-id</code> header of any previous object-related request.</li>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 */
	public function copy_part($source, $dest, $upload_id, $part_number, $opt = null)
	{
		if (!$opt) $opt = array();

		// Add this to our request
		$opt['verb'] = 'PUT';
		$opt['resource'] = $dest['filename'];
		$opt['uploadId'] = $upload_id;
		$opt['partNumber'] = $part_number;

		// Handle copy source
		if (isset($source['bucket']) && isset($source['filename']))
		{
			$opt['headers']['x-amz-copy-source'] = '/' . $source['bucket'] . '/' . rawurlencode($source['filename'])
				. (isset($opt['versionId']) ? ('?' . 'versionId=' . rawurlencode($opt['versionId'])) : ''); // Append the versionId to copy, if available
			unset($opt['versionId']);
		}

		// Handle conditional-copy parameters
		if (isset($opt['ifMatch']))
		{
			$opt['headers']['x-amz-copy-source-if-match'] = $opt['ifMatch'];
			unset($opt['ifMatch']);
		}
		if (isset($opt['ifNoneMatch']))
		{
			$opt['headers']['x-amz-copy-source-if-none-match'] = $opt['ifNoneMatch'];
			unset($opt['ifNoneMatch']);
		}
		if (isset($opt['ifUnmodifiedSince']))
		{
			$opt['headers']['x-amz-copy-source-if-unmodified-since'] = $opt['ifUnmodifiedSince'];
			unset($opt['ifUnmodifiedSince']);
		}
		if (isset($opt['ifModifiedSince']))
		{
			$opt['headers']['x-amz-copy-source-if-modified-since'] = $opt['ifModifiedSince'];
			unset($opt['ifModifiedSince']);
		}

		// Partial content range
		if (isset($opt['range']))
		{
			$opt['headers']['x-amz-copy-source-range'] = 'bytes=' . $opt['range'];
		}

		// Authenticate to S3
		return $this->authenticate($dest['bucket'], $opt);
	}

	/**
	 * Creates an Amazon S3 object using the multipart upload APIs. It is analogous to <create_object()>.
	 *
	 * While each individual part of a multipart upload can hold up to 5 GB of data, this method limits the
	 * part size to a maximum of 500 MB. The combined size of all parts can not exceed 5 TB of data. When an
	 * object is stored in Amazon S3, the data is streamed to multiple storage servers in multiple data
	 * centers. This ensures the data remains available in the event of internal network or hardware failure.
	 *
	 * Amazon S3 charges for storage as well as requests to the service. Smaller part sizes (and more
	 * requests) allow for faster failures and better upload reliability. Larger part sizes (and fewer
	 * requests) costs slightly less but has lower upload reliability.
	 *
	 * In certain cases with large objects, it's possible for this method to attempt to open more file system
	 * connections than allowed by the OS. In this case, either
	 * <a href="https://forums.aws.amazon.com/thread.jspa?threadID=70216">increase the number of connections
	 * allowed</a> or increase the value of the <code>partSize</code> parameter to use a larger part size.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param string $filename (Required) The file name for the object.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>fileUpload</code> - <code>string|resource</code> - Required - The URL/path for the file to upload, or an open resource.</li>
	 * 	<li><code>acl</code> - <code>string</code> - Optional - The ACL settings for the specified object. [Allowed values: <code>AmazonS3::ACL_PRIVATE</code>, <code>AmazonS3::ACL_PUBLIC</code>, <code>AmazonS3::ACL_OPEN</code>, <code>AmazonS3::ACL_AUTH_READ</code>, <code>AmazonS3::ACL_OWNER_READ</code>, <code>AmazonS3::ACL_OWNER_FULL_CONTROL</code>]. The default value is <code>ACL_PRIVATE</code>.</li>
	 * 	<li><code>contentType</code> - <code>string</code> - Optional - The type of content that is being sent in the body. The default value is <code>application/octet-stream</code>.</li>
	 * 	<li><code>headers</code> - <code>array</code> - Optional - Standard HTTP headers to send along in the request. Accepts an associative array of key-value pairs.</li>
	 * 	<li><code>length</code> - <code>integer</code> - Optional - The size of the object in bytes. For more information, see <a href="http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.13">RFC 2616, section 14.13</a>. The value can also be passed to the <code>header</code> option as <code>Content-Length</code>.</li>
	 * 	<li><code>limit</code> - <code>integer</code> - Optional - The maximum number of concurrent uploads done by cURL. Gets passed to <code>CFBatchRequest</code>.</li>
	 * 	<li><code>meta</code> - <code>array</code> - Optional - An associative array of key-value pairs. Any header starting with <code>x-amz-meta-:</code> is considered user metadata. It will be stored with the object and returned when you retrieve the object. The total size of the HTTP request, not including the body, must be less than 4 KB.</li>
	 * 	<li><code>partSize</code> - <code>integer</code> - Optional - The size of an individual part. The size may not be smaller than 5 MB or larger than 500 MB. The default value is 50 MB.</li>
	 * 	<li><code>seekTo</code> - <code>integer</code> - Optional - The starting position in bytes for the first piece of the file/stream to upload.</li>
	 * 	<li><code>storage</code> - <code>string</code> - Optional - Whether to use Standard or Reduced Redundancy storage. [Allowed values: <code>AmazonS3::STORAGE_STANDARD</code>, <code>AmazonS3::STORAGE_REDUCED</code>]. The default value is <code>STORAGE_STANDARD</code>.</li>
	 * 	<li><code>uploadId</code> - <code>string</code> - Optional - An upload ID identifying an existing multipart upload to use. If this option is not set, one will be created automatically.</li>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 * @link http://docs.amazonwebservices.com/AmazonS3/latest/dev/RESTAccessPolicy.html REST Access Control Policy
	 */
	public function create_mpu_object($bucket, $filename, $opt = null)
	{
		if ($this->use_batch_flow)
		{
			throw new S3_Exception(__FUNCTION__ . '() cannot be batch requested');
		}

		if (!$opt) $opt = array();

		// Handle content length. Can also be passed as an HTTP header.
		if (isset($opt['length']))
		{
			$opt['headers']['Content-Length'] = $opt['length'];
			unset($opt['length']);
		}

		if (!isset($opt['fileUpload']))
		{
			throw new S3_Exception('The `fileUpload` option is required in ' . __FUNCTION__ . '().');
		}
		elseif (is_resource($opt['fileUpload']))
		{
			$opt['limit'] = 1; // We can only read from this one resource.
			$upload_position = isset($opt['seekTo']) ? (integer) $opt['seekTo'] : ftell($opt['fileUpload']);
			$upload_filesize = isset($opt['headers']['Content-Length']) ? (integer) $opt['headers']['Content-Length'] : null;

			if (!isset($upload_filesize) && $upload_position !== false)
			{
				$stats = fstat($opt['fileUpload']);

				if ($stats && $stats['size'] >= 0)
				{
					$upload_filesize = $stats['size'] - $upload_position;
				}
			}
		}
		else
		{
			$upload_position = isset($opt['seekTo']) ? (integer) $opt['seekTo'] : 0;

			if (isset($opt['headers']['Content-Length']))
			{
				$upload_filesize = (integer) $opt['headers']['Content-Length'];
			}
			else
			{
				$upload_filesize = filesize($opt['fileUpload']);

				if ($upload_filesize !== false)
				{
					$upload_filesize -= $upload_position;
				}
			}
		}

		if ($upload_position === false || !isset($upload_filesize) || $upload_filesize === false || $upload_filesize < 0)
		{
			throw new S3_Exception('The size of `fileUpload` cannot be determined in ' . __FUNCTION__ . '().');
		}

		// Handle part size
		if (isset($opt['partSize']))
		{
			// If less that 5 MB...
			if ((integer) $opt['partSize'] < 5242880)
			{
				$opt['partSize'] = 5242880; // 5 MB
			}
			// If more than 500 MB...
			elseif ((integer) $opt['partSize'] > 524288000)
			{
				$opt['partSize'] = 524288000; // 500 MB
			}
		}
		else
		{
			$opt['partSize'] = 52428800; // 50 MB
		}

		// If the upload size is smaller than the piece size, failover to create_object().
		if ($upload_filesize < $opt['partSize'] && !isset($opt['uploadId']))
		{
			return $this->create_object($bucket, $filename, $opt);
		}

		// Initiate multipart upload
		if (isset($opt['uploadId']))
		{
			$upload_id = $opt['uploadId'];
		}
		else
		{
			// Compose options for initiate_multipart_upload().
			$_opt = array();
			foreach (array('contentType', 'acl', 'storage', 'headers', 'meta') as $param)
			{
				if (isset($opt[$param]))
				{
					$_opt[$param] = $opt[$param];
				}
			}

			$upload = $this->initiate_multipart_upload($bucket, $filename, $_opt);
			if (!$upload->isOK())
			{
				return $upload;
			}

			// Fetch the UploadId
			$upload_id = (string) $upload->body->UploadId;
		}

		// Get the list of pieces
		$pieces = $this->get_multipart_counts($upload_filesize, (integer) $opt['partSize']);

		// Queue batch requests
		$batch = new CFBatchRequest(isset($opt['limit']) ? (integer) $opt['limit'] : null);
		foreach ($pieces as $i => $piece)
		{
			$this->batch($batch)->upload_part($bucket, $filename, $upload_id, array(
				'expect' => '100-continue',
				'fileUpload' => $opt['fileUpload'],
				'partNumber' => ($i + 1),
				'seekTo' => $upload_position + (integer) $piece['seekTo'],
				'length' => (integer) $piece['length'],
			));
		}

		// Send batch requests
		$batch_responses = $this->batch($batch)->send();
		if (!$batch_responses->areOK())
		{
			return $batch_responses;
		}

		// Compose completion XML
		$parts = array();
		foreach ($batch_responses as $i => $response)
		{
			$parts[] = array('PartNumber' => ($i + 1), 'ETag' => $response->header['etag']);
		}

		return $this->complete_multipart_upload($bucket, $filename, $upload_id, $parts);
	}

	/**
	 * Aborts all multipart uploads initiated before the specified date. This operation cannot be reversed.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param string|integer $when (Optional) The time and date to use for comparison. Accepts any value that <php:strtotime()> understands.
	 * @return CFArray A <CFArray> containing a series of 0 or more <CFResponse> objects, containing a parsed HTTP response.
	 */
	public function abort_multipart_uploads_by_date($bucket, $when = null)
	{
		if ($this->use_batch_flow)
		{
			// @codeCoverageIgnoreStart
			throw new S3_Exception(__FUNCTION__ . '() cannot be batch requested');
			// @codeCoverageIgnoreEnd
		}

		$when = $when ? $when : time();
		$handles = array();
		$data = $this->list_multipart_uploads($bucket)->body;
		$when = is_int($when) ? $when : strtotime($when);

		if (!($data instanceof CFSimpleXML))
		{
			return false;
		}

		$list = $data->query('descendant-or-self::Upload/Initiated');

		if (count($list) > 0)
		{
			foreach ($list as $node)
			{
				if (strtotime((string) $node) < $when)
				{
					$q = new CFBatchRequest();
					$parent = $node->parent();

					$upload_id = $parent
						->query('descendant-or-self::UploadId')
						->first()
						->to_string();

					$filename = $parent
						->query('descendant-or-self::Key')
						->first()
						->to_string();

					$handles[] = $this->abort_multipart_upload($bucket, $filename, $upload_id, array(
						'returnCurlHandle' => true
					));
				}
			}

			$http = new CFRequest();
			$responses = $http->send_multi_request($handles);

			if (is_array($responses) && count($responses) > 0)
			{
				return new CFArray($responses);
			}
		}

		return new CFArray();
	}


	/*%******************************************************************************************%*/
	// WEBSITE CONFIGURATION

	/**
	 * Enables and configures an Amazon S3 website using the corresponding bucket as the content source.
	 * The website will have one default domain name associated with it, which is the bucket name. If you
	 * attempt to configure an Amazon S3 website for a bucket whose name is not compatible with DNS,
	 * Amazon S3 returns an <code>InvalidBucketName</code> error. For more information on bucket names and DNS,
	 * refer to <a href="http://docs.amazonwebservices.com/AmazonS3/latest/dev/BucketRestrictions.html">Bucket Restrictions and Limitations.</a>
	 *
	 * To visit the bucket as a website a new endpoint is created in the following pattern:
	 * <code>http://&lt;bucketName&gt;.s3-website-&lt;region&gt;.amazonaws.com</code>. This is a sample URL
	 * for a bucket called <code>example-bucket</code> in the <code>us-east-1</code> region.
	 * (e.g., <code>http://example-bucket.s3-website-us-east-1.amazonaws.com</code>)
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>indexDocument</code> - <code>string</code> - Optional - The file path to use as the root document. The default value is <code>index.html</code>.</li>
	 * 	<li><code>errorDocument</code> - <code>string</code> - Optional - The file path to use as the error document. The default value is <code>error.html</code>.</li>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 */
	public function create_website_config($bucket, $opt = null)
	{
		if (!$opt) $opt = array();
		$opt['verb'] = 'PUT';
		$opt['sub_resource'] = 'website';

		$xml = simplexml_load_string($this->website_config_xml);
		if (isset($opt['indexDocument']))
		{
			$xml->IndexDocument->Suffix = $opt['indexDocument'];
		}
		if (isset($opt['errorDocument']))
		{
			$xml->ErrorDocument->Key = $opt['errorDocument'];
		}

		$opt['body'] = $xml->asXML();

		// Authenticate to S3
		return $this->authenticate($bucket, $opt);
	}

	/**
	 * Retrieves the website configuration for a bucket. The contents of this response are identical to the
	 * content submitted by the user during the website creation operation. If a website configuration has
	 * never been set, Amazon S3 will return a 404 error.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 */
	public function get_website_config($bucket, $opt = null)
	{
		if (!$opt) $opt = array();
		$opt['verb'] = 'GET';
		$opt['sub_resource'] = 'website';
		$opt['headers'] = array(
			'Content-Type' => 'application/xml'
		);

		// Authenticate to S3
		return $this->authenticate($bucket, $opt);
	}

	/**
	 * Removes the website configuration for a bucket.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 */
	public function delete_website_config($bucket, $opt = null)
	{
		if (!$opt) $opt = array();
		$opt['verb'] = 'DELETE';
		$opt['sub_resource'] = 'website';

		// Authenticate to S3
		return $this->authenticate($bucket, $opt);
	}


	/*%******************************************************************************************%*/
	// OBJECT EXPIRATION

	/**
	 * Enables the ability to specify an expiry period for objects when an object should be deleted,
	 * measured as number of days from creation time. Amazon S3 guarantees that the object will be
	 * deleted when the expiration time is passed.
	 *
	 * @param string $bucket (Required) The name of the bucket to use.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>rules</code> - <code>string</code> - Required - The object expiration rule-sets to apply to the bucket. <ul>
	 * 		<li><code>x</code> - <code>array</code> - Required - This represents a simple array index. <ul>
	 * 			<li><code>id</code> - <code>string</code> - Optional - Unique identifier for the rule. The value cannot be longer than 255 characters.
	 * 			<li><code>prefix</code> - <code>string</code> - Required - The Amazon S3 object prefix which targets the file(s) for expiration.</li>
	 * 			<li><code>expiration</code> - <code>array</code> - Required - The container for the unit of measurement by which the expiration time is calculated. <ul>
	 * 				<li><code>days</code> - <code>integer</code> - Required - The number of days until the targetted objects expire from the bucket.</li>
	 * 			</ul></li>
	 * 			<li><code>enabled</code> - <code>boolean</code> - Optional - Whether or not to enable this rule-set. A value of <code>true</code> enables the rule-set. A value of <code>false</code> disables the rule-set. The default value is <code>true</code>.</li>
	 * 		</ul></li>
	 * 	</ul></li>
	 * 	<li><code>curlopts</code> - <code>array</code> - Optional - A set of values to pass directly into <code>curl_setopt()</code>, where the key is a pre-defined <code>CURLOPT_*</code> constant.</li>
	 * 	<li><code>returnCurlHandle</code> - <code>boolean</code> - Optional - A private toggle specifying that the cURL handle be returned rather than actually completing the request. This toggle is useful for manually managed batch requests.</li></ul>
	 * @return CFResponse A <CFResponse> object containing a parsed HTTP response.
	 */
	public function create_object_expiration_config($bucket, $opt = null)
	{
		if (!$opt) $opt = array();
		$opt['verb'] = 'PUT';
		$opt['sub_resource'] = 'lifecycle';
		$opt['headers'] = array(
			'Content-Type' => 'application/xml'
		);

		$xml = simplexml_load_string($this->object_expiration_xml, $this->parser_class);

		if (isset($opt['rules']) && is_array($opt['rules']) && count($opt['rules']))
		{
			foreach ($opt['rules'] as $rule)
			{
				$xrule = $xml->addChild('Rule');

				// ID
				if (isset($rule['id']))
				{
					if (strlen($rule['id']) > 255)
					{
						throw new S3_Exception('The "id" for a rule must not be more than 255 characters in the ' . __FUNCTION__ . ' method.');
					}

					$xrule->addChild('ID', $rule['id']);
				}

				// Prefix
				if (isset($rule['prefix']))
				{
					$xrule->addChild('Prefix', $rule['prefix']);
				}
				else
				{
					throw new S3_Exception('Each rule requires a "prefix" in the ' . __FUNCTION__ . ' method.');
				}

				// Status
				$enabled = 'Enabled';
				if (isset($rule['enabled']))
				{
					if (is_bool($rule['enabled'])) // Boolean
					{
						$enabled = $rule['enabled'] ? 'Enabled' : 'Disabled';
					}
					elseif (is_string($rule['enabled'])) // String
					{
						$enabled = (strtolower($rule['enabled']) === 'true') ? 'Enabled' : 'Disabled';
					}

					$xrule->addChild('Status', $enabled);
				}
				else
				{
					$xrule->addChild('Status', 'Enabled');
				}

				// Expiration
				if (isset($rule['expiration']))
				{
					$xexpiration = $xrule->addChild('Expiration');

					if (isset($rule['expiration']['days']))
					{
						$xexpiration->addChild('Days', $rule['expiration']['days']);
					}
				}
				else
				{
					throw new S3_Exception('Each rule requires a "expiration" in the ' . __FUNCTION__ . ' method.');
				}
			}
		}

		$opt['body'] = $xml->asXML();

		// Authenticate to S3
		return $this->authenticate($bucket, $opt);
	}

	public function get_object_expiration_config($bucket, $opt = null)
	{
		if (!$opt) $opt = array();
		$opt['verb'] = 'GET';
		$opt['sub_resource'] = 'lifecycle';

		// Authenticate to S3
		return $this->authenticate($bucket, $opt);
	}

	public function delete_object_expiration_config($bucket, $opt = null)
	{
		if (!$opt) $opt = array();
		$opt['verb'] = 'DELETE';
		$opt['sub_resource'] = 'lifecycle';

		// Authenticate to S3
		return $this->authenticate($bucket, $opt);
	}


	/*%******************************************************************************************%*/
	// MISCELLANEOUS

	/**
	 * Gets the canonical user ID and display name from the Amazon S3 server.
	 *
	 * @return array An associative array containing the `id` and `display_name` values.
	 */
	public function get_canonical_user_id()
	{
		if ($this->use_batch_flow)
		{
			throw new S3_Exception(__FUNCTION__ . '() cannot be batch requested');
		}

		$id = $this->list_buckets();

		return array(
			'id' => (string) $id->body->Owner->ID,
			'display_name' => (string) $id->body->Owner->DisplayName
		);
	}

	/**
	 * Loads and registers the S3StreamWrapper class as a stream wrapper.
	 *
	 * @param string $protocol (Optional) The name of the protocol to register.
	 * @return boolean Whether or not the registration succeeded.
	 */
	public function register_stream_wrapper($protocol = 's3')
	{
		require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'extensions'
			. DIRECTORY_SEPARATOR . 's3streamwrapper.class.php';

		return S3StreamWrapper::register($this, $protocol);
	}
}
