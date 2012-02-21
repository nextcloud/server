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
require_once('../../lib/base.php');
OC_Util::checkLoggedIn();
OC_Util::checkAppEnabled('contacts');

$id = $_GET['id'];

$contact = OC_Contacts_App::getContactVCard($id);
$image = new OC_Image();
// invalid vcard
if( is_null($contact)) {
	OC_Log::write('contacts','photo.php. The VCard for ID '.$id.' is not RFC compatible',OC_Log::ERROR);
} else {
	OC_Response::enableCaching();
	OC_Contacts_App::setLastModifiedHeader($contact);

	// Photo :-)
	if($image->loadFromBase64($contact->getAsString('PHOTO'))) {
		// OK
		OC_Response::setETagHeader(md5($contact->getAsString('PHOTO')));
	}
	else
	// Logo :-/
	if($image->loadFromBase64($contact->getAsString('LOGO'))) {
		// OK
		OC_Response::setETagHeader(md5($contact->getAsString('LOGO')));
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
	$image->loadFromFile('img/person_large.png');
}
header('Content-Type: '.$image->mimeType());
$image->show();
//echo OC_Contacts_App::$l10n->t('This card does not contain a photo.');
