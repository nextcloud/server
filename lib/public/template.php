<?php
/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @copyright 2012 Frank Karlitschek frank@owncloud.org
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * Public interface of ownCloud for apps to use.
 * Template Class
 *
 */

// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal ownCloud classes
namespace OCP;


/**
 * Make OC_Helper::imagePath available as a simple function
 * @param string $app
 * @param string $image
 * @return string to the image
 *
 * @see OC_Helper::imagePath
 * @deprecated Use \OCP\Template::image_path() instead
 */
function image_path( $app, $image ) {
	return(\image_path( $app, $image ));
}


/**
 * Make OC_Helper::mimetypeIcon available as a simple function
 * @param string $mimetype
 * @return string to the image of this file type.
 * @deprecated Use \OCP\Template::mimetype_icon() instead
 */
function mimetype_icon( $mimetype ) {
	return(\mimetype_icon( $mimetype ));
}

/**
 * Make preview_icon available as a simple function
 * @param string $path path to file
 * @return string to the preview of the image
 * @deprecated Use \OCP\Template::preview_icon() instead
 */
function preview_icon( $path ) {
	return(\preview_icon( $path ));
}

/**
 * Make publicpreview_icon available as a simple function
 * Returns the path to the preview of the image.
 * @param string $path of file
 * @param string $token
 * @return string link to the preview
 * @deprecated Use \OCP\Template::publicPreview_icon() instead
 */
function publicPreview_icon ( $path, $token ) {
	return(\publicPreview_icon( $path, $token ));
}

/**
 * Make OC_Helper::humanFileSize available as a simple function
 * Example: 2048 to 2 kB.
 * @param int $bytes in bytes
 * @return string size as string
 * @deprecated Use \OCP\Template::human_file_size() instead
 */
function human_file_size( $bytes ) {
	return(\human_file_size( $bytes ));
}


/**
 * Return the relative date in relation to today. Returns something like "last hour" or "two month ago"
 * @param int $timestamp unix timestamp
 * @param boolean $dateOnly
 * @return \OC_L10N_String human readable interpretation of the timestamp
 *
 * @deprecated Use \OCP\Template::relative_modified_date() instead
 */
function relative_modified_date( $timestamp, $dateOnly = false ) {
	return(\relative_modified_date($timestamp, null, $dateOnly));
}


/**
 * Return a human readable outout for a file size.
 * @param integer $bytes size of a file in byte
 * @return string human readable interpretation of a file size
 * @deprecated Use \OCP\Template::human_file_size() instead
 */
function simple_file_size($bytes) {
	return(\human_file_size($bytes));
}


/**
 * Generate html code for an options block.
 * @param array $options the options
 * @param mixed $selected which one is selected?
 * @param array $params the parameters
 * @return string html options
 * @deprecated Use \OCP\Template::html_select_options() instead
 */
function html_select_options($options, $selected, $params=array()) {
	return(\html_select_options($options, $selected, $params));
}


/**
 * This class provides the template system for owncloud. You can use it to load
 * specific templates, add data and generate the html code
 */
class Template extends \OC_Template {
	/**
	 * Make OC_Helper::imagePath available as a simple function
	 *
	 * @see OC_Helper::imagePath
	 *
	 * @param string $app
	 * @param string $image
	 * @return string to the image
	 */
	public static function image_path($app, $image) {
		return \image_path($app, $image);
	}


	/**
	 * Make OC_Helper::mimetypeIcon available as a simple function
	 *
	 * @param string $mimetype
	 * @return string to the image of this file type.
	 */
	public static function mimetype_icon($mimetype) {
		return \mimetype_icon($mimetype);
	}

	/**
	 * Make preview_icon available as a simple function
	 *
	 * @param string $path path to file
	 * @return string to the preview of the image
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
	 */
	public static function publicPreview_icon($path, $token) {
		return \publicPreview_icon($path, $token);
	}

	/**
	 * Make OC_Helper::humanFileSize available as a simple function
	 * Example: 2048 to 2 kB.
	 *
	 * @param int $bytes in bytes
	 * @return string size as string
	 */
	public static function human_file_size($bytes) {
		return \human_file_size($bytes);
	}

	/**
	* Return the relative date in relation to today. Returns something like "last hour" or "two month ago"
	*
	* @param int $timestamp unix timestamp
	* @param boolean $dateOnly
	* @return string human readable interpretation of the timestamp
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
	 */
	public static function html_select_options($options, $selected, $params=array()) {
		return \html_select_options($options, $selected, $params);
	}
}
