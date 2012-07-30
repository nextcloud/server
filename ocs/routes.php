<?php
/**
 * Copyright (c) 2012, Tom Needham <tom@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

// Config
OC_API::register('get', '/config', array('OC_OCS_Config', 'apiConfig'), 'ocs');
// Person
OC_API::register('post', '/person/check', array('OC_OCS_Person', 'check'), 'ocs');
// Activity
OC_API::register('get', '/activity', array('OC_OCS_Activity', 'activityGet'), 'ocs');
// Privatedata
OC_API::register('get', '/privatedata/getattribute/{app}/{key}', array('OC_OCS_Privatedata', 'privatedataGet'), 'ocs');
OC_API::register('post', '/privatedata/setattribute/{app}/{key}', array('OC_OCS_Privatedata', 'privatedataPut'), 'ocs');
OC_API::register('post', '/privatedata/deleteattribute/{app}/{key}', array('OC_OCS_Privatedata', 'privatedataDelete'), 'ocs');
// Cloud
OC_API::register('get', '/cloud/system/webapps', array('OC_OCS_Cloud', 'systemwebapps'), 'ocs');
OC_API::register('get', '/cloud/user/{user}', array('OC_OCS_Cloud', 'getQuota'), 'ocs');
OC_API::register('post', '/cloud/user/{user}', array('OC_OCS_Cloud', 'setQuota'), 'ocs');
OC_API::register('get', '/cloud/user/{user}/publickey', array('OC_OCS_Cloud', 'getPublicKey'), 'ocs');
OC_API::register('get', '/cloud/user/{user}/privatekey', array('OC_OCS_Cloud', 'getPrivateKey'), 'ocs');

?>
