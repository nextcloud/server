<?php

/**
 * LICENSE: The MIT License (the "License")
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * https://github.com/azure/azure-storage-php/LICENSE
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * PHP version 5
 *
 * @ignore
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common\Internal
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2017 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

namespace MicrosoftAzure\Storage\Common\Internal;

use MicrosoftAzure\Storage\Common\LocationMode;
use MicrosoftAzure\Storage\Common\Models\ServiceOptions;
use MicrosoftAzure\Storage\Common\Models\ServiceProperties;
use MicrosoftAzure\Storage\Common\Models\GetServicePropertiesResult;
use MicrosoftAzure\Storage\Common\Models\GetServiceStatsResult;

/**
 * Trait implementing common REST API for all the services, including the
 * following:
 * Get/Set Service Properties
 * Get service stats
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common\Internal
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2017 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
trait ServiceRestTrait
{
    /**
     * Gets the properties of the service.
     *
     * @param ServiceOptions $options The optional parameters.
     *
     * @return \MicrosoftAzure\Storage\Common\Models\GetServicePropertiesResult
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/hh452239.aspx
     */
    public function getServiceProperties(
        ServiceOptions $options = null
    ) {
        return $this->getServicePropertiesAsync($options)->wait();
    }

    /**
     * Creates promise to get the properties of the service.
     *
     * @param ServiceOptions $options The optional parameters.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/hh452239.aspx
     */
    public function getServicePropertiesAsync(
        ServiceOptions $options = null
    ) {
        $method      = Resources::HTTP_GET;
        $headers     = array();
        $queryParams = array();
        $postParams  = array();
        $path        = Resources::EMPTY_STRING;

        if (is_null($options)) {
            $options = new ServiceOptions();
        }

        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_REST_TYPE,
            'service'
        );
        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_COMP,
            'properties'
        );

        $dataSerializer = $this->dataSerializer;

        return $this->sendAsync(
            $method,
            $headers,
            $queryParams,
            $postParams,
            $path,
            Resources::STATUS_OK,
            Resources::EMPTY_STRING,
            $options
        )->then(function ($response) use ($dataSerializer) {
            $parsed = $dataSerializer->unserialize($response->getBody());
            return GetServicePropertiesResult::create($parsed);
        }, null);
    }

    /**
     * Sets the properties of the service.
     *
     * It's recommended to use getServiceProperties, alter the returned object and
     * then use setServiceProperties with this altered object.
     *
     * @param ServiceProperties $serviceProperties The service properties.
     * @param ServiceOptions    $options           The optional parameters.
     *
     * @return void
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/hh452235.aspx
     */
    public function setServiceProperties(
        ServiceProperties $serviceProperties,
        ServiceOptions $options = null
    ) {
        $this->setServicePropertiesAsync($serviceProperties, $options)->wait();
    }

    /**
     * Creates the promise to set the properties of the service.
     *
     * It's recommended to use getServiceProperties, alter the returned object and
     * then use setServiceProperties with this altered object.
     *
     * @param ServiceProperties $serviceProperties The service properties.
     * @param ServiceOptions    $options           The optional parameters.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/hh452235.aspx
     */
    public function setServicePropertiesAsync(
        ServiceProperties $serviceProperties,
        ServiceOptions $options = null
    ) {
        Validate::isTrue(
            $serviceProperties instanceof ServiceProperties,
            Resources::INVALID_SVC_PROP_MSG
        );

        $method      = Resources::HTTP_PUT;
        $headers     = array();
        $queryParams = array();
        $postParams  = array();
        $path        = Resources::EMPTY_STRING;
        $body        = $serviceProperties->toXml($this->dataSerializer);

        if (is_null($options)) {
            $options = new ServiceOptions();
        }

        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_REST_TYPE,
            'service'
        );
        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_COMP,
            'properties'
        );
        $this->addOptionalHeader(
            $headers,
            Resources::CONTENT_TYPE,
            Resources::URL_ENCODED_CONTENT_TYPE
        );

        $options->setLocationMode(LocationMode::PRIMARY_ONLY);

        return $this->sendAsync(
            $method,
            $headers,
            $queryParams,
            $postParams,
            $path,
            Resources::STATUS_ACCEPTED,
            $body,
            $options
        );
    }

    /**
     * Retrieves statistics related to replication for the service. The operation
     * will only be sent to secondary location endpoint.
     *
     * @param  ServiceOptions|null $options The options this operation sends with.
     *
     * @return GetServiceStatsResult
     */
    public function getServiceStats(ServiceOptions $options = null)
    {
        return $this->getServiceStatsAsync($options)->wait();
    }

    /**
     * Creates promise that retrieves statistics related to replication for the
     * service. The operation will only be sent to secondary location endpoint.
     *
     * @param  ServiceOptions|null $options The options this operation sends with.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function getServiceStatsAsync(ServiceOptions $options = null)
    {
        $method      = Resources::HTTP_GET;
        $headers     = array();
        $queryParams = array();
        $postParams  = array();
        $path        = Resources::EMPTY_STRING;

        if (is_null($options)) {
            $options = new ServiceOptions();
        }

        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_REST_TYPE,
            'service'
        );
        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_COMP,
            'stats'
        );

        $dataSerializer = $this->dataSerializer;

        $options->setLocationMode(LocationMode::SECONDARY_ONLY);

        return $this->sendAsync(
            $method,
            $headers,
            $queryParams,
            $postParams,
            $path,
            Resources::STATUS_OK,
            Resources::EMPTY_STRING,
            $options
        )->then(function ($response) use ($dataSerializer) {
            $parsed = $dataSerializer->unserialize($response->getBody());
            return GetServiceStatsResult::create($parsed);
        }, null);
    }
}
