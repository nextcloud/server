<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * Prints an XSS escaped string
 * @param string $string the string which will be escaped and printed
 */
function p($string) {
	print(OC_Util::sanitizeHTML($string));
}

/**
 * Prints an unescaped string
 * @param string $string the string which will be printed as it is
 */
function print_unescaped($string) {
	print($string);
}

/**
 * @brief make OC_Helper::linkTo available as a simple function
 * @param string $app app
 * @param string $file file
 * @param array $args array with param=>value, will be appended to the returned url
 * @return string link to the file
 *
 * For further information have a look at OC_Helper::linkTo
 */
function link_to( $app, $file, $args = array() ) {
	return OC_Helper::linkTo( $app, $file, $args );
}

/**
 * @brief make OC_Helper::imagePath available as a simple function
 * @param string $app app
 * @param string $image image
 * @return string link to the image
 *
 * For further information have a look at OC_Helper::imagePath
 */
function image_path( $app, $image ) {
	return OC_Helper::imagePath( $app, $image );
}

/**
 * @brief make OC_Helper::mimetypeIcon available as a simple function
 * @param string $mimetype mimetype
 * @return string link to the image
 *
 * For further information have a look at OC_Helper::mimetypeIcon
 */
function mimetype_icon( $mimetype ) {
	return OC_Helper::mimetypeIcon( $mimetype );
}

/**
 * @brief make OC_Helper::humanFileSize available as a simple function
 * @param int $bytes size in bytes
 * @return string size as string
 *
 * For further information have a look at OC_Helper::humanFileSize
 */
function human_file_size( $bytes ) {
	return OC_Helper::humanFileSize( $bytes );
}

function relative_modified_date($timestamp) {
	$l=OC_L10N::get('lib');
	$timediff = time() - $timestamp;
	$diffminutes = round($timediff/60);
	$diffhours = round($diffminutes/60);
	$diffdays = round($diffhours/24);
	$diffmonths = round($diffdays/31);

	if($timediff < 60) { return $l->t('seconds ago'); }
	else if($timediff < 120) { return $l->t('1 minute ago'); }
	else if($timediff < 3600) { return $l->t('%d minutes ago', $diffminutes); }
	else if($timediff < 7200) { return $l->t('1 hour ago'); }
	else if($timediff < 86400) { return $l->t('%d hours ago', $diffhours); }
	else if((date('G')-$diffhours) > 0) { return $l->t('today'); }
	else if((date('G')-$diffhours) > -24) { return $l->t('yesterday'); }
	else if($timediff < 2678400) { return $l->t('%d days ago', $diffdays); }
	else if($timediff < 5184000) { return $l->t('last month'); }
	else if((date('n')-$diffmonths) > 0) { return $l->t('%d months ago', $diffmonths); }
	else if($timediff < 63113852) { return $l->t('last year'); }
	else { return $l->t('years ago'); }
}

function html_select_options($options, $selected, $params=array()) {
	if (!is_array($selected)) {
		$selected=array($selected);
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
	foreach($options as $value => $label) {
		if ($value_name && is_array($label)) {
			$value = $label[$value_name];
		}
		if ($label_name && is_array($label)) {
			$label = $label[$label_name];
		}
		$select = in_array($value, $selected) ? ' selected="selected"' : '';
		$html .= '<option value="' . OC_Util::sanitizeHTML($value) . '"' . $select . '>' . OC_Util::sanitizeHTML($label) . '</option>'."\n";
	}
	return $html;
}
