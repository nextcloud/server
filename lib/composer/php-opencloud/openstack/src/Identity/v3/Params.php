<?php

declare(strict_types=1);

namespace OpenStack\Identity\v3;

use OpenStack\Common\Api\AbstractParams;

class Params extends AbstractParams
{
    public function methods(): array
    {
        return [
            'type'        => self::ARRAY_TYPE,
            'path'        => 'auth.identity',
            'items'       => ['type' => self::STRING_TYPE],
            'description' => <<<EOT
An array of authentication methods (in string form) that the SDK will use to authenticate. The only acceptable methods
are "password" or "token".
EOT
        ];
    }

    public function user(): array
    {
        return [
            'type'       => self::OBJECT_TYPE,
            'path'       => 'auth.identity.password',
            'properties' => [
                'id' => [
                    'type'        => self::STRING_TYPE,
                    'description' => $this->id('user'),
                ],
                'name' => [
                    'type'        => self::STRING_TYPE,
                    'description' => 'The username of the user',
                ],
                'password' => [
                    'type'        => self::STRING_TYPE,
                    'description' => 'The password of the user',
                ],
                'domain' => $this->domain(),
            ],
        ];
    }

    public function tokenBody(): array
    {
        return [
            'path'        => 'auth.identity.token',
            'sentAs'      => 'id',
            'type'        => self::STRING_TYPE,
            'description' => $this->id('token'),
        ];
    }

    public function scope(): array
    {
        return [
            'type'       => self::OBJECT_TYPE,
            'path'       => 'auth',
            'properties' => [
                'project' => $this->project(),
                'domain'  => $this->domain(),
            ],
        ];
    }

    public function typeQuery(): array
    {
        return [
            'type'        => 'string',
            'location'    => 'query',
            'description' => 'Filters all the available services according to a given type',
        ];
    }

    public function interf(): array
    {
        return [
            'description' => <<<EOT
Denotes the type of visibility the endpoint will have. Acceptable values are "admin", "public" or "internal". Admin
endpoints are only accessible to users who have authenticated with an admin role. Public endpoints are available to
all users and use a public IP. Internal endpoints are available to all users, but only via an internal, private IP.
EOT
        ];
    }

    public function region(): array
    {
        return [
            'description' => <<<EOT
Denotes the geographic location that the endpoint will serve traffic from. This provides greater redundancy and also
offers better latency to your regions, but will require the system administrator to set up.
EOT
        ];
    }

    public function endpointUrl(): array
    {
        return [
            'description' => <<<EOT
The HTTP or HTTPS URL that clients will communicate with when accessing your service endpoint.
EOT
        ];
    }

    public function serviceId(): array
    {
        return [
            'type'        => 'string',
            'sentAs'      => 'service_id',
            'description' => $this->id('service')['description'].' that this endpoint belongs to',
        ];
    }

    public function password(): array
    {
        return [
            'description' => <<<EOT
The password for the user that they will use to authenticate with. Please ensure it is sufficiently long and random. If
you want a password generated for you, you can use TODO.
EOT
        ];
    }

    public function email(): array
    {
        return [
            'description' => 'The personal e-mail address of the user',
        ];
    }

    public function effective(): array
    {
        return [
            'type'        => self::BOOL_TYPE,
            'location'    => self::QUERY,
            'description' => <<<EOT
Use the effective query parameter to list effective assignments at the user, project, and domain level. This parameter
allows for the effects of group membership. The group role assignment entities themselves are not returned in the
collection. This represents the effective role assignments that would be included in a scoped token. You can use the
other query parameters with the effective parameter.

For example, to determine what a user can actually do, issue this request: GET /role_assignments?user.id={user_id}&effective

To return the equivalent set of role assignments that would be included in the token response of a project-scoped
token, issue: GET /role_assignments?user.id={user_id}&scope.project.id={project_id}&effective
EOT
        ];
    }

    public function projectIdQuery(): array
    {
        return [
            'sentAs'      => 'scope.project.id',
            'location'    => 'query',
            'description' => 'Filter by project ID',
        ];
    }

    public function domainIdQuery(): array
    {
        return [
            'sentAs'      => 'scope.domain.id',
            'location'    => 'query',
            'description' => $this->id('domain')['description'].' associated with the role assignments',
        ];
    }

    public function roleIdQuery(): array
    {
        return [
            'sentAs'      => 'role.id',
            'location'    => 'query',
            'description' => 'Filter by role ID',
        ];
    }

    public function groupIdQuery(): array
    {
        return [
            'sentAs'      => 'group.id',
            'location'    => 'query',
            'description' => 'Filter by group ID',
        ];
    }

    public function userIdQuery(): array
    {
        return [
            'sentAs'      => 'user.id',
            'location'    => 'query',
            'description' => 'Filter by user ID',
        ];
    }

    public function domain(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'id'   => $this->id('domain'),
                'name' => $this->name('domain'),
            ],
        ];
    }

    public function project(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'id'     => $this->id('project'),
                'name'   => $this->name('project'),
                'domain' => $this->domain(),
            ],
        ];
    }

    public function idUrl($type)
    {
        return [
            'required'    => true,
            'location'    => self::URL,
            'description' => sprintf('The unique ID, or identifier, for the %s', $type),
        ];
    }

    public function tokenId(): array
    {
        return [
            'location'    => self::HEADER,
            'sentAs'      => 'X-Subject-Token',
            'description' => 'The unique token ID',
        ];
    }

    public function domainId($type)
    {
        return [
            'sentAs'      => 'domain_id',
            'description' => sprintf('%s associated with this %s', $this->id('domain')['description'], $type),
        ];
    }

    public function parentId(): array
    {
        return [
            'sentAs'      => 'parent_id',
            'description' => <<<EOT
The unique ID of the project which serves as the parent for this project. For more information about hierarchical
multitenancy in Keystone v3, see: http://specs.openstack.org/openstack/keystone-specs/specs/juno/hierarchical_multitenancy.html
EOT
        ];
    }

    public function type($resource)
    {
        return [
            'description' => sprintf('The type of the %s', $resource),
        ];
    }

    public function desc($resource)
    {
        return [
            'description' => sprintf('A human-friendly summary that explains what the %s does', $resource),
        ];
    }

    public function enabled($resource)
    {
        return [
            'type'        => self::BOOL_TYPE,
            'description' => sprintf(
                'Indicates whether this %s is enabled or not. If not, the %s will be unavailable for use.',
                $resource,
                $resource
            ),
        ];
    }

    public function defaultProjectId(): array
    {
        return [
            'sentAs'      => 'default_project_id',
            'description' => <<<EOT
The unique ID of the project which will serve as a default for the user. Unless another project ID is specified in an
API operation, it is assumed that this project was meant - and so it is used as a default throughout.
EOT
        ];
    }

    public function projectId(): array
    {
        return [
            'sentAs'      => 'project_id',
            'description' => $this->id('project'),
        ];
    }

    public function userId(): array
    {
        return [
            'sentAs'      => 'user_id',
            'description' => $this->id('user'),
        ];
    }

    public function blob(): array
    {
        return [
            'type'        => 'string',
            'description' => "This does something, but it's not explained in the docs (as of writing this)",
        ];
    }
}
