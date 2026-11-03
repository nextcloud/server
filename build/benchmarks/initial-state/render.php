<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Prints the markup of every variant for a fixture as JSON, used by bench-browser.mjs.
 *
 * Usage: php render.php <fixture.json> <nonce>
 */

require __DIR__ . '/variants.php';

[, $fixture, $nonce] = $argv;
$states = bench_load_fixture($fixture);

$markup = [];
foreach (VARIANTS as $variant) {
	ob_start();
	bench_render($variant, $states, $nonce);
	$markup[$variant] = ob_get_clean();
}
echo json_encode($markup, JSON_THROW_ON_ERROR);
