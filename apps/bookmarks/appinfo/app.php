<?php
/**
* Copyright (c) 2011 Marvin Thomas Rabe <mrabe@marvinrabe.de>
* Copyright (c) 2011 Arthur Schiwon <blizzz@arthur-schiwon.de>
* This file is licensed under the Affero General Public License version 3 or
* later.
* See the COPYING-README file.
*/

OC::$CLASSPATH['OC_Bookmarks_Bookmarks'] = 'apps/bookmarks/lib/bookmarks.php';
OC::$CLASSPATH['OC_Search_Provider_Bookmarks'] = 'apps/bookmarks/lib/search.php';

OCP\App::register( array( 'order' => 70, 'id' => 'bookmark', 'name' => 'Bookmarks' ));

$l = new OC_l10n('bookmarks');
OCP\App::addNavigationEntry( array( 'id' => 'bookmarks_index', 'order' => 70, 'href' => OCP\Util::linkTo( 'bookmarks', 'index.php' ), 'icon' => OCP\Util::imagePath( 'bookmarks', 'bookmarks.png' ), 'name' => $l->t('Bookmarks')));

OCP\App::registerPersonal('bookmarks', 'settings');
OCP\Util::addscript('bookmarks','bookmarksearch');

OC_Search::registerProvider('OC_Search_Provider_Bookmarks');
