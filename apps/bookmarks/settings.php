<?php
/**
 * Copyright (c) 2011 Marvin Thomas Rabe <m.rabe@echtzeitraum.de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

$tmpl = new OC_Template( 'bookmarks', 'settings');

//OC_Util::addScript('bookmarks','settings');

return $tmpl->fetchPage();
