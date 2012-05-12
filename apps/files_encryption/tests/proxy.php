<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_CryptProxy extends UnitTestCase {
	
	public function setUp(){
		//set testing key
		$_SESSION['enckey']=md5(time());
	
		//clear all proxies and hooks so we can do clean testing
		OC_FileProxy::clearProxies();
		OC_Hook::clear('OC_Filesystem');

		//enable only the encryption hook
		OC_FileProxy::register(new OC_FileProxy_Encryption());

		//set up temporary storage
		OC_Filesystem::clearMounts();
		OC_Filesystem::mount('OC_Filestorage_Temporary',array(),'/');

		//set up the users home folder in the temp storage
		$rootView=new OC_FilesystemView('');
		$rootView->mkdir('/'.OC_User::getUser());
		$rootView->mkdir('/'.OC_User::getUser().'/files');
	}

	public function testSimple(){
		$file=OC::$SERVERROOT.'/3rdparty/MDB2.php';
		$original=file_get_contents($file);

		OC_Filesystem::file_put_contents('/file',$original);
		
		OC_FileProxy::$enabled=false;
		$stored=OC_Filesystem::file_get_contents('/file');
		OC_FileProxy::$enabled=true;
		
		$fromFile=OC_Filesystem::file_get_contents('/file');
		$this->assertNotEqual($original,$stored);
		$this->assertEqual($original,$fromFile);
	}
}
