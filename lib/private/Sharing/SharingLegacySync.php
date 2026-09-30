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
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Federation\ICloudIdManager;
use OCP\Files\IMimeTypeLoader;
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
use OCP\Share\IShare;
use OCP\Snowflake\ISnowflakeGenerator;
use RuntimeException;

/**
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
		private readonly IEventDispatcher $eventDispatcher,
		private readonly IManager $legacySharingManager,
		private readonly ISharingBackend $backend,
		private readonly IDBConnection $dbConnection,
		private readonly ISnowflakeGenerator $snowflakeGenerator,
		private readonly ICloudIdManager $cloudIdManager,
		private readonly LegacyMapper $legacyMapper,
		private readonly ISharingRegistry $sharingRegistry,
		private readonly IConfig $config,
		private readonly IMimeTypeLoader $mimeTypeLoader,
	) {
		$this->eventDispatcher->addServiceListener(ShareAcceptedEvent::class, self::class);
		$this->eventDispatcher->addServiceListener(ShareCreatedEvent::class, self::class);
		$this->eventDispatcher->addServiceListener(ShareDeletedEvent::class, self::class);
		$this->eventDispatcher->addServiceListener(ShareDeletedFromSelfEvent::class, self::class);
		$this->eventDispatcher->addServiceListener(ShareMovedEvent::class, self::class);
		$this->eventDispatcher->addServiceListener(ShareRestoredEvent::class, self::class);
		$this->eventDispatcher->addServiceListener(ShareUpdatedEvent::class, self::class);
		$this->eventDispatcher->addServiceListener(SharesUpdatedEvent::class, self::class);
		$this->eventDispatcher->addServiceListener(SharesDeletedEvent::class, self::class);
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

	// TODO: Test
	public function mapLegacyShares(?IUser $user, ?int $limit = null): void {
		// TODO: Make it work with all providers (deck, talk, mail, external etc.)
		$provider = 'ocinternal';
		// TODO: Filter by user
		$qb = $this->dbConnection->getQueryBuilder();
		$result = $qb
			->select('s.id', 's.stime')
			->from('share', 's')
			->leftJoin('s', 'sharing_share_legacy_mapping', 'l', $qb->expr()->eq('s.id', 'l.legacy_id'))
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

		/** @var list<array{id: int, stime: int}> $rows */
		$rows = $result->fetchAll();
		foreach ($rows as $row) {
			$id = $this->snowflakeGenerator->nextId(DateTimeImmutable::createFromMutable((new DateTime())->setTimestamp($row['stime'])));
			$legacyMapping = $this->legacyMapper->createLegacyMapping(
				$id,
				$provider,
				$row['id'],
				new DateTimeImmutable(),
				$this->getSharingManager()->generateSecret(),
				$this->getSharingManager()->generateSecret(),
			);

			$this->syncLegacySharesToShare($id, [$legacyMapping]);
		}
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
			$legacyProvider = $legacyShare->getProviderId();
			$legacyId = (int)$legacyShare->getId();
			$legacyMapping = $this->legacyMapper->getLegacyMappingByLegacyProviderAndId($legacyProvider, $legacyId);

			if ($event instanceof ShareDeletedEvent) {
				// If the legacy share was not mapped to a share, we can ignore it.
				if ($legacyMapping instanceof LegacyMapping) {
					$this->wrapSync($legacyMapping->id, function () use ($legacyMapping): void {
						try {
							$this->backend->deleteShare($legacyMapping->id);
						} catch (ShareNotFoundException) {
							// If the owner is deleted, the share was already deleted by the user recipient type through the manager, but we still receive this event from the legacy sharing manager.
						}

						$this->legacyMapper->deleteLegacyMappings($legacyMapping->id);
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
					$this->wrapSync($id, function () use ($id, $legacyProvider, $legacyId, $legacyShare): void {
						$secret = null;
						$value = null;
						if ($this->isPublicLegacyShare($legacyShare)) {
							$secret = $legacyShare->getToken();
							if ($secret === '') {
								throw new RuntimeException('Token must not be an empty string.');
							}
						}

						if ($legacyShare->getShareType() === IShare::TYPE_LINK) {
							$value = $this->getSharingManager()->generateSecret();
						}

						if ($legacyShare->getAttributes()?->getAttribute('fileRequest', 'enabled') === true) {
							$emails = $legacyShare->getAttributes()?->getAttribute('shareWith', 'emails') ?? [];
							if (!is_array($emails) || !array_is_list($emails) || array_any($emails, static fn (mixed $value): bool => !is_string($value) || $value === '')) {
								throw new RuntimeException('The emails must be a list of non-empty strings.');
							}

							$secrets = [$legacyShare->getToken()];
							$emailCount = count($emails);
							for ($i = 0; $i < $emailCount; ++$i) {
								$secrets[] = $this->getSharingManager()->generateSecret();
							}

							$secret = implode('|', $secrets);

							$value = $this->getSharingManager()->generateSecret();
						}

						$secret ??= $this->getSharingManager()->generateSecret();
						$value ??= $this->splitLegacySharedWith($legacyShare)['value'];

						$legacyMapping = $this->legacyMapper->createLegacyMapping(
							$id,
							$legacyProvider,
							$legacyId,
							new DateTimeImmutable(),
							$secret,
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
							$this->legacySharingManager->deleteShare($legacyMapping->getLegacyShare($this->legacySharingManager));
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
			foreach ($event->shareIds as $id) {
				$this->wrapSync($id, function () use ($id): void {
					$share = $this->getSharingManager()->getShare(new ShareAccessContext(overrideChecks: true), $id);
					// Only active shares are synced, everything else is dropped
					if ($share->state === ShareState::Active) {
						$this->syncShareToLegacyShares($share);
					} else {
						$legacyMappings = $this->legacyMapper->getLegacyMappings($id);
						foreach ($legacyMappings as $legacyMapping) {
							try {
								$this->legacySharingManager->deleteShare($legacyMapping->getLegacyShare($this->legacySharingManager));
							} catch (ShareNotFound) {
							}
						}

						$this->legacyMapper->deleteLegacyMappings($id);
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

			$legacyShare = $legacyMapping->getLegacyShare($this->legacySharingManager);
			$legacyShares[] = $legacyShare;

			$nodeId = $legacyShare->getNodeId();
			$sources[$nodeId] ??= new ShareSource(NodeShareSourceType::class, (string)$nodeId);

			$sharedBy = $legacyShare->getSharedBy();
			if ($sharedBy === '') {
				throw new RuntimeException('Shared by must not be an empty string.');
			}

			if ($legacyShare->getAttributes()?->getAttribute('fileRequest', 'enabled') === true) {
				$emails = $legacyShare->getAttributes()?->getAttribute('shareWith', 'emails') ?? [];
				if (!is_array($emails) || !array_is_list($emails) || array_any($emails, static fn (mixed $value): bool => !is_string($value) || $value === '')) {
					throw new RuntimeException('The emails must be a list of non-empty strings.');
				}

				/** @var list<non-empty-string> $secrets */
				$secrets = explode('|', $legacyMapping->recipientSecret);
				if (count($secrets) !== count($emails) + 1) {
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
					$secrets[0],
					new ShareUser(
						$sharedBy,
						// TODO: Incoming remote shares aren't handled by this
						null,
					),
				);

				/** @var list<non-empty-string> $emails */
				foreach ($emails as $index => $email) {
					$recipients[EmailShareRecipientType::class] ??= [];
					$recipients[EmailShareRecipientType::class][$email] ??= new ShareRecipient(
						EmailShareRecipientType::class,
						$email,
						null,
						// TODO: Make sure mapping secret is updated if token changes (for all mappings of the same recipient).
						$secrets[$index + 1],
						new ShareUser(
							$sharedBy,
							// TODO: Incoming remote shares aren't handled by this
							null,
						),
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
					$legacyMapping->recipientSecret,
					new ShareUser(
						$sharedBy,
						// TODO: Incoming remote shares aren't handled by this
						null,
					),
				);
			}
		}

		if (!$this->checkAllSame($legacyShares, fn (IShare $share) => $share->getShareOwner())) {
			throw new RuntimeException("All legacy shares sharing a share id don't have the same owner");
		}

		/** @psalm-suppress ArgumentTypeCoercion */
		$owner = new ShareUser(
			$legacyShares[0]->getShareOwner(),
			// Incoming remote shares aren't handled by this
			null,
		);

		if (!$this->checkAllSame($legacyShares, fn (IShare $share) => $share->getExpirationDate())) {
			throw new RuntimeException("All legacy shares sharing a share id don't have the expiration date");
		}

		if (!$this->checkAllSame($legacyShares, fn (IShare $share) => $share->getPassword())) {
			throw new RuntimeException("All legacy shares sharing a share id don't have the password");
		}

		if (!$this->checkAllSame($legacyShares, fn (IShare $share) => $share->getLabel())) {
			throw new RuntimeException("All legacy shares sharing a share id don't have the label");
		}

		if (!$this->checkAllSame($legacyShares, fn (IShare $share) => $share->getNote())) {
			throw new RuntimeException("All legacy shares sharing a share id don't have the note");
		}

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

		// TODO: Wrong, reshare can have less permissions
		if (!$this->checkAllSame($legacyShares, fn (IShare $share): ?array => $share->getAttributes()?->toArray())) {
			throw new RuntimeException("All legacy shares sharing a share id don't have the same attributes");
		}

		// TODO: Wrong, reshare can have less permissions
		if (!$this->checkAllSame($legacyShares, fn (IShare $share) => $share->getPermissions())) {
			throw new RuntimeException("All legacy shares sharing a share id don't have the same permissions");
		}

		// Only allow if both agree, to avoid mixups.
		$allowDownload = !$legacyShares[0]->getHideDownload() && $legacyShares[0]->getAttributes()?->getAttribute('permissions', 'download') !== false;
		$permissions = $this->legacyPermissionsToPermissions($legacyShares[0]->getPermissions(), $allowDownload);

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
		}

		foreach ($share->properties as $property) {
			$this->backend->updateShareProperty($share->id, $property);
		}

		foreach ($share->permissions as $permission) {
			$this->backend->updateSharePermission($share->id, $permission);
		}

		foreach ($legacyShares as $legacyShare) {
			if ($legacyShare->getShareType() === IShare::TYPE_USER) {
				$this->backend->updateShareUserStatus($share->id, $legacyShare->getSharedWith(), $this->legacyUserStatusToUserStatus($legacyShare->getStatus() ?? IShare::STATUS_PENDING));
				$this->getSourceNodeTargetManager()->setTarget($legacyShare->getSharedWith(), $share->owner, $legacyShare->getNodeId(), $legacyShare->getTarget());
			} elseif ($legacyShare->getShareType() === IShare::TYPE_GROUP) {
				$qb = $this->dbConnection->getQueryBuilder();
				$result = $qb
					->select('share_with', 'accepted', 'file_target')
					->from('share')
					->where($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_USERGROUP)))
					->andWhere($qb->expr()->eq('parent', $qb->createNamedParameter($legacyShare->getId(), IQueryBuilder::PARAM_INT)))
					->executeQuery();

				/** @var list<array{share_with: string, accepted: IShare::STATUS_*, file_target: string}> $rows */
				$rows = $result->fetchAllAssociative();
				foreach ($rows as $row) {
					$this->backend->updateShareUserStatus($share->id, $row['share_with'], $this->legacyUserStatusToUserStatus($row['accepted']));
					$this->getSourceNodeTargetManager()->setTarget($row['share_with'], $share->owner, $legacyShare->getNodeId(), $row['file_target']);
				}
			}
		}

		[$share] = $this->backend->ensureDefaults([$share]);

		return $share;
	}

	private function syncShareToLegacyShares(Share $share): void {
		/** @var array<IShare::TYPE_*, array<string, array<int, LegacyMapping>>> $legacyMappings */
		$legacyMappings = [];
		/** @var array<string, IShare> */
		$oldLegacyShares = [];
		foreach ($this->legacyMapper->getLegacyMappings($share->id) as $legacyMapping) {
			$legacyShare = $legacyMapping->getLegacyShare($this->legacySharingManager);

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

				$legacyShare->setSharedBy($tokenRecipient->initiator->userId);

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

				$this->setCommonLegacyShareFields($share, $source, $legacyShare);

				$legacyShare = $this->createOrUpdateLegacyShare($share, $legacyMappings, $legacyShare, $tokenRecipient->value, implode('|', $orderedRecipientSecrets));

				$newLegacyShares[$legacyShare->getFullId()] = $legacyShare;
			} else {
				foreach ($share->recipients as $recipient) {
					// {@see IShare::TYPE_USERGROUP} shares are handled automatically by the DefaultShareProvider.
					/** @psalm-suppress ArgumentTypeCoercion Psalm gets confused with the properties keys */
					$legacyShareType = $this->recipientTypeClassToLegacyShareType($recipient->class, $recipient->instance, $share->properties);
					if ($legacyShareType === null) {
						continue;
					}

					// Create a new object to avoid copying existing attributes.
					$legacyShare = $this->legacySharingManager->newShare();
					$legacyShare->setShareType($legacyShareType);

					/** @psalm-suppress ArgumentTypeCoercion Psalm gets confused with the properties keys */
					if (($legacySharedWith = $this->recipientToLegacySharedWith($recipient, $share->properties)) !== null) {
						$legacyShare->setSharedWith($legacySharedWith);
					}

					if ($recipient->instance !== null) {
						throw new RuntimeException("Incoming remote shares aren't handled by " . self::class);
					}

					if ($recipient->initiator === null) {
						throw new RuntimeException('Share recipient cannot be null at this point.');
					}

					$legacyShare->setSharedBy($recipient->initiator->userId);

					$recipientSecret = $recipient->secret;
					if ($recipientSecret === null) {
						throw new RuntimeException('secret must be set.');
					}

					$legacyShare->setToken($this->isPublicLegacyShare($legacyShare) ? $recipientSecret : '');

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
						$target = $this->getSourceNodeTargetManager()->getTarget($recipient->value, $share->owner, (int)$source->value) ?? $this->getSourceNodeTargetManager()->createDefaultTarget($recipient->value, $share->owner, (int)$source->value);
						$legacyShare->setTarget($target);
					}

					$this->setCommonLegacyShareFields($share, $source, $legacyShare);

					$legacyShare = $this->createOrUpdateLegacyShare($share, $legacyMappings, $legacyShare, $recipient->value, $recipientSecret);

					foreach ($share->recipients as $recipient) {
						// Child shares might automatically get accepted, so we need to update their user status.
						if ($recipient->class === GroupShareRecipientType::class) {
							if (($recipientType = $this->sharingRegistry->getRecipientTypes()[$recipient->class] ?? null) === null) {
								throw new RuntimeException('The recipient type is not registered: ' . $recipient->class);
							}

							$userIds = $recipientType->getUsers($recipient->value);

							$userIdsByUserStatus = [];
							foreach ($this->backend->getUserStatuses($share->id, $userIds) as $userId => $userStatus) {
								$userIdsByUserStatus[$userStatus->value] ??= [];
								$userIdsByUserStatus[$userStatus->value][] = $userId;
							}

							foreach ($userIdsByUserStatus as $userStatus => $userIds) {
								foreach (array_chunk($userIds, 1000) as $chunk) {
									$qb = $this->dbConnection->getQueryBuilder();
									$qb
										->update('share')
										->set('accepted', $qb->createNamedParameter($this->userStatusToLegacyUserStatus(ShareUserStatus::from($userStatus)), IQueryBuilder::PARAM_INT))
										->where($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_USERGROUP, IQueryBuilder::PARAM_INT)))
										->andWhere($qb->expr()->eq('parent', $qb->createNamedParameter((int)$legacyShare->getId(), IQueryBuilder::PARAM_INT)))
										->andWhere($qb->expr()->in('share_with', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_STR_ARRAY)))
										->executeStatement();
								}
							}

							$userIdsByTarget = [];
							foreach ($this->getSourceNodeTargetManager()->getTargets($userIds, $share->owner, (int)$source->value) as $userId => $target) {
								$userIdsByTarget[$target] ??= [];
								$userIdsByTarget[$target][] = $userId;
							}

							foreach ($userIdsByTarget as $target => $userIds) {
								foreach (array_chunk($userIds, 1000) as $chunk) {
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

	private function setCommonLegacyShareFields(Share $share, ShareSource $source, IShare $legacyShare): void {
		$attributes = $legacyShare->getAttributes() ?? new ShareAttributes();

		$legacyShare->setNodeId((int)$source->value);

		$legacyShare->setShareTime(DateTime::createFromImmutable($share->getCreatedAt()));

		// TODO: Compute permissions for the recipient instead
		[$permissions, $allowDownload] = $this->permissionsToLegacyPermissions($source, array_keys($share->getEffectiveEnabledPermissions(new ShareAccessContext(overrideChecks: true))));
		$legacyShare->setPermissions($permissions);

		// Always set both, to avoid mixups.
		$attributes->setAttribute('permissions', 'download', $allowDownload);
		$legacyShare->setHideDownload(!$allowDownload);

		$attributes->setAttribute('fileRequest', 'enabled', ($share->properties[NodeNicknameSharePropertyType::class] ?? null)?->value === 'true');

		if ($share->owner->instance !== null) {
			throw new RuntimeException("Incoming remote shares aren't handled by " . self::class);
		}

		$legacyShare->setShareOwner($share->owner->userId);

		$legacyShare->setNote(($share->properties[NoteSharePropertyType::class] ?? null)?->value ?? '');

		$legacyShare->setLabel($this->isPublicLegacyShare($legacyShare) ? ($share->properties[LabelSharePropertyType::class] ?? null)?->value ?? '' : '');

		$attributes->setAttribute('config', 'grid_view', ($share->properties[NodeGridViewSharePropertyType::class] ?? null)?->value === 'true');

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
	 * @param non-empty-string $recipientSecret
	 */
	private function createOrUpdateLegacyShare(Share $share, array $legacyMappings, IShare $legacyShare, string $recipientValue, string $recipientSecret): IShare {
		if (($legacyMapping = $legacyMappings[$legacyShare->getShareType()][$this->getLegacySharedWithUnique($legacyShare)][$legacyShare->getNodeId()] ?? null) !== null) {
			$oldLegacyShare = $legacyMapping->getLegacyShare($this->legacySharingManager);

			$legacyShare->setId($oldLegacyShare->getId());
			$legacyShare->setProviderId($oldLegacyShare->getProviderId());

			$this->legacyMapper->updateLegacyMapping(new LegacyMapping(
				$legacyMapping->id,
				$legacyMapping->legacyProvider,
				$legacyMapping->legacyId,
				$share->lastUpdated,
				$legacyMapping->recipientSecret,
				$legacyMapping->recipientValue,
			));
		} else {
			try {
				// We need to create the legacy mapping ourselves to control the share id, so we disable the automapping of new legacy shares.
				$this->ignoreNewLegacyShare = true;
				// This is the only way we can get a provider and id assigned.
				$legacyShare = $this->legacySharingManager->createShare($legacyShare);
				$this->ignoreNewLegacyShare = false;
			} catch (Exception $exception) {
				$this->ignoreNewLegacyShare = false;
				throw $exception;
			}

			$this->legacyMapper->createLegacyMapping(
				$share->id,
				$legacyShare->getProviderId(),
				(int)$legacyShare->getId(),
				$share->lastUpdated,
				$recipientSecret,
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

		/** @var int|false */
		$mimeTypeId = $result->fetchOne();
		if ($mimeTypeId === false) {
			throw new RuntimeException('Share source does not exist: ' . $source->value);
		}

		$nodeIsFile = $this->mimeTypeLoader->getMimetypeById($mimeTypeId) !== 'httpd/unix-directory';

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
	 * @return list<SharePermission>
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
			$permissions[] = new SharePermission($permissionTypeClass, ($legacyPermissions & $mask) === $mask);
		}

		$permissions[] = new SharePermission(NodeDownloadSharePermissionType::class, $allowDownload);

		return $permissions;
	}

	/**
	 * @param array<class-string<ISharePropertyType>, ShareProperty> $shareProperties
	 */
	private function recipientToLegacySharedWith(ShareRecipient $recipient, array $shareProperties): ?string {
		if (($shareProperties[NodeNicknameSharePropertyType::class] ?? null)?->value === 'true') {
			return '';
		}

		if ($recipient->instance !== null) {
			return $this->cloudIdManager->getCloudId($recipient->value, $recipient->instance)->getId();
		}

		if ($recipient->class === TokenShareRecipientType::class) {
			return null;
		}

		return $recipient->value;
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

	private function getLegacySharedWithUnique(IShare $legacyShare): string {
		return $this->isPublicLegacyShare($legacyShare) ? $legacyShare->getToken() : $legacyShare->getSharedWith();
	}
}
