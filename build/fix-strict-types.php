#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

include __DIR__ . '/../build/integration/vendor/autoload.php';

use PhpParser\Node\DeclareItem;
use PhpParser\Node\Stmt\Declare_;
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

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('lib'));

/** @var SplFileInfo $info */
foreach ($iterator as $info) {
	if ($info->getType() !== 'file' || $info->getExtension() !== 'php') {
		continue;
	}

	error_log($info->getRealPath());

	$code = file_get_contents($info->getRealPath());
	$oldStmts = $parser->parse($code);
	$oldTokens = $parser->getTokens();
	$newStmts = $nodeTraverser->traverse($oldStmts);

	$hasStrictTypes = false;
	foreach ($newStmts as $stmt) {
		if ($stmt instanceof Declare_) {
			$hasStrictTypes = true;
			break;
		}
	}

	if (!$hasStrictTypes) {
		array_unshift($newStmts, new Declare_([new DeclareItem('strict_types', new PhpParser\Node\Scalar\Int_(1))]));
		file_put_contents($info->getRealPath(), $prettyPrinter->printFormatPreserving($newStmts, $oldStmts, $oldTokens));
	}
}
