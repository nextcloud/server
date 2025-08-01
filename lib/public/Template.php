<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP;

use OCP\Template\ITemplate;

/*
 * We have to require the functions file because this class contains aliases to the functions
 */
require_once __DIR__ . '/../private/Template/functions.php';

/**
 * This class provides the template system for owncloud. You can use it to load
 * specific templates, add data and generate the html code
 *
 * @since 8.0.0
 * @deprecated 32.0.0 Use \OCP\Template\ITemplateManager instead
 */
class Template extends \OC_Template implements ITemplate {
	/**
	 * Make \OCP\IURLGenerator::imagePath available as a simple function
	 *
	 * @see \OCP\IURLGenerator::imagePath
	 *
	 * @param string $app
	 * @param string $image
	 * @return string to the image
	 * @since 8.0.0
	 * @deprecated 32.0.0 Use the function directly instead
	 */
	public static function image_path($app, $image) {
		return \image_path($app, $image);
	}


	/**
	 * Make IMimeTypeDetector->mimeTypeIcon available as a simple function
	 *
	 * @param string $mimetype
	 * @return string to the image of this file type.
	 * @since 8.0.0
	 * @deprecated 32.0.0 Use the function directly instead
	 */
	public static function mimetype_icon($mimetype) {
		return \mimetype_icon($mimetype);
	}

	/**
	 * Make preview_icon available as a simple function
	 *
	 * @param string $path path to file
	 * @return string to the preview of the image
	 * @since 8.0.0
	 * @deprecated 32.0.0 Use the function directly instead
	 */
	public static function preview_icon($path) {
		return \preview_icon($path);
	}

	/**
	 * Make publicpreview_icon available as a simple function
	 * Returns the path to the preview of the image.
	 *
	 * @param string $path of file
	 * @param string $token
	 * @return string link to the preview
	 * @since 8.0.0
	 * @deprecated 32.0.0 Use the function directly instead
	 */
	public static function publicPreview_icon($path, $token) {
		return \publicPreview_icon($path, $token);
	}

	/**
	 * Make \OCP\Util::humanFileSize available as a simple function
	 * Example: 2048 to 2 kB.
	 *
	 * @param int $bytes in bytes
	 * @return string size as string
	 * @since 8.0.0
	 * @deprecated 32.0.0 Use \OCP\Util::humanFileSize instead
	 */
	public static function human_file_size($bytes) {
		return Util::humanFileSize($bytes);
	}

	/**
	 * Return the relative date in relation to today. Returns something like "last hour" or "two month ago"
	 *
	 * @param int $timestamp unix timestamp
	 * @param boolean $dateOnly
	 * @return string human readable interpretation of the timestamp
	 * @since 8.0.0
	 * @suppress PhanTypeMismatchArgument
	 * @deprecated 32.0.0 Use the function directly instead
	 */
	public static function relative_modified_date($timestamp, $dateOnly = false) {
		return \relative_modified_date($timestamp, null, $dateOnly);
	}

	/**
	 * Generate html code for an options block.
	 *
	 * @param array $options the options
	 * @param mixed $selected which one is selected?
	 * @param array $params the parameters
	 * @return string html options
	 * @since 8.0.0
	 * @deprecated 32.0.0 Use the function directly instead
	 */
	public static function html_select_options($options, $selected, $params = []) {
		return \html_select_options($options, $selected, $params);
	}
}
