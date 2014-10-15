<?php
/**
 * @author Lukas Reschke
 * @copyright 2014 Lukas Reschke lukas@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

// This file is just used to redirect the legacy sharing URLs (< ownCloud 8) to the new ones

$urlGenerator = new \OC\URLGenerator(\OC::$server->getConfig());
$token = isset($_GET['t']) ? $_GET['t'] : '';
$route = isset($_GET['download']) ? 'files_sharing.sharecontroller.downloadshare' : 'files_sharing.sharecontroller.showshare';

OC_Response::redirect($urlGenerator->linkToRoute($route, array('token' => $token)));
