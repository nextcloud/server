<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\DirectEditing;

use OCP\DirectEditing\IToken;
use OCP\Files\File;

class Token implements IToken {
	public function __construct(
		private Manager $manager,
		private $data,
	) {
	}

	#[\Override]
	public function extend(): void {
		$this->manager->refreshToken($this->data['token']);
	}

	#[\Override]
	public function invalidate(): void {
		$this->manager->invalidateToken($this->data['token']);
	}

	#[\Override]
	public function getFile(): File {
		if ($this->data['share_id'] !== null) {
			return $this->manager->getShareForToken($this->data['share_id']);
		}
		return $this->manager->getFileForToken($this->data['user_id'], $this->data['file_id'], $this->data['file_path']);
	}

	public function getToken(): string {
		return $this->data['token'];
	}

	#[\Override]
	public function useTokenScope(): void {
		$this->manager->invokeTokenScope($this->data['user_id']);
	}

	#[\Override]
	public function hasBeenAccessed(): bool {
		return (bool)$this->data['accessed'];
	}

	#[\Override]
	public function getEditor(): string {
		return $this->data['editor_id'];
	}

	#[\Override]
	public function getUser(): string {
		return $this->data['user_id'];
	}
}
