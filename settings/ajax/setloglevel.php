<?php
/**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

require_once('../../lib/base.php');
OC_Util::checkAdminUser();
OCP\JSON::callCheck();

OC_Config::setValue( 'loglevel', $_POST['level'] );

echo 'true';

?>