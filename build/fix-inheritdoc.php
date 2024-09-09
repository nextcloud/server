#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

include __DIR__ . '/../lib/composer/autoload.php';
include __DIR__ . '/../build/integration/vendor/autoload.php';
include __DIR__ . '/../3rdparty/autoload.php';

use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;

$parser = (new ParserFactory())->createForHostVersion();

$nodeTraverser = new NodeTraverser();
$nodeTraverser->addVisitor(new CloningVisitor());
$nodeTraverser->addVisitor(new NameResolver());

$prettyPrinter = new PrettyPrinter\Standard();

$iterators = [
	new RecursiveIteratorIterator(new RecursiveDirectoryIterator('lib/private')),
	new RecursiveIteratorIterator(new RecursiveDirectoryIterator('core')),
];
foreach ($iterators as $iterator) {
	/** @var SplFileInfo $info */
	foreach ($iterator as $info) {
		if ($info->getType() !== 'file' || $info->getExtension() !== 'php') {
			continue;
		}

		$code = file_get_contents($info->getRealPath());
		$oldStmts = $parser->parse($code);
		$oldTokens = $parser->getTokens();
		$newStmts = $nodeTraverser->traverse($oldStmts);

		$changed = false;
		foreach ($newStmts as $stmt) {
			if (!$stmt instanceof Namespace_) {
				continue;
			}

			foreach ($stmt->stmts as $childClass) {
				if (!$childClass instanceof Class_) {
					continue;
				}

				$childClassName = $childClass->namespacedName->toString();
				if (!str_starts_with($childClassName, 'OC\\')) {
					continue;
				}

				/** @var FullyQualified $impl */
				foreach ($childClass->implements as $impl) {
					$parentClassName = $impl->toString();
					if ($parentClassName === 'OCP\\Capabilities\\ICapability' || $parentClassName === 'OCP\\Capabilities\\IPublicCapability' || !str_starts_with($parentClassName, 'OCP\\')) {
						continue;
					}

					$parentClass = new ReflectionClass($parentClassName);
					$parentMethods = $parentClass->getMethods();

					foreach ($childClass->getMethods() as $childMethod) {
						foreach ($parentMethods as $parentMethod) {
							if (strtolower($childMethod->name->name) !== strtolower($parentMethod->name)) {
								continue;
							}

							$childMethod->setAttribute('comments', []);
							$changed = true;
							break;
						}
					}
				}
			}
		}

		if ($changed) {
			file_put_contents($info->getRealPath(), $prettyPrinter->printFormatPreserving($newStmts, $oldStmts, $oldTokens));
		}
	}
}
