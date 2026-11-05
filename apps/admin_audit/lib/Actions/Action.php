<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Actions;

use OC\Files\Storage\FailedStorage;
use OC\Files\Storage\Local;
use OC\Files\Storage\Wrapper\Jail;
use OCA\AdminAudit\IAuditLogger;
use OCA\AdminAudit\Operation;
use OCP\Files\ForbiddenException;
use OCP\Files\IHomeStorage;
use OCP\Files\InvalidPathException;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Files\Storage\ISharedStorage;
use OCP\Files\Storage\IStorage;
use OCP\IConfig;
use OCP\Server;

class Action {

	public function __construct(
		private IAuditLogger $logger,
	) {
	}

	/**
	 * Log a single action with a log level of info
	 *
	 * @param Operation|string|null $operation Stable identifier of the action. A string in the form `app.entity.action` is only expected from other apps via CriticalActionPerformedEvent
	 * @param string $text
	 * @param array<string, scalar|null|\DateTimeInterface> $params
	 * @param list<string> $elements
	 * @param bool $obfuscateParameters
	 */
	public function log(
		Operation|string|null $operation,
		string $text,
		array $params,
		array $elements,
		bool $obfuscateParameters = false,
	): void {
		$baseContext = ['app' => 'admin_audit'];
		if ($operation !== null) {
			$baseContext['operation'] = $operation instanceof Operation ? $operation->value : $operation;
		}

		foreach ($elements as $element) {
			if (!array_key_exists($element, $params)) {
				$message = '$params["' . $element . '"] was missing.';
				$context = $baseContext;

				if (!$obfuscateParameters) {
					$message .= ' Transferred value: {params}';
					$context['params'] = $params;
				}

				$this->logger->critical($message, $context);
				return;
			}
		}

		$replaceArray = [];
		$structuredParams = [];
		$context = $baseContext;
		foreach ($elements as $element) {
			$value = $params[$element];
			if ($value instanceof \DateTimeInterface) {
				$value = $value->format('Y-m-d H:i:s');
			}
			$replaceArray[] = $value;
			$structuredParams[$element] = $value;
			// Named {placeholders} are interpolated by the logger
			if (str_contains($text, '{' . $element . '}')) {
				$context[$element] = $value;
			}
		}

		if (!$obfuscateParameters && $structuredParams !== [] && !isset($context['params'])) {
			$context['params'] = $structuredParams;
		}

		$this->logger->info(
			vsprintf($text, $replaceArray),
			$context,
		);
	}

	/**
	 * Returns [$key => path] with the path of the node as seen by the owner of the
	 * share it was accessed through, or [] if it was not accessed through a share
	 *
	 * @return array<string, string>
	 */
	protected function getOwnerPathParam(string $key, Node $node): array {
		$ownerPath = $this->resolveNodePath($node, static function (IStorage $storage, string $internalPath): ?string {
			if (!$storage->instanceOfStorage(ISharedStorage::class)) {
				return null;
			}
			/** @var ISharedStorage $storage */
			$sharePath = $storage->getShare()->getNode()->getPath();
			return $internalPath === '' ? $sharePath : $sharePath . '/' . $internalPath;
		});
		return $ownerPath === null ? [] : [$key => $ownerPath];
	}

	/**
	 * Returns [$key => path] with the location of the node in the storage that holds it,
	 * or [] if it is held in a home storage or the storage is not available. On local
	 * storage this is the path relative to the data directory
	 * (e.g. "__groupfolders/1/files/a.txt"), otherwise the storage id followed by the
	 * path in that storage.
	 *
	 * @return array<string, string>
	 */
	protected function getRealPathParam(string $key, Node $node): array {
		$realPath = $this->resolveNodePath($node, static function (IStorage $storage, string $internalPath): ?string {
			while ($storage->instanceOfStorage(Jail::class)) {
				/** @var Jail $storage */
				$internalPath = $storage->getUnjailedPath($internalPath);
				$storage = $storage->getUnjailedStorage();
			}
			if ($storage->instanceOfStorage(IHomeStorage::class) || $storage->instanceOfStorage(FailedStorage::class)) {
				return null;
			}
			if (!$storage->instanceOfStorage(Local::class)) {
				return rtrim($storage->getId(), '/') . '/' . $internalPath;
			}
			/** @var Local $storage */
			$sourcePath = rtrim($storage->getSourcePath($internalPath), '/');
			$dataDirectory = rtrim(Server::get(IConfig::class)->getSystemValueString('datadirectory', \OC::$SERVERROOT . '/data'), '/') . '/';
			return str_starts_with($sourcePath, $dataDirectory) ? substr($sourcePath, strlen($dataDirectory)) : $sourcePath;
		});
		return $realPath === null ? [] : [$key => $realPath];
	}

	/**
	 * Appends placeholders for the real path parameters in $params to $text.
	 * They must directly follow the parameters used by $text.
	 */
	protected function appendRealPathText(string $text, array $params): string {
		if (isset($params['realPath'])) {
			return $text . ' (real path = "%s")';
		}
		$old = isset($params['oldRealPath']);
		$new = isset($params['newRealPath']);
		if ($old && $new) {
			return $text . ' (real path from "%s" to "%s")';
		}
		if ($old) {
			return $text . ' (real path from "%s")';
		}
		if ($new) {
			return $text . ' (real path to "%s")';
		}
		return $text;
	}

	/**
	 * @param callable(IStorage, string): ?string $resolve
	 */
	private function resolveNodePath(Node $node, callable $resolve): ?string {
		try {
			try {
				$storage = $node->getStorage();
				$internalPath = $node->getInternalPath();
			} catch (NotFoundException) {
				// Nodes that no longer exist, like the source of a rename, are resolved through their parent
				$parentPath = $this->resolveNodePath($node->getParent(), $resolve);
				return $parentPath === null ? null : $parentPath . '/' . $node->getName();
			}
			return $resolve($storage, $internalPath);
		} catch (InvalidPathException|NotFoundException|ForbiddenException) {
			return null;
		}
	}
}
