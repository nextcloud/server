<?php

declare(strict_types=1);

namespace OpenStack\Identity\v3;

use GuzzleHttp\ClientInterface;
use OpenStack\Common\Auth\IdentityService;
use OpenStack\Common\Error\BadResponseError;
use OpenStack\Common\Service\AbstractService;

/**
 * Represents the Keystone v3 service.
 *
 * @property \OpenStack\Identity\v3\Api $api
 */
class Service extends AbstractService implements IdentityService
{
    public static function factory(ClientInterface $client): self
    {
        return new static($client, new Api());
    }

    /**
     * Authenticates credentials, giving back a token and a base URL for the service.
     *
     * @param array $options {@see \OpenStack\Identity\v3\Api::postTokens}
     *
     * @return array Returns a {@see Models\Token} as the first element, a string base URL as the second
     */
    public function authenticate(array $options): array
    {
        $authOptions = array_intersect_key($options, $this->api->postTokens()['params']);

        if (!empty($options['cachedToken'])) {
            $token = $this->generateTokenFromCache($options['cachedToken']);

            if ($token->hasExpired()) {
                throw new \RuntimeException(sprintf('Cached token has expired on "%s".', $token->expires->format(\DateTime::ISO8601)));
            }
        } else {
            $token = $this->generateToken($authOptions);
        }

        $name      = $options['catalogName'];
        $type      = $options['catalogType'];
        $region    = $options['region'];
        $interface = isset($options['interface']) ? $options['interface'] : Enum::INTERFACE_PUBLIC;

        if ($baseUrl = $token->catalog->getServiceUrl($name, $type, $region, $interface)) {
            return [$token, $baseUrl];
        }

        throw new \RuntimeException(sprintf('No service found with type [%s] name [%s] region [%s] interface [%s]', $type, $name, $region, $interface));
    }

    /**
     * Generates authentication token from cached token using `$token->export()`.
     *
     * @param array $cachedToken {@see \OpenStack\Identity\v3\Models\Token::export}
     */
    public function generateTokenFromCache(array $cachedToken): Models\Token
    {
        return $this->model(Models\Token::class)->populateFromArray($cachedToken);
    }

    /**
     * Generates a new authentication token.
     *
     * @param array $options {@see \OpenStack\Identity\v3\Api::postTokens}
     */
    public function generateToken(array $options): Models\Token
    {
        return $this->model(Models\Token::class)->create($options);
    }

    /**
     * Retrieves a token object and populates its unique identifier object. This operation will not perform a GET or
     * HEAD request by default; you will need to call retrieve() if you want to pull in remote state from the API.
     *
     * @param string $id The unique ID of the token to retrieve
     */
    public function getToken(string $id): Models\Token
    {
        return $this->model(Models\Token::class, ['id' => $id]);
    }

    /**
     * Validates a token, identified by its ID, and returns TRUE if its valid, FALSE if not.
     *
     * @param string $id The unique ID of the token
     */
    public function validateToken(string $id): bool
    {
        try {
            $this->execute($this->api->headTokens(), ['tokenId' => $id]);

            return true;
        } catch (BadResponseError $e) {
            return false;
        }
    }

    /**
     * Revokes a token, identified by its ID. After this operation completes, users will not be able to use this token
     * again for authentication.
     *
     * @param string $id The unique ID of the token
     */
    public function revokeToken(string $id)
    {
        $this->execute($this->api->deleteTokens(), ['tokenId' => $id]);
    }

    /**
     * Creates a new service according to the provided options.
     *
     * @param array $options {@see \OpenStack\Identity\v3\Api::postServices}
     */
    public function createService(array $options): Models\Service
    {
        return $this->model(Models\Service::class)->create($options);
    }

    /**
     * Returns a generator which will yield a collection of service objects. The elements which generators yield can be
     * accessed using a foreach loop. Often the API will not return the full state of the resource in collections; you
     * will need to use retrieve() to pull in the full state of the remote resource from the API.
     *
     * @param array $options {@see \OpenStack\Identity\v3\Api::getServices}
     */
    public function listServices(array $options = []): \Generator
    {
        return $this->model(Models\Service::class)->enumerate($this->api->getServices(), $options);
    }

    /**
     * Retrieves a service object and populates its unique identifier object. This operation will not perform a GET or
     * HEAD request by default; you will need to call retrieve() if you want to pull in remote state from the API.
     *
     * @param string $id The unique ID of the service
     */
    public function getService(string $id): Models\Service
    {
        return $this->model(Models\Service::class, ['id' => $id]);
    }

    /**
     * Creates a new endpoint according to the provided options.
     *
     * @param array $options {@see \OpenStack\Identity\v3\Api::postEndpoints}
     */
    public function createEndpoint(array $options): Models\Endpoint
    {
        return $this->model(Models\Endpoint::class)->create($options);
    }

    /**
     * Retrieves an endpoint object and populates its unique identifier object. This operation will not perform a GET or
     * HEAD request by default; you will need to call retrieve() if you want to pull in remote state from the API.
     *
     * @param string $id The unique ID of the service
     */
    public function getEndpoint(string $id): Models\Endpoint
    {
        return $this->model(Models\Endpoint::class, ['id' => $id]);
    }

    /**
     * Returns a generator which will yield a collection of endpoint objects. The elements which generators yield can be
     * accessed using a foreach loop. Often the API will not return the full state of the resource in collections; you
     * will need to use retrieve() to pull in the full state of the remote resource from the API.
     *
     * @param array $options {@see \OpenStack\Identity\v3\Api::getEndpoints}
     */
    public function listEndpoints(array $options = []): \Generator
    {
        return $this->model(Models\Endpoint::class)->enumerate($this->api->getEndpoints(), $options);
    }

    /**
     * Creates a new domain according to the provided options.
     *
     * @param array $options {@see \OpenStack\Identity\v3\Api::postDomains}
     */
    public function createDomain(array $options): Models\Domain
    {
        return $this->model(Models\Domain::class)->create($options);
    }

    /**
     * Returns a generator which will yield a collection of domain objects. The elements which generators yield can be
     * accessed using a foreach loop. Often the API will not return the full state of the resource in collections; you
     * will need to use retrieve() to pull in the full state of the remote resource from the API.
     *
     * @param array $options {@see \OpenStack\Identity\v3\Api::getDomains}
     */
    public function listDomains(array $options = []): \Generator
    {
        return $this->model(Models\Domain::class)->enumerate($this->api->getDomains(), $options);
    }

    /**
     * Retrieves a domain object and populates its unique identifier object. This operation will not perform a GET or
     * HEAD request by default; you will need to call retrieve() if you want to pull in remote state from the API.
     *
     * @param string $id The unique ID of the domain
     */
    public function getDomain(string $id): Models\Domain
    {
        return $this->model(Models\Domain::class, ['id' => $id]);
    }

    /**
     * Creates a new project according to the provided options.
     *
     * @param array $options {@see \OpenStack\Identity\v3\Api::postProjects}
     */
    public function createProject(array $options): Models\Project
    {
        return $this->model(Models\Project::class)->create($options);
    }

    /**
     * Returns a generator which will yield a collection of project objects. The elements which generators yield can be
     * accessed using a foreach loop. Often the API will not return the full state of the resource in collections; you
     * will need to use retrieve() to pull in the full state of the remote resource from the API.
     *
     * @param array $options {@see \OpenStack\Identity\v3\Api::getProjects}
     */
    public function listProjects(array $options = []): \Generator
    {
        return $this->model(Models\Project::class)->enumerate($this->api->getProjects(), $options);
    }

    /**
     * Retrieves a project object and populates its unique identifier object. This operation will not perform a GET or
     * HEAD request by default; you will need to call retrieve() if you want to pull in remote state from the API.
     *
     * @param string $id The unique ID of the project
     */
    public function getProject(string $id): Models\Project
    {
        return $this->model(Models\Project::class, ['id' => $id]);
    }

    /**
     * Creates a new user according to the provided options.
     *
     * @param array $options {@see \OpenStack\Identity\v3\Api::postUsers}
     */
    public function createUser(array $options): Models\User
    {
        return $this->model(Models\User::class)->create($options);
    }

    /**
     * Returns a generator which will yield a collection of user objects. The elements which generators yield can be
     * accessed using a foreach loop. Often the API will not return the full state of the resource in collections; you
     * will need to use retrieve() to pull in the full state of the remote resource from the API.
     *
     * @param array $options {@see \OpenStack\Identity\v3\Api::getUsers}
     */
    public function listUsers(array $options = []): \Generator
    {
        return $this->model(Models\User::class)->enumerate($this->api->getUsers(), $options);
    }

    /**
     * Retrieves a user object and populates its unique identifier object. This operation will not perform a GET or
     * HEAD request by default; you will need to call retrieve() if you want to pull in remote state from the API.
     *
     * @param string $id The unique ID of the user
     */
    public function getUser(string $id): Models\User
    {
        return $this->model(Models\User::class, ['id' => $id]);
    }

    /**
     * Creates a new group according to the provided options.
     *
     * @param array $options {@see \OpenStack\Identity\v3\Api::postGroups}
     */
    public function createGroup(array $options): Models\Group
    {
        return $this->model(Models\Group::class)->create($options);
    }

    /**
     * Returns a generator which will yield a collection of group objects. The elements which generators yield can be
     * accessed using a foreach loop. Often the API will not return the full state of the resource in collections; you
     * will need to use retrieve() to pull in the full state of the remote resource from the API.
     *
     * @param array $options {@see \OpenStack\Identity\v3\Api::getGroups}
     */
    public function listGroups(array $options = []): \Generator
    {
        return $this->model(Models\Group::class)->enumerate($this->api->getGroups(), $options);
    }

    /**
     * Retrieves a group object and populates its unique identifier object. This operation will not perform a GET or
     * HEAD request by default; you will need to call retrieve() if you want to pull in remote state from the API.
     *
     * @param string $id The unique ID of the group
     */
    public function getGroup($id): Models\Group
    {
        return $this->model(Models\Group::class, ['id' => $id]);
    }

    /**
     * Creates a new credential according to the provided options.
     *
     * @param array $options {@see \OpenStack\Identity\v3\Api::postCredentials}
     */
    public function createCredential(array $options): Models\Credential
    {
        return $this->model(Models\Credential::class)->create($options);
    }

    /**
     * Returns a generator which will yield a collection of credential objects. The elements which generators yield can
     * be accessed using a foreach loop. Often the API will not return the full state of the resource in collections;
     * you will need to use retrieve() to pull in the full state of the remote resource from the API.
     */
    public function listCredentials(): \Generator
    {
        return $this->model(Models\Credential::class)->enumerate($this->api->getCredentials());
    }

    /**
     * Retrieves a credential object and populates its unique identifier object. This operation will not perform a GET
     * or HEAD request by default; you will need to call retrieve() if you want to pull in remote state from the API.
     *
     * @param string $id The unique ID of the credential
     */
    public function getCredential(string $id): Models\Credential
    {
        return $this->model(Models\Credential::class, ['id' => $id]);
    }

    /**
     * Creates a new role according to the provided options.
     *
     * @param array $options {@see \OpenStack\Identity\v3\Api::postRoles}
     */
    public function createRole(array $options): Models\Role
    {
        return $this->model(Models\Role::class)->create($options);
    }

    /**
     * Returns a generator which will yield a collection of role objects. The elements which generators yield can be
     * accessed using a foreach loop. Often the API will not return the full state of the resource in collections; you
     * will need to use retrieve() to pull in the full state of the remote resource from the API.
     *
     * @param array $options {@see \OpenStack\Identity\v3\Api::getRoles}
     */
    public function listRoles(array $options = []): \Generator
    {
        return $this->model(Models\Role::class)->enumerate($this->api->getRoles(), $options);
    }

    /**
     * Returns a generator which will yield a collection of role assignment objects. The elements which generators
     * yield can be accessed using a foreach loop. Often the API will not return the full state of the resource in
     * collections; you will need to use retrieve() to pull in the full state of the remote resource from the API.
     *
     * @param array $options {@see \OpenStack\Identity\v3\Api::getRoleAssignments}
     */
    public function listRoleAssignments(array $options = []): \Generator
    {
        return $this->model(Models\Assignment::class)->enumerate($this->api->getRoleAssignments(), $options);
    }

    /**
     * Creates a new policy according to the provided options.
     *
     * @param array $options {@see \OpenStack\Identity\v3\Api::postPolicies}
     */
    public function createPolicy(array $options): Models\Policy
    {
        return $this->model(Models\Policy::class)->create($options);
    }

    /**
     * Returns a generator which will yield a collection of policy objects. The elements which generators yield can be
     * accessed using a foreach loop. Often the API will not return the full state of the resource in collections; you
     * will need to use retrieve() to pull in the full state of the remote resource from the API.
     *
     * @param array $options {@see \OpenStack\Identity\v3\Api::getPolicies}
     */
    public function listPolicies(array $options = []): \Generator
    {
        return $this->model(Models\Policy::class)->enumerate($this->api->getPolicies(), $options);
    }

    /**
     * Retrieves a policy object and populates its unique identifier object. This operation will not perform a GET or
     * HEAD request by default; you will need to call retrieve() if you want to pull in remote state from the API.
     *
     * @param string $id The unique ID of the policy
     */
    public function getPolicy(string $id): Models\Policy
    {
        return $this->model(Models\Policy::class, ['id' => $id]);
    }
}
