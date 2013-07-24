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

use Aws\Common\Aws;
use Aws\Common\Credentials\Credentials;
use Aws\Common\Credentials\CredentialsInterface;
use Aws\Common\Enum\ClientOptions as Options;
use Aws\Common\Exception\InvalidArgumentException;
use Aws\Common\Signature\EndpointSignatureInterface;
use Aws\Common\Signature\SignatureInterface;
use Aws\Common\Signature\SignatureListener;
use Aws\Common\Waiter\WaiterClassFactory;
use Aws\Common\Waiter\CompositeWaiterFactory;
use Aws\Common\Waiter\WaiterFactoryInterface;
use Aws\Common\Waiter\WaiterConfigFactory;
use Guzzle\Common\Collection;
use Guzzle\Service\Client;
use Guzzle\Service\Description\ServiceDescriptionInterface;

/**
 * Abstract AWS client
 */
abstract class AbstractClient extends Client implements AwsClientInterface
{
    /**
     * @var CredentialsInterface AWS credentials
     */
    protected $credentials;

    /**
     * @var SignatureInterface Signature implementation of the service
     */
    protected $signature;

    /**
     * @var WaiterFactoryInterface Factory used to create waiter classes
     */
    protected $waiterFactory;

    /**
     * {@inheritdoc}
     */
    public static function getAllEvents()
    {
        return array_merge(Client::getAllEvents(), array(
            'client.region_changed',
            'client.credentials_changed',
        ));
    }

    /**
     * @param CredentialsInterface $credentials AWS credentials
     * @param SignatureInterface   $signature   Signature implementation
     * @param Collection           $config      Configuration options
     *
     * @throws InvalidArgumentException if an endpoint provider isn't provided
     */
    public function __construct(CredentialsInterface $credentials, SignatureInterface $signature, Collection $config)
    {
        // Bootstrap with Guzzle
        parent::__construct($config->get(Options::BASE_URL), $config);
        $this->credentials = $credentials;
        $this->signature = $signature;

        // Make sure the user agent is prefixed by the SDK version
        $this->setUserAgent('aws-sdk-php2/' . Aws::VERSION, true);

        // Add the event listener so that requests are signed before they are sent
        $dispatcher = $this->getEventDispatcher();
        $dispatcher->addSubscriber(new SignatureListener($credentials, $signature));

        if ($backoff = $config->get(Options::BACKOFF)) {
            $dispatcher->addSubscriber($backoff, -255);
        }
    }

    public function __call($method, $args)
    {
        if (substr($method, 0, 3) === 'get' && substr($method, -8) === 'Iterator') {
            // Allow magic method calls for iterators (e.g. $client->get<CommandName>Iterator($params))
            $commandOptions = isset($args[0]) ? $args[0] : null;
            $iteratorOptions = isset($args[1]) ? $args[1] : array();
            return $this->getIterator(substr($method, 3, -8), $commandOptions, $iteratorOptions);
        } elseif (substr($method, 0, 9) == 'waitUntil') {
            // Allow magic method calls for waiters (e.g. $client->waitUntil<WaiterName>($params))
            return $this->waitUntil(substr($method, 9), isset($args[0]) ? $args[0]: array());
        } else {
            return parent::__call(ucfirst($method), $args);
        }
    }

    /**
     * Get an endpoint for a specific region from a service description
     *
     * @param ServiceDescriptionInterface $description Service description
     * @param string                      $region      Region of the endpoint
     * @param string                      $scheme      URL scheme
     *
     * @return string
     * @throws InvalidArgumentException
     */
    public static function getEndpoint(ServiceDescriptionInterface $description, $region, $scheme)
    {
        $service = $description->getData('serviceFullName');
        // Lookup the region in the service description
        if (!($regions = $description->getData('regions'))) {
            throw new InvalidArgumentException("No regions found in the {$service} description");
        }
        // Ensure that the region exists for the service
        if (!isset($regions[$region])) {
            throw new InvalidArgumentException("{$region} is not a valid region for {$service}");
        }
        // Ensure that the scheme is valid
        if ($regions[$region][$scheme] == false) {
            throw new InvalidArgumentException("{$scheme} is not a valid URI scheme for {$service} in {$region}");
        }

        return $scheme . '://' . $regions[$region]['hostname'];
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials()
    {
        return $this->credentials;
    }

    /**
     * {@inheritdoc}
     */
    public function setCredentials(CredentialsInterface $credentials)
    {
        $formerCredentials = $this->credentials;
        $this->credentials = $credentials;

        // Dispatch an event that the credentials have been changed
        $this->dispatch('client.credentials_changed', array(
            'credentials'        => $credentials,
            'former_credentials' => $formerCredentials,
        ));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * {@inheritdoc}
     */
    public function getRegions()
    {
        return $this->serviceDescription->getData('regions');
    }

    /**
     * {@inheritdoc}
     */
    public function getRegion()
    {
        return $this->getConfig(Options::REGION);
    }

    /**
     * {@inheritdoc}
     */
    public function setRegion($region)
    {
        $config = $this->getConfig();
        $formerRegion = $config->get(Options::REGION);
        $global = $this->serviceDescription->getData('globalEndpoint');

        // Only change the region if the service does not have a global endpoint
        if (!$global || $this->serviceDescription->getData('namespace') === 'S3') {
            $baseUrl = self::getEndpoint($this->serviceDescription, $region, $config->get(Options::SCHEME));
            $this->setBaseUrl($baseUrl);
            $config->set(Options::BASE_URL, $baseUrl)->set(Options::REGION, $region);

            // Update the signature if necessary
            $signature = $this->getSignature();
            if ($signature instanceof EndpointSignatureInterface) {
                /** @var $signature EndpointSignatureInterface */
                $signature->setRegionName($region);
            }

            // Dispatch an event that the region has been changed
            $this->dispatch('client.region_changed', array(
                'region'        => $region,
                'former_region' => $formerRegion,
            ));
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function waitUntil($waiter, array $input = array())
    {
        $this->getWaiter($waiter, $input)->wait();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getWaiter($waiter, array $input = array())
    {
        return $this->getWaiterFactory()->build($waiter)
            ->setClient($this)
            ->setConfig($input);
    }

    /**
     * {@inheritdoc}
     */
    public function setWaiterFactory(WaiterFactoryInterface $waiterFactory)
    {
        $this->waiterFactory = $waiterFactory;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getWaiterFactory()
    {
        if (!$this->waiterFactory) {
            $clientClass = get_class($this);
            // Use a composite factory that checks for classes first, then config waiters
            $this->waiterFactory = new CompositeWaiterFactory(array(
                new WaiterClassFactory(substr($clientClass, 0, strrpos($clientClass, '\\')) . '\\Waiter')
            ));
            if ($this->getDescription()) {
                $this->waiterFactory->addFactory(new WaiterConfigFactory($this->getDescription()->getData('waiters')));
            }
        }

        return $this->waiterFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getApiVersion()
    {
        return $this->serviceDescription->getApiVersion();
    }
}
