<?php

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
	 * @throws IllegalIDChangeException
	 * @throws \InvalidArgumentException
	 * @since 9.1.0
	 */
	public function setId(string $id): static;

	/**
	 * Get the internal id of the share.
	 *
	 * @since 9.0.0
	 */
	public function getId(): ?string;

	/**
	 * Get the full share id. This is the <providerid>:<internalid>.
	 * The full id is unique in the system.
	 *
	 * @throws \UnexpectedValueException If the fullId could not be constructed
	 *
	 * @since 9.0.0
	 */
	public function getFullId(): string;

	/**
	 * Set the provider id of the share
	 * It is only allowed to set the provider id of a share once.
	 * Attempts to override the provider id will result in an IllegalIDChangeException
	 *
	 * @throws IllegalIDChangeException
	 * @throws \InvalidArgumentException
	 *
	 * @since 9.1.0
	 */
	public function setProviderId(string $id): static;

	/**
	 * Set the node of the file/folder that is shared
	 *
	 * @since 9.0.0
	 */
	public function setNode(Node $node): static;

	/**
	 * Get the node of the file/folder that is shared
	 *
	 * @since 9.0.0
	 * @throws NotFoundException
	 */
	public function getNode(): Node; 

	/**
	 * Set file id for lazy evaluation of the node
	 * @since 9.0.0
	 */
	public function setNodeId(int $fileId): static;

	/**
	 * Get the fileid of the node of this share
	 * @since 9.0.0
	 * @throws NotFoundException
	 */
	public function getNodeId(): int;

	/**
	 * Set the type of node (file/folder)
	 *
	 * @since 9.0.0
	 */
	public function setNodeType(string $type): static;

	/**
	 * Get the type of node (file/folder)
	 *
	 * @since 9.0.0
	 * @throws NotFoundException
	 */
	public function getNodeType(): string;

	/**
	 * Set the shareType
	 *
	 * @since 9.0.0
	 */
	public function setShareType(int $shareType): static;

	/**
	 * Get the shareType
	 *
	 * @since 9.0.0
	 */
	public function getShareType(): ?int;

	/**
	 * Set the receiver of this share.
	 *
	 * @since 9.0.0
	 */
	public function setSharedWith(string $sharedWith): static;

	/**
	 * Get the receiver of this share.
	 *
	 * @since 9.0.0
	 */
	public function getSharedWith(): ?string;

	/**
	 * Set the display name of the receiver of this share.
	 *
	 * @since 14.0.0
	 */
	public function setSharedWithDisplayName(string $displayName): static;

	/**
	 * Get the display name of the receiver of this share.
	 *
	 * @since 14.0.0
	 */
	public function getSharedWithDisplayName(): ?string;

	/**
	 * Set the avatar of the receiver of this share.
	 *
	 * @since 14.0.0
	 */
	public function setSharedWithAvatar(string $src): static;

	/**
	 * Get the avatar of the receiver of this share.
	 *
	 * @return string
	 * @since 14.0.0
	 */
	public function getSharedWithAvatar(): ?string;

	/**
	 * Set the permissions.
	 * See \OCP\Constants::PERMISSION_*
	 *
	 * @since 9.0.0
	 */
	public function setPermissions(int $permissions): static;

	/**
	 * Get the share permissions
	 * See \OCP\Constants::PERMISSION_*
	 *
	 * @since 9.0.0
	 */
	public function getPermissions(): ?int;

	/**
	 * Create share attributes object
	 *
	 * @since 25.0.0
	 */
	public function newAttributes(): IAttributes;

	/**
	 * Set share attributes
	 *
	 * @since 25.0.0
	 */
	public function setAttributes(?IAttributes $attributes): static;

	/**
	 * Get share attributes
	 *
	 * @since 25.0.0
	 */
	public function getAttributes(): ?IAttributes;

	/**
	 * Set the accepted status
	 * See self::STATUS_*
	 *
	 * @since 18.0.0
	 */
	public function setStatus(int $status): static;

	/**
	 * Get the accepted status
	 * See self::STATUS_*
	 *
	 * @since 18.0.0
	 */
	public function getStatus(): ?int;

	/**
	 * Attach a note to a share
	 *
	 * @since 14.0.0
	 */
	public function setNote(string $note): static;

	/**
	 * Get note attached to a share
	 *
	 * @since 14.0.0
	 */
	public function getNote(): string;


	/**
	 * Set the expiration date
	 *
	 * @since 9.0.0
	 */
	public function setExpirationDate(?\DateTimeInterface $expireDate): static;

	/**
	 * Get the expiration date
	 *
	 * @since 9.0.0
	 */
	public function getExpirationDate(): ?\DateTimeInterface;

	/**
	 * Set overwrite flag for falsy expiry date values
	 *
	 * @since 30.0.0
	 */
	public function setNoExpirationDate(bool $noExpirationDate): static;


	/**
	 * Get value of overwrite falsy expiry date flag
	 *
	 * @since 30.0.0
	 */
	public function getNoExpirationDate(): bool;

	/**
	 * Is the share expired ?
	 *
	 * @since 18.0.0
	 */
	public function isExpired(): bool;

	/**
	 * set a label for a share, some shares, e.g. public links can have a label
	 *
	 * @since 15.0.0
	 */
	public function setLabel(string $label): static;

	/**
	 * get label for the share, some shares, e.g. public links can have a label
	 *
	 * @since 15.0.0
	 */
	public function getLabel(): string;

	/**
	 * Set the sharer of the path.
	 *
	 * @since 9.0.0
	 */
	public function setSharedBy(string $sharedBy): static;

	/**
	 * Get share sharer
	 *
	 * @since 9.0.0
	 */
	public function getSharedBy(): ?string;

	/**
	 * Set the original share owner (who owns the path that is shared)
	 *
	 * @since 9.0.0
	 */
	public function setShareOwner(string $shareOwner): static;

	/**
	 * Get the original share owner (who owns the path that is shared)
	 *
	 * @since 9.0.0
	 */
	public function getShareOwner(): ?string;

	/**
	 * Set the password for this share.
	 * When the share is passed to the share manager to be created
	 * or updated the password will be hashed.
	 *
	 * @since 9.0.0
	 */
	public function setPassword(?string $password): static;

	/**
	 * Get the password of this share.
	 * If this share is obtained via a shareprovider the password is
	 * hashed.
	 *
	 * @since 9.0.0
	 */
	public function getPassword(): ?string;

	/**
	 * Set the password's expiration time of this share.
	 *
	 * @since 24.0.0
	 */
	public function setPasswordExpirationTime(?\DateTimeInterface $passwordExpirationTime = null): static;

	/**
	 * Get the password's expiration time of this share.
	 *
	 * @since 24.0.0
	 */
	public function getPasswordExpirationTime(): ?\DateTimeInterface;

	/**
	 * Set if the recipient can start a conversation with the owner to get the
	 * password using Nextcloud Talk.
	 *
	 * @since 14.0.0
	 */
	public function setSendPasswordByTalk(bool $sendPasswordByTalk): static;

	/**
	 * Get if the recipient can start a conversation with the owner to get the
	 * password using Nextcloud Talk.
	 * The returned value does not take into account other factors, like Talk
	 * being enabled for the owner of the share or not; it just cover whether
	 * the option is enabled for the share itself or not.
	 *
	 * @since 14.0.0
	 */
	public function getSendPasswordByTalk(): bool;

	/**
	 * Set the public link token.
	 *
	 * @since 9.0.0
	 */
	public function setToken(string $token): static;

	/**
	 * Get the public link token.
	 *
	 * @since 9.0.0
	 */
	public function getToken(): ?string;

	/**
	 * Set the parent of this share
	 *
	 * @since 9.0.0
	 */
	public function setParent(int $parent): static;

	/**
	 * Get the parent of this share.
	 *
	 * @since 9.0.0
	 */
	public function getParent(): ?int;

	/**
	 * Set the target path of this share relative to the recipients user folder.
	 *
	 * @since 9.0.0
	 */
	public function setTarget(string $target): static;

	/**
	 * Get the target path of this share relative to the recipients user folder.
	 *
	 * @since 9.0.0
	 */
	public function getTarget(): ?string;

	/**
	 * Set the time this share was created
	 *
	 * @since 9.0.0
	 */
	public function setShareTime(\DateTimeInterface $shareTime): static;

	/**
	 * Get the timestamp this share was created
	 *
	 * @since 9.0.0
	 */
	public function getShareTime(): ?\DateTimeInterface;

	/**
	 * Set if the recipient should be informed by mail about the share.
	 *
	 * @since 9.0.0
	 */
	public function setMailSend(bool $mailSend): static;

	/**
	 * Get if the recipient should be informed by mail about the share.
	 *
	 * @since 9.0.0
	 */
	public function getMailSend(): ?bool;

	/**
	 * Set the cache entry for the shared node
	 *
	 * @since 11.0.0
	 */
	public function setNodeCacheEntry(ICacheEntry $entry): static;

	/**
	 * Get the cache entry for the shared node
	 *
	 * @since 11.0.0
	 */
	public function getNodeCacheEntry(): ?ICacheEntry;

	/**
	 * Sets a shares hide download state
	 * This is mainly for public shares. It will signal that the share page should
	 * hide download buttons etc.
	 *
	 * @since 15.0.0
	 */
	public function setHideDownload(bool $hide): static;

	/**
	 * Gets a shares hide download state
	 * This is mainly for public shares. It will signal that the share page should
	 * hide download buttons etc.
	 *
	 * @since 15.0.0
	 */
	public function getHideDownload(): bool;

	/**
	 * Sets a flag that stores whether a reminder via email has been sent
	 *
	 * @since 31.0.0
	 */
	public function setReminderSent(bool $reminderSent): static;

	/**
	 * Gets a flag that stores whether a reminder via email has been sent
	 *
	 * @since 31.0.0
	 */
	public function getReminderSent(): bool;

	/**
	 * Check if the current user can see this share files contents.
	 * This will check the download permissions as well as the global
	 * admin setting to allow viewing files without downloading.
	 */
	public function canSeeContent(): bool;
}
