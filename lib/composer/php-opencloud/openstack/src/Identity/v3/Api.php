<?php

declare(strict_types=1);

namespace OpenStack\Identity\v3;

use OpenStack\Common\Api\AbstractApi;

class Api extends AbstractApi
{
    public function __construct()
    {
        $this->params = new Params();
    }

    public function postTokens(): array
    {
        return [
            'method' => 'POST',
            'path'   => 'auth/tokens',
            'params' => [
                'methods' => $this->params->methods(),
                'user'    => $this->params->user(),
                'tokenId' => $this->params->tokenBody(),
                'scope'   => $this->params->scope(),
            ],
        ];
    }

    public function getTokens(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'auth/tokens',
            'params' => ['tokenId' => $this->params->tokenId()],
        ];
    }

    public function headTokens(): array
    {
        return [
            'method' => 'HEAD',
            'path'   => 'auth/tokens',
            'params' => ['tokenId' => $this->params->tokenId()],
        ];
    }

    public function deleteTokens(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => 'auth/tokens',
            'params' => ['tokenId' => $this->params->tokenId()],
        ];
    }

    public function postServices(): array
    {
        return [
            'method'  => 'POST',
            'path'    => 'services',
            'jsonKey' => 'service',
            'params'  => [
                'name'        => $this->params->name('service'),
                'type'        => $this->params->type('service'),
                'description' => $this->params->desc('service'),
            ],
        ];
    }

    public function getServices(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'services',
            'params' => ['type' => $this->params->typeQuery()],
        ];
    }

    public function getService(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'services/{id}',
            'params' => ['id' => $this->params->idUrl('service')],
        ];
    }

    public function patchService(): array
    {
        return [
            'method'  => 'PATCH',
            'path'    => 'services/{id}',
            'jsonKey' => 'service',
            'params'  => [
                'id'          => $this->params->idUrl('service'),
                'name'        => $this->params->name('service'),
                'type'        => $this->params->type('service'),
                'description' => $this->params->desc('service'),
            ],
        ];
    }

    public function deleteService(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => 'services/{id}',
            'params' => ['id' => $this->params->idUrl('service')],
        ];
    }

    public function postEndpoints(): array
    {
        return [
            'method'  => 'POST',
            'path'    => 'endpoints',
            'jsonKey' => 'endpoint',
            'params'  => [
                'interface' => $this->params->interf(),
                'name'      => $this->isRequired($this->params->name('endpoint')),
                'region'    => $this->params->region(),
                'url'       => $this->params->endpointUrl(),
                'serviceId' => $this->params->serviceId(),
            ],
        ];
    }

    public function getEndpoints(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'endpoints',
            'params' => [
                'interface' => $this->query($this->params->interf()),
                'serviceId' => $this->query($this->params->serviceId()),
            ],
        ];
    }

    public function getEndpoint(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'endpoints/{id}',
            'params' => [
                'id' => $this->params->idUrl('service'),
            ],
        ];
    }

    public function patchEndpoint(): array
    {
        return [
            'method'  => 'PATCH',
            'path'    => 'endpoints/{id}',
            'jsonKey' => 'endpoint',
            'params'  => [
                'id'        => $this->params->idUrl('endpoint'),
                'interface' => $this->params->interf(),
                'name'      => $this->params->name('endpoint'),
                'region'    => $this->params->region(),
                'url'       => $this->params->endpointUrl(),
                'serviceId' => $this->params->serviceId(),
            ],
        ];
    }

    public function deleteEndpoint(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => 'endpoints/{id}',
            'params' => ['id' => $this->params->idUrl('endpoint')],
        ];
    }

    public function postDomains(): array
    {
        return [
            'method'  => 'POST',
            'path'    => 'domains',
            'jsonKey' => 'domain',
            'params'  => [
                'name'        => $this->isRequired($this->params->name('domain')),
                'enabled'     => $this->params->enabled('domain'),
                'description' => $this->params->desc('domain'),
            ],
        ];
    }

    public function getDomains(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'domains',
            'params' => [
                'name'    => $this->query($this->params->name('domain')),
                'enabled' => $this->query($this->params->enabled('domain')),
            ],
        ];
    }

    public function getDomain(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'domains/{id}',
            'params' => ['id' => $this->params->idUrl('domain')],
        ];
    }

    public function patchDomain(): array
    {
        return [
            'method'  => 'PATCH',
            'path'    => 'domains/{id}',
            'jsonKey' => 'domain',
            'params'  => [
                'id'          => $this->params->idUrl('domain'),
                'name'        => $this->params->name('domain'),
                'enabled'     => $this->params->enabled('domain'),
                'description' => $this->params->desc('domain'),
            ],
        ];
    }

    public function deleteDomain(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => 'domains/{id}',
            'params' => ['id' => $this->params->idUrl('domain')],
        ];
    }

    public function getUserRoles(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'domains/{domainId}/users/{userId}/roles',
            'params' => [
                'domainId' => $this->params->idUrl('domain'),
                'userId'   => $this->params->idUrl('user'),
            ],
        ];
    }

    public function putUserRoles(): array
    {
        return [
            'method' => 'PUT',
            'path'   => 'domains/{domainId}/users/{userId}/roles/{roleId}',
            'params' => [
                'domainId' => $this->params->idUrl('domain'),
                'userId'   => $this->params->idUrl('user'),
                'roleId'   => $this->params->idUrl('role'),
            ],
        ];
    }

    public function headUserRole(): array
    {
        return [
            'method' => 'HEAD',
            'path'   => 'domains/{domainId}/users/{userId}/roles/{roleId}',
            'params' => [
                'domainId' => $this->params->idUrl('domain'),
                'userId'   => $this->params->idUrl('user'),
                'roleId'   => $this->params->idUrl('role'),
            ],
        ];
    }

    public function deleteUserRole(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => 'domains/{domainId}/users/{userId}/roles/{roleId}',
            'params' => [
                'domainId' => $this->params->idUrl('domain'),
                'userId'   => $this->params->idUrl('user'),
                'roleId'   => $this->params->idUrl('role'),
            ],
        ];
    }

    public function getGroupRoles(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'domains/{domainId}/groups/{groupId}/roles',
            'params' => [
                'domainId' => $this->params->idUrl('domain'),
                'groupId'  => $this->params->idUrl('group'),
            ],
        ];
    }

    public function putGroupRole(): array
    {
        return [
            'method' => 'PUT',
            'path'   => 'domains/{domainId}/groups/{groupId}/roles/{roleId}',
            'params' => [
                'domainId' => $this->params->idUrl('domain'),
                'groupId'  => $this->params->idUrl('group'),
                'roleId'   => $this->params->idUrl('role'),
            ],
        ];
    }

    public function headGroupRole(): array
    {
        return [
            'method' => 'HEAD',
            'path'   => 'domains/{domainId}/groups/{groupId}/roles/{roleId}',
            'params' => [
                'domainId' => $this->params->idUrl('domain'),
                'groupId'  => $this->params->idUrl('group'),
                'roleId'   => $this->params->idUrl('role'),
            ],
        ];
    }

    public function deleteGroupRole(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => 'domains/{domainId}/groups/{groupId}/roles/{roleId}',
            'params' => [
                'domainId' => $this->params->idUrl('domain'),
                'groupId'  => $this->params->idUrl('group'),
                'roleId'   => $this->params->idUrl('role'),
            ],
        ];
    }

    public function postProjects(): array
    {
        return [
            'method'  => 'POST',
            'path'    => 'projects',
            'jsonKey' => 'project',
            'params'  => [
                'description' => $this->params->desc('project'),
                'domainId'    => $this->params->domainId('project'),
                'parentId'    => $this->params->parentId(),
                'enabled'     => $this->params->enabled('project'),
                'name'        => $this->isRequired($this->params->name('project')),
            ],
        ];
    }

    public function getProjects(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'projects',
            'params' => [
                'domainId' => $this->query($this->params->domainId('project')),
                'enabled'  => $this->query($this->params->enabled('project')),
                'name'     => $this->query($this->params->name('project')),
            ],
        ];
    }

    public function getProject(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'projects/{id}',
            'params' => ['id' => $this->params->idUrl('project')],
        ];
    }

    public function patchProject(): array
    {
        return [
            'method'  => 'PATCH',
            'path'    => 'projects/{id}',
            'jsonKey' => 'project',
            'params'  => [
                'id'          => $this->params->idUrl('project'),
                'description' => $this->params->desc('project'),
                'domainId'    => $this->params->domainId('project'),
                'parentId'    => $this->params->parentId(),
                'enabled'     => $this->params->enabled('project'),
                'name'        => $this->params->name('project'),
            ],
        ];
    }

    public function deleteProject(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => 'projects/{id}',
            'params' => ['id' => $this->params->idUrl('project')],
        ];
    }

    public function getProjectUserRoles(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'projects/{projectId}/users/{userId}/roles',
            'params' => [
                'projectId' => $this->params->idUrl('project'),
                'userId'    => $this->params->idUrl('user'),
            ],
        ];
    }

    public function putProjectUserRole(): array
    {
        return [
            'method' => 'PUT',
            'path'   => 'projects/{projectId}/users/{userId}/roles/{roleId}',
            'params' => [
                'projectId' => $this->params->idUrl('project'),
                'userId'    => $this->params->idUrl('user'),
                'roleId'    => $this->params->idUrl('role'),
            ],
        ];
    }

    public function headProjectUserRole(): array
    {
        return [
            'method' => 'HEAD',
            'path'   => 'projects/{projectId}/users/{userId}/roles/{roleId}',
            'params' => [
                'projectId' => $this->params->idUrl('project'),
                'userId'    => $this->params->idUrl('user'),
                'roleId'    => $this->params->idUrl('role'),
            ],
        ];
    }

    public function deleteProjectUserRole(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => 'projects/{projectId}/users/{userId}/roles/{roleId}',
            'params' => [
                'projectId' => $this->params->idUrl('project'),
                'userId'    => $this->params->idUrl('user'),
                'roleId'    => $this->params->idUrl('role'),
            ],
        ];
    }

    public function getProjectGroupRoles(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'projects/{projectId}/groups/{groupId}/roles',
            'params' => [
                'projectId' => $this->params->idUrl('project'),
                'groupId'   => $this->params->idUrl('group'),
            ],
        ];
    }

    public function putProjectGroupRole(): array
    {
        return [
            'method' => 'PUT',
            'path'   => 'projects/{projectId}/groups/{groupId}/roles/{roleId}',
            'params' => [
                'projectId' => $this->params->idUrl('project'),
                'groupId'   => $this->params->idUrl('group'),
                'roleId'    => $this->params->idUrl('role'),
            ],
        ];
    }

    public function headProjectGroupRole(): array
    {
        return [
            'method' => 'HEAD',
            'path'   => 'projects/{projectId}/groups/{groupId}/roles/{roleId}',
            'params' => [
                'projectId' => $this->params->idUrl('project'),
                'groupId'   => $this->params->idUrl('group'),
                'roleId'    => $this->params->idUrl('role'),
            ],
        ];
    }

    public function deleteProjectGroupRole(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => 'projects/{projectId}/groups/{groupId}/roles/{roleId}',
            'params' => [
                'projectId' => $this->params->idUrl('project'),
                'groupId'   => $this->params->idUrl('group'),
                'roleId'    => $this->params->idUrl('role'),
            ],
        ];
    }

    public function postUsers(): array
    {
        return [
            'method'  => 'POST',
            'path'    => 'users',
            'jsonKey' => 'user',
            'params'  => [
                'defaultProjectId' => $this->params->defaultProjectId(),
                'description'      => $this->params->desc('user'),
                'domainId'         => $this->params->domainId('user'),
                'email'            => $this->params->email(),
                'enabled'          => $this->params->enabled('user'),
                'name'             => $this->isRequired($this->params->name('user')),
                'password'         => $this->params->password(),
            ],
        ];
    }

    public function getUsers(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'users',
            'params' => [
                'domainId' => $this->query($this->params->domainId('user')),
                'enabled'  => $this->query($this->params->enabled('user')),
                'name'     => $this->query($this->params->name('user')),
            ],
        ];
    }

    public function getUser(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'users/{id}',
            'params' => ['id' => $this->params->idUrl('user')],
        ];
    }

    public function patchUser(): array
    {
        return [
            'method'  => 'PATCH',
            'path'    => 'users/{id}',
            'jsonKey' => 'user',
            'params'  => [
                'id'               => $this->params->idUrl('user'),
                'defaultProjectId' => $this->params->defaultProjectId(),
                'description'      => $this->params->desc('user'),
                'email'            => $this->params->email(),
                'enabled'          => $this->params->enabled('user'),
                'password'         => $this->params->password(),
            ],
        ];
    }

    public function deleteUser(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => 'users/{id}',
            'params' => ['id' => $this->params->idUrl('user')],
        ];
    }

    public function getUserGroups(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'users/{id}/groups',
            'params' => ['id' => $this->params->idUrl('user')],
        ];
    }

    public function getUserProjects(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'users/{id}/projects',
            'params' => ['id' => $this->params->idUrl('user')],
        ];
    }

    public function postGroups(): array
    {
        return [
            'method'  => 'POST',
            'path'    => 'groups',
            'jsonKey' => 'group',
            'params'  => [
                'description' => $this->params->desc('group'),
                'domainId'    => $this->params->domainId('group'),
                'name'        => $this->params->name('group'),
            ],
        ];
    }

    public function getGroups(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'groups',
            'params' => ['domainId' => $this->query($this->params->domainId('group'))],
        ];
    }

    public function getGroup(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'groups/{id}',
            'params' => ['id' => $this->params->idUrl('group')],
        ];
    }

    public function patchGroup(): array
    {
        return [
            'method'  => 'PATCH',
            'path'    => 'groups/{id}',
            'jsonKey' => 'group',
            'params'  => [
                'id'          => $this->params->idUrl('group'),
                'description' => $this->params->desc('group'),
                'name'        => $this->params->name('group'),
            ],
        ];
    }

    public function deleteGroup(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => 'groups/{id}',
            'params' => ['id' => $this->params->idUrl('group')],
        ];
    }

    public function getGroupUsers(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'groups/{id}/users',
            'params' => ['id' => $this->params->idUrl('group')],
        ];
    }

    public function putGroupUser(): array
    {
        return [
            'method' => 'PUT',
            'path'   => 'groups/{groupId}/users/{userId}',
            'params' => [
                'groupId' => $this->params->idUrl('group'),
                'userId'  => $this->params->idUrl('user'),
            ],
        ];
    }

    public function deleteGroupUser(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => 'groups/{groupId}/users/{userId}',
            'params' => [
                'groupId' => $this->params->idUrl('group'),
                'userId'  => $this->params->idUrl('user'),
            ],
        ];
    }

    public function headGroupUser(): array
    {
        return [
            'method' => 'HEAD',
            'path'   => 'groups/{groupId}/users/{userId}',
            'params' => [
                'groupId' => $this->params->idUrl('group'),
                'userId'  => $this->params->idUrl('user'),
            ],
        ];
    }

    public function postCredentials(): array
    {
        return [
            'method' => 'POST',
            'path'   => 'credentials',
            'params' => [
                'blob'      => $this->params->blob(),
                'projectId' => $this->params->projectId(),
                'type'      => $this->params->type('credential'),
                'userId'    => $this->params->userId(),
            ],
        ];
    }

    public function getCredentials(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'credentials',
            'params' => [],
        ];
    }

    public function getCredential(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'credentials/{id}',
            'params' => ['id' => $this->params->idUrl('credential')],
        ];
    }

    public function patchCredential(): array
    {
        return [
            'method' => 'PATCH',
            'path'   => 'credentials/{id}',
            'params' => ['id' => $this->params->idUrl('credential')] + $this->postCredentials()['params'],
        ];
    }

    public function deleteCredential(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => 'credentials/{id}',
            'params' => ['id' => $this->params->idUrl('credential')],
        ];
    }

    public function postRoles(): array
    {
        return [
            'method'  => 'POST',
            'path'    => 'roles',
            'jsonKey' => 'role',
            'params'  => ['name' => $this->isRequired($this->params->name('role'))],
        ];
    }

    public function getRoles(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'roles',
            'params' => ['name' => $this->query($this->params->name('role'))],
        ];
    }

    public function deleteRole(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => 'roles/{id}',
            'params' => ['id' => $this->params->idUrl('role')],
        ];
    }

    public function getRoleAssignments(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'role_assignments',
            'params' => [
                'userId'    => $this->params->userIdQuery(),
                'groupId'   => $this->params->groupIdQuery(),
                'roleId'    => $this->params->roleIdQuery(),
                'domainId'  => $this->params->domainIdQuery(),
                'projectId' => $this->params->projectIdQuery(),
                'effective' => $this->params->effective(),
            ],
        ];
    }

    public function postPolicies(): array
    {
        return [
            'method'  => 'POST',
            'path'    => 'policies',
            'jsonKey' => 'policy',
            'params'  => [
                'blob'      => $this->params->blob(),
                'projectId' => $this->params->projectId('policy'),
                'type'      => $this->params->type('policy'),
                'userId'    => $this->params->userId('policy'),
            ],
        ];
    }

    public function getPolicies(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'policies',
            'params' => ['type' => $this->query($this->params->type('policy'))],
        ];
    }

    public function getPolicy(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'policies/{id}',
            'params' => ['id' => $this->params->idUrl('policy')],
        ];
    }

    public function patchPolicy(): array
    {
        return [
            'method'  => 'PATCH',
            'path'    => 'policies/{id}',
            'jsonKey' => 'policy',
            'params'  => [
                'id'        => $this->params->idUrl('policy'),
                'blob'      => $this->params->blob(),
                'projectId' => $this->params->projectId('policy'),
                'type'      => $this->params->type('policy'),
                'userId'    => $this->params->userId(),
            ],
        ];
    }

    public function deletePolicy(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => 'policies/{id}',
            'params' => ['id' => $this->params->idUrl('policy')],
        ];
    }
}
