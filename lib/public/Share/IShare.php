<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Share;

use OCP\Files\Cache\ICacheEntry;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Share\Exceptions\IllegalIDChangeException;

/**
 * This interface allows to represent a share object.
 *
 * This interface must not be implemented in your application.
 *
 * @since 9.0.0
 */
interface IShare {
	/**
	 * @since 17.0.0
	 */
	public const TYPE_USER = 0;

	/**
	 * @since 17.0.0
	 */
	public const TYPE_GROUP = 1;

	/**
	 * @internal
	 * @since 18.0.0
	 */
	public const TYPE_USERGROUP = 2;

	/**
	 * @since 17.0.0
	 */
	public const TYPE_LINK = 3;

	/**
	 * @since 17.0.0
	 */
	public const TYPE_EMAIL = 4;

	/**
	 * @since 17.0.0
	 */
	public const TYPE_REMOTE = 6;

	/**
	 * @since 17.0.0
	 */
	public const TYPE_CIRCLE = 7;

	/**
	 * @since 17.0.0
	 */
	public const TYPE_GUEST = 8;

	/**
	 * @since 17.0.0
	 */
	public const TYPE_REMOTE_GROUP = 9;

	/**
	 * @since 17.0.0
	 */
	public const TYPE_ROOM = 10;

	/**
	 * Internal type used by RoomShareProvider
	 *
	 * @since 17.0.0
	 */
	// const TYPE_USERROOM = 11;

	/**
	 * @since 21.0.0
	 */
	public const TYPE_DECK = 12;

	/**
	 * @internal
	 * @since 21.0.0
	 */
	public const TYPE_DECK_USER = 13;

	/**
	 * @since 26.0.0
	 * @deprecated 33.0.0 The app is abandonned.
	 */
	public const TYPE_SCIENCEMESH = 15;

	/**
	 * @since 18.0.0
	 */
	public const STATUS_PENDING = 0;

	/**
	 * @since 18.0.0
	 */
	public const STATUS_ACCEPTED = 1;

	/**
	 * @since 18.0.0
	 */
	public const STATUS_REJECTED = 2;

	/**
	 * Set the internal id of the share
	 * It is only allowed to set the internal id of a share once.
	 * Attempts to override the internal id will result in an IllegalIDChangeException
	 *
	 * @return IShare The modified object
	 * @throws IllegalIDChangeException
	 * @since 9.1.0
	 */
	public function setId(string $id): IShare;

	/**
	 * Get the internal id of the share.
	 *
	 * @return string|null
	 * @since 9.0.0
	 */
	public function getId(): ?string;

	/**
	 * Get the full share id. This is the <providerid>:<internalid>.
	 * The full id is unique in the system.
	 *
	 * @return string
	 * @since 9.0.0
	 * @throws \UnexpectedValueException If the fullId could not be constructed
	 */
	public function getFullId(): string;

	/**
	 * Set the provider id of the share
	 * It is only allowed to set the provider id of a share once.
	 * Attempts to override the provider id will result in an IllegalIDChangeException
	 *
	 * @return IShare The modified object
	 * @throws IllegalIDChangeException
	 * @since 9.1.0
	 */
	public function setProviderId(string $id): IShare;

	/**
	 * Set the node of the file/folder that is shared
	 *
	 * @param Node $node
	 * @return IShare The modified object
	 * @since 9.0.0
	 */
	public function setNode(Node $node): IShare;

	/**
	 * Get the node of the file/folder that is shared
	 *
	 * @return Node
	 * @since 9.0.0
	 * @throws NotFoundException
	 */
	public function getNode(): Node;

	/**
	 * Set file id for lazy evaluation of the node
	 * @param int $fileId
	 * @return IShare The modified object
	 * @since 9.0.0
	 */
	public function setNodeId(int $fileId): IShare;

	/**
	 * Get the fileid of the node of this share
	 * @return int
	 * @since 9.0.0
	 * @throws NotFoundException
	 */
	public function getNodeId(): int;

	/**
	 * Set the type of node (file/folder)
	 *
	 * @param string $type
	 * @return IShare The modified object
	 * @since 9.0.0
	 */
	public function setNodeType(string $type): IShare;

	/**
	 * Get the type of node (file/folder)
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getNodeType(): string;

	/**
	 * Set the shareType
	 *
	 * @param int $shareType
	 * @return IShare The modified object
	 * @since 9.0.0
	 */
	public function setShareType(int $shareType): IShare;

	/**
	 * Get the shareType
	 *
	 * @return int|null
	 * @since 9.0.0
	 */
	public function getShareType(): ?int;

	/**
	 * Set the receiver of this share.
	 *
	 * @param string $sharedWith
	 * @return IShare The modified object
	 * @since 9.0.0
	 */
	public function setSharedWith(string $sharedWith): IShare;

	/**
	 * Get the receiver of this share.
	 *
	 * @return string|null
	 * @since 9.0.0
	 */
	public function getSharedWith(): ?string;

	/**
	 * Set the display name of the receiver of this share.
	 *
	 * @param string $displayName
	 * @return IShare The modified object
	 * @since 14.0.0
	 */
	public function setSharedWithDisplayName(string $displayName): IShare;

	/**
	 * Get the display name of the receiver of this share.
	 *
	 * @return string|null
	 * @since 14.0.0
	 */
	public function getSharedWithDisplayName(): ?string;

	/**
	 * Set the avatar of the receiver of this share.
	 *
	 * @param string $src
	 * @return IShare The modified object
	 * @since 14.0.0
	 */
	public function setSharedWithAvatar(string $src): IShare;

	/**
	 * Get the avatar of the receiver of this share.
	 *
	 * @return string|null
	 * @since 14.0.0
	 */
	public function getSharedWithAvatar(): ?string;

	/**
	 * Set the permissions.
	 * See \OCP\Constants::PERMISSION_*
	 *
	 * @param int $permissions
	 * @return IShare The modified object
	 * @since 9.0.0
	 */
	public function setPermissions(int $permissions): IShare;

	/**
	 * Get the share permissions
	 * See \OCP\Constants::PERMISSION_*
	 *
	 * @return int|null
	 * @since 9.0.0
	 */
	public function getPermissions(): ?int;

	/**
	 * Create share attributes object
	 *
	 * @since 25.0.0
	 * @return IAttributes
	 */
	public function newAttributes(): IAttributes;

	/**
	 * Set share attributes
	 *
	 * @param IAttributes|null $attributes
	 * @since 25.0.0
	 * @return IShare The modified object
	 */
	public function setAttributes(?IAttributes $attributes): IShare;

	/**
	 * Get share attributes
	 *
	 * @since 25.0.0
	 * @return IAttributes|null
	 */
	public function getAttributes(): ?IAttributes;

	/**
	 * Set the accepted status
	 * See self::STATUS_*
	 *
	 * @param int $status
	 * @return IShare The modified object
	 * @since 18.0.0
	 */
	public function setStatus(int $status): IShare;

	/**
	 * Get the accepted status
	 * See self::STATUS_*
	 *
	 * @return int|null
	 * @since 18.0.0
	 */
	public function getStatus(): ?int;

	/**
	 * Attach a note to a share
	 *
	 * @param string $note
	 * @return IShare The modified object
	 * @since 14.0.0
	 */
	public function setNote(string $note): IShare;

	/**
	 * Get note attached to a share
	 *
	 * @return string
	 * @since 14.0.0
	 */
	public function getNote(): string;

	/**
	 * Set the expiration date
	 *
	 * @param \DateTimeInterface|null $expireDate
	 * @return IShare The modified object
	 * @since 9.0.0
	 */
	public function setExpirationDate(?\DateTimeInterface $expireDate): IShare;

	/**
	 * Get the expiration date
	 *
	 * @return \DateTimeInterface|null
	 * @since 9.0.0
	 */
	public function getExpirationDate(): ?\DateTimeInterface;

	/**
	 * Set overwrite flag for falsy expiry date values
	 *
	 * @param bool $noExpirationDate
	 * @return IShare The modified object
	 * @since 30.0.0
	 */
	public function setNoExpirationDate(bool $noExpirationDate): IShare;

	/**
	 * Get value of overwrite falsy expiry date flag
	 *
	 * @return bool
	 * @since 30.0.0
	 */
	public function getNoExpirationDate(): bool;

	/**
	 * Is the share expired ?
	 *
	 * @return bool
	 * @since 18.0.0
	 */
	public function isExpired(): bool;

	/**
	 * set a label for a share, some shares, e.g. public links can have a label
	 *
	 * @param string $label
	 * @return IShare The modified object
	 * @since 15.0.0
	 */
	public function setLabel(string $label): IShare;

	/**
	 * get label for the share, some shares, e.g. public links can have a label
	 *
	 * @return string
	 * @since 15.0.0
	 */
	public function getLabel(): string;

	/**
	 * Set the sharer of the path.
	 *
	 * @param string $sharedBy
	 * @return IShare The modified object
	 * @since 9.0.0
	 */
	public function setSharedBy(string $sharedBy): IShare;

	/**
	 * Get share sharer
	 *
	 * @return string|null
	 * @since 9.0.0
	 */
	public function getSharedBy(): ?string;

	/**
	 * Set the original share owner (who owns the path that is shared)
	 *
	 * @param string $shareOwner
	 * @return IShare The modified object
	 * @since 9.0.0
	 */
	public function setShareOwner(string $shareOwner): IShare;

	/**
	 * Get the original share owner (who owns the path that is shared)
	 *
	 * @return string|null
	 * @since 9.0.0
	 */
	public function getShareOwner(): ?string;

	/**
	 * Set the password for this share.
	 * When the share is passed to the share manager to be created
	 * or updated the password will be hashed.
	 *
	 * @param string|null $password
	 * @return IShare The modified object
	 * @since 9.0.0
	 */
	public function setPassword(?string $password): IShare;

	/**
	 * Get the password of this share.
	 * If this share is obtained via a shareprovider the password is
	 * hashed.
	 *
	 * @return string|null
	 * @since 9.0.0
	 */
	public function getPassword(): ?string;

	/**
	 * Set the password's expiration time of this share.
	 *
	 * @param \DateTimeInterface|null $passwordExpirationTime
	 * @return IShare The modified object
	 * @since 24.0.0
	 */
	public function setPasswordExpirationTime(?\DateTimeInterface $passwordExpirationTime = null): IShare;

	/**
	 * Get the password's expiration time of this share.
	 *
	 * @return \DateTimeInterface|null
	 * @since 24.0.0
	 */
	public function getPasswordExpirationTime(): ?\DateTimeInterface;

	/**
	 * Set if the recipient can start a conversation with the owner to get the
	 * password using Nextcloud Talk.
	 *
	 * @param bool $sendPasswordByTalk
	 * @return IShare The modified object
	 * @since 14.0.0
	 */
	public function setSendPasswordByTalk(bool $sendPasswordByTalk): IShare;

	/**
	 * Get if the recipient can start a conversation with the owner to get the
	 * password using Nextcloud Talk.
	 * The returned value does not take into account other factors, like Talk
	 * being enabled for the owner of the share or not; it just cover whether
	 * the option is enabled for the share itself or not.
	 *
	 * @return bool
	 * @since 14.0.0
	 */
	public function getSendPasswordByTalk(): bool;

	/**
	 * Set the public link token.
	 *
	 * @param string $token
	 * @return IShare The modified object
	 * @since 9.0.0
	 */
	public function setToken(string $token): IShare;

	/**
	 * Get the public link token.
	 *
	 * @return string|null
	 * @since 9.0.0
	 */
	public function getToken(): ?string;

	/**
	 * Set the parent of this share
	 *
	 * @param int $parent
	 * @return IShare The modified object
	 * @since 9.0.0
	 */
	public function setParent(int $parent): IShare;

	/**
	 * Get the parent of this share.
	 *
	 * @return int|null
	 * @since 9.0.0
	 */
	public function getParent(): ?int;

	/**
	 * Set the target path of this share relative to the recipients user folder.
	 *
	 * @param string $target
	 * @return IShare The modified object
	 * @since 9.0.0
	 */
	public function setTarget(string $target): IShare;

	/**
	 * Get the target path of this share relative to the recipients user folder.
	 *
	 * @return string|null
	 * @since 9.0.0
	 */
	public function getTarget(): ?string;

	/**
	 * Set the time this share was created
	 *
	 * @param \DateTimeInterface $shareTime
	 * @return IShare The modified object
	 * @since 9.0.0
	 */
	public function setShareTime(\DateTimeInterface $shareTime): IShare;

	/**
	 * Get the timestamp this share was created
	 *
	 * @return \DateTimeInterface|null
	 * @since 9.0.0
	 */
	public function getShareTime(): ?\DateTimeInterface;

	/**
	 * Set if the recipient should be informed by mail about the share.
	 *
	 * @param bool $mailSend
	 * @return IShare The modified object
	 * @since 9.0.0
	 */
	public function setMailSend(bool $mailSend): IShare;

	/**
	 * Get if the recipient should be informed by mail about the share.
	 *
	 * @return bool|null
	 * @since 9.0.0
	 */
	public function getMailSend(): ?bool;

	/**
	 * Set the cache entry for the shared node
	 *
	 * @param ICacheEntry $entry
	 * @return IShare The modified object
	 * @since 11.0.0
	 */
	public function setNodeCacheEntry(ICacheEntry $entry): IShare;

	/**
	 * Get the cache entry for the shared node
	 *
	 * @return ICacheEntry|null
	 * @since 11.0.0
	 */
	public function getNodeCacheEntry(): ?ICacheEntry;

	/**
	 * Sets a shares hide download state
	 * This is mainly for public shares. It will signal that the share page should
	 * hide download buttons etc.
	 *
	 * @param bool $hide
	 * @return IShare The modified object
	 * @since 15.0.0
	 */
	public function setHideDownload(bool $hide): IShare;

	/**
	 * Gets a shares hide download state
	 * This is mainly for public shares. It will signal that the share page should
	 * hide download buttons etc.
	 *
	 * @return bool
	 * @since 15.0.0
	 */
	public function getHideDownload(): bool;

	/**
	 * Sets a flag that stores whether a reminder via email has been sent
	 *
	 * @param bool $reminderSent
	 * @return IShare The modified object
	 * @since 31.0.0
	 */
	public function setReminderSent(bool $reminderSent): IShare;

	/**
	 * Gets a flag that stores whether a reminder via email has been sent
	 *
	 * @return bool
	 * @since 31.0.0
	 */
	public function getReminderSent(): bool;

	/**
	 * Check if the current user can see this share files contents.
	 * This will check the download permissions as well as the global
	 * admin setting to allow viewing files without downloading.
	 *
	 * @return bool
	 * @since 15.0.0
	 */
	public function canSeeContent(): bool;
}
