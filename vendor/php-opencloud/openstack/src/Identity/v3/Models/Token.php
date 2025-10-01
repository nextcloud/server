<?php

declare(strict_types=1);

namespace OpenStack\Identity\v3\Models;

use InvalidArgumentException;
use OpenStack\Common\Error\BadResponseError;
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

    public function retrieve()
    {
        $response = $this->execute($this->api->getTokens(), ['tokenId' => $this->id]);
        $this->populateFromResponse($response);
    }

    /**
     * @param array $userOptions {@see \OpenStack\Identity\v3\Api::postTokens}
     */
    public function create(array $userOptions): Creatable
    {
        if (isset($userOptions['user'])) {
            $userOptions['methods'] = ['password'];
            if (!isset($userOptions['user']['id']) && empty($userOptions['user']['domain'])) {
                throw new InvalidArgumentException('When authenticating with a username, you must also provide either the domain name '.'or domain ID to which the user belongs to. Alternatively, if you provide a user ID instead, '.'you do not need to provide domain information.');
            }
        } elseif (isset($userOptions['application_credential'])) {
            $userOptions['methods'] = ['application_credential'];
            if (!isset($userOptions['application_credential']['id']) || !isset($userOptions['application_credential']['secret'])) {
                throw new InvalidArgumentException('When authenticating with a application_credential, you must provide application credential ID '.' and application credential secret.');
            }
        } elseif (isset($userOptions['tokenId'])) {
            $userOptions['methods'] = ['token'];
        } else {
            throw new InvalidArgumentException('Either a user, tokenId or application_credential must be provided.');
        }

        $response = $this->execute($this->api->postTokens(), $userOptions);
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

    /**
     * Checks if the token is valid.
     *
     * @return bool TRUE if the token is valid; FALSE otherwise
     */
    public function validate(): bool
    {
        try {
            $this->execute($this->api->headTokens(), ['tokenId' => $this->id]);

            return true;
        } catch (BadResponseError $e) {
            return false;
        }
    }
}
