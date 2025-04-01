<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

use OC\Security\CSP\ContentSecurityPolicyNonceManager;
use OCP\Files\IMimeTypeDetector;
use OCP\IDateTimeFormatter;
use OCP\IURLGenerator;
use OCP\Server;
use OCP\Util;

/**
 * @param string $string
 */
function p($string): void {
	print(Util::sanitizeHTML($string));
}

/**
 * Prints a <link> tag for loading css
 * @param string $href the source URL, ignored when empty
 * @param string $opts, additional optional options
 */
function emit_css_tag($href, $opts = ''): void {
	$s = '<link rel="stylesheet"';
	if (!empty($href)) {
		$s .= ' href="' . $href . '"';
	}
	if (!empty($opts)) {
		$s .= ' ' . $opts;
	}
	print_unescaped($s . ">\n");
}

/**
 * Prints all tags for CSS loading
 * @param array $obj all the script information from template
 */
function emit_css_loading_tags($obj): void {
	foreach ($obj['cssfiles'] as $css) {
		emit_css_tag($css);
	}
	foreach ($obj['printcssfiles'] as $css) {
		emit_css_tag($css, 'media="print"');
	}
}

/**
 * Prints a <script> tag with nonce and defer depending on config
 * @param string $src the source URL, ignored when empty
 * @param string $script_content the inline script content, ignored when empty
 * @param string $content_type the type of the source (e.g. 'module')
 */
function emit_script_tag(string $src, string $script_content = '', string $content_type = ''): void {
	$nonceManager = Server::get(ContentSecurityPolicyNonceManager::class);

	$defer_str = ' defer';
	$type = $content_type !== '' ? ' type="' . $content_type . '"' : '';

	$s = '<script nonce="' . $nonceManager->getNonce() . '"';
	if (!empty($src)) {
		// emit script tag for deferred loading from $src
		$s .= $defer_str . ' src="' . $src . '"' . $type . '>';
	} elseif ($script_content !== '') {
		// emit script tag for inline script from $script_content without defer (see MDN)
		$s .= ">\n" . $script_content . "\n";
	} else {
		// no $src nor $src_content, really useless empty tag
		$s .= '>';
	}
	$s .= '</script>';
	print_unescaped($s . "\n");
}

/**
 * Print all <script> tags for loading JS
 * @param array $obj all the script information from template
 */
function emit_script_loading_tags($obj): void {
	foreach ($obj['jsfiles'] as $jsfile) {
		$fileName = explode('?', $jsfile, 2)[0];
		$type = str_ends_with($fileName, '.mjs') ? 'module' : '';
		emit_script_tag($jsfile, '', $type);
	}
	if (!empty($obj['inline_ocjs'])) {
		emit_script_tag('', $obj['inline_ocjs']);
	}
}

/**
 * Prints an unsanitized string - usage of this function may result into XSS.
 * Consider using p() instead.
 * @param string $string the string which will be printed as it is
 */
function print_unescaped($string): void {
	print($string);
}

/**
 * Shortcut for adding scripts to a page
 * All scripts are forced to be loaded after core since
 * they are coming from a template registration.
 * Please consider moving them into the relevant controller
 *
 * @deprecated 24.0.0 - Use \OCP\Util::addScript
 *
 * @param string $app the appname
 * @param string|string[] $file the filename,
 *                              if an array is given it will add all scripts
 */
function script($app, $file = null): void {
	if (is_array($file)) {
		foreach ($file as $script) {
			Util::addScript($app, $script, 'core');
		}
	} else {
		Util::addScript($app, $file, 'core');
	}
}

/**
 * Shortcut for adding styles to a page
 * @param string $app the appname
 * @param string|string[] $file the filename,
 *                              if an array is given it will add all styles
 */
function style($app, $file = null): void {
	if (is_array($file)) {
		foreach ($file as $f) {
			Util::addStyle($app, $f);
		}
	} else {
		Util::addStyle($app, $file);
	}
}

/**
 * Shortcut for adding vendor styles to a page
 * @param string $app the appname
 * @param string|string[] $file the filename,
 *                              if an array is given it will add all styles
 * @deprecated 32.0.0
 */
function vendor_style($app, $file = null): void {
	if (is_array($file)) {
		foreach ($file as $f) {
			OC_Util::addVendorStyle($app, $f);
		}
	} else {
		OC_Util::addVendorStyle($app, $file);
	}
}

/**
 * Shortcut for adding translations to a page
 * @param string $app the appname
 *                    if an array is given it will add all styles
 */
function translation($app): void {
	Util::addTranslations($app);
}

/**
 * make \OCP\IURLGenerator::linkTo available as a simple function
 * @param string $app app
 * @param string $file file
 * @param array $args array with param=>value, will be appended to the returned url
 * @return string link to the file
 *
 * For further information have a look at \OCP\IURLGenerator::linkTo
 */
function link_to($app, $file, $args = []) {
	return Server::get(IURLGenerator::class)->linkTo($app, $file, $args);
}

/**
 * @param string $key
 * @return string url to the online documentation
 */
function link_to_docs($key) {
	return Server::get(IURLGenerator::class)->linkToDocs($key);
}

/**
 * make \OCP\IURLGenerator::imagePath available as a simple function
 * @param string $app app
 * @param string $image image
 * @return string link to the image
 *
 * For further information have a look at \OCP\IURLGenerator::imagePath
 */
function image_path($app, $image) {
	return Server::get(IURLGenerator::class)->imagePath($app, $image);
}

/**
 * make mimetypeIcon available as a simple function
 * @param string $mimetype mimetype
 * @return string link to the image
 */
function mimetype_icon($mimetype) {
	return Server::get(IMimeTypeDetector::class)->mimeTypeIcon($mimetype);
}

/**
 * make preview_icon available as a simple function
 * Returns the path to the preview of the image.
 * @param string $path path of file
 * @return string link to the preview
 */
function preview_icon($path) {
	return Server::get(IURLGenerator::class)->linkToRoute('core.Preview.getPreview', ['x' => 32, 'y' => 32, 'file' => $path]);
}

/**
 * @param string $path
 * @param string $token
 * @return string
 */
function publicPreview_icon($path, $token) {
	return Server::get(IURLGenerator::class)->linkToRoute('files_sharing.PublicPreview.getPreview', ['x' => 32, 'y' => 32, 'file' => $path, 'token' => $token]);
}

/**
 * make Util::humanFileSize available as a simple function
 * @param int $bytes size in bytes
 * @return string size as string
 * @deprecated use Util::humanFileSize instead
 *
 * For further information have a look at Util::humanFileSize
 */
function human_file_size($bytes) {
	return Util::humanFileSize($bytes);
}

/**
 * Strips the timestamp of its time value
 * @param int $timestamp UNIX timestamp to strip
 * @return int timestamp without time value
 */
function strip_time($timestamp) {
	$date = new \DateTime("@{$timestamp}");
	$date->setTime(0, 0, 0);
	return (int)$date->format('U');
}

/**
 * Formats timestamp relatively to the current time using
 * a human-friendly format like "x minutes ago" or "yesterday"
 * @param int $timestamp timestamp to format
 * @param int|null $fromTime timestamp to compare from, defaults to current time
 * @param bool|null $dateOnly whether to strip time information
 * @return string timestamp
 */
function relative_modified_date($timestamp, $fromTime = null, $dateOnly = false): string {
	$formatter = Server::get(IDateTimeFormatter::class);

	if ($dateOnly) {
		return $formatter->formatDateSpan($timestamp, $fromTime);
	}
	return $formatter->formatTimeSpan($timestamp, $fromTime);
}

/**
 * @param array $options
 * @param string[]|string $selected
 * @param array $params
 */
function html_select_options($options, $selected, $params = []): string {
	if (!is_array($selected)) {
		$selected = [$selected];
	}
	if (isset($params['combine']) && $params['combine']) {
		$options = array_combine($options, $options);
	}
	$value_name = $label_name = false;
	if (isset($params['value'])) {
		$value_name = $params['value'];
	}
	if (isset($params['label'])) {
		$label_name = $params['label'];
	}
	$html = '';
	foreach ($options as $value => $label) {
		if ($value_name && is_array($label)) {
			$value = $label[$value_name];
		}
		if ($label_name && is_array($label)) {
			$label = $label[$label_name];
		}
		$select = in_array($value, $selected) ? ' selected="selected"' : '';
		$html .= '<option value="' . Util::sanitizeHTML($value) . '"' . $select . '>' . Util::sanitizeHTML($label) . '</option>' . "\n";
	}
	return $html;
}
