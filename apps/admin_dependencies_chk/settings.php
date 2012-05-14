<?php

/**
 * ownCloud - user_ldap
 *
 * @author Brice Maron
 * @copyright 2011 Brice Maron brice __from__ bmaron _DOT_ net
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
$l=OC_L10N::get('admin_dependencies_chk');
$tmpl = new OCP\Template( 'admin_dependencies_chk', 'settings');

$modules = array();

//Possible status are : ok, error, warning
$modules[] =array(
	'status' => function_exists('json_encode') ? 'ok' : 'error',
	'part'=> 'php-json',
	'modules'=> array('core'),
	'message'=> $l->t('The php-json module is needed by the many applications for inter communications'));

$modules[] =array(
	'status' => function_exists('curl_init') ? 'ok' : 'error',
	'part'=> 'php-curl',
	'modules'=> array('bookmarks'),
	'message'=> $l->t('The php-curl modude is needed to fetch the page title when adding a bookmarks'));

$modules[] =array(
	'status' => function_exists('imagepng') ? 'ok' : 'error',
	'part'=> 'php-gd',
	'modules'=> array('gallery'),
	'message'=> $l->t('The php-gd module is needed to create thumbnails of your images'));

$modules[] =array(
	'status' => function_exists("ldap_bind") ? 'ok' : 'error',
	'part'=> 'php-ldap',
	'modules'=> array('user_ldap'),
	'message'=> $l->t('The php-ldap module is needed connect to your ldap server'));

$modules[] =array(
	'status' => class_exists('ZipArchive') ? 'ok' : 'warning',
	'part'=> 'php-zip',
	'modules'=> array('admin_export','core'),
	'message'=> $l->t('The php-zip module is needed download multiple files at once'));

$modules[] =array(
	'status' => function_exists('mb_detect_encoding') ? 'ok' : 'error',
	'part'=> 'php-mb_multibyte ',
	'modules'=> array('core'),
	'message'=> $l->t('The php-mb_multibyte module is needed to manage correctly the encoding.'));

$modules[] =array(
	'status' => function_exists('ctype_digit') ? 'ok' : 'error',
	'part'=> 'php-ctype',
	'modules'=> array('core'),
	'message'=> $l->t('The php-ctype module is needed validate data.'));

$modules[] =array(
	'status' => class_exists('DOMDocument') ? 'ok' : 'error',
	'part'=> 'php-xml',
	'modules'=> array('core'),
	'message'=> $l->t('The php-xml module is needed to share files with webdav.'));

$modules[] =array(
	'status' => ini_get('allow_url_fopen') == '1' ? 'ok' : 'error',
	'part'=> 'allow_url_fopen',
	'modules'=> array('core'),
	'message'=> $l->t('The allow_url_fopen directive of your php.ini should be set to 1 to retrieve knowledge base from OCS servers'));

$modules[] =array(
	'status' => class_exists('PDO') ? 'ok' : 'warning',
	'part'=> 'php-pdo',
	'modules'=> array('core'),
	'message'=> $l->t('The php-pdo module is needed to store owncloud data into a database.'));

foreach($modules as $key => $module) {
	$enabled = false ;
	foreach($module['modules'] as $app) {
		if(OCP\App::isEnabled($app) || $app=='core'){
				$enabled = true;
		}
	}
	if($enabled == false) unset($modules[$key]);
}

OCP\UTIL::addStyle('admin_dependencies_chk', 'style');
$tmpl->assign( 'items', $modules );

return $tmpl->fetchPage();
