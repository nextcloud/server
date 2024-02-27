<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\SMB\Native;

use Icewind\SMB\ACL;
use Icewind\SMB\Exception\Exception;
use Icewind\SMB\IFileInfo;

class NativeFileInfo implements IFileInfo {
	/** @var string */
	protected $path;
	/** @var string */
	protected $name;
	/** @var NativeShare */
	protected $share;
	/** @var array{"mode": int, "size": int, "write_time": int}|null */
	protected $attributeCache = null;

	public function __construct(NativeShare $share, string $path, string $name) {
		$this->share = $share;
		$this->path = $path;
		$this->name = $name;
	}

	public function getPath(): string {
		return $this->path;
	}

	public function getName(): string {
		return $this->name;
	}

	/**
	 * @return array{"mode": int, "size": int, "write_time": int}
	 */
	protected function stat(): array {
		if (is_null($this->attributeCache)) {
			$rawAttributes = explode(',', $this->share->getAttribute($this->path, 'system.dos_attr.*'));
			$attributes = [];
			foreach ($rawAttributes as $rawAttribute) {
				list($name, $value) = explode(':', $rawAttribute);
				$name = strtolower($name);
				if ($name == 'mode') {
					$attributes[$name] = (int)hexdec(substr($value, 2));
				} else {
					$attributes[$name] = (int)$value;
				}
			}
			if (!isset($attributes['mode'])) {
				throw new Exception("Invalid attribute response");
			}
			if (!isset($attributes['size'])) {
				throw new Exception("Invalid attribute response");
			}
			if (!isset($attributes['write_time'])) {
				throw new Exception("Invalid attribute response");
			}
			$this->attributeCache = $attributes;
		}
		return $this->attributeCache;
	}

	public function getSize(): int {
		$stat = $this->stat();
		return $stat['size'];
	}

	public function getMTime(): int {
		$stat = $this->stat();
		return $stat['write_time'];
	}

	/**
	 * On "mode":
	 *
	 * different smbclient versions seem to return different mode values for 'system.dos_attr.mode'
	 *
	 * older versions return the dos permissions mask as defined in `IFileInfo::MODE_*` while
	 * newer versions return the equivalent unix permission mask.
	 *
	 * Since the unix mask doesn't contain the proper hidden/archive/system flags we have to assume them
	 * as false (except for `hidden` where we use the unix dotfile convention)
	 */

	protected function getMode(): int {
		$mode = $this->stat()['mode'];

		// Let us ignore the ATTR_NOT_CONTENT_INDEXED for now
		$mode &= ~0x00002000;

		return $mode;
	}

	public function isDirectory(): bool {
		$mode = $this->getMode();
		if ($mode > 0x1000) {
			return ($mode & 0x4000 && !($mode & 0x8000)); // 0x4000: unix directory flag shares bits with 0xC000: socket
		} else {
			return (bool)($mode & IFileInfo::MODE_DIRECTORY);
		}
	}

	public function isReadOnly(): bool {
		$mode = $this->getMode();
		if ($mode > 0x1000) {
			return !(bool)($mode & 0x80); // 0x80: owner write permissions
		} else {
			return (bool)($mode & IFileInfo::MODE_READONLY);
		}
	}

	public function isHidden(): bool {
		$mode = $this->getMode();
		if ($mode > 0x1000) {
			return strlen($this->name) > 0 && $this->name[0] === '.';
		} else {
			return (bool)($mode & IFileInfo::MODE_HIDDEN);
		}
	}

	public function isSystem(): bool {
		$mode = $this->getMode();
		if ($mode > 0x1000) {
			return false;
		} else {
			return (bool)($mode & IFileInfo::MODE_SYSTEM);
		}
	}

	public function isArchived(): bool {
		$mode = $this->getMode();
		if ($mode > 0x1000) {
			return false;
		} else {
			return (bool)($mode & IFileInfo::MODE_ARCHIVE);
		}
	}

	/**
	 * @return ACL[]
	 */
	public function getAcls(): array {
		$acls = [];
		$attribute = $this->share->getAttribute($this->path, 'system.nt_sec_desc.acl.*+');

		foreach (explode(',', $attribute) as $acl) {
			list($user, $permissions) = explode(':', $acl, 2);
			$user = trim($user, '\\');
			list($type, $flags, $mask) = explode('/', $permissions);
			$mask = hexdec($mask);

			$acls[$user] = new ACL((int)$type, (int)$flags, (int)$mask);
		}

		return $acls;
	}
}
