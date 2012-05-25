<?php
/**
* ownCloud
*
* @author Frank Karlitschek
* @copyright 2010 Frank Karlitschek karlitschek@kde.org
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
 * @brief make OC_Helper::imagePath available as a simple function
 * @param $app app
 * @param $image image
 * @returns link to the image
 *
 * For further information have a look at OC_Helper::imagePath
 */
function image_path( $app, $image ){
	return(\image_path( $app, $image ));
}


/**
 * @brief make OC_Helper::mimetypeIcon available as a simple function
 * Returns the path to the image of this file type.
 * @param $mimetype mimetype
 * @returns link to the image
 */
function mimetype_icon( $mimetype ){
	return(\mimetype_icon( $mimetype ));
}


/**
 * @brief make OC_Helper::humanFileSize available as a simple function
 * Makes 2048 to 2 kB.
 * @param $bytes size in bytes
 * @returns size as string
 */
function human_file_size( $bytes ){
	return(\human_file_size( $bytes ));
}


/**
 * @brief Return the relative date in relation to today. Returns something like "last hour" or "two month ago"
 * @param $timestamp unix timestamp
 * @returns human readable interpretation of the timestamp
 */
function relative_modified_date($timestamp) {
	return(\relative_modified_date($timestamp));
}


/**
 * @brief Return a human readable outout for a file size.
 * @param $byte size of a file in byte
 * @returns human readable interpretation of a file size
 */
function simple_file_size($bytes) {
	return(\simple_file_size($bytes));
}


/**
 * @brief Generate html code for an options block.
 * @param $options the options 
 * @param $selected which one is selected? 
 * @param $params the parameters 
 * @returns html options
 */
function html_select_options($options, $selected, $params=array()) {
	return(\html_select_options($options, $selected, $params)); 
}


/**
 * This class provides the template system for owncloud. You can use it to load specific templates, add data and generate the html code
 */
class Template extends \OC_Template {

}


?>
