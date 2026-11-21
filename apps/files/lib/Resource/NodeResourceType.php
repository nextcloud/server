<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Files\Resource;

use NCU\Resource\IResourceType;
use NCU\Resource\Permission\AccessEntry;
use NCU\Resource\Permission\CorePermission;
use OCA\Files\AppInfo\Application;
use OCP\Actor\Actor;
use OCP\Actor\PrincipalType;
use OCP\Constants;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\Storage\ISharedStorage;
use OCP\L10N\IFactory;

/**
 * Files and folders, the ID of the resource is the file ID.
 */
final readonly class NodeResourceType implements IResourceType {
	public const string ID = 'files:node';

	private const array PERMISSIONS = [
		Constants::PERMISSION_READ => CorePermission::Read,
		Constants::PERMISSION_UPDATE => CorePermission::Update,
		Constants::PERMISSION_CREATE => CorePermission::Create,
		Constants::PERMISSION_DELETE => CorePermission::Delete,
		Constants::PERMISSION_SHARE => CorePermission::Share,
	];

	public function __construct(
		private IFactory $l10nFactory,
		private IRootFolder $rootFolder,
	) {
	}

	#[\Override]
	public function getId(): string {
		return self::ID;
	}

	#[\Override]
	public function getDisplayName(): string {
		return $this->l10nFactory->get(Application::APP_ID)->t('File or folder');
	}

	#[\Override]
	public function getPermissionIds(): array {
		return [...array_column(self::PERMISSIONS, 'value'), CorePermission::DOWNLOAD];
	}

	/**
	 * Only local users have access, with the permissions the node has in their files.
	 */
	#[\Override]
	public function getAccess(Actor $actor, array $resources): array {
		$principal = $actor->principal;
		if ($principal->type !== PrincipalType::User || $principal->origin !== null) {
			return [];
		}

		$userFolder = $this->rootFolder->getUserFolder($principal->id);
		$access = [];
		foreach ($resources as $resource) {
			$node = ctype_digit($resource->id) ? $userFolder->getFirstNodeById((int)$resource->id) : null;
			if ($node === null) {
				continue;
			}

			$permissionIds = $this->getGrantedPermissionIds($node);
			if ($permissionIds !== []) {
				$access[$resource->key()] = [new AccessEntry(AccessEntry::RECIPIENT_USER, $principal->id, $permissionIds)];
			}
		}

		return $access;
	}

	/**
	 * @return list<non-empty-string>
	 */
	private function getGrantedPermissionIds(Node $node): array {
		$mask = $node->getPermissions();
		$permissionIds = [];
		foreach (self::PERMISSIONS as $bit => $permission) {
			if (($mask & $bit) !== 0) {
				$permissionIds[] = $permission->value;
			}
		}

		if (($mask & Constants::PERMISSION_READ) !== 0 && $this->canDownload($node)) {
			$permissionIds[] = CorePermission::DOWNLOAD;
		}

		return $permissionIds;
	}

	private function canDownload(Node $node): bool {
		$storage = $node->getStorage();
		if (!$storage->instanceOfStorage(ISharedStorage::class)) {
			return true;
		}

		/** @var ISharedStorage $storage */
		return $storage->getShare()->canDownload();
	}
}
