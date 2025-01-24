<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP;

/**
 * @since 31.0.0
 */
class ServerVersion {

	private array $version;
	private string $versionString;
	private string $build;
	/** @var 'beta'|'stable'|'enterprise'|'git' */
	private string $channel;

	/**
	 * @since 31.0.0
	 */
	public function __construct() {
		$versionFile = __DIR__ . '/../../version.php';
		require $versionFile;

		/** @var int[] $OC_Version */
		$this->version = $OC_Version;
		/** @var string $OC_VersionString */
		$this->versionString = $OC_VersionString;
		/** @var string $OC_Build */
		$this->build = $OC_Build;
		/** @var string $OC_Channel */
		$this->channel = $OC_Channel;
	}

	/**
	 * @since 31.0.0
	 */
	public function getMajorVersion(): int {
		return $this->version[0];
	}

	/**
	 * @since 31.0.0
	 */
	public function getMinorVersion(): int {
		return $this->version[1];
	}

	/**
	 * @since 31.0.0
	 */
	public function getPatchVersion(): int {
		return $this->version[2];
	}

	/**
	 * @since 31.0.0
	 */
	public function getVersion(): array {
		return $this->version;
	}

	/**
	 * @since 31.0.0
	 */
	public function getVersionString(): string {
		return $this->versionString;
	}

	/**
	 * @psalm-return 'beta'|'stable'|'enterprise'|'git'
	 * @since 31.0.0
	 */
	public function getChannel(): string {
		$updaterChannel = Server::get(IConfig::class)->getSystemValueString('updater.release.channel', $this->channel);

		if (in_array($updaterChannel, ['beta', 'stable', 'enterprise', 'git'], true)) {
			return $updaterChannel;
		}

		return $this->channel;
	}

	/**
	 * @since 31.0.0
	 */
	public function getBuild(): string {
		return $this->build;
	}

	/**
	 * @since 31.0.0
	 */
	public function getHumanVersion(): string {
		$version = $this->getVersionString();
		$build = $this->getBuild();
		if (!empty($build) && $this->getChannel() === 'daily') {
			$version .= ' Build:' . $build;
		}
		return $version;

	}
}
