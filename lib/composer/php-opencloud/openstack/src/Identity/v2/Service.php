<?php

declare(strict_types=1);

namespace OpenStack\Identity\v2;

use GuzzleHttp\ClientInterface;
use OpenStack\Common\Auth\IdentityService;
use OpenStack\Common\Service\AbstractService;
use OpenStack\Identity\v2\Models\Catalog;
use OpenStack\Identity\v2\Models\Token;

/**
 * Represents the OpenStack Identity v2 service.
 *
 * @property \OpenStack\Identity\v2\Api $api
 */
class Service extends AbstractService implements IdentityService
{
    public static function factory(ClientInterface $client): self
    {
        return new static($client, new Api());
    }

    public function authenticate(array $options = []): array
    {
        $definition = $this->api->postToken();

        $response = $this->execute($definition, array_intersect_key($options, $definition['params']));

        $token = $this->model(Token::class, $response);

        $serviceUrl = $this->model(Catalog::class, $response)->getServiceUrl(
            $options['catalogName'],
            $options['catalogType'],
            $options['region'],
            $options['urlType']
        );

        return [$token, $serviceUrl];
    }

    /**
     * Generates a new authentication token.
     *
     * @param array $options {@see \OpenStack\Identity\v2\Api::postToken}
     *
     * @return Models\Token
     */
    public function generateToken(array $options = []): Token
    {
        $response = $this->execute($this->api->postToken(), $options);

        return $this->model(Token::class, $response);
    }
}
