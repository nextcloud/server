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

namespace Aws\Common\Client;

use Aws\Common\Credentials\Credentials;
use Aws\Common\Credentials\CredentialsInterface;
use Aws\Common\Credentials\NullCredentials;
use Aws\Common\Enum\ClientOptions as Options;
use Aws\Common\Enum\Region;
use Aws\Common\Exception\ExceptionListener;
use Aws\Common\Exception\InvalidArgumentException;
use Aws\Common\Exception\NamespaceExceptionFactory;
use Aws\Common\Exception\Parser\DefaultXmlExceptionParser;
use Aws\Common\Exception\Parser\ExceptionParserInterface;
use Aws\Common\Iterator\AwsResourceIteratorFactory;
use Aws\Common\Signature\EndpointSignatureInterface;
use Aws\Common\Signature\SignatureInterface;
use Aws\Common\Signature\SignatureV2;
use Aws\Common\Signature\SignatureV3Https;
use Aws\Common\Signature\SignatureV4;
use Guzzle\Common\Collection;
use Guzzle\Plugin\Backoff\BackoffPlugin;
use Guzzle\Plugin\Backoff\CurlBackoffStrategy;
use Guzzle\Plugin\Backoff\ExponentialBackoffStrategy;
use Guzzle\Plugin\Backoff\HttpBackoffStrategy;
use Guzzle\Plugin\Backoff\TruncatedBackoffStrategy;
use Guzzle\Service\Client;
use Guzzle\Service\Description\ServiceDescription;
use Guzzle\Service\Resource\ResourceIteratorClassFactory;
use Guzzle\Log\LogAdapterInterface;
use Guzzle\Log\ClosureLogAdapter;
use Guzzle\Plugin\Backoff\BackoffLogger;

/**
 * Builder for creating AWS service clients
 */
class ClientBuilder
{
    /**
     * @var array Default client config
     */
    protected static $commonConfigDefaults = array('scheme' => 'https');

    /**
     * @var array Default client requirements
     */
    protected static $commonConfigRequirements = array(Options::SERVICE_DESCRIPTION);

    /**
     * @var string The namespace of the client
     */
    protected $clientNamespace;

    /**
     * @var array The config options
     */
    protected $config = array();

    /**
     * @var array The config defaults
     */
    protected $configDefaults = array();

    /**
     * @var array The config requirements
     */
    protected $configRequirements = array();

    /**
     * @var ExceptionParserInterface The Parser interface for the client
     */
    protected $exceptionParser;

    /**
     * @var array Array of configuration data for iterators available for the client
     */
    protected $iteratorsConfig = array();

    /**
     * Factory method for creating the client builder
     *
     * @param string $namespace The namespace of the client
     *
     * @return ClientBuilder
     */
    public static function factory($namespace = null)
    {
        return new static($namespace);
    }

    /**
     * Constructs a client builder
     *
     * @param string $namespace The namespace of the client
     */
    public function __construct($namespace = null)
    {
        $this->clientNamespace = $namespace;
    }

    /**
     * Sets the config options
     *
     * @param array|Collection $config The config options
     *
     * @return ClientBuilder
     */
    public function setConfig($config)
    {
        $this->config = $this->processArray($config);

        return $this;
    }

    /**
     * Sets the config options' defaults
     *
     * @param array|Collection $defaults The default values
     *
     * @return ClientBuilder
     */
    public function setConfigDefaults($defaults)
    {
        $this->configDefaults = $this->processArray($defaults);

        return $this;
    }

    /**
     * Sets the required config options
     *
     * @param array|Collection $required The required config options
     *
     * @return ClientBuilder
     */
    public function setConfigRequirements($required)
    {
        $this->configRequirements = $this->processArray($required);

        return $this;
    }

    /**
     * Sets the exception parser. If one is not provided the builder will use
     * the default XML exception parser.
     *
     * @param ExceptionParserInterface $parser The exception parser
     *
     * @return ClientBuilder
     */
    public function setExceptionParser(ExceptionParserInterface $parser)
    {
        $this->exceptionParser = $parser;

        return $this;
    }

    /**
     * Set the configuration for the client's iterators
     *
     * @param array $config Configuration data for client's iterators
     *
     * @return ClientBuilder
     */
    public function setIteratorsConfig(array $config)
    {
        $this->iteratorsConfig = $config;

        return $this;
    }

    /**
     * Performs the building logic using all of the parameters that have been
     * set and falling back to default values. Returns an instantiate service
     * client with credentials prepared and plugins attached.
     *
     * @return AwsClientInterface
     * @throws InvalidArgumentException
     */
    public function build()
    {
        // Resolve configuration
        $config = Collection::fromConfig(
            $this->config,
            array_merge(self::$commonConfigDefaults, $this->configDefaults),
            (self::$commonConfigRequirements + $this->configRequirements)
        );

        // Resolve the endpoint, signature, and credentials
        $description = $this->updateConfigFromDescription($config);
        $signature = $this->getSignature($description, $config);
        $credentials = $this->getCredentials($config);

        // Resolve exception parser
        if (!$this->exceptionParser) {
            $this->exceptionParser = new DefaultXmlExceptionParser();
        }

        // Resolve backoff strategy
        $backoff = $config->get(Options::BACKOFF);
        if ($backoff === null) {
            $backoff = new BackoffPlugin(
                // Retry failed requests up to 3 times if it is determined that the request can be retried
                new TruncatedBackoffStrategy(3,
                    // Retry failed requests with 400-level responses due to throttling
                    new ThrottlingErrorChecker($this->exceptionParser,
                        // Retry failed requests due to transient network or cURL problems
                        new CurlBackoffStrategy(null,
                            // Retry failed requests with 500-level responses
                            new HttpBackoffStrategy(array(500, 503, 509),
                                // Retry requests that failed due to expired credentials
                                new ExpiredCredentialsChecker($this->exceptionParser,
                                    new ExponentialBackoffStrategy()
                                )
                            )
                        )
                    )
                )
            );
            $config->set(Options::BACKOFF, $backoff);
        }

        if ($backoff) {
            $this->addBackoffLogger($backoff, $config);
        }

        // Determine service and class name
        $clientClass = 'Aws\Common\Client\DefaultClient';
        if ($this->clientNamespace) {
            $serviceName = substr($this->clientNamespace, strrpos($this->clientNamespace, '\\') + 1);
            $clientClass = $this->clientNamespace . '\\' . $serviceName . 'Client';
        }

        /** @var $client AwsClientInterface */
        $client = new $clientClass($credentials, $signature, $config);
        $client->setDescription($description);

        // Add exception marshaling so that more descriptive exception are thrown
        if ($this->clientNamespace) {
            $exceptionFactory = new NamespaceExceptionFactory(
                $this->exceptionParser,
                "{$this->clientNamespace}\\Exception",
                "{$this->clientNamespace}\\Exception\\{$serviceName}Exception"
            );
            $client->addSubscriber(new ExceptionListener($exceptionFactory));
        }

        // Add the UserAgentPlugin to append to the User-Agent header of requests
        $client->addSubscriber(new UserAgentListener());

        // Filters used for the cache plugin
        $client->getConfig()->set(
            'params.cache.key_filter',
            'header=date,x-amz-date,x-amz-security-token,x-amzn-authorization'
        );

        // Set the iterator resource factory based on the provided iterators config
        $client->setResourceIteratorFactory(new AwsResourceIteratorFactory(
            $this->iteratorsConfig,
            new ResourceIteratorClassFactory($this->clientNamespace . '\\Iterator')
        ));

        // Disable parameter validation if needed
        if ($config->get(Options::VALIDATION) === false) {
            $params = $config->get('command.params') ?: array();
            $params['command.disable_validation'] = true;
            $config->set('command.params', $params);
        }

        return $client;
    }

    /**
     * Add backoff logging to the backoff plugin if needed
     *
     * @param BackoffPlugin $plugin Backoff plugin
     * @param Collection    $config Configuration settings
     *
     * @throws InvalidArgumentException
     */
    protected function addBackoffLogger(BackoffPlugin $plugin, Collection $config)
    {
        // The log option can be set to `debug` or an instance of a LogAdapterInterface
        if ($logger = $config->get(Options::BACKOFF_LOGGER)) {
            $format = $config->get(Options::BACKOFF_LOGGER_TEMPLATE);
            if ($logger === 'debug') {
                $logger = new ClosureLogAdapter(function ($message) {
                    trigger_error($message . "\n");
                });
            } elseif (!($logger instanceof LogAdapterInterface)) {
                throw new InvalidArgumentException(
                    Options::BACKOFF_LOGGER . ' must be set to `debug` or an instance of '
                        . 'Guzzle\\Common\\Log\\LogAdapterInterface'
                );
            }
            // Create the plugin responsible for logging exponential backoff retries
            $logPlugin = new BackoffLogger($logger);
            // You can specify a custom format or use the default
            if ($format) {
                $logPlugin->setTemplate($format);
            }
            $plugin->addSubscriber($logPlugin);
        }
    }

    /**
     * Ensures that an array (e.g. for config data) is actually in array form
     *
     * @param array|Collection $array The array data
     *
     * @return array
     * @throws InvalidArgumentException if the arg is not an array or Collection
     */
    protected function processArray($array)
    {
        if ($array instanceof Collection) {
            $array = $array->getAll();
        }

        if (!is_array($array)) {
            throw new InvalidArgumentException('The config must be provided as an array or Collection.');
        }

        return $array;
    }

    /**
     * Update a configuration object from a service description
     *
     * @param Collection $config Config to update
     *
     * @return ServiceDescription
     * @throws InvalidArgumentException
     */
    protected function updateConfigFromDescription(Collection $config)
    {
        $description = $config->get(Options::SERVICE_DESCRIPTION);
        if (!($description instanceof ServiceDescription)) {
            // Inject the version into the sprintf template if it is a string
            if (is_string($description)) {
                $description = sprintf($description, $config->get(Options::VERSION));
            }
            $description = ServiceDescription::factory($description);
            $config->set(Options::SERVICE_DESCRIPTION, $description);
        }

        if (!$config->get(Options::SERVICE)) {
            $config->set(Options::SERVICE, $description->getData('endpointPrefix'));
        }

        if ($iterators = $description->getData('iterators')) {
            $this->setIteratorsConfig($iterators);
        }

        // Ensure that the service description has regions
        if (!$description->getData('regions')) {
            throw new InvalidArgumentException(
                'No regions found in the ' . $description->getData('serviceFullName'). ' description'
            );
        }

        // Make sure a valid region is set
        $region = $config->get(Options::REGION);
        $global = $description->getData('globalEndpoint');
        if (!$global && !$region) {
            throw new InvalidArgumentException(
                'A region is required when using ' . $description->getData('serviceFullName')
                . '. Set "region" to one of: ' . implode(', ', array_keys($description->getData('regions')))
            );
        } elseif ($global && (!$region || $description->getData('namespace') !== 'S3')) {
            $region = Region::US_EAST_1;
            $config->set(Options::REGION, $region);
        }

        if (!$config->get(Options::BASE_URL)) {
            // Set the base URL using the scheme and hostname of the service's region
            $config->set(Options::BASE_URL, AbstractClient::getEndpoint(
                $description,
                $region,
                $config->get(Options::SCHEME)
            ));
        }

        return $description;
    }

    /**
     * Return an appropriate signature object for a a client based on the
     * "signature" configuration setting, or the default signature specified in
     * a service description. The signature can be set to a valid signature
     * version identifier string or an instance of Aws\Common\Signature\SignatureInterface.
     *
     * @param ServiceDescription $description Description that holds a signature option
     * @param Collection         $config      Configuration options
     *
     * @return SignatureInterface
     * @throws InvalidArgumentException
     */
    protected function getSignature(ServiceDescription $description, Collection $config)
    {
        // If a custom signature has not been provided, then use the default
        // signature setting specified in the service description.
        $signature = $config->get(Options::SIGNATURE) ?: $description->getData('signatureVersion');

        if (is_string($signature)) {
            if ($signature == 'v4') {
                $signature = new SignatureV4();
            } elseif ($signature == 'v2') {
                $signature = new SignatureV2();
            } elseif ($signature == 'v3https') {
                $signature = new SignatureV3Https();
            } else {
                throw new InvalidArgumentException("Invalid signature type: {$signature}");
            }
        } elseif (!($signature instanceof SignatureInterface)) {
            throw new InvalidArgumentException('The provided signature is not '
                . 'a signature version string or an instance of '
                . 'Aws\\Common\\Signature\\SignatureInterface');
        }

        // Allow a custom service name or region value to be provided
        if ($signature instanceof EndpointSignatureInterface) {

            // Determine the service name to use when signing
            $signature->setServiceName($config->get(Options::SIGNATURE_SERVICE)
                ?: $description->getData('signingName')
                ?: $description->getData('endpointPrefix'));

            // Determine the region to use when signing requests
            $signature->setRegionName($config->get(Options::SIGNATURE_REGION) ?: $config->get(Options::REGION));
        }

        return $signature;
    }

    protected function getCredentials(Collection $config)
    {
        $credentials = $config->get(Options::CREDENTIALS);
        if ($credentials === false) {
            $credentials = new NullCredentials();
        } elseif (!$credentials instanceof CredentialsInterface) {
            $credentials = Credentials::factory($config);
        }

        return $credentials;
    }
}
