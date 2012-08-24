<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

OC::$CLASSPATH['OC_Files_Sharing_Log'] = 'apps/files_sharing_log/log.php';

$l=new OC_L10N('files_sharing_log');
OCP\App::addNavigationEntry( array(
  'id' => 'files_sharing_log_index',
  'order' => 5,
  'href' => OCP\Util::linkTo( 'files_sharing_log', 'index.php' ),
  'icon' => OCP\Util::imagePath( 'files_sharing_log', 'icon.png' ),
  'name' => $l->t('Shared files log'))
);

OCP\Util::connectHook('OC_Filestorage_Shared', 'fopen', 'OC_Files_Sharing_Log', 'fopen');
OCP\Util::connectHook('OC_Filestorage_Shared', 'file_get_contents', 'OC_Files_Sharing_Log', 'file_get_contents');
OCP\Util::connectHook('OC_Filestorage_Shared', 'file_put_contents', 'OC_Files_Sharing_Log', 'file_put_contents');
