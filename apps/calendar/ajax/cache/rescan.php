<?php
/**
 * Copyright (c) 2012 Georg Ehrke <georg@ownCloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('calendar');
OC_Calendar_Repeat::cleancalendar(OCP\USER::getUser());
OC_Calendar_Repeat::generatecalendar(OCP\USER::getUser());