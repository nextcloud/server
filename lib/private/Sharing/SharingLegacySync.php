<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Sharing;

use Closure;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use NCU\Sharing\Event\SharesDeletedEvent;
use NCU\Sharing\Event\SharesUpdatedEvent;
use NCU\Sharing\Exception\ShareNotFoundException;
use NCU\Sharing\ISharingBackend;
use NCU\Sharing\ISharingManager;
use NCU\Sharing\ISharingRegistry;
use NCU\Sharing\Permission\ISharePermissionType;
use NCU\Sharing\Permission\SharePermission;
use NCU\Sharing\Property\ISharePropertyType;
use NCU\Sharing\Property\ShareProperty;
use NCU\Sharing\Recipient\IShareRecipientType;
use NCU\Sharing\Recipient\ShareRecipient;
use NCU\Sharing\Share;
use NCU\Sharing\ShareAccessContext;
use NCU\Sharing\ShareState;
use NCU\Sharing\ShareUser;
use NCU\Sharing\ShareUserStatus;
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
use OC\Share20\ShareAttributes;
use OCA\Files\Sharing\Permission\NodeCreateSharePermissionType;
use OCA\Files\Sharing\Permission\NodeDeleteSharePermissionType;
use OCA\Files\Sharing\Permission\NodeDownloadSharePermissionType;
use OCA\Files\Sharing\Permission\NodeReadSharePermissionType;
use OCA\Files\Sharing\Permission\NodeUpdateSharePermissionType;
use OCA\Files\Sharing\Property\NodeGridViewSharePropertyType;
use OCA\Files\Sharing\Property\NodeNicknameSharePropertyType;
use OCA\Files\Sharing\Source\NodeShareSourceType;
use OCA\Files\Sharing\SourceNodeTargetManager;
use OCP\Constants;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Federation\ICloudIdManager;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\Server;
use OCP\Share\Events\ShareAcceptedEvent;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\Events\ShareDeletedEvent;
use OCP\Share\Events\ShareDeletedFromSelfEvent;
use OCP\Share\Events\ShareMovedEvent;
use OCP\Share\Events\ShareRestoredEvent;
use OCP\Share\Events\ShareUpdatedEvent;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IAttributes;
use OCP\Share\IManager;
use OCP\Share\IProviderFactory;
use OCP\Share\IShare;
use OCP\Snowflake\ISnowflakeGenerator;
use RuntimeException;

/**
 * Incoming remote shares are completely ignored here, because they are already handled separately from the legacy sharing system.
 *
 * @template-implements IEventListener<ShareAcceptedEvent|ShareCreatedEvent|ShareDeletedEvent|ShareDeletedFromSelfEvent|ShareMovedEvent|ShareRestoredEvent|ShareUpdatedEvent|SharesUpdatedEvent|SharesDeletedEvent>
 */
final class SharingLegacySync implements IEventListener {
	private ?ISharingManager $sharingManager = null;

	private ?SourceNodeTargetManager $sourceNodeTargetManager = null;

	/** @var array<string, true> */
	private array $sharesInSync = [];

	// Only used for testing!
	private bool $ignoreAllEvents = false;

	private bool $ignoreNewLegacyShare = false;

	public function __construct(
		private readonly IManager $legacySharingManager,
		private readonly ISharingBackend $backend,
		private readonly IDBConnection $dbConnection,
		private readonly ISnowflakeGenerator $snowflakeGenerator,
		private readonly ICloudIdManager $cloudIdManager,
		private readonly LegacyMapper $legacyMapper,
		private readonly ISharingRegistry $sharingRegistry,
		private readonly IConfig $config,
		private readonly IMimeTypeLoader $mimeTypeLoader,
		private readonly IRootFolder $rootFolder,
		private readonly IProviderFactory $legacyShareProviderFactory,
	) {
	}

	private function getSharingManager(): ISharingManager {
		// Can't inject in constructor, as it leads to an infinite loop
		return $this->sharingManager ??= Server::get(ISharingManager::class);
	}

	private function getSourceNodeTargetManager(): SourceNodeTargetManager {
		// Class is not resolved when DI tries to construct this class.
		return $this->sourceNodeTargetManager ??= Server::get(SourceNodeTargetManager::class);
	}

	private function isValidationEnabled(): bool {
		return $this->config->getSystemValueBool('unified_sharing.legacy_sync.validation.enable');
	}

	private function isPublicLegacyShare(IShare $legacyShare): bool {
		return $legacyShare->getShareType() === IShare::TYPE_LINK || $legacyShare->getShareType() === IShare::TYPE_EMAIL;
	}

	private function isRemoteLegacyShare(IShare $legacyShare): bool {
		return $legacyShare->getShareType() === IShare::TYPE_REMOTE || $legacyShare->getShareType() === IShare::TYPE_REMOTE_GROUP;
	}

	#[\Override]
	public function handle(Event $event): void {
		if ($this->ignoreAllEvents) {
			return;
		}

		// TODO: Disable syncing if kill switch is enabled and truncate the unified sharing table to avoid the two systems getting out of sync.
		// TODO: Sync shares might trigger event listeners that modify the target shares (e.g. auto accept), but we ignore them here. We need to detect this case and then sync back the changes to the source shares. Let's pray no infinite loop get's started by this.

		if (
			$event instanceof ShareAcceptedEvent
			|| $event instanceof ShareCreatedEvent
			|| $event instanceof ShareDeletedEvent
			|| $event instanceof ShareDeletedFromSelfEvent
			|| $event instanceof ShareMovedEvent
			|| $event instanceof ShareRestoredEvent
			|| $event instanceof ShareUpdatedEvent
		) {
			$legacyShare = $event->getShare();
			$legacyProviderClass = $this->legacyShareProviderFactory->getProvider($legacyShare->getProviderId())::class;
			$legacyId = (int)$legacyShare->getId();
			$legacyMapping = $this->legacyMapper->getLegacyMappingByLegacyProviderClassAndId($legacyProviderClass, $legacyId);

			if ($event instanceof ShareDeletedEvent) {
				// If the legacy share was not mapped to a share, we can ignore it.
				if ($legacyMapping instanceof LegacyMapping) {
					$this->wrapSync($legacyMapping->id, function () use ($legacyMapping): void {
						$legacyMappings = $this->legacyMapper->getLegacyMappings($legacyMapping->id);
						// If this is the last mapping, delete the entire share.
						// If there are more mappings, only delete that mapping and then sync.
						if (count($legacyMappings) === 1) {
							try {
								$this->backend->deleteShare($legacyMapping->id);
							} catch (ShareNotFoundException) {
								// If the owner is deleted, the share was already deleted by the user recipient type through the manager, but we still receive this event from the legacy sharing manager.
							}

							$this->legacyMapper->deleteLegacyMappings($legacyMapping->id);
						} else {
							$this->legacyMapper->deleteLegacyMapping($legacyMapping->legacyProviderClass, $legacyMapping->legacyId);
							/** @var non-empty-list<LegacyMapping> $legacyMappings The list can't be empty, because there is at least one more legacy mapping than $legacyMapping */
							$legacyMappings = $this->legacyMapper->getLegacyMappings($legacyMapping->id);
							$this->syncLegacySharesToShare($legacyMapping->id, $legacyMappings);
						}
					});
				}
			} else {
				$id = null;
				if ($legacyMapping instanceof LegacyMapping) {
					$id = $legacyMapping->id;
					$this->wrapSync($id, function () use ($id): void {
						/** @var non-empty-list<LegacyMapping> $legacyMappings The list can't be empty, because $legacyMapping is not null */
						$legacyMappings = $this->legacyMapper->getLegacyMappings($id);
						$this->syncLegacySharesToShare($id, $legacyMappings);
					});
				} elseif (!$this->ignoreNewLegacyShare) {
					$id = $this->snowflakeGenerator->nextId(DateTimeImmutable::createFromMutable($legacyShare->getShareTime()));
					$this->wrapSync($id, function () use ($id, $legacyProviderClass, $legacyId, $legacyShare): void {
						$secrets = [];
						$value = null;
						if ($this->isPublicLegacyShare($legacyShare) || $this->isRemoteLegacyShare($legacyShare)) {
							$token = $legacyShare->getToken();
							if ($token === '') {
								throw new RuntimeException('Token must not be an empty string.');
							}

							$secrets = [$token];
						}

						if ($legacyShare->getShareType() === IShare::TYPE_LINK) {
							$value = $this->getSharingManager()->generateSecret();
						}

						if ($legacyShare->getAttributes()?->getAttribute('fileRequest', 'enabled') === true) {
							$emails = $legacyShare->getAttributes()?->getAttribute('shareWith', 'emails') ?? [];
							if (!is_array($emails) || !array_is_list($emails) || array_any($emails, static fn (mixed $value): bool => !is_string($value) || $value === '')) {
								throw new RuntimeException('The emails must be a list of non-empty strings.');
							}

							$token = $legacyShare->getToken();
							if ($token === '') {
								throw new RuntimeException('Token must not be an empty string.');
							}

							$secrets = [$token];
							$emailCount = count($emails);
							for ($i = 0; $i < $emailCount; ++$i) {
								$secrets[] = $this->getSharingManager()->generateSecret();
							}

							$value = $this->getSharingManager()->generateSecret();
						}

						if ($secrets === []) {
							$secrets = [$this->getSharingManager()->generateSecret()];
						}

						$value ??= $this->splitLegacySharedWith($legacyShare)['value'];

						$legacyMapping = $this->legacyMapper->createLegacyMapping(
							$id,
							$legacyProviderClass,
							$legacyId,
							new DateTimeImmutable(),
							$secrets,
							$value,
						);
						$this->syncLegacySharesToShare($id, [$legacyMapping]);
					});
				}

				if ($id !== null && $this->isValidationEnabled()) {
					$this->wrapSync($id, function () use ($id): void {
						$share = $this->getSharingManager()->getShare(new ShareAccessContext(overrideChecks: true), $id);
						$this->syncShareToLegacyShares($share);
					});

					if (!$event instanceof ShareDeletedFromSelfEvent) {
						$event->setShare($this->legacySharingManager->getShareById($legacyShare->getFullId(), onlyValid: false));
					}
				}
			}

			return;
		}

		if ($event instanceof SharesDeletedEvent) {
			foreach ($event->shareIds as $id) {
				$this->wrapSync($id, function () use ($id): void {
					$legacyMappings = $this->legacyMapper->getLegacyMappings($id);
					foreach ($legacyMappings as $legacyMapping) {
						try {
							$this->legacySharingManager->deleteShare($legacyMapping->getLegacyShare());
						} catch (ShareNotFound) {
						}
					}

					$this->legacyMapper->deleteLegacyMappings($id);
				});
			}

			return;
		}

		/** @psalm-suppress RedundantConditionGivenDocblockType */
		if ($event instanceof SharesUpdatedEvent) {
			foreach ($event->getShares() as $share) {
				$this->wrapSync($share->id, function () use ($share): void {
					// Only active shares are synced, everything else is dropped
					if ($share->state === ShareState::Active) {
						$this->syncShareToLegacyShares($share);
					} else {
						$legacyMappings = $this->legacyMapper->getLegacyMappings($share->id);
						foreach ($legacyMappings as $legacyMapping) {
							try {
								$this->legacySharingManager->deleteShare($legacyMapping->getLegacyShare());
							} catch (ShareNotFound) {
							}
						}

						$this->legacyMapper->deleteLegacyMappings($share->id);
					}
				});

				// This has no validation mode, because we don't have existing integration tests for it.
			}

			return;
		}
	}

	/**
	 * @param (Closure(): void) $callback
	 */
	private function wrapSync(string $id, Closure $callback): void {
		if (isset($this->sharesInSync[$id])) {
			return;
		}

		try {
			$this->sharesInSync[$id] = true;
			$this->dbConnection->beginTransaction();

			$callback();

			$this->dbConnection->commit();
			unset($this->sharesInSync[$id]);
		} catch (Exception $exception) {
			$this->dbConnection->rollBack();
			unset($this->sharesInSync[$id]);
			throw $exception;
		}
	}

	/**
	 * @param non-empty-string $id
	 * @param non-empty-list<LegacyMapping> $legacyMappings
	 */
	private function syncLegacySharesToShare(string $id, array $legacyMappings): void {
		// TODO: Instead of using the first legacy share for common attributes, use the mostly recently updated one. Then also update the common stuff for the other legacy shares?

		/** @var array<int, ShareSource> */
		$sources = [];
		/** @var array<class-string<IShareRecipientType>, array<string, ShareRecipient>> */
		$recipients = [];
		$legacyLastUpdated = null;

		/** @var list<IShare> $legacyShares */
		$legacyShares = [];
		foreach ($legacyMappings as $legacyMapping) {
			if ($legacyLastUpdated === null || $legacyLastUpdated->diff($legacyMapping->lastUpdated)->invert === 0) {
				$legacyLastUpdated = $legacyMapping->lastUpdated;
			}

			$legacyShare = $legacyMapping->getLegacyShare();
			$legacyShares[] = $legacyShare;
		}

		/** @var array<class-string<ISharePermissionType>, SharePermission> */
		$permissions = [];
		foreach ($legacyShares as $legacyShare) {
			// Only allow if both agree, to avoid mixups.
			$allowDownload = !$legacyShare->getHideDownload() && $legacyShare->getAttributes()?->getAttribute('permissions', 'download') !== false;
			// We want the sum of all enabled permissions.
			foreach ($this->legacyPermissionsToPermissions($legacyShare->getPermissions(), $allowDownload) as $permission) {
				if (!isset($permissions[$permission->class]) || !$permissions[$permission->class]->enabled) {
					$permissions[$permission->class] = $permission;
				}
			}
		}

		foreach ($legacyMappings as $legacyMapping) {
			// TODO: Don't refetch share
			$legacyShare = $legacyMapping->getLegacyShare();

			$nodeId = $legacyShare->getNodeId();
			$sources[$nodeId] ??= new ShareSource(NodeShareSourceType::class, (string)$nodeId);

			$shareInitiator = $this->splitLegacySharedByOrOwner($legacyShare->getSharedBy());
			if ($shareInitiator['value'] === '') {
				throw new RuntimeException('sharedBy value must not be an empty string.');
			}

			if ($shareInitiator['instance'] !== null) {
				// This should never happen, because incoming legacy remote shares are handled separately.
				throw new RuntimeException('Incoming remote shares are not supported.');
			}

			$initiatorUser = new ShareUser(
				$shareInitiator['value'],
				$shareInitiator['instance'],
			);

			// Only allow if both agree, to avoid mixups.
			$recipientAllowDownload = !$legacyShare->getHideDownload() && $legacyShare->getAttributes()?->getAttribute('permissions', 'download') !== false;
			// We only want to set those permissions that are masked and not already denied by the share itself.
			$recipientPermissions = array_filter($this->legacyPermissionsToPermissions($legacyShare->getPermissions(), $recipientAllowDownload), fn (SharePermission $permission): bool => !$permission->enabled && (!isset($permissions[$permission->class]) || $permissions[$permission->class]->enabled));

			if ($legacyShare->getAttributes()?->getAttribute('fileRequest', 'enabled') === true) {
				$emails = $legacyShare->getAttributes()?->getAttribute('shareWith', 'emails') ?? [];
				if (!is_array($emails) || !array_is_list($emails) || array_any($emails, static fn (mixed $value): bool => !is_string($value) || $value === '')) {
					throw new RuntimeException('The emails must be a list of non-empty strings.');
				}

				if (count($legacyMapping->recipientSecrets) !== count($emails) + 1) {
					throw new RuntimeException('The secrets count does not match.');
				}

				if ($legacyMapping->recipientValue === '') {
					throw new RuntimeException('Recipient value must not be an empty string.');
				}

				$recipients[TokenShareRecipientType::class] ??= [];
				$recipients[TokenShareRecipientType::class][$this->getLegacySharedWithUnique($legacyShare)] ??= new ShareRecipient(
					TokenShareRecipientType::class,
					$legacyMapping->recipientValue,
					null,
					// TODO: Make sure mapping secret is updated if token changes (for all mappings of the same recipient).
					$legacyMapping->recipientSecrets[0],
					$initiatorUser,
					$recipientPermissions,
				);

				/** @var list<non-empty-string> $emails */
				foreach ($emails as $index => $email) {
					$recipients[EmailShareRecipientType::class] ??= [];
					$recipients[EmailShareRecipientType::class][$email] ??= new ShareRecipient(
						EmailShareRecipientType::class,
						$email,
						null,
						// TODO: Make sure mapping secret is updated if token changes (for all mappings of the same recipient).
						$legacyMapping->recipientSecrets[$index + 1],
						$initiatorUser,
						$recipientPermissions,
					);
				}
			} else {
				$recipientTypeClass = $this->legacyShareTypeToRecipientTypeClass($legacyShare->getShareType(), $legacyShare->getAttributes());
				$isTokenRecipient = $recipientTypeClass === TokenShareRecipientType::class;
				$recipient = $isTokenRecipient ? ['value' => $legacyMapping->recipientValue, 'instance' => null] : $this->splitLegacySharedWith($legacyShare);
				$recipientValue = $recipient['value'];
				if ($recipientValue === '') {
					throw new RuntimeException('Recipient value must not be an empty string.');
				}

				$recipients[$recipientTypeClass] ??= [];
				$recipients[$recipientTypeClass][$this->getLegacySharedWithUnique($legacyShare)] ??= new ShareRecipient(
					$recipientTypeClass,
					$recipientValue,
					$recipient['instance'],
					// TODO: Make sure mapping secret is updated if token changes (for all mappings of the same recipient).
					$legacyMapping->recipientSecrets[0],
					$initiatorUser,
					$recipientPermissions,
				);
			}
		}

		$shareOwner = $this->splitLegacySharedByOrOwner($legacyShares[0]->getShareOwner());
		if ($shareOwner['value'] === '') {
			throw new RuntimeException('shareOwner value must not be an empty string.');
		}

		if ($shareOwner['instance'] !== null) {
			// This should never happen, because incoming legacy remote shares are handled separately.
			throw new RuntimeException('Incoming remote shares are not supported.');
		}

		$owner = new ShareUser(
			$shareOwner['value'],
			$shareOwner['instance'],
		);

		$password = $legacyShares[0]->getPassword();
		if ($password !== null && !$legacyShare->isPasswordHashed()) {
			throw new RuntimeException('The password must be hashed already.');
		}

		$properties = [
			new ShareProperty(ExpirationDateSharePropertyType::class, $legacyShares[0]->getExpirationDate()?->format(DateTimeInterface::ATOM)),
			new ShareProperty(PasswordSharePropertyType::class, $password),
			new ShareProperty(LabelSharePropertyType::class, ($label = $legacyShares[0]->getLabel()) !== '' ? $label : null),
			new ShareProperty(NoteSharePropertyType::class, ($note = $legacyShares[0]->getNote()) !== '' ? $note : null),
			new ShareProperty(NodeGridViewSharePropertyType::class, $legacyShares[0]->getAttributes()?->getAttribute('config', 'grid_view') === true ? 'true' : 'false'),
			new ShareProperty(NodeNicknameSharePropertyType::class, $legacyShares[0]->getAttributes()?->getAttribute('fileRequest', 'enabled') === true ? 'true' : 'false'),
		];

		// To avoid diffing the shares, we just delete and create it.
		try {
			$this->backend->deleteShare($id);
		} catch (ShareNotFoundException) {
		}

		$this->createShare($legacyShares, new Share(
			$id,
			$owner,
			$legacyLastUpdated,
			// Legacy shares are always active
			ShareState::Active,
			null,
			array_values($sources),
			array_values(array_merge(...array_values($recipients))),
			array_combine(array_map(static fn (ShareProperty $property): string => $property->class, $properties), $properties),
			array_combine(array_map(static fn (SharePermission $permission): string => $permission->class, $permissions), $permissions),
		));
	}

	/**
	 * @param list<IShare> $legacyShares
	 */
	private function createShare(array $legacyShares, Share $share): Share {
		$this->backend->createShare($share->id, $share->owner, $share->lastUpdated);
		$this->backend->updateShareState($share->id, $share->state);

		foreach ($share->sources as $source) {
			$this->backend->addShareSource($share->id, $source);
		}

		foreach ($share->recipients as $recipient) {
			$this->backend->addShareRecipient($share->id, $recipient);

			foreach ($recipient->permissions as $permission) {
				$this->backend->updateShareRecipientPermission($share->id, $recipient, $permission);
			}
		}

		foreach ($share->properties as $property) {
			$this->backend->updateShareProperty($share->id, $property);
		}

		foreach ($share->permissions as $permission) {
			$this->backend->updateSharePermission($share->id, $permission);
		}

		foreach ($legacyShares as $legacyShare) {
			/** {@see IShare::TYPE_USERGROUP} shares are never handled directly here */
			if ($legacyShare->getShareType() === IShare::TYPE_USER) {
				$legacySharedWith = $legacyShare->getSharedWith();
				if ($legacySharedWith === '') {
					throw new RuntimeException('Shared with must be a non-empty string.');
				}

				$this->backend->updateShareUserStatus($share->id, $legacySharedWith, $this->legacyUserStatusToUserStatus($legacyShare->getStatus() ?? IShare::STATUS_PENDING));
				$this->getSourceNodeTargetManager()->setTarget($legacySharedWith, $share->owner, $legacyShare->getNode(), $legacyShare->getTarget());
			} elseif ($legacyShare->getShareType() === IShare::TYPE_GROUP) {
				$qb = $this->dbConnection->getQueryBuilder();
				$result = $qb
					->select('share_with', 'accepted', 'file_target', 'permissions')
					->from('share')
					->where($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_USERGROUP)))
					->andWhere($qb->expr()->eq('parent', $qb->createNamedParameter($legacyShare->getId(), IQueryBuilder::PARAM_INT)))
					->executeQuery();

				/** @var list<array{share_with: non-empty-string, accepted: IShare::STATUS_*|string, file_target: string, permissions: int}> $rows */
				$rows = $result->fetchAllAssociative();
				foreach ($rows as $row) {
					if ($row['permissions'] === 0) {
						$this->backend->updateShareUserStatus($share->id, $row['share_with'], ShareUserStatus::Rejected);
					} else {
						/** @psalm-suppress ArgumentTypeCoercion */
						$this->backend->updateShareUserStatus($share->id, $row['share_with'], $this->legacyUserStatusToUserStatus((int)$row['accepted']));
					}

					$this->getSourceNodeTargetManager()->setTarget($row['share_with'], $share->owner, $legacyShare->getNode(), $row['file_target']);
				}
			}
		}

		[$share] = $this->backend->ensureDefaults([$share]);

		return $share;
	}

	private function syncShareToLegacyShares(Share $share): void {
		if ($share->owner->instance !== null) {
			// We can't save them, because they are handled separately in the legacy sharing system.
			return;
		}

		/** @var array<IShare::TYPE_*, array<string, array<int, LegacyMapping>>> $legacyMappings */
		$legacyMappings = [];
		/** @var array<string, IShare> */
		$oldLegacyShares = [];
		foreach ($this->legacyMapper->getLegacyMappings($share->id) as $legacyMapping) {
			$legacyShare = $legacyMapping->getLegacyShare();

			$legacyShareType = $legacyShare->getShareType();
			$legacySharedWithUnique = $this->getLegacySharedWithUnique($legacyShare);

			$legacyMappings[$legacyShareType] ??= [];
			$legacyMappings[$legacyShareType][$legacySharedWithUnique] ??= [];
			$legacyMappings[$legacyShareType][$legacySharedWithUnique][$legacyShare->getNodeId()] = $legacyMapping;

			$oldLegacyShares[$legacyShare->getFullId()] = $legacyShare;
		}

		if ($share->recipients === []) {
			return;
		}

		/** @var array<string, IShare> */
		$newLegacyShares = [];
		foreach ($share->sources as $source) {
			if ($source->class !== NodeShareSourceType::class) {
				continue;
			}

			if (($share->properties[NodeNicknameSharePropertyType::class] ?? null)?->value === 'true') {
				$tokenRecipient = null;
				$emailRecipients = [];
				foreach ($share->recipients as $recipient) {
					if ($recipient->class === TokenShareRecipientType::class) {
						if ($tokenRecipient !== null) {
							throw new RuntimeException('More than one token recipient not allowed.');
						}

						$tokenRecipient = $recipient;
						continue;
					}

					if ($recipient->class === EmailShareRecipientType::class) {
						$emailRecipients[] = $recipient;
						continue;
					}

					throw new RuntimeException('Recipient type that is not a token or email: ' . $recipient->class);
				}

				if ($tokenRecipient === null) {
					throw new RuntimeException('No token recipient.');
				}

				if ($tokenRecipient->initiator === null) {
					throw new RuntimeException('Token recipient initiator cannot be null at this point.');
				}

				if ($tokenRecipient->secret === null) {
					throw new RuntimeException('Token recipient secret cannot be null at this point.');
				}

				$attributes = new ShareAttributes();

				// Create a new object to avoid copying existing attributes.
				$legacyShare = $this->legacySharingManager->newShare();
				$legacyShare->setShareType(IShare::TYPE_EMAIL);
				$legacyShare->setSharedWith('');

				$attributes->setAttribute('shareWith', 'emails', array_map(static fn (ShareRecipient $recipient): string => $recipient->value, $emailRecipients));

				$legacyShare->setSharedBy($this->shareUserToLegacyIdentifier($tokenRecipient->initiator));

				// Make sure the token recipient is always the first one, so we can correctly assign back the secrets later.
				$orderedRecipients = array_merge([$tokenRecipient], $emailRecipients);
				$orderedRecipientSecrets = array_map(function (ShareRecipient $recipient): string {
					if ($recipient->secret === null) {
						throw new RuntimeException('The secret must not be null.');
					}

					return $recipient->secret;
				}, $orderedRecipients);

				$legacyShare->setToken($this->isPublicLegacyShare($legacyShare) ? $tokenRecipient->secret : '');

				$legacyShare->setStatus(IShare::STATUS_PENDING);

				$legacyShare->setAttributes($attributes);

				$this->setCommonLegacyShareFields($share, $source, $tokenRecipient, $legacyShare);

				$legacyShare = $this->createOrUpdateLegacyShare($share, $legacyMappings, $legacyShare, $tokenRecipient->value, $orderedRecipientSecrets);

				$newLegacyShares[$legacyShare->getFullId()] = $legacyShare;
			} else {
				$ownerUserFolder = null;
				$sourceNode = null;

				foreach ($share->recipients as $recipient) {
					/** {@see IShare::TYPE_USERGROUP} shares are handled automatically by the DefaultShareProvider. */
					/** @psalm-suppress ArgumentTypeCoercion Psalm gets confused with the properties keys */
					$legacyShareType = $this->recipientTypeClassToLegacyShareType($recipient->class, $recipient->instance, $share->properties);
					if ($legacyShareType === null) {
						continue;
					}

					// Create a new object to avoid copying existing attributes.
					$legacyShare = $this->legacySharingManager->newShare();
					$legacyShare->setShareType($legacyShareType);

					/** @psalm-suppress ArgumentTypeCoercion Psalm gets confused with the properties keys */
					if (($legacySharedBy = $this->shareRecipientToLegacyIdentifier($recipient, $share->properties)) !== null) {
						$legacyShare->setSharedWith($legacySharedBy);
					}

					if ($recipient->initiator === null) {
						throw new RuntimeException('Share recipient initiator cannot be null at this point.');
					}

					$legacyShare->setSharedBy($this->shareUserToLegacyIdentifier($recipient->initiator));

					$recipientSecret = $recipient->secret;
					if ($recipientSecret === null) {
						throw new RuntimeException('secret must be set.');
					}

					$legacyShare->setToken($this->isPublicLegacyShare($legacyShare) || $this->isRemoteLegacyShare($legacyShare) ? $recipientSecret : '');

					$legacyShare->setStatus(
						$this->userStatusToLegacyUserStatus(
							(
								$recipient->class === UserShareRecipientType::class && $recipient->instance === null
								? ($this->backend->getUserStatuses($share->id, [$recipient->value])[$recipient->value] ?? null)
								: null
							) ?? ShareUserStatus::Pending
						)
					);

					if ($recipient->class === UserShareRecipientType::class && $recipient->instance === null) {
						$ownerUserFolder ??= $this->rootFolder->getUserFolder($share->owner->userId);
						$sourceNode ??= $ownerUserFolder->getFirstNodeById((int)$source->value);
						if (!$sourceNode instanceof Node) {
							throw new NotFoundException();
						}

						$target = $this->getSourceNodeTargetManager()->getTarget($recipient->value, $share->owner, $sourceNode) ?? $this->getSourceNodeTargetManager()->createDefaultTarget($recipient->value, $share->owner, $sourceNode);
						$legacyShare->setTarget($target);
					}

					$this->setCommonLegacyShareFields($share, $source, $recipient, $legacyShare);

					$legacyShare = $this->createOrUpdateLegacyShare($share, $legacyMappings, $legacyShare, $recipient->value, [$recipientSecret]);

					// Child shares might automatically get accepted, so we need to update their user status.
					// We don't need to update any legacy share objects, because the group child shares are handled completely outside of this sync (which is the reason why we're doing the following directly in the DB).
					if ($recipient->class === GroupShareRecipientType::class) {
						if (($recipientType = $this->sharingRegistry->getRecipientTypes()[$recipient->class] ?? null) === null) {
							throw new RuntimeException('The recipient type is not registered: ' . $recipient->class);
						}

						$ownerUserFolder ??= $this->rootFolder->getUserFolder($share->owner->userId);
						$sourceNode ??= $ownerUserFolder->getFirstNodeById((int)$source->value);
						if (!$sourceNode instanceof Node) {
							throw new NotFoundException();
						}

						$userIds = array_map(static fn (IUser $user): string => $user->getUID(), $recipientType->getUsers($recipient->value));

						$userIdsByUserStatus = [];
						foreach ($this->backend->getUserStatuses($share->id, $userIds) as $userId => $userStatus) {
							$userIdsByUserStatus[$userStatus->value] ??= [];
							$userIdsByUserStatus[$userStatus->value][] = $userId;
						}

						foreach ($userIdsByUserStatus as $userStatus => $ids) {
							foreach (array_chunk($ids, 1000) as $chunk) {
								$qb = $this->dbConnection->getQueryBuilder();
								$qb
									->update('share')
									->where($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_USERGROUP, IQueryBuilder::PARAM_INT)))
									->andWhere($qb->expr()->eq('parent', $qb->createNamedParameter((int)$legacyShare->getId(), IQueryBuilder::PARAM_INT)))
									->andWhere($qb->expr()->in('share_with', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_STR_ARRAY)));
								if ($userStatus === ShareUserStatus::Rejected->value) {
									$qb
										->set('permissions', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT));
								} else {
									$qb
										->set('accepted', $qb->createNamedParameter($this->userStatusToLegacyUserStatus(ShareUserStatus::from($userStatus)), IQueryBuilder::PARAM_INT));
								}

								$qb->executeStatement();
							}
						}

						$userIdsByTarget = [];
						foreach ($this->getSourceNodeTargetManager()->getTargets($userIds, $share->owner, $sourceNode) as $userId => $target) {
							$userIdsByTarget[$target] ??= [];
							$userIdsByTarget[$target][] = $userId;
						}

						foreach ($userIdsByTarget as $target => $ids) {
							foreach (array_chunk($ids, 1000) as $chunk) {
								$qb = $this->dbConnection->getQueryBuilder();
								$qb
									->update('share')
									->set('file_target', $qb->createNamedParameter($target))
									->where($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_USERGROUP, IQueryBuilder::PARAM_INT)))
									->andWhere($qb->expr()->eq('parent', $qb->createNamedParameter((int)$legacyShare->getId(), IQueryBuilder::PARAM_INT)))
									->andWhere($qb->expr()->in('share_with', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_STR_ARRAY)))
									->executeStatement();
							}
						}
					}

					$newLegacyShares[$legacyShare->getFullId()] = $legacyShare;
				}
			}
		}

		foreach (array_diff(array_keys($oldLegacyShares), array_keys($newLegacyShares)) as $invalidLegacyShareId) {
			$this->legacySharingManager->deleteShare($oldLegacyShares[$invalidLegacyShareId]);
		}

		foreach ($newLegacyShares as $legacyShare) {
			$this->legacySharingManager->updateShare($legacyShare);
		}
	}

	private function setCommonLegacyShareFields(Share $share, ShareSource $source, ShareRecipient $recipient, IShare $legacyShare): void {
		$attributes = $legacyShare->getAttributes() ?? new ShareAttributes();

		$legacyShare->setNodeId((int)$source->value);

		$legacyShare->setShareTime(DateTime::createFromImmutable($share->getCreatedAt()));

		[$permissions, $allowDownload] = $this->permissionsToLegacyPermissions($source, $share->getEffectiveEnabledPermissionsForRecipients([$recipient]));
		$legacyShare->setPermissions($permissions);

		// Always set both, to avoid mixups.
		$attributes->setAttribute('permissions', 'download', $allowDownload);
		$legacyShare->setHideDownload(!$allowDownload);

		if (($share->properties[NodeNicknameSharePropertyType::class] ?? null)?->value === 'true') {
			$attributes->setAttribute('fileRequest', 'enabled', true);
		}

		$legacyShare->setShareOwner($this->shareUserToLegacyIdentifier($share->owner));

		$legacyShare->setNote(($share->properties[NoteSharePropertyType::class] ?? null)?->value ?? '');

		$legacyShare->setLabel($this->isPublicLegacyShare($legacyShare) ? ($share->properties[LabelSharePropertyType::class] ?? null)?->value ?? '' : '');

		if (($share->properties[NodeGridViewSharePropertyType::class] ?? null)?->value === 'true') {
			$attributes->setAttribute('config', 'grid_view', true);
		}

		$expirationDate = ($share->properties[ExpirationDateSharePropertyType::class] ?? null)?->value;
		$expirationDate = $expirationDate !== null ? DateTime::createFromFormat(DateTimeInterface::ATOM, $expirationDate) : null;
		if ($expirationDate === false) {
			throw new RuntimeException('Failed to parse expiration date.');
		}

		$legacyShare->setExpirationDate($expirationDate);
		// We don't call setNoExpirationDate, because the value isn't actually saved

		if (($passwordHash = ($share->properties[PasswordSharePropertyType::class] ?? null)?->value) !== null) {
			$legacyShare->setPasswordHash($passwordHash);
		}

		$legacyShare->setAttributes($attributes);
	}

	/**
	 * @param array<IShare::TYPE_*, array<string, array<int, LegacyMapping>>> $legacyMappings
	 * @param non-empty-string $recipientValue
	 * @param non-empty-list<non-empty-string> $recipientSecrets
	 */
	private function createOrUpdateLegacyShare(Share $share, array $legacyMappings, IShare $legacyShare, string $recipientValue, array $recipientSecrets): IShare {
		if (($legacyMapping = $legacyMappings[$legacyShare->getShareType()][$this->getLegacySharedWithUnique($legacyShare)][$legacyShare->getNodeId()] ?? null) !== null) {
			$oldLegacyShare = $legacyMapping->getLegacyShare();

			$legacyShare->setId($oldLegacyShare->getId());
			$legacyShare->setProviderId($oldLegacyShare->getProviderId());

			$this->legacyMapper->updateLegacyMapping(new LegacyMapping(
				$legacyMapping->id,
				$legacyMapping->legacyProviderClass,
				$legacyMapping->legacyId,
				$share->lastUpdated,
				$legacyMapping->recipientSecrets,
				$legacyMapping->recipientValue,
			));
		} else {
			try {
				// We need to create the legacy mapping ourselves to control the share id, so we disable the automapping of new legacy shares.
				$this->ignoreNewLegacyShare = true;
				// This is the only way we can get a provider and id assigned.
				$legacyShare = $this->legacySharingManager->createShare($legacyShare);
			} catch (Exception $exception) {
				throw $exception;
			} finally {
				$this->ignoreNewLegacyShare = false;
			}

			$this->legacyMapper->createLegacyMapping(
				$share->id,
				$this->legacyShareProviderFactory->getProvider($legacyShare->getProviderId())::class,
				(int)$legacyShare->getId(),
				$share->lastUpdated,
				$recipientSecrets,
				$recipientValue,
			);
		}

		return $legacyShare;
	}

	/**
	 * @return IShare::STATUS_*
	 */
	private function userStatusToLegacyUserStatus(ShareUserStatus $userStatus): int {
		return match ($userStatus) {
			ShareUserStatus::Accepted => IShare::STATUS_ACCEPTED,
			ShareUserStatus::Pending => IShare::STATUS_PENDING,
			ShareUserStatus::Rejected => IShare::STATUS_REJECTED,
		};
	}

	/**
	 * @param IShare::STATUS_* $legacyUserStatus
	 */
	private function legacyUserStatusToUserStatus(int $legacyUserStatus): ShareUserStatus {
		return match ($legacyUserStatus) {
			IShare::STATUS_ACCEPTED => ShareUserStatus::Accepted,
			IShare::STATUS_PENDING => ShareUserStatus::Pending,
			IShare::STATUS_REJECTED => ShareUserStatus::Rejected,
		};
	}

	/**
	 * @param list<class-string<ISharePermissionType>> $permissions
	 * @return array{int-mask-of<Constants::PERMISSION_*>, bool}
	 */
	private function permissionsToLegacyPermissions(ShareSource $source, array $permissions): array {
		$qb = $this->dbConnection->getQueryBuilder();
		$result = $qb
			->select('mimetype')
			->from('filecache')
			->where($qb->expr()->eq('fileid', $qb->createNamedParameter((int)$source->value, IQueryBuilder::PARAM_INT)))
			->executeQuery();

		/** @var int|string|false */
		$mimeTypeId = $result->fetchOne();
		if ($mimeTypeId === false) {
			throw new RuntimeException('Share source does not exist: ' . $source->value);
		}

		$nodeIsFile = $this->mimeTypeLoader->getMimetypeById((int)$mimeTypeId) !== 'httpd/unix-directory';

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
	 * @return array<class-string<ISharePermissionType>, SharePermission>
	 */
	private function legacyPermissionsToPermissions(int $legacyPermissions, bool $allowDownload): array {
		/** @var array<class-string<ISharePermissionType>, SharePermission> $permissions */
		$permissions = [];

		foreach ([
			NodeReadSharePermissionType::class => Constants::PERMISSION_READ,
			NodeUpdateSharePermissionType::class => Constants::PERMISSION_UPDATE,
			NodeCreateSharePermissionType::class => Constants::PERMISSION_CREATE,
			NodeDeleteSharePermissionType::class => Constants::PERMISSION_DELETE,
			ReshareSharePermissionType::class => Constants::PERMISSION_SHARE,
		] as $permissionTypeClass => $mask) {
			$permissions[$permissionTypeClass] = new SharePermission($permissionTypeClass, ($legacyPermissions & $mask) === $mask);
		}

		$permissions[NodeDownloadSharePermissionType::class] = new SharePermission(NodeDownloadSharePermissionType::class, $allowDownload);

		return $permissions;
	}

	/**
	 * @param array<class-string<ISharePropertyType>, ShareProperty> $shareProperties
	 */
	private function shareRecipientToLegacyIdentifier(ShareRecipient $recipient, array $shareProperties): ?string {
		if (($shareProperties[NodeNicknameSharePropertyType::class] ?? null)?->value === 'true') {
			return '';
		}

		if ($recipient->instance !== null) {
			$cloudId = $this->cloudIdManager->getCloudId($recipient->value, $recipient->instance);
			return $cloudId->getUser() . '@' . $cloudId->getRemote();
		}

		if ($recipient->class === TokenShareRecipientType::class) {
			return null;
		}

		return $recipient->value;
	}

	private function shareUserToLegacyIdentifier(ShareUser $user): string {
		if ($user->instance !== null) {
			$cloudId = $this->cloudIdManager->getCloudId($user->userId, $user->instance);
			return $cloudId->getUser() . '@' . $cloudId->getRemote();
		}

		return $user->userId;
	}

	/**
	 * @param class-string<IShareRecipientType> $recipientTypeClass
	 * @param array<class-string<ISharePropertyType>, ShareProperty> $shareProperties
	 * @return ?IShare::TYPE_*
	 */
	private function recipientTypeClassToLegacyShareType(string $recipientTypeClass, ?string $recipientInstance, array $shareProperties): ?int {
		if (($shareProperties[NodeNicknameSharePropertyType::class] ?? null)?->value === 'true') {
			return IShare::TYPE_EMAIL;
		}

		return match ($recipientTypeClass) {
			UserShareRecipientType::class => $recipientInstance === null ? IShare::TYPE_USER : IShare::TYPE_REMOTE,
			GroupShareRecipientType::class => $recipientInstance === null ? IShare::TYPE_GROUP : IShare::TYPE_REMOTE_GROUP,
			TokenShareRecipientType::class => IShare::TYPE_LINK,
			EmailShareRecipientType::class => IShare::TYPE_EMAIL,
			TeamShareRecipientType::class => IShare::TYPE_CIRCLE,
			// TODO talk, deck
			default => null,
		};
	}

	/**
	 * @param IShare::TYPE_* $legacyShareType
	 * @return class-string<IShareRecipientType>
	 */
	private function legacyShareTypeToRecipientTypeClass(int $legacyShareType, ?IAttributes $attributes): string {
		if ($attributes?->getAttribute('fileRequest', 'enabled') === true) {
			return TokenShareRecipientType::class;
		}

		return match ($legacyShareType) {
			IShare::TYPE_USER, IShare::TYPE_REMOTE => UserShareRecipientType::class,
			IShare::TYPE_GROUP, IShare::TYPE_REMOTE_GROUP => GroupShareRecipientType::class,
			IShare::TYPE_LINK => TokenShareRecipientType::class,
			IShare::TYPE_EMAIL => EmailShareRecipientType::class,
			IShare::TYPE_CIRCLE => TeamShareRecipientType::class,
			// TODO talk, deck
			default => throw new RuntimeException('Unsupported legacy share type: ' . $legacyShareType),
		};
	}

	/**
	 * @return array{value: string, instance: non-empty-string|null}
	 */
	private function splitLegacySharedWith(IShare $legacyShare): array {
		if ($legacyShare->getShareType() === IShare::TYPE_REMOTE || $legacyShare->getShareType() === IShare::TYPE_REMOTE_GROUP) {
			$cloudId = $this->cloudIdManager->resolveCloudId($legacyShare->getSharedWith());

			$user = $cloudId->getUser();
			if ($user === '') {
				throw new RuntimeException('User must not be an empty string.');
			}

			$remote = $cloudId->getRemote();
			if ($remote === '') {
				throw new RuntimeException('Remote must not be an empty string.');
			}

			return [
				'value' => $user,
				'instance' => $remote,
			];
		}

		return [
			// This can be an empty string, in case it is a file request
			'value' => $legacyShare->getSharedWith(),
			'instance' => null,
		];
	}

	/**
	 * @return array{value: string, instance: non-empty-string|null}
	 */
	private function splitLegacySharedByOrOwner(string $sharedByOrOwner): array {
		// The type of the legacy share is only for the sharedWith, but we also can't have an email here, so we just have to check if it is a Cloud ID.
		if ($this->cloudIdManager->isValidCloudId($sharedByOrOwner)) {
			$cloudId = $this->cloudIdManager->resolveCloudId($sharedByOrOwner);

			$user = $cloudId->getUser();
			if ($user === '') {
				throw new RuntimeException('User must not be an empty string.');
			}

			$remote = $cloudId->getRemote();
			if ($remote === '') {
				throw new RuntimeException('Remote must not be an empty string.');
			}

			return [
				'value' => $user,
				'instance' => $remote,
			];
		}

		return [
			'value' => $sharedByOrOwner,
			'instance' => null,
		];
	}

	private function getLegacySharedWithUnique(IShare $legacyShare): string {
		return $this->isPublicLegacyShare($legacyShare) ? $legacyShare->getToken() : $legacyShare->getSharedWith();
	}
}
