<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Share20;

use OCP\Files\Cache\ICacheEntry;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\IUserManager;
use OCP\Server;
use OCP\Share\Exceptions\IllegalIDChangeException;
use OCP\Share\IAttributes;
use OCP\Share\IManager;
use OCP\Share\IShare;

class Share implements IShare {
	private ?string $id = null;
	private ?string $providerId = null;
	private ?Node $node = null;
	private ?int $fileId = null;
	private ?string $nodeType = null;
	private ?int $shareType = null;
	private ?string $sharedWith = null;
	private ?string $sharedWithDisplayName = null;
	private ?string $sharedWithAvatar = null;
	private ?string $sharedBy = null;
	private ?string $shareOwner = null;
	private ?int $permissions = null;
	private ?IAttributes $attributes = null;
	private ?int $status = null;
	private string $note = '';
	private ?\DateTimeInterface $expireDate = null;
	private ?string $password = null;
	private ?\DateTimeInterface $passwordExpirationTime = null;
	private bool $sendPasswordByTalk = false;
	private ?string $token = null;
	private ?int $parent = null;
	private ?string $target = null;
	private ?\DateTimeInterface $shareTime = null;
	private ?bool $mailSend = null;
	private ?ICacheEntry $nodeCacheEntry = null;
	private bool $hideDownload = false;
	private bool $reminderSent = false;
	private string $label = '';
	private bool $noExpirationDate = false;

	public function __construct(
		private readonly IRootFolder $rootFolder,
		private readonly IUserManager $userManager,
	) {
	}

	public function setId(string $id): static {
		if ($this->id !== null) {
			throw new IllegalIDChangeException('Not allowed to assign a new internal id to a share');
		}

		$this->id = trim($id);
		return $this;
	}

	public function getId(): ?string {
		return $this->id;
	}

	public function getFullId(): string {
		if ($this->providerId === null || $this->id === null) {
			throw new \UnexpectedValueException('Provider ID and ID must be set before getting full ID');
		}
		return $this->providerId . ':' . $this->id;
	}

	public function setProviderId(string $id): static {
		if ($this->providerId !== null) {
			throw new IllegalIDChangeException('Not allowed to assign a new provider id to a share');
		}

		$this->providerId = trim($id);
		return $this;
	}

	public function setNode(Node $node): static {
		$this->fileId = null;
		$this->nodeType = null;
		$this->node = $node;
		return $this;
	}

	public function getNode(): Node {
		if ($this->node !== null) {
			return $this->node;
		}

		if ($this->shareOwner === null || $this->fileId === null) {
			throw new NotFoundException('Share owner and file ID must be set');
		}

		// For federated shares the owner can be a remote user, in this case we use the initiator
		$owner = $this->userManager->userExists($this->shareOwner)
			? $this->shareOwner
			: $this->sharedBy;

		$userFolder = $this->rootFolder->getUserFolder($owner);
		$node = $userFolder->getFirstNodeById($this->fileId);

		if (!$node) {
			throw new NotFoundException('Node for share not found, fileid: ' . $this->fileId);
		}

		$this->node = $node;
		return $this->node;
	}

	public function setNodeId(int $fileId): static {
		$this->node = null;
		$this->fileId = $fileId;
		return $this;
	}

	public function getNodeId(): int {
		if ($this->fileId === null) {
			$this->fileId = $this->getNode()->getId();
		}

		if ($this->fileId === null) {
			throw new NotFoundException('Share source not found');
		}

		return $this->fileId;
	}

	public function setNodeType(string $type): static {
		if ($type !== 'file' && $type !== 'folder') {
			throw new \InvalidArgumentException('Node type must be "file" or "folder"');
		}

		$this->nodeType = $type;
		return $this;
	}

	public function getNodeType(): string {
		if ($this->nodeType !== null) {
			return $this->nodeType;
		}

		if ($cacheEntry = $this->getNodeCacheEntry()) {
			$this->nodeType = $cacheEntry->getMimeType() === FileInfo::MIMETYPE_FOLDER ? 'folder' : 'file';
		} else {
			$node = $this->getNode();
			$this->nodeType = $node instanceof File ? 'file' : 'folder';
		}

		return $this->nodeType;
	}

	public function setShareType(int $shareType): static {
		$this->shareType = $shareType;
		return $this;
	}

	public function getShareType(): ?int {
		return $this->shareType;
	}

	public function setSharedWith(string $sharedWith): static {
		$this->sharedWith = $sharedWith;
		return $this;
	}

	public function getSharedWith(): ?string {
		return $this->sharedWith;
	}

	public function setSharedWithDisplayName(string $displayName): static {
		$this->sharedWithDisplayName = $displayName;
		return $this;
	}

	public function getSharedWithDisplayName(): ?string {
		return $this->sharedWithDisplayName;
	}

	public function setSharedWithAvatar(string $src): static {
		$this->sharedWithAvatar = $src;
		return $this;
	}

	public function getSharedWithAvatar(): ?string {
		return $this->sharedWithAvatar;
	}

	public function setPermissions(int $permissions): static {
		$this->permissions = $permissions;
		return $this;
	}

	public function getPermissions(): ?int {
		return $this->permissions;
	}

	public function newAttributes(): IAttributes {
		return new ShareAttributes();
	}

	public function setAttributes(?IAttributes $attributes): static {
		$this->attributes = $attributes;
		return $this;
	}

	public function getAttributes(): ?IAttributes {
		return $this->attributes;
	}

	public function setStatus(int $status): static {
		$this->status = $status;
		return $this;
	}

	public function getStatus(): ?int {
		return $this->status;
	}

	public function setNote(string $note): static {
		$this->note = $note;
		return $this;
	}

	public function getNote(): string {
		return $this->note;
	}

	public function setLabel(string $label): static {
		$this->label = $label;
		return $this;
	}

	public function getLabel(): string {
		return $this->label;
	}

	public function setExpirationDate(?\DateTimeInterface $expireDate): static {
		$this->expireDate = $expireDate;
		return $this;
	}

	public function getExpirationDate(): ?\DateTimeInterface {
		return $this->expireDate;
	}

	public function setNoExpirationDate(bool $noExpirationDate): static {
		$this->noExpirationDate = $noExpirationDate;
		return $this;
	}

	public function getNoExpirationDate(): bool {
		return $this->noExpirationDate;
	}

	public function isExpired(): bool {
		$expirationDate = $this->getExpirationDate();
		return $expirationDate !== null && $expirationDate <= new \DateTime();
	}

	public function setSharedBy(string $sharedBy): static {
		$this->sharedBy = $sharedBy;
		return $this;
	}

	public function getSharedBy(): ?string {
		return $this->sharedBy;
	}

	public function setShareOwner(string $shareOwner): static {
		$this->shareOwner = $shareOwner;
		return $this;
	}

	public function getShareOwner(): ?string {
		return $this->shareOwner;
	}

	public function setPassword(?string $password): static {
		$this->password = $password;
		return $this;
	}

	public function getPassword(): ?string {
		return $this->password;
	}

	public function setPasswordExpirationTime(?\DateTimeInterface $passwordExpirationTime = null): static {
		$this->passwordExpirationTime = $passwordExpirationTime;
		return $this;
	}

	public function getPasswordExpirationTime(): ?\DateTimeInterface {
		return $this->passwordExpirationTime;
	}

	public function setSendPasswordByTalk(bool $sendPasswordByTalk): static {
		$this->sendPasswordByTalk = $sendPasswordByTalk;
		return $this;
	}

	public function getSendPasswordByTalk(): bool {
		return $this->sendPasswordByTalk;
	}

	public function setToken(string $token): static {
		$this->token = $token;
		return $this;
	}

	public function getToken(): ?string {
		return $this->token;
	}

	public function setParent(int $parent): static {
		$this->parent = $parent;
		return $this;
	}

	public function getParent(): ?int {
		return $this->parent;
	}

	public function setTarget(string $target): static {
		$this->target = $target;
		return $this;
	}

	public function getTarget(): ?string {
		return $this->target;
	}

	public function setShareTime(\DateTimeInterface $shareTime): static {
		$this->shareTime = $shareTime;
		return $this;
	}

	public function getShareTime(): ?\DateTimeInterface {
		return $this->shareTime;
	}

	public function setMailSend(bool $mailSend): static {
		$this->mailSend = $mailSend;
		return $this;
	}

	public function getMailSend(): ?bool {
		return $this->mailSend;
	}

	public function setNodeCacheEntry(ICacheEntry $entry): static {
		$this->nodeCacheEntry = $entry;
		return $this;
	}

	public function getNodeCacheEntry(): ?ICacheEntry {
		return $this->nodeCacheEntry;
	}

	public function setHideDownload(bool $hide): static {
		$this->hideDownload = $hide;
		return $this;
	}

	public function getHideDownload(): bool {
		return $this->hideDownload;
	}

	public function setReminderSent(bool $reminderSent): static {
		$this->reminderSent = $reminderSent;
		return $this;
	}

	public function getReminderSent(): bool {
		return $this->reminderSent;
	}

	public function canSeeContent(): bool {
		$shareManager = Server::get(IManager::class);

		$allowViewWithoutDownload = $shareManager->allowViewWithoutDownload();
		if ($allowViewWithoutDownload) {
			return true;
		}

		$attributes = $this->getAttributes();
		if ($attributes?->getAttribute('permissions', 'download') === false) {
			return false;
		}

		return true;
	}
}
