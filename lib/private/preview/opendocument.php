<?php
/**
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OC\Preview;

//.odt, .ott, .oth, .odm, .odg, .otg, .odp, .otp, .ods, .ots, .odc, .odf, .odb, .odi, .oxt
class OpenDocument extends Office {
	/**
	 * {@inheritDoc}
	 */
	public function getMimeType() {
		return '/application\/vnd.oasis.opendocument.*/';
	}
}
