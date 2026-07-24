<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External\Lib\Storage;

use phpseclib3\Net\SFTP;

/**
 * The SFTP read/write stream wrappers implement pipelined reads and buffered
 * writes directly on the SFTP protocol to get better throughput than the public
 * SFTP::get()/put() API can offer (a single kept-open remote handle, read-ahead,
 * chunked writes).
 *
 * phpseclib v3 made the required low-level packet methods and a couple of
 * properties private/protected. To keep the performance-oriented implementation
 * working without vendoring or forking phpseclib, we reach into those internals
 * through reflection. The reflection objects are cached because they are used
 * once per protocol packet.
 */
trait SFTPReflection {
	/** @var array<string, \ReflectionMethod> */
	private static array $sftpReflectionMethods = [];
	/** @var array<string, \ReflectionProperty> */
	private static array $sftpReflectionProperties = [];

	/**
	 * Invoke a method that is private in phpseclib v3.
	 */
	private function invokeSftp(SFTP $sftp, string $method, array $arguments = []): mixed {
		self::$sftpReflectionMethods[$method] ??= new \ReflectionMethod(SFTP::class, $method);
		return self::$sftpReflectionMethods[$method]->invokeArgs($sftp, $arguments);
	}

	/**
	 * Read a property that is private/protected in phpseclib v3.
	 */
	private function getSftpProperty(SFTP $sftp, string $property): mixed {
		self::$sftpReflectionProperties[$property] ??= new \ReflectionProperty(SFTP::class, $property);
		return self::$sftpReflectionProperties[$property]->getValue($sftp);
	}
}
