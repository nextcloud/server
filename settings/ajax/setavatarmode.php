<?php
/**
 * Copyright (c) 2013 Christopher Schäpers <christopher@schaepers.it>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

OC_Util::checkAdminUser();
OCP\JSON::callCheck();

OC_Config::setValue('avatar', $_POST['mode']);
