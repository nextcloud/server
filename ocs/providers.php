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

require_once '../lib/base.php';

header('Content-type: application/xml');

$url=OCP\Util::getServerProtocol().'://'.substr(OCP\Util::getServerHost().OCP\Util::getRequestUri(), 0, -17).'ocs/v1.php/';

$writer = new XMLWriter();
$writer->openURI('php://output');
$writer->startDocument('1.0','UTF-8');
$writer->setIndent(4);
$writer->startElement('providers');
$writer->startElement('provider');
$writer->writeElement('id', 'ownCloud');
$writer->writeElement('location', $url);
$writer->writeElement('name', 'ownCloud');
$writer->writeElement('icon', '');
$writer->writeElement('termsofuse', '');
$writer->writeElement('register', '');
$writer->startElement('services');
$writer->startElement('config');
$writer->writeAttribute('ocsversion', '1.7');
$writer->endElement();
$writer->startElement('activity');
$writer->writeAttribute('ocsversion', '1.7');
$writer->endElement();
$writer->startElement('cloud');
$writer->writeAttribute('ocsversion', '1.7');
$writer->endElement();
$writer->endElement();
$writer->endElement();
$writer->endDocument();
$writer->flush();
