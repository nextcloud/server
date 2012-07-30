<?php
/**
 * Copyright (c) 2012, Tom Needham <tom@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

// Config
OC_API::register('get', '/config.{format}', array('OC_API_Config', 'apiConfig'));
// Person
OC_API::register('post', '/person/check.{format}', array('OC_API_Person', 'check'));
// Activity
OC_API::register('get', '/activity.{format}', array('OC_API_Activity', 'activityGet'));
OC_API::register('post', '/activity.{format}', array('OC_API_Activity', 'activityPut'));
// Privatedata
OC_API::register('get', '/privatedata/getattribute/{app}/{key}.{format}', array('OC_API_Privatedata', 'privatedataGet'));
OC_API::register('post', '/privatedata/setattribute/{app}/{key}.{format}', array('OC_API_Privatedata', 'privatedataPut'));
OC_API::register('post', '/privatedata/deleteattribute/{app}/{key}.{format}', array('OC_API_Privatedata', 'privatedataDelete'));
// Cloud
OC_API::register('get', '/cloud/system/webapps.{format}', array('OC_API_Cloud', 'systemwebapps'));
OC_API::register('get', '/cloud/user/{user}.{format}', array('OC_API_Cloud', 'getQuota'));
OC_API::register('post', '/cloud/user/{user}.{format}', array('OC_API_Cloud', 'setQuota'));
OC_API::register('get', '/cloud/user/{user}/publickey.{format}', array('OC_API_Cloud', 'getPublicKey'));
OC_API::register('get', '/cloud/user/{user}/privatekey.{format}', array('OC_API_Cloud', 'getPrivateKey'));
?>