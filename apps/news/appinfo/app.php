<?php
/**
* ownCloud - News app
*
* @author Alessandro Cosentino
* Copyright (c) 2012 - Alessandro Cosentino <cosenal@gmail.com>
* 
* This file is licensed under the Affero General Public License version 3 or later.
* See the COPYING-README file
* 
*/

OC::$CLASSPATH['OC_News_Item'] = 'apps/news/lib/item.php';
OC::$CLASSPATH['OC_News_Collection'] = 'apps/news/lib/collection.php';
OC::$CLASSPATH['OC_News_Feed'] = 'apps/news/lib/feed.php';
OC::$CLASSPATH['OC_News_Folder'] = 'apps/news/lib/folder.php';

OC::$CLASSPATH['OC_News_FeedMapper'] = 'apps/news/lib/feedmapper.php';
OC::$CLASSPATH['OC_News_ItemMapper'] = 'apps/news/lib/itemmapper.php';
OC::$CLASSPATH['OC_News_FolderMapper'] = 'apps/news/lib/foldermapper.php';

OC::$CLASSPATH['OC_News_Utils'] = 'apps/news/lib/utils.php';


$l = new OC_l10n('news');

OCP\App::registerPersonal('news', 'settings');

OCP\App::register( array( 
  'order' => 70, 
  'id' => 'news', 
  'name' => 'News' 
));

OCP\App::addNavigationEntry( array( 
  'id' => 'news', 
  'order' => 74, 
  'href' => OC_Helper::linkTo( 'news', 'index.php' ), 
  'icon' => OC_Helper::imagePath( 'news', 'icon.svg' ), 
  'name' => $l->t('News')
));

