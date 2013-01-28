<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

$this->create('download', 'download{file}')
	->requirements(array('file' => '.*'))
	->actionInclude('files/download.php');