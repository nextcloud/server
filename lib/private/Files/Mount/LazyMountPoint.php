<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Mount;

use OCP\Files\Mount\IMountPoint;

class LazyMountPoint implements IMountPoint {
	private ?IMountPoint $mountPoint = null;

	/**
	 * @param \Closure(): IMountPoint $mountPointClosure
	 */
	public function __construct(
		private readonly \Closure $mountPointClosure,
		private readonly array $data,
	) {
	}

	private function getRealMountPoint(): IMountPoint {
		if ($this->mountPoint === null) {
			$this->mountPoint = call_user_func($this->mountPointClosure);
		}
		return $this->mountPoint;
	}

	public function __call($method, $args) {
		return call_user_func_array([$this->getRealMountPoint(), $method], $args);
	}

	public function getMountPoint() {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function setMountPoint($mountPoint) {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	private function createStorage() {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function getStorage() {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function getStorageId(): ?string {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function getNumericStorageId(): int {
		if (isset($this->data['numericStorageId'])) {
			return $this->data['numericStorageId'];
		}
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function getInternalPath($path) {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function wrapStorage($wrapper): void {
		$this->__call(__FUNCTION__, func_get_args());
	}

	public function getOption($name, $default) {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function getOptions(): array {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function getStorageRootId(): int {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function getMountId() {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function getMountType() {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	public function getMountProvider(): string {
		return $this->__call(__FUNCTION__, func_get_args());
	}
}
