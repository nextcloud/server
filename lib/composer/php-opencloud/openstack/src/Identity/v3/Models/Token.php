<?php

declare(strict_types=1);

namespace OpenStack\Identity\v3\Models;

use OpenStack\Common\Resource\Alias;
use OpenStack\Common\Resource\Creatable;
use OpenStack\Common\Resource\OperatorResource;
use OpenStack\Common\Resource\Retrievable;
use OpenStack\Common\Transport\Utils;
use Psr\Http\Message\ResponseInterface;

/**
 * @property \OpenStack\Identity\v3\Api $api
 */
class Token extends OperatorResource implements Creatable, Retrievable, \OpenStack\Common\Auth\Token
{
    /** @var array */
    public $methods;

    /** @var Role[] */
    public $roles;

    /** @var \DateTimeImmutable */
    public $expires;

    /** @var Project */
    public $project;

    /** @var Catalog */
    public $catalog;

    /** @var mixed */
    public $extras;

    /** @var User */
    public $user;

    /** @var \DateTimeImmutable */
    public $issued;

    /** @var string */
    public $id;

    protected $resourceKey  = 'token';
    protected $resourcesKey = 'tokens';

    protected $cachedToken;

    /**
     * {@inheritdoc}
     */
    protected function getAliases(): array
    {
        return parent::getAliases() + [
            'roles'      => new Alias('roles', Role::class, true),
            'expires_at' => new Alias('expires', \DateTimeImmutable::class),
            'project'    => new Alias('project', Project::class),
            'catalog'    => new Alias('catalog', Catalog::class),
            'user'       => new Alias('user', User::class),
            'issued_at'  => new Alias('issued', \DateTimeImmutable::class),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function populateFromResponse(ResponseInterface $response)
    {
        parent::populateFromResponse($response);
        $this->id = $response->getHeaderLine('X-Subject-Token');

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return bool TRUE if the token has expired (and is invalid); FALSE otherwise
     */
    public function hasExpired(): bool
    {
        return $this->expires <= new \DateTimeImmutable('now', $this->expires->getTimezone());
    }

    /**
     * {@inheritdoc}
     */
    public function retrieve()
    {
        $response = $this->execute($this->api->getTokens(), ['tokenId' => $this->id]);
        $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     *
     * @param array $data {@see \OpenStack\Identity\v3\Api::postTokens}
     */
    public function create(array $data): Creatable
    {
        if (isset($data['user'])) {
            $data['methods'] = ['password'];
            if (!isset($data['user']['id']) && empty($data['user']['domain'])) {
                throw new \InvalidArgumentException('When authenticating with a username, you must also provide either the domain name or domain ID to '.'which the user belongs to. Alternatively, if you provide a user ID instead, you do not need to '.'provide domain information.');
            }
        } elseif (isset($data['tokenId'])) {
            $data['methods'] = ['token'];
        } else {
            throw new \InvalidArgumentException('Either a user or token must be provided.');
        }

        $response = $this->execute($this->api->postTokens(), $data);
        $token    = $this->populateFromResponse($response);

        // Cache response as an array to export if needed.
        // Added key `id` which is auth token from HTTP header X-Subject-Token
        $this->cachedToken       = Utils::flattenJson(Utils::jsonDecode($response), $this->resourceKey);
        $this->cachedToken['id'] = $token->id;

        return $token;
    }

    /**
     * Returns a serialized representation of an authentication token.
     *
     * Initialize OpenStack object using $params['cachedToken'] to reduce the amount of HTTP calls.
     *
     * This array is a modified version of response from `/auth/tokens`. Do not manually modify this array.
     */
    public function export(): array
    {
        return $this->cachedToken;
    }
}
