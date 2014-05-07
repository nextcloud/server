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
 * @param string app
 * @param string image
 * @return string to the image
 *
 * @see OC_Helper::imagePath
 */
function image_path( $app, $image ) {
	return(\image_path( $app, $image ));
}


/**
 * Make OC_Helper::mimetypeIcon available as a simple function
 * @param string mimetype
 * @return string to the image of this file type.
 */
function mimetype_icon( $mimetype ) {
	return(\mimetype_icon( $mimetype ));
}

/**
 * Make preview_icon available as a simple function
 * @param string path of file
 * @return string to the preview of the image
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
 */
function publicPreview_icon ( $path, $token ) {
	return(\publicPreview_icon( $path, $token ));
}

/**
 * Make OC_Helper::humanFileSize available as a simple function
 * Example: 2048 to 2 kB.
 * @param int size in bytes
 * @return string size as string
 */
function human_file_size( $bytes ) {
	return(\human_file_size( $bytes ));
}


/**
 * Return the relative date in relation to today. Returns something like "last hour" or "two month ago"
 * @param int unix timestamp
 * @param boolean date only
 * @return OC_L10N_String human readable interpretation of the timestamp
 */
function relative_modified_date( $timestamp, $dateOnly = false ) {
	return(\relative_modified_date($timestamp, null, $dateOnly));
}


/**
 * Return a human readable outout for a file size.
 * @deprecated human_file_size() instead
 * @param integer size of a file in byte
 * @return string human readable interpretation of a file size
 */
function simple_file_size($bytes) {
	return(\human_file_size($bytes));
}


/**
 * Generate html code for an options block.
 * @param $options the options
 * @param $selected which one is selected?
 * @param array the parameters
 * @return string html options
 */
function html_select_options($options, $selected, $params=array()) {
	return(\html_select_options($options, $selected, $params));
}


/**
 * This class provides the template system for owncloud. You can use it to load
 * specific templates, add data and generate the html code
 */
class Template extends \OC_Template {

}
