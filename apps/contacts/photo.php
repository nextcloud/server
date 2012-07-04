<?php
/**
 * Copyright (c) 2012 Thomas Tanghus <thomas@tanghus.net>
 * Copyright (c) 2011, 2012 Bart Visscher <bartv@thisnet.nl>
 * Copyright (c) 2011 Jakob Sack mail@jakobsack.de
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

// Init owncloud

OCP\User::checkLoggedIn();
OCP\App::checkAppEnabled('contacts');

function getStandardImage(){
	//OCP\Response::setExpiresHeader('P10D');
	OCP\Response::enableCaching();
	OCP\Response::redirect(OCP\Util::imagePath('contacts', 'person_large.png'));
}

$id = isset($_GET['id']) ? $_GET['id'] : null;
$caching = isset($_GET['refresh']) ? 0 : null;

if(is_null($id)) {
	getStandardImage();
}

if(!extension_loaded('gd') || !function_exists('gd_info')) {
	OCP\Util::writeLog('contacts','photo.php. GD module not installed',OCP\Util::DEBUG);
	getStandardImage();
}

$contact = OC_Contacts_App::getContactVCard($id);
$image = new OC_Image();
if(!$image) {
	getStandardImage();
}
// invalid vcard
if( is_null($contact)) {
	OCP\Util::writeLog('contacts','photo.php. The VCard for ID '.$id.' is not RFC compatible',OCP\Util::ERROR);
} else {
	OCP\Response::enableCaching($caching);
	OC_Contacts_App::setLastModifiedHeader($contact);

	// Photo :-)
	if($image->loadFromBase64($contact->getAsString('PHOTO'))) {
		// OK
		OCP\Response::setETagHeader(md5($contact->getAsString('PHOTO')));
	}
	else
	// Logo :-/
	if($image->loadFromBase64($contact->getAsString('LOGO'))) {
		// OK
		OCP\Response::setETagHeader(md5($contact->getAsString('LOGO')));
	}
	if ($image->valid()) {
		$max_size = 200;
		if($image->width() > $max_size ||
		   $image->height() > $max_size) {
			$image->resize($max_size);
		}
	}
}
if (!$image->valid()) {
	// Not found :-(
	getStandardImage();
	//$image->loadFromFile('img/person_large.png');
}
header('Content-Type: '.$image->mimeType());
$image->show();
//echo OC_Contacts_App::$l10n->t('This card does not contain a photo.');
