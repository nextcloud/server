<?php
/**
 * Copyright (c) 2013, Tom Needham <tom@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

// Register with the capabilities API
OC_API::register('get', '/cloud/capabilities', array('OC_Files_Versions_Capabiltiies', 'getCapabilities'), 'ocs', OC_API::USER_AUTH);