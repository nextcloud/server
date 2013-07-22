<?php
/**
 * Copyright 2010-2013 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 * http://aws.amazon.com/apache2.0
 *
 * or in the "license" file accompanying this file. This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */

namespace Aws\S3\Model;

use Aws\Common\Exception\InvalidArgumentException;
use Aws\Common\Enum\DateFormat;
use Aws\S3\S3Client;
use Guzzle\Common\Collection;
use Guzzle\Http\Url;

/**
 * Encapsulates the logic for getting the data for an S3 object POST upload form
 */
class PostObject extends Collection
{
    /**
     * @var S3Client The S3 client being used to sign the policy
     */
    protected $client;

    /**
     * @var string The bucket name where the object will be posted
     */
    protected $bucket;

    /**
     * @var array The <form> tag attributes as an array
     */
    protected $formAttributes;

    /**
     * @var array The form's <input> elements as an array
     */
    protected $formInputs;

    /**
     * @var string The raw json policy
     */
    protected $jsonPolicy;

    /**
     * Constructs the PostObject
     *
     * The options array accepts the following keys:
     *
     * - acl:                          The access control setting to apply to the uploaded file. Accepts any of the
     *                                 CannedAcl constants
     * - Cache-Control:                The Cache-Control HTTP header value to apply to the uploaded file
     * - Content-Disposition:          The Content-Disposition HTTP header value to apply to the uploaded file
     * - Content-Encoding:             The Content-Encoding HTTP header value to apply to the uploaded file
     * - Content-Type:                 The Content-Type HTTP header value to apply to the uploaded file. The default
     *                                 value is `application/octet-stream`
     * - Expires:                      The Expires HTTP header value to apply to the uploaded file
     * - key:                          The location where the file should be uploaded to. The default value is
     *                                 `^${filename}` which will use the name of the uploaded file
     * - policy:                       A raw policy in JSON format. By default, the PostObject creates one for you
     * - success_action_redirect:      The URI for Amazon S3 to redirect to upon successful upload
     * - success_action_status:        The status code for Amazon S3 to return upon successful upload
     * - ttd:                          The expiration time for the generated upload form data
     * - x-amz-server-side-encryption: The server-side encryption mechanism to use
     * - x-amz-storage-class:          The storage setting to apply to the object
     * - x-amz-meta-*:                 Any custom meta tag that should be set to the object
     *
     * For the Cache-Control, Content-Disposition, Content-Encoding,
     * Content-Type, Expires, and key options, to use a "starts-with" comparison
     * instead of an equals comparison, prefix the value with a ^ (carat)
     * character
     *
     * @param S3Client $client
     * @param $bucket
     * @param array $options
     */
    public function __construct(S3Client $client, $bucket, array $options = array())
    {
        $this->setClient($client);
        $this->setBucket($bucket);
        parent::__construct($options);
    }

    /**
     * Analyzes the provided data and turns it into useful data that can be
     * consumed and used to build an upload form
     *
     * @return PostObject
     */
    public function prepareData()
    {
        // Validate required options
        $options = Collection::fromConfig($this->data, array(
            'ttd' => '+1 hour',
            'key' => '^${filename}',
        ));

        // Format ttd option
        $ttd = $options['ttd'];
        $ttd = is_numeric($ttd) ? (int) $ttd : strtotime($ttd);
        unset($options['ttd']);

        // Save policy if passed in
        $rawPolicy = $options['policy'];
        unset($options['policy']);

        // Setup policy document
        $policy = array(
            'expiration' => gmdate(DateFormat::ISO8601_S3, $ttd),
            'conditions' => array(array('bucket' => $this->bucket))
        );

        // Configure the endpoint/action
        $url = Url::factory($this->client->getBaseUrl());
        $url->setHost($this->bucket . '.' . $url->getHost());

        // Setup basic form
        $this->formAttributes = array(
            'action' => (string) $url,
            'method' => 'POST',
            'enctype' => 'multipart/form-data'
        );
        $this->formInputs = array(
            'AWSAccessKeyId' => $this->client->getCredentials()->getAccessKeyId()
        );

        // Add success action status
        $status = (int) $options->get('success_action_status');
        if ($status && in_array($status, array(200, 201, 204))) {
            $this->formInputs['success_action_status'] = (string) $status;
            $policy['conditions'][] = array(
                'success_action_status' => (string) $status
            );
            $options->remove('success_action_status');
        }

        // Add other options
        foreach ($options as $key => $value) {
            $value = (string) $value;
            if ($value[0] === '^') {
                $value = substr($value, 1);
                $this->formInputs[$key] = $value;
                $value = preg_replace('/\$\{(\w*)\}/', '', $value);
                $policy['conditions'][] = array('starts-with', '$' . $key, $value);
            } else {
                $this->formInputs[$key] = $value;
                $policy['conditions'][] = array($key => $value);
            }
        }

        // Add policy
        $this->jsonPolicy = $rawPolicy ?: json_encode($policy);
        $jsonPolicy64 = base64_encode($this->jsonPolicy);
        $this->formInputs['policy'] = $jsonPolicy64;

        // Add signature
        $this->formInputs['signature'] = base64_encode(hash_hmac(
            'sha1',
            $jsonPolicy64,
            $this->client->getCredentials()->getSecretKey(),
            true
        ));

        return $this;
    }

    /**
     * Sets the S3 client
     *
     * @param S3Client $client
     *
     * @return PostObject
     */
    public function setClient(S3Client $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Gets the S3 client
     *
     * @return S3Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Sets the bucket and makes sure it is a valid bucket name
     *
     * @param string $bucket
     *
     * @return PostObject
     */
    public function setBucket($bucket)
    {
        $this->bucket = $bucket;

        return $this;
    }

    /**
     * Gets the bucket name
     *
     * @return string
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * Gets the form attributes as an array
     *
     * @return array
     */
    public function getFormAttributes()
    {
        return $this->formAttributes;
    }

    /**
     * Gets the form inputs as an array
     *
     * @return array
     */
    public function getFormInputs()
    {
        return $this->formInputs;
    }

    /**
     * Gets the raw JSON policy
     *
     * @return string
     */
    public function getJsonPolicy()
    {
        return $this->jsonPolicy;
    }
}
