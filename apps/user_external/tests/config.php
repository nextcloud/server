<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

return array(
	'imap'=>array(
		'run'=>false,
		'mailbox'=>'{imap.gmail.com:993/imap/ssl}INBOX', //see http://php.net/manual/en/function.imap-open.php
		'user'=>'foo',//valid username/password combination
		'password'=>'bar',
	),
	'smb'=>array(
		'run'=>false,
		'host'=>'localhost',
		'user'=>'test',//valid username/password combination
		'password'=>'test',
	),
	'ftp'=>array(
		'run'=>false,
		'host'=>'localhost',
		'user'=>'test',//valid username/password combination
		'password'=>'test',
	),
);
