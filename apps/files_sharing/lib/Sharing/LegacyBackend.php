<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Sharing;

use DateTime;
use DateTimeInterface;
use NCU\Sharing\Exception\ShareNotFoundException;
use NCU\Sharing\ISharingManager;
use NCU\Sharing\Permission\ISharePermissionType;
use NCU\Sharing\Permission\SharePermission;
use NCU\Sharing\Property\ISharePropertyType;
use NCU\Sharing\Property\ShareProperty;
use NCU\Sharing\Recipient\IShareRecipientType;
use NCU\Sharing\Recipient\ShareRecipient;
use NCU\Sharing\Share;
use NCU\Sharing\ShareState;
use NCU\Sharing\ShareUser;
use NCU\Sharing\Source\ShareSource;
use OC\Core\Sharing\Permission\ReshareSharePermissionType;
use OC\Core\Sharing\Property\ExpirationDateSharePropertyType;
use OC\Core\Sharing\Property\LabelSharePropertyType;
use OC\Core\Sharing\Property\NoteSharePropertyType;
use OC\Core\Sharing\Property\PasswordSharePropertyType;
use OC\Core\Sharing\Recipient\EmailShareRecipientType;
use OC\Core\Sharing\Recipient\GroupShareRecipientType;
use OC\Core\Sharing\Recipient\TeamShareRecipientType;
use OC\Core\Sharing\Recipient\TokenShareRecipientType;
use OC\Core\Sharing\Recipient\UserShareRecipientType;
use OC\Sharing\ISharingLegacyBackend;
use OCA\Files\Sharing\Permission\NodeCreateSharePermissionType;
use OCA\Files\Sharing\Permission\NodeDeleteSharePermissionType;
use OCA\Files\Sharing\Permission\NodeDownloadSharePermissionType;
use OCA\Files\Sharing\Permission\NodeReadSharePermissionType;
use OCA\Files\Sharing\Permission\NodeUpdateSharePermissionType;
use OCA\Files\Sharing\Source\NodeShareSourceType;
use OCP\Constants;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Federation\ICloudIdManager;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\L10N\IFactory;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IAttributes;
use OCP\Share\IManager;
use OCP\Share\IShare;
use OCP\Snowflake\ISnowflakeGenerator;
use RuntimeException;

// TODO: Implement accept/reject mechanism and password expiration time
final readonly class LegacyBackend implements ISharingLegacyBackend {
	public function __construct(
		IFactory $factory,
		private IDBConnection $connection,
		private IRootFolder $rootFolder,
		private IManager $legacyManager,
		private ISnowflakeGenerator $snowflakeGenerator,
		private ISharingManager $sharingManager,
		private ICloudIdManager $cloudIdManager,
	) {
	}

	#[\Override]
	public function getCompatibleSourceTypes(): array {
		return [NodeShareSourceType::class];
	}

	#[\Override]
	public function getCompatibleRecipientTypes(): array {
		return [
			EmailShareRecipientType::class,
			GroupShareRecipientType::class,
			TeamShareRecipientType::class,
			TokenShareRecipientType::class,
			UserShareRecipientType::class,
		];
	}

	#[\Override]
	public function updateShare(Share $share): void {
		$legacyShares = $this->getLegacyShares($share->id);

		/** @var array<class-string<IShareRecipientType>, array<string, array<string, IShare>>> $legacyShareMapping */
		$legacyShareMapping = [];
		foreach ($legacyShares as $legacyShare) {
			$recipientTypeClass = $this->legacyShareTypeToRecipientTypeClass($legacyShare->getShareType());
			$sharedWith = $legacyShare->getSharedWith();
			$legacyNodeId = (string)$legacyShare->getNodeId();

			$legacyShareMapping[$recipientTypeClass] ??= [];
			$legacyShareMapping[$recipientTypeClass][$sharedWith] ??= [];
			$legacyShareMapping[$recipientTypeClass][$sharedWith][$legacyNodeId] = $legacyShare;
		}

		/** @var array<string, true> $validLegacyShares */
		$validLegacyShares = [];
		/** @var array<string, true> $updatedLegacyShares */
		$updatedLegacyShares = [];
		foreach ($share->recipients as $recipient) {
			$legacyShareType = $this->recipientTypeClassToLegacyShareType($recipient->class, $recipient->instance);
			foreach ($share->sources as $source) {
				$create = false;
				if (($legacyShare = $legacyShareMapping[$recipient->class][$recipient->value][$source->value] ?? null) === null) {
					$legacyShare = $this->legacyManager->newShare();
					$legacyShare->setShareType($legacyShareType);
					$legacyShare->setNodeId((int)$source->value);
					$legacyShare->setSharedWith($this->recipientToLegacySharedWith($recipient));

					$this->setCommonFields($share, $legacyShare);
					$create = true;
				}

				$update = false;

				if ($recipient->instance !== null) {
					throw new \Exception("Incoming remote shares aren't handled by " . self::class);
				}

				if ($recipient->initiator !== null && $legacyShare->getSharedBy() !== $recipient->initiator->userId) {
					$legacyShare->setSharedBy($recipient->initiator->userId);
					$update = true;
				}

				[$permissions, $allowDownload] = $this->permissionsToLegacyPermission($source, array_keys($share->getEnabledPermissions()));
				if ($legacyShare->getPermissions() !== $permissions) {
					$legacyShare->setPermissions($permissions);
					$update = true;
				}

				if ($legacyShare->getShareType() === IShare::TYPE_LINK || $legacyShare->getShareType() === IShare::TYPE_EMAIL) {
					if ($legacyShare->getHideDownload() !== !$allowDownload) {
						$legacyShare->setHideDownload(!$allowDownload);
						$update = true;
					}
				} else {
					$attributes = $legacyShare->getAttributes() ?? $legacyShare->newAttributes();
					if ($attributes->getAttribute('permissions', 'download') !== !$allowDownload) {
						$attributes->setAttribute('permissions', 'download', true);
						$legacyShare->setAttributes($attributes);
						$update = true;
					}
				}

				$token = $recipient->secret ?? '';
				if (in_array($recipient->class, [EmailShareRecipientType::class, TokenShareRecipientType::class], true) && $legacyShare->getToken() !== $token) {
					$legacyShare->setToken($token);
					$update = true;
				}

				if ($create) {
					$legacyShare = $this->legacyManager->createShare($legacyShare);
					// No need to insert the legacy full id, because the listener in the SharingManager will already trigger this process.
					$legacyShares[$legacyShare->getFullId()] = $legacyShare;
				} elseif ($update) {
					$updatedLegacyShares[$legacyShare->getFullId()] = true;
					$legacyShares[$legacyShare->getFullId()] = $legacyShare;
				}

				$validLegacyShares[$legacyShare->getFullId()] = true;
			}
		}

		$invalidLegacyShares = array_diff(array_keys($legacyShares), array_keys($validLegacyShares));
		foreach ($invalidLegacyShares as $invalidLegacyShareId) {
			$this->legacyManager->deleteShare($legacyShares[$invalidLegacyShareId]);
			unset($legacyShares[$invalidLegacyShareId]);
		}

		foreach ($legacyShares as $legacyShare) {
			$update = $this->setCommonFields($share, $legacyShare);

			if ($update || ($updatedLegacyShares[$legacyShare->getFullId()] ?? false)) {
				$this->legacyManager->updateShare($legacyShare, false);
			}
		}
	}

	private function setCommonFields(Share $share, IShare $legacyShare): bool {
		$update = false;

		if ($share->owner->instance !== null) {
			throw new \Exception("Incoming remote shares aren't handled by " . self::class);
		}

		if ($legacyShare->getShareOwner() !== $share->owner->userId) {
			$legacyShare->setShareOwner($share->owner->userId);
			$update = true;
		}

		// TODO: Implement accept/reject mechanism
		$status = IShare::STATUS_ACCEPTED;
		if ($legacyShare->getShareType() === IShare::TYPE_USER && $legacyShare->getStatus() !== $status) {
			$legacyShare->setStatus($status);
			$update = true;
		}

		$note = $share->properties[NoteSharePropertyType::class]?->value ?? '';
		if ($legacyShare->getNote() !== $note) {
			$legacyShare->setNote($note);
			$update = true;
		}

		$label = $share->properties[LabelSharePropertyType::class]?->value ?? '';
		if (($legacyShare->getShareType() === IShare::TYPE_LINK || $legacyShare->getShareType() === IShare::TYPE_EMAIL) && $legacyShare->getLabel() !== $label) {
			$legacyShare->setLabel($label);
			$update = true;
		}

		$expirationDateProperty = $share->properties[ExpirationDateSharePropertyType::class] ?? null;
		$expirationDate = $expirationDateProperty?->value;
		if ($expirationDate !== null) {
			$expirationDate = DateTime::createFromFormat(DateTimeInterface::ATOM, $expirationDate);
		}

		if ($legacyShare->getExpirationDate()?->getTimestamp() !== $expirationDate?->getTimestamp()) {
			$legacyShare->setExpirationDate($expirationDate);
			$update = true;
		}

		// The value and applying a default value is already handled by Unified Sharing.
		if ($expirationDate === null && !$legacyShare->getNoExpirationDate()) {
			$legacyShare->setNoExpirationDate(true);
			$update = true;
		}

		// TODO: Update details that are the same for all legacy shares

		return $update;
	}

	/**
	 * @param list<class-string<ISharePermissionType>> $permissions
	 * @return array{int-mask-of<Constants::PERMISSION_*>, bool}
	 */
	private function permissionsToLegacyPermission(ShareSource $source, array $permissions): array {
		$node = $this->rootFolder->getFirstNodeById((int)$source->value);
		if (!$node instanceof Node) {
			throw new RuntimeException('Share source does not exist: ' . $source->value);
		}

		$nodeIsFile = $node instanceof File;

		/** @var int-mask-of<Constants::PERMISSION_*> $legacyPermissions */
		$legacyPermissions = 0;
		$allowDownload = false;

		if (in_array(NodeReadSharePermissionType::class, $permissions, true)) {
			$legacyPermissions |= Constants::PERMISSION_READ;
		}

		if (in_array(NodeUpdateSharePermissionType::class, $permissions, true)) {
			$legacyPermissions |= Constants::PERMISSION_UPDATE;
		}

		if (!$nodeIsFile && in_array(NodeCreateSharePermissionType::class, $permissions, true)) {
			$legacyPermissions |= Constants::PERMISSION_CREATE;
		}

		if (!$nodeIsFile && in_array(NodeDeleteSharePermissionType::class, $permissions, true)) {
			$legacyPermissions |= Constants::PERMISSION_DELETE;
		}

		if (in_array(ReshareSharePermissionType::class, $permissions, true)) {
			$legacyPermissions |= Constants::PERMISSION_SHARE;
		}

		if (in_array(NodeDownloadSharePermissionType::class, $permissions, true)) {
			$allowDownload = true;
		}

		return [$legacyPermissions, $allowDownload];
	}

	/**
	 * @param int-mask-of<Constants::PERMISSION_*> $legacyPermissions
	 * @return list<class-string<ISharePermissionType>>
	 */
	private function legacyPermissionsToPermissions(int $legacyPermissions, bool $allowDownload): array {
		$permissions = [];

		foreach ([
			NodeReadSharePermissionType::class => Constants::PERMISSION_READ,
			NodeUpdateSharePermissionType::class => Constants::PERMISSION_UPDATE,
			NodeCreateSharePermissionType::class => Constants::PERMISSION_CREATE,
			NodeDeleteSharePermissionType::class => Constants::PERMISSION_DELETE,
			ReshareSharePermissionType::class => Constants::PERMISSION_SHARE,
		] as $permissionTypeClass => $mask) {
			if (($legacyPermissions & $mask) === $mask) {
				$permissions[] = $permissionTypeClass;
			}
		}

		if ($allowDownload) {
			$permissions[] = NodeDownloadSharePermissionType::class;
		}

		return $permissions;
	}

	#[\Override]
	public function deleteShare(string $id): void {
		foreach ($this->getLegacyFullIds($id) as $legacyShareId) {
			try {
				$this->legacyManager->deleteShare($this->legacyManager->getShareById($legacyShareId, null, false));
				$this->removeLegacyFullId($legacyShareId);
			} catch (ShareNotFound) {
				throw new ShareNotFoundException();
			}
		}
	}

	#[\Override]
	public function getShare(string $id): Share {
		$legacyShareIds = $this->getLegacyFullIds($id);
		if ($legacyShareIds === []) {
			throw new ShareNotFoundException();
		}

		$sources = [];
		$recipients = [];

		$legacyShares = array_map(fn (string $legacyShareId): IShare => $this->legacyManager->getShareById($legacyShareId, null, false), $legacyShareIds);

		foreach ($legacyShares as $legacyShare) {
			$nodeId = $legacyShare->getNodeId();
			$sources[$nodeId] ??= new ShareSource(NodeShareSourceType::class, (string)$nodeId);

			$recipientTypeClass = $this->legacyShareTypeToRecipientTypeClass($legacyShare->getShareType());
			$isTokenRecipient = $recipientTypeClass === TokenShareRecipientType::class;
			$recipients[$recipientTypeClass] ??= [];
			$uniqueId = $isTokenRecipient ? $legacyShare->getToken() : $legacyShare->getSharedWith();
			$recipient = $this->splitLegacySharedWith($legacyShare->getShareType(), $legacyShare->getSharedWith());
			/** @psalm-suppress ArgumentTypeCoercion */
			$recipients[$recipientTypeClass][$uniqueId] ??= new ShareRecipient(
				$recipientTypeClass,
				$recipient['value'],
				$recipient['remote'],
				$isTokenRecipient ? $legacyShare->getToken() : $this->sharingManager->generateSecret(),
				new ShareUser(
					$legacyShare->getSharedBy(),
					// Incoming remote shares aren't handled by this
					null,
				),
			);
		}

		if (!$this->checkAllSame($legacyShares, fn (IShare $share) => $share->getShareOwner())) {
			throw new \Exception("All legacy shares sharing a share id don't have the same owner");
		}

		/** @psalm-suppress ArgumentTypeCoercion */
		$owner = new ShareUser(
			$legacyShares[0]->getShareOwner(),
			// Incoming remote shares aren't handled by this
			null,
		);

		if (!$this->checkAllSame($legacyShares, fn (IShare $share) => $share->getExpirationDate())) {
			throw new \Exception("All legacy shares sharing a share id don't have the expiration date");
		}

		if (!$this->checkAllSame($legacyShares, fn (IShare $share) => $share->getPassword())) {
			throw new \Exception("All legacy shares sharing a share id don't have the password");
		}

		if (!$this->checkAllSame($legacyShares, fn (IShare $share) => $share->getLabel())) {
			throw new \Exception("All legacy shares sharing a share id don't have the label");
		}

		if (!$this->checkAllSame($legacyShares, fn (IShare $share) => $share->getNote())) {
			throw new \Exception("All legacy shares sharing a share id don't have the note");
		}

		$properties = $this->extractProperties($legacyShares[0]);

		if (!$this->checkAllSame($legacyShares, fn (IShare $share): ?IAttributes => $share->getAttributes())) {
			throw new \Exception("All legacy shares sharing a share id don't have the same attributes");
		}

		if (!$this->checkAllSame($legacyShares, fn (IShare $share) => $share->getPermissions())) {
			throw new \Exception("All legacy shares sharing a share id don't have the same permissions");
		}

		$allowDownload = $legacyShares[0]->getShareType() === IShare::TYPE_LINK || $legacyShares[0]->getShareType() === IShare::TYPE_EMAIL
			? !$legacyShares[0]->getHideDownload()
			: $legacyShares[0]->getAttributes()?->getAttribute('permissions', 'download') === true;
		$enabledPermissions = $this->legacyPermissionsToPermissions($legacyShares[0]->getPermissions(), $allowDownload);
		$permissions = [];
		foreach ([
			NodeReadSharePermissionType::class,
			NodeUpdateSharePermissionType::class,
			NodeCreateSharePermissionType::class,
			NodeDeleteSharePermissionType::class,
			ReshareSharePermissionType::class,
			NodeDownloadSharePermissionType::class,
		] as $permissionTypeClass) {
			$permissions[$permissionTypeClass] = new SharePermission($permissionTypeClass, in_array($permissionTypeClass, $enabledPermissions, true));
		}

		/** @psalm-suppress ArgumentTypeCoercion */
		return new Share(
			$id,
			$owner,
			// TODO
			\DateTimeImmutable::createFromMutable($legacyShares[0]->getShareTime()),
			// TODO
			ShareState::Active,
			array_values($sources),
			array_merge(...array_values($recipients)),
			$properties,
			$permissions,
		);
	}

	/**
	 * Check that all items return the same result when used as argument to a function
	 *
	 * @template T
	 * @template U
	 * @param iterable<T> $items
	 * @param callable(T):U $fn
	 */
	private function checkAllSame(iterable $items, callable $fn): bool {
		$first = true;
		$commonValue = null;
		foreach ($items as $item) {
			$value = $fn($item);
			if ($first) {
				$commonValue = $value;
				$first = false;
			} elseif ($value !== $commonValue) {
				return false;
			}
		}

		return true;
	}

	#[\Override]
	public function getShareByLegacyProviderAndId(string $legacyProvider, string $legacyId): Share {
		$id = $this->getId($legacyProvider, $legacyId);
		if ($id === null) {
			throw new ShareNotFoundException();
		}

		return $this->getShare($id);
	}

	#[\Override]
	public function getUnmappedShares(IUser $user): array {
		// TODO: Make it work with all providers
		// TODO: Filter by user
		$qb = $this->connection->getQueryBuilder();
		$result = $qb
			->select('s.id')
			->from('share', 's')
			->leftJoin('s', 'share_legacy_mapping', 'l', $qb->expr()->eq('s.id', 'l.legacy_id'))
			->where($qb->expr()->isNull('l.legacy_id'))
			->andWhere($qb->expr()->in('s.share_type', $qb->createNamedParameter([
				IShare::TYPE_USER,
				IShare::TYPE_REMOTE,
				IShare::TYPE_GROUP,
				IShare::TYPE_REMOTE_GROUP,
				IShare::TYPE_LINK,
				IShare::TYPE_EMAIL,
				IShare::TYPE_CIRCLE,
			], IQueryBuilder::PARAM_INT_ARRAY)))
			->executeQuery();

		/** @var list<int> $legacyIds */
		$legacyIds = $result->fetchFirstColumn();
		if ($legacyIds === []) {
			return [];
		}

		$ids = [];
		foreach ($legacyIds as $legacyId) {
			$id = $this->snowflakeGenerator->nextId();
			$this->addLegacyFullId($id, 'ocinternal', (string)$legacyId);
			$ids[] = $id;
		}

		return array_map($this->getShare(...), $ids);
	}

	/**
	 * @return list<string>
	 */
	public function getLegacyFullIds(string $id): array {
		$qb = $this->connection->getQueryBuilder();
		$result = $qb
			->select('legacy_provider', 'legacy_id')
			->from('share_legacy_mapping')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->executeQuery();

		/** @var list<array{legacy_provider: string, legacy_id: string}> $rows */
		$rows = $result->fetchAllAssociative();

		return array_map(static fn (array $row): string => $row['legacy_provider'] . ':' . $row['legacy_id'], $rows);
	}

	/**
	 * Get the unified share if for a legacy share id
	 *
	 * @return non-empty-string|null
	 */
	private function getId(string $legacyProvider, string $legacyId): ?string {
		$qb = $this->connection->getQueryBuilder();
		$result = $qb
			->select('id')
			->from('share_legacy_mapping')
			->where($qb->expr()->eq('legacy_provider', $qb->createNamedParameter($legacyProvider)))
			->andWhere($qb->expr()->eq('legacy_id', $qb->createNamedParameter($legacyId)))
			->executeQuery();

		/** @var int|false $id */
		$id = $result->fetchOne();
		if ($id === false) {
			return null;
		}

		return (string)$id;
	}

	/**
	 * @param non-empty-string $id
	 * @return array<string, IShare>
	 */
	private function getLegacyShares(string $id): array {
		$legacyIds = $this->getLegacyFullIds($id);

		$shares = [];
		foreach ($legacyIds as $legacyFullId) {
			try {
				$shares[$legacyFullId] = $this->legacyManager->getShareById($legacyFullId, null, false);
			} catch (ShareNotFound) {
				$this->removeLegacyFullId($legacyFullId);
			}
		}

		return $shares;
	}

	private function removeLegacyFullId(string $legacyFullId): void {
		[$providerId, $shareId] = explode(':', $legacyFullId);

		$qb = $this->connection->getQueryBuilder();
		$qb
			->delete('share_legacy_mapping')
			->where($qb->expr()->eq('legacy_provder', $qb->createNamedParameter($providerId)))
			->where($qb->expr()->eq('legacy_id', $qb->createNamedParameter($shareId)))
			->executeStatement();
	}

	private function addLegacyFullId(string $id, string $legacyProvider, string $legacyId): void {
		$qb = $this->connection->getQueryBuilder();
		$qb
			->insert('share_legacy_mapping')
			->values([
				'id' => $qb->createNamedParameter($id),
				'legacy_provider' => $qb->createNamedParameter($legacyProvider),
				'legacy_id' => $qb->createNamedParameter($legacyId),
			])
			->executeStatement();
	}

	/**
	 * @param IShare::TYPE_* $legacyShareType
	 * @return class-string<IShareRecipientType>
	 */
	private function legacyShareTypeToRecipientTypeClass(int $legacyShareType): string {
		return match ($legacyShareType) {
			IShare::TYPE_USER, IShare::TYPE_REMOTE => UserShareRecipientType::class,
			IShare::TYPE_GROUP, IShare::TYPE_REMOTE_GROUP => GroupShareRecipientType::class,
			IShare::TYPE_LINK => TokenShareRecipientType::class,
			IShare::TYPE_EMAIL => EmailShareRecipientType::class,
			IShare::TYPE_CIRCLE => TeamShareRecipientType::class,
			default => throw new RuntimeException('Unsupported legacy share type: ' . $legacyShareType),
		};
	}

	/**
	 * @param class-string<IShareRecipientType> $recipientTypeClass
	 * @return IShare::TYPE_*
	 */
	private function recipientTypeClassToLegacyShareType(string $recipientTypeClass, ?string $instance): int {
		return match ($recipientTypeClass) {
			UserShareRecipientType::class => $instance === null ? IShare::TYPE_USER : IShare::TYPE_REMOTE,
			GroupShareRecipientType::class => $instance === null ? IShare::TYPE_GROUP : IShare::TYPE_REMOTE_GROUP,
			TokenShareRecipientType::class => IShare::TYPE_LINK,
			EmailShareRecipientType::class => IShare::TYPE_EMAIL,
			TeamShareRecipientType::class => IShare::TYPE_CIRCLE,
			default => throw new RuntimeException('Unsupported recipient type: ' . $recipientTypeClass),
		};
	}

	private function recipientToLegacySharedWith(ShareRecipient $recipient): string {
		if ($recipient->class === TokenShareRecipientType::class) {
			return $this->cloudIdManager->getCloudId($recipient->value, $recipient->instance)->getId();
		}

		return $recipient->value;
	}

	/**
	 * @return array{value: string, remote: string|null}
	 */
	private function splitLegacySharedWith(int $shareType, string $sharedWith): array {
		if ($shareType === IShare::TYPE_REMOTE || $shareType === IShare::TYPE_REMOTE_GROUP) {
			$cloudId = $this->cloudIdManager->resolveCloudId($sharedWith);
			return [
				'value' => $cloudId->getUser(),
				'remote' => $cloudId->getRemote(),
			];
		}

		return [
			'value' => $sharedWith,
			'remote' => null,
		];
	}

	/**
	 * @return array<class-string<ISharePropertyType>, ShareProperty>
	 */
	private function extractProperties(IShare $share): array {
		/** @var array<class-string<ISharePropertyType>, ShareProperty> $properties */
		$properties = [];

		if ($expire = $share->getExpirationDate()) {
			$properties[ExpirationDateSharePropertyType::class] = new ShareProperty(ExpirationDateSharePropertyType::class, $expire->format(DateTimeInterface::ATOM));
		}

		$password = $share->getPassword();
		if ($password !== null) {
			$properties[PasswordSharePropertyType::class] = new ShareProperty(PasswordSharePropertyType::class, $password);
		}

		if ($label = $share->getLabel()) {
			$properties[LabelSharePropertyType::class] = new ShareProperty(LabelSharePropertyType::class, $label);
		}

		if ($note = $share->getNote()) {
			$properties[NoteSharePropertyType::class] = new ShareProperty(NoteSharePropertyType::class, $note);
		}

		return $properties;
	}
}
