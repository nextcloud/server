<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
require_once __DIR__ . '/../lib/versioncheck.php';
require_once __DIR__ . '/../lib/base.php';

header('Content-type: application/xml');

$request = \OC::$server->getRequest();

$url = $request->getServerProtocol() . '://' . substr($request->getServerHost() . $request->getRequestUri(), 0, -17) . 'ocs/v1.php/';

$writer = new XMLWriter();
$writer->openURI('php://output');
$writer->startDocument('1.0', 'UTF-8');
$writer->setIndent(true);
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
