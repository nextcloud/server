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

function getStandardImage() {
	//OCP\Response::setExpiresHeader('P10D');
	OCP\Response::enableCaching();
	OCP\Response::redirect(OCP\Util::imagePath('contacts', 'person_large.png'));
}

$id = isset($_GET['id']) ? $_GET['id'] : null;
$etag = null;
$caching = null;

if(is_null($id)) {
	getStandardImage();
}

if(!extension_loaded('gd') || !function_exists('gd_info')) {
	OCP\Util::writeLog('contacts',
		'photo.php. GD module not installed', OCP\Util::DEBUG);
	getStandardImage();
}

$contact = OC_Contacts_App::getContactVCard($id);
$image = new OC_Image();
if (!$image) {
	getStandardImage();
}
// invalid vcard
if (is_null($contact)) {
	OCP\Util::writeLog('contacts',
		'photo.php. The VCard for ID ' . $id . ' is not RFC compatible',
		OCP\Util::ERROR);
} else {
	// Photo :-)
	if ($image->loadFromBase64($contact->getAsString('PHOTO'))) {
		// OK
		$etag = md5($contact->getAsString('PHOTO'));
	}
	else
	// Logo :-/
	if ($image->loadFromBase64($contact->getAsString('LOGO'))) {
		// OK
		$etag = md5($contact->getAsString('LOGO'));
	}
	if ($image->valid()) {
		$modified = OC_Contacts_App::lastModified($contact);
		// Force refresh if modified within the last minute.
		if(!is_null($modified)) {
			$caching = (time() - $modified->format('U') > 60) ? null : 0;
		}
		OCP\Response::enableCaching($caching);
		if(!is_null($modified)) {
			OCP\Response::setLastModifiedHeader($modified);
		}
		if($etag) {
			OCP\Response::setETagHeader($etag);
		}
		$max_size = 200;
		if ($image->width() > $max_size || $image->height() > $max_size) {
			$image->resize($max_size);
		}
	}
}
if (!$image->valid()) {
	// Not found :-(
	getStandardImage();
}
header('Content-Type: '.$image->mimeType());
$image->show();

