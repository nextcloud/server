<?php

declare(strict_types=1);

namespace OpenStack\Identity\v3\Models;

use OpenStack\Common\Resource\Alias;
use OpenStack\Common\Resource\Listable;
use OpenStack\Common\Resource\OperatorResource;

class Assignment extends OperatorResource implements Listable
{
    /** @var Role */
    public $role;

    /** @var array */
    public $scope;

    /** @var Group */
    public $group;

    /** @var User */
    public $user;

    protected $resourcesKey = 'role_assignments';
    protected $resourceKey  = 'role_assignment';

    /**
     * {@inheritdoc}
     */
    protected function getAliases(): array
    {
        return parent::getAliases() + [
            'role'  => new Alias('role', Role::class),
            'user'  => new Alias('user', User::class),
            'group' => new Alias('group', Group::class),
        ];
    }
}
