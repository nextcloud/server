<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Server side renderers for the initial state transport variants.
 *
 * Every renderer receives the output of InitialStateService::getInitialStates(),
 * i.e. a map of "<app>-<key>" to an already JSON encoded value, and echoes the
 * markup that ends up in the page body. `$nonce` is the CSP nonce.
 */

const VARIANTS = [
	'none',
	'input-base64',
	'input-json',
	'script-json-per-key',
	'script-json-single',
	'inline-js-object',
	'inline-js-map',
	'inline-js-json-parse',
];

/**
 * Same escaping as the p() template helper.
 */
function bench_p(string $value): void {
	print(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
}

/**
 * Makes JSON safe to embed in a <script> element: "<" only occurs inside
 * JSON strings, so replacing it with its escape sequence keeps the value
 * identical while ruling out "</script" and "<!--".
 */
function bench_escape_script(string $json): string {
	return str_replace('<', '<', $json);
}

/**
 * Builds `{"#initial-state-<app>-<key>": <value>, ...}` from the already
 * encoded values without decoding and re-encoding them. The selector shaped
 * keys are what @nextcloud/initial-state 3.x looks up in window._nc_initial_state.
 */
function bench_object_literal(array $states): string {
	$entries = [];
	foreach ($states as $key => $json) {
		$entries[] = json_encode('#initial-state-' . $key, JSON_THROW_ON_ERROR) . ':' . $json;
	}
	return '{' . implode(',', $entries) . '}';
}

/**
 * Has/get facade over a frozen object, compatible with the Map subset that
 * @nextcloud/initial-state 3.x uses on window._nc_initial_state.
 */
const BENCH_FACADE = 's=>Object.freeze({has:k=>Object.hasOwn(s,k),get:k=>s[k],set(){}})';

/**
 * Renders the markup of `$variant` for the given states.
 */
function bench_render(string $variant, array $states, string $nonce): void {
	switch ($variant) {
		case 'none':
			return;

		case 'input-base64':
			?>
<div id="initial-state-container" style="display: none;">
	<?php foreach ($states as $app => $initialState) { ?>
		<input type="hidden" id="initial-state-<?php bench_p($app); ?>" value="<?php bench_p(base64_encode($initialState)); ?>">
	<?php }?>
</div>
<?php
			return;

		case 'input-json':
			?>
<div id="initial-state-container" style="display: none;">
	<?php foreach ($states as $app => $initialState) { ?>
		<input type="hidden" id="initial-state-<?php bench_p($app); ?>" value="<?php bench_p($initialState); ?>">
	<?php }?>
</div>
<?php
			return;

		case 'script-json-per-key':
			foreach ($states as $app => $initialState) { ?>
<script type="application/json" id="initial-state-<?php bench_p($app); ?>"><?php print(bench_escape_script($initialState)); ?></script>
<?php }
			return;

		case 'script-json-single':
			?>
<script type="application/json" id="initial-state"><?php print(bench_escape_script(bench_object_literal($states))); ?></script>
<?php
			return;

		case 'inline-js-object':
			?>
<script nonce="<?php bench_p($nonce); ?>">window._nc_initial_state=(<?php print(BENCH_FACADE); ?>)(Object.freeze(<?php print(bench_escape_script(bench_object_literal($states))); ?>))</script>
<?php
			return;

		case 'inline-js-map':
			$entries = [];
			foreach ($states as $key => $json) {
				$entries[] = '[' . json_encode('#initial-state-' . $key, JSON_THROW_ON_ERROR) . ',' . $json . ']';
			}
			?>
<script nonce="<?php bench_p($nonce); ?>">window._nc_initial_state=new Map([<?php print(bench_escape_script(implode(',', $entries))); ?>])</script>
<?php
			return;

		case 'inline-js-json-parse':
			?>
<script nonce="<?php bench_p($nonce); ?>">window._nc_initial_state=(<?php print(BENCH_FACADE); ?>)(Object.freeze(JSON.parse(<?php print(json_encode(bench_object_literal($states), JSON_HEX_TAG | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)); ?>)))</script>
<?php
			return;
	}

	throw new InvalidArgumentException('Unknown variant ' . $variant);
}

/**
 * Loads a fixture written by capture.mjs or synthesize.php.
 *
 * @return array<string, string>
 */
function bench_load_fixture(string $path): array {
	return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
}
