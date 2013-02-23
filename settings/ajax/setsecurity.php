<?php
/**
 * Copyright (c) 2013, Lukas Reschke <lukas@statuscode.ch>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OC_Util::checkAdminUser();
OCP\JSON::callCheck();

OC_Config::setValue( 'forcessl', filter_var($_POST['enforceHTTPS'], FILTER_VALIDATE_BOOLEAN));

echo 'true';