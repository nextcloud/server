<?php
/**
 * Copyright (c) 2012, Tom Needham <tom@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

// Config
OC_API::register('get', '/config', array('OC_OCS_Config', 'apiConfig'), 'ocs', OC_API::GUEST_AUTH);
// Person
OC_API::register('post', '/person/check', array('OC_OCS_Person', 'check'), 'ocs', OC_API::GUEST_AUTH);
// Activity
OC_API::register('get', '/activity', array('OC_OCS_Activity', 'activityGet'), 'ocs', OC_API::USER_AUTH); 
// Privatedata
OC_API::register('get', '/privatedata/getattribute', array('OC_OCS_Privatedata', 'get'), 'ocs', OC_API::USER_AUTH, array('app' => '', 'key' => ''));
OC_API::register('get', '/privatedata/getattribute/{app}', array('OC_OCS_Privatedata', 'get'), 'ocs', OC_API::USER_AUTH, array('key' => ''));
OC_API::register('get', '/privatedata/getattribute/{app}/{key}', array('OC_OCS_Privatedata', 'get'), 'ocs', OC_API::USER_AUTH);
OC_API::register('post', '/privatedata/setattribute/{app}/{key}', array('OC_OCS_Privatedata', 'set'), 'ocs', OC_API::USER_AUTH);
OC_API::register('post', '/privatedata/deleteattribute/{app}/{key}', array('OC_OCS_Privatedata', 'delete'), 'ocs', OC_API::USER_AUTH);
// cloud
OC_API::register('get', '/cloud/capabilities', array('OC_OCS_Cloud', 'getCapabilities'), 'core', OC_API::USER_AUTH);