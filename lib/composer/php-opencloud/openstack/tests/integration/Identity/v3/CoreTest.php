<?php

namespace OpenStack\integration\Identity\v3;

use OpenStack\Identity\v3\Models;
use OpenStack\Integration\TestCase;
use OpenStack\Integration\Utils;

class CoreTest extends TestCase
{
    private $service;

    /**
     * @return \OpenStack\Identity\v3\Service
     */
    private function getService()
    {
        if (null === $this->service) {
            $this->service = Utils::getOpenStack()->identityV3();
        }

        return $this->service;
    }

    public function runTests()
    {
        $this->defaultLogging = true;
        $this->startTimer();

        $this->tokens();
        $this->domains();

        $this->outputTimeTaken();
    }

    public function tokens()
    {
        /** @var $token \OpenStack\Identity\v3\Models\Token */
        $path = $this->sampleFile([], 'tokens/generate_token_with_username.php');
        require_once $path;
        self::assertInstanceOf(Models\Token::class, $token);

        /** @var $token \OpenStack\Identity\v3\Models\Token */
        $path = $this->sampleFile([], 'tokens/generate_token_with_user_id.php');
        require_once $path;
        self::assertInstanceOf(Models\Token::class, $token);

        $replacements = ['{tokenId}' => $token->id];

        /** @var $token \OpenStack\Identity\v3\Models\Token */
        $path = $this->sampleFile($replacements, 'tokens/generate_token_scoped_to_project_id.php');
        require_once $path;
        self::assertInstanceOf(Models\Token::class, $token);

        /** @var $token \OpenStack\Identity\v3\Models\Token */
        $path = $this->sampleFile($replacements, 'tokens/generate_token_scoped_to_project_name.php');
        require_once $path;
        self::assertInstanceOf(Models\Token::class, $token);

        /** @var $token \OpenStack\Identity\v3\Models\Token */
        $path = $this->sampleFile($replacements, 'tokens/generate_token_from_id.php');
        require_once $path;
        self::assertInstanceOf(Models\Token::class, $token);

        /** @var $result bool */
        $path = $this->sampleFile($replacements, 'tokens/validate_token.php');
        require_once $path;
        self::assertTrue($result);

        $path = $this->sampleFile($replacements, 'tokens/revoke_token.php');
        require_once $path;

        /** @var $result bool */
        $path = $this->sampleFile($replacements, 'tokens/validate_token.php');
        require_once $path;
        self::assertFalse($result);
    }

    public function domains()
    {
        $replacements = [
            '{name}'        => $this->randomStr(),
            '{description}' => $this->randomStr(),
        ];

        /** @var $domain \OpenStack\Identity\v3\Models\Domain */
        $path = $this->sampleFile($replacements, 'domains/add_domain.php');
        require_once $path;
        self::assertInstanceOf(Models\Domain::class, $domain);

        $replacements['{domainId}'] = $domain->id;

        $path = $this->sampleFile([], 'domains/list_domains.php');
        require_once $path;

        /** @var $domain \OpenStack\Identity\v3\Models\Domain */
        $path = $this->sampleFile($replacements, 'domains/show_domain.php');
        require_once $path;
        self::assertInstanceOf(Models\Domain::class, $domain);

        $parentRole = $this->getService()->createRole(['name' => $this->randomStr()]);
        $group = $this->getService()->createGroup(['name' => $this->randomStr(), 'domainId' => $domain->id]);

        $path = $this->sampleFile($replacements + ['{groupId}' => $group->id, '{roleId}' => $parentRole->id], 'domains/grant_group_role.php');
        require_once $path;

        /** @var $result bool */
        $path = $this->sampleFile($replacements + ['{groupId}' => $group->id, '{roleId}' => $parentRole->id], 'domains/check_group_role.php');
        require_once $path;
        self::assertTrue($result);

        $path = $this->sampleFile($replacements + ['{groupId}' => $group->id], 'domains/list_group_roles.php');
        require_once $path;

        $path = $this->sampleFile($replacements + ['{groupId}' => $group->id, '{roleId}' => $parentRole->id], 'domains/revoke_group_role.php');
        require_once $path;

        $group->delete();

        $user = $this->getService()->createUser(['name' => $this->randomStr(), 'domainId' => $domain->id]);

        $path = $this->sampleFile($replacements + ['{domainUserId}' => $user->id, '{roleId}' => $parentRole->id], 'domains/grant_user_role.php');
        require_once $path;

        /** @var $result bool */
        $path = $this->sampleFile($replacements + ['{domainUserId}' => $user->id, '{roleId}' => $parentRole->id], 'domains/check_user_role.php');
        require_once $path;
        self::assertTrue($result);

        $path = $this->sampleFile($replacements + ['{domainUserId}' => $user->id], 'domains/list_user_roles.php');
        require_once $path;

        $path = $this->sampleFile($replacements + ['{domainUserId}' => $user->id, '{roleId}' => $parentRole->id], 'domains/revoke_user_role.php');
        require_once $path;

        $user->delete();
        $parentRole->delete();

        /** @var $domain \OpenStack\Identity\v3\Models\Domain */
        $path = $this->sampleFile($replacements, 'domains/update_domain.php');
        require_once $path;
        self::assertInstanceOf(Models\Domain::class, $domain);

        $path = $this->sampleFile($replacements, 'domains/delete_domain.php');
        require_once $path;
    }

    public function endpoints()
    {
        $service = $this->getService()->createService(['name' => $this->randomStr(), 'type' => 'volume', 'description' => $this->randomStr()]);

        $replacements = [
            '{endpointName}' => $this->randomStr(),
            '{serviceId}' => $service->id,
            '{endpointUrl}' => getenv('OS_AUTH_URL'),
            '{region}' => 'RegionOne',
        ];

        /** @var $endpoint \OpenStack\Identity\v3\Models\Endpoint */
        $path = $this->sampleFile($replacements, 'endpoints/add_endpoint.php');
        require_once $path;
        self::assertInstanceOf(Models\Endpoint::class, $endpoint);

        $replacements['{endpointId}'] = $endpoint->id;

        $path = $this->sampleFile($replacements, 'endpoints/list_endpoints.php');
        require_once $path;

        /** @var $endpoint \OpenStack\Identity\v3\Models\Endpoint */
        $path = $this->sampleFile($replacements, 'endpoints/update_endpoint.php');
        require_once $path;

        $path = $this->sampleFile($replacements, 'endpoints/delete_endpoint.php');
        require_once $path;

        $service->delete();
    }

    public function services()
    {
        $replacements = [
            '{serviceName}' => $this->randomStr(),
            '{serviceType}' => $this->randomStr(),
        ];

        /** @var $service \OpenStack\Identity\v3\Models\Service */
        $path = $this->sampleFile($replacements, 'services/add_service.php');
        require_once $path;
        self::assertInstanceOf(Models\Service::class, $service);

        $replacements['{serviceId}'] = $service->id;

        $path = $this->sampleFile($replacements, 'services/list_services.php');
        require_once $path;

        /** @var $service \OpenStack\Identity\v3\Models\Service */
        $path = $this->sampleFile($replacements, 'services/update_service.php');
        require_once $path;
        self::assertInstanceOf(Models\Service::class, $service);

        /** @var $service \OpenStack\Identity\v3\Models\Service */
        $path = $this->sampleFile($replacements, 'services/get_service.php');
        require_once $path;
        self::assertInstanceOf(Models\Service::class, $service);

        $path = $this->sampleFile($replacements, 'services/delete_service.php');
        require_once $path;
    }

    public function groups()
    {
        $groupUser = $this->getService()->createUser(['name' => $this->randomStr()]);

        /** @var $group \OpenStack\Identity\v3\Models\Group */
        $path = $this->sampleFile(['{name}' => $this->randomStr(), '{description}' => $this->randomStr()], 'groups/add_group.php');
        require_once $path;
        self::assertInstanceOf(Models\Group::class, $group);

        $replacements = ['{groupId}' => $group->id];

        $path = $this->sampleFile($replacements + ['{groupUserId}' => $groupUser->id], 'groups/add_user.php');
        require_once $path;

        /** @var $group \OpenStack\Identity\v3\Models\Group */
        $path = $this->sampleFile($replacements, 'groups/get_group.php');
        require_once $path;
        self::assertInstanceOf(Models\Group::class, $group);

        /** @var $result bool */
        $path = $this->sampleFile($replacements + ['{groupUserId}' => $groupUser->id], 'groups/check_user_membership.php');
        require_once $path;
        self::assertTrue($result);

        $path = $this->sampleFile($replacements, 'groups/list_users.php');
        require_once $path;

        $path = $this->sampleFile($replacements + ['{groupUserId}' => $groupUser->id], 'groups/remove_user.php');
        require_once $path;

        /** @var $result bool */
        $path = $this->sampleFile($replacements + ['{groupUserId}' => $groupUser->id], 'groups/check_user_membership.php');
        require_once $path;
        self::assertFalse($result);

        $path = $this->sampleFile($replacements + ['{name}' => $this->randomStr(), '{description}' => $this->randomStr()], 'groups/update_group.php');
        require_once $path;

        $path = $this->sampleFile($replacements, 'groups/list_groups.php');
        require_once $path;

        $path = $this->sampleFile($replacements, 'groups/delete_group.php');
        require_once $path;

        $groupUser->delete();
    }

    public function projects()
    {
        /** @var $project \OpenStack\Identity\v3\Models\Project */
        $path = $this->sampleFile(['{name}' => $this->randomStr(), '{description}' => $this->randomStr()], 'projects/add_project.php');
        require_once $path;
        self::assertInstanceOf(Models\Project::class, $project);

        $replacements = ['{id}' => $project->id];

        /** @var $project \OpenStack\Identity\v3\Models\Project */
        $path = $this->sampleFile($replacements, 'projects/get_project.php');
        require_once $path;
        self::assertInstanceOf(Models\Project::class, $project);

        $domain = $this->getService()->createDomain(['name' => $this->randomStr()]);
        $parentRole = $this->getService()->createRole(['name' => $this->randomStr()]);
        $group = $this->getService()->createGroup(['name' => $this->randomStr(), 'domainId' => $domain->id]);

        $path = $this->sampleFile($replacements + ['{groupId}' => $group->id, '{roleId}' => $parentRole->id], 'projects/grant_group_role.php');
        require_once $path;

        /** @var $result bool */
        $path = $this->sampleFile($replacements + ['{groupId}' => $group->id, '{roleId}' => $parentRole->id], 'projects/check_group_role.php');
        require_once $path;
        self::assertTrue($result);

        $path = $this->sampleFile($replacements + ['{groupId}' => $group->id], 'projects/list_group_roles.php');
        require_once $path;

        $path = $this->sampleFile($replacements + ['{groupId}' => $group->id, '{roleId}' => $parentRole->id], 'projects/revoke_group_role.php');
        require_once $path;

        $group->delete();

        $user = $this->getService()->createUser(['name' => $this->randomStr(), 'domainId' => $domain->id]);

        $path = $this->sampleFile($replacements + ['{projectUserId}' => $user->id, '{roleId}' => $parentRole->id], 'projects/grant_user_role.php');
        require_once $path;

        /** @var $result bool */
        $path = $this->sampleFile($replacements + ['{projectUserId}' => $user->id, '{roleId}' => $parentRole->id], 'projects/check_user_role.php');
        require_once $path;
        self::assertTrue($result);

        $path = $this->sampleFile($replacements + ['{projectUserId}' => $user->id], 'projects/list_user_roles.php');
        require_once $path;

        $path = $this->sampleFile($replacements + ['{projectUserId}' => $user->id, '{roleId}' => $parentRole->id], 'projects/revoke_user_role.php');
        require_once $path;

        $path = $this->sampleFile($replacements, 'projects/update_project.php');
        require_once $path;

        $path = $this->sampleFile($replacements, 'projects/delete_project.php');
        require_once $path;

        $user->delete();
        $parentRole->delete();

        $domain->enabled = false;
        $domain->update();
        $domain->delete();
    }

    public function roles()
    {
        /** @var $role \OpenStack\Identity\v3\Models\Role */
        $path = $this->sampleFile(['{name}' => $this->randomStr()], 'roles/add_role.php');
        require_once $path;
        self::assertInstanceOf(Models\Role::class, $role);

        $path = $this->sampleFile([], 'roles/list_roles.php');
        require_once $path;

        $path = $this->sampleFile([], 'roles/list_assignments.php');
        require_once $path;
    }

    public function users()
    {
        $parentDomain  = $this->getService()->createDomain(['name' => $this->randomStr()]);
        $parentProject = $this->getService()->createProject(['name' => $this->randomStr(), 'domainId' => $parentDomain->id]);

        $replacements = [
            '{defaultProjectId}' => $parentProject->id,
            '{description}'      => $this->randomStr(),
            '{domainId}'         => $parentDomain->id,
            '{email}'            => 'foo@bar.com',
            '{enabled}'          => true,
            '{name}'             => $this->randomStr(),
            '{userPass}'         => $this->randomStr(),
        ];

        /** @var $user \OpenStack\Identity\v3\Models\User */
        $path = $this->sampleFile($replacements, 'users/add_user.php');
        require_once $path;
        self::assertInstanceOf(Models\User::class, $user);

        $replacements = ['{id}' => $user->id];

        /** @var $user \OpenStack\Identity\v3\Models\User */
        $path = $this->sampleFile($replacements, 'users/get_user.php');
        require_once $path;
        self::assertInstanceOf(Models\User::class, $user);

        $path = $this->sampleFile([], 'users/list_users.php');
        require_once $path;

        $path = $this->sampleFile($replacements, 'users/list_groups.php');
        require_once $path;

        $path = $this->sampleFile($replacements, 'users/list_projects.php');
        require_once $path;

        /** @var $user \OpenStack\Identity\v3\Models\User */
        $path = $this->sampleFile($replacements + ['{name}' => $this->randomStr(), '{description}' => $this->randomStr()], 'users/update_user.php');
        require_once $path;
        self::assertInstanceOf(Models\User::class, $user);

        $path = $this->sampleFile($replacements, 'users/delete_user.php');
        require_once $path;

        $parentProject->delete();

        $parentDomain->enabled = false;
        $parentDomain->update();
        $parentDomain->delete();
    }
}
