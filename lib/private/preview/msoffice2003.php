<?php
/**
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OC\Preview;

//.docm, .dotm, .xls(m), .xlt(m), .xla(m), .ppt(m), .pot(m), .pps(m), .ppa(m)
class MSOffice2003 extends Office {
	/**
	 * {@inheritDoc}
	 */
	public function getMimeType() {
		return '/application\/vnd.ms-.*/';
	}
}
