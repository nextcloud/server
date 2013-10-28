<?php
/**
 * Copyright (c) 2012 Thomas Müller <thomas.mueller@tmit.eu>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once __DIR__.'/../lib/base.php';

OC_App::enable('files_sharing');
OC_App::enable('files_encryption');
OC_App::enable('calendar');
OC_App::enable('contacts');
OC_App::enable('apptemplateadvanced');
OC_App::enable('appframework');
#OC_App::enable('files_archive');
#OC_App::enable('mozilla_sync');
#OC_App::enable('news');
#OC_App::enable('provisioning_api');
#OC_App::enable('user_external');

