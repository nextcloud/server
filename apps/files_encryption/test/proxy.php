<?php
/**
 * Copyright (c) 2012 Sam Tuke <samtuke@owncloud.com>, 
 * and Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

// require_once "PHPUnit/Framework/TestCase.php";
// require_once realpath( dirname(__FILE__).'/../../../lib/base.php' );
// require_once realpath( dirname(__FILE__).'/../../../3rdparty/mockery/Mockery.php' );
// require_once realpath( dirname(__FILE__).'/../../../3rdparty/mockery/Mockery/Generator.php' );
// require_once realpath( dirname(__FILE__).'/../../../3rdparty/mockery/Mockery/MockInterface.php' );
// require_once realpath( dirname(__FILE__).'/../../../3rdparty/mockery/Mockery/Mock.php' );
// require_once realpath( dirname(__FILE__).'/../../../3rdparty/mockery/Mockery/Container.php' );
// require_once realpath( dirname(__FILE__).'/../../../3rdparty/mockery/Mockery/Configuration.php' );
// require_once realpath( dirname(__FILE__).'/../../../3rdparty/mockery/Mockery/CompositeExpectation.php' );
// require_once realpath( dirname(__FILE__).'/../../../3rdparty/mockery/Mockery/ExpectationDirector.php' );
// require_once realpath( dirname(__FILE__).'/../../../3rdparty/mockery/Mockery/Expectation.php' );
// require_once realpath( dirname(__FILE__).'/../../../3rdparty/mockery/Mockery/Exception.php' );
// require_once realpath( dirname(__FILE__).'/../../../3rdparty/mockery/Mockery/CountValidator/CountValidatorAbstract.php' );
// require_once realpath( dirname(__FILE__).'/../../../3rdparty/mockery/Mockery/CountValidator/Exception.php' );
// require_once realpath( dirname(__FILE__).'/../../../3rdparty/mockery/Mockery/CountValidator/Exact.php' );
// 
// use \Mockery as m;
// use OCA\Encryption;

// class Test_Util extends \PHPUnit_Framework_TestCase {
// 
// 	public function setUp() {
// 	
// 		$this->proxy = new Encryption\Proxy();
// 		
// 		$this->tmpFileName = "tmpFile-".time();
// 		
// 		$this->privateKey = file_get_contents( realpath( dirname(__FILE__).'/data/admin.public.key' ) );
// 		$this->publicKey = file_get_contents( realpath( dirname(__FILE__).'/data/admin.private.key' ) );
// 		$this->encDataShort = file_get_contents( realpath( dirname(__FILE__).'/data/yoga-manchester-enc' ) );
// 		$this->encDataShortKey = file_get_contents( realpath( dirname(__FILE__).'/data/yoga-manchester.key' ) );
// 		
// 		$this->dataShort = file_get_contents( realpath( dirname(__FILE__).'/data/yoga-manchester' ) );
// 		$this->dataLong = file_get_contents( realpath( dirname(__FILE__).'/../lib/crypt.php' ) );
// 		$this->longDataPath = realpath( dirname(__FILE__).'/../lib/crypt.php' );
// 		
// 		$this->data1 = file_get_contents( realpath( dirname(__FILE__).'/../../../data/admin/files/enc-test.txt' ) );
// 		
// 		\OC_FileProxy::$enabled = false;
// 		$this->Encdata1 = file_get_contents( realpath( dirname(__FILE__).'/../../../data/admin/files/enc-test.txt' ) );
// 		\OC_FileProxy::$enabled = true;
// 		
// 		$this->userId = 'admin';
// 		$this->pass = 'admin';
// 		
// 		$this->session = new Encryption\Session();
// 		
// $this->session->setPrivateKey( 
// '-----BEGIN PRIVATE KEY-----
// MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDiH3EA4EpFA7Fx
// s2dyyfL5jwXeYXrTqQJ6DqKgGn8VsbT3eu8R9KzM2XitVwZe8c8L52DvJ06o5vg0
// GqPYxilFdOFJe/ggac5Tq8UmJiZS4EqYEMwxBIfIyWTxeGV06/0HOwnVAkqHMcBz
// 64qldtgi5O8kZMEM2/gKBgU0kMLJzM+8oEWhL1+gsUWQhxd8cKLXypS6iWgqFJrz
// f/X0hJsJR+gyYxNpahtnjzd/LxLAETrOMsl2tue+BAxmjbAM0aG0NEM0div+b59s
// 2uz/iWbxImp5pOdYVKcVW89D4XBMyGegR40trV2VwiuX1blKCfdjMsJhiaL9pymp
// ug1wzyQFAgMBAAECggEAK6c+PZkPPXuVCgpEcliiW6NM0r2m5K3AGKgypQ34csu3
// z/8foCvIIFPrhCtEw5eTDQ1CHWlNOjY8vHJYJ0U6Onpx86nHIRrMBkMm8FJ1G5LJ
// U8oKYXwqaozWu/cuPwA//OFc6I5krOzh5n8WaRMkbrgbor8AtebRX74By0AXGrXe
// cswJI7zR96oFn4Dm7Pgvpg5Zhk1vFJ+w6QtH+4DDJ6PBvlZsRkGxYBLGVd/3qhAI
// sBAyjFlSzuP4eCRhHOhHC/e4gmAH9evFVXB88jFyRZm3K+jQ5W5CwrVRBCV2lph6
// 2B6P7CBJN+IjGKMhy+75y13UvvKPv9IwH8Fzl2x1gQKBgQD8qQOr7a6KhSj16wQE
// jim2xqt9gQ2jH5No405NrKs/PFQQZnzD4YseQsiK//NUjOJiUhaT+L5jhIpzINHt
// RJpt3bGkEZmLyjdjgTpB3GwZdXa28DNK9VdXZ19qIl/ZH0qAjKmJCRahUDASMnVi
// M4Pkk9yx9ZIKkri4TcuMWqc0DQKBgQDlHKBTITZq/arYPD6Nl3NsoOdqVRqJrGay
// 0TjXAVbBXe46+z5lnMsqwXb79nx14hdmSEsZULrw/3f+MnQbdjMTYLFP24visZg9
// MN8vAiALiiiR1a+Crz+DTA1Q8sGOMVCMqMDmD7QBys3ZuWxuapm0txAiIYUtsjJZ
// XN76T4nZ2QKBgQCHaT3igzwsWTmesxowJtEMeGWomeXpKx8h89EfqA8PkRGsyIDN
// qq+YxEoe1RZgljEuaLhZDdNcGsjo8woPk9kAUPTH7fbRCMuutK+4ZJ469s1tNkcH
// QX5SBcEJbOrZvv967ehe3VQXmJZq6kgnHVzuwKBjcC2ZJRGDFY6l5l/+cQKBgCqh
// +Adf/8NK7paMJ0urqfPFwSodKfICXZ3apswDWMRkmSbqh4La+Uc8dsqN5Dz/VEFZ
// JHhSeGbN8uMfOlG93eU2MehdPxtw1pZUWMNjjtj23XO9ooob2CKzbSrp8TBnZsi1
// widNNr66oTFpeo7VUUK6acsgF6sYJJxSVr+XO1yJAoGAEhvitq8shNKcEY0xCipS
// k1kbgyS7KKB7opVxI5+ChEqyUDijS3Y9FZixrRIWE6i2uGu86UG+v2lbKvSbM4Qm
// xvbOcX9OVMnlRb7n8woOP10UMY+ZE2x+YEUXQTLtPYq7F66e1OfxltstMxLQA+3d
// Y1d5piFV8PXK3Fg2F+Cj5qg=
// -----END PRIVATE KEY-----
// '
// , $this->userId 
// );
// 		
// 		\OC_User::setUserId( $this->userId );
// 		
// 	}
// 
// 	public function testpreFile_get_contents() {
// 	
// 		// This won't work for now because mocking of the static keymanager class isn't working :(
// 	
// // 		$mock = m::mock( 'alias:OCA\Encryption\Keymanager' );
// // 		
// // 		$mock->shouldReceive( 'getFileKey' )->times(2)->andReturn( $this->encDataShort );
// // 	
// // 		$encrypted = $this->proxy->postFile_get_contents( 'data/'.$this->tmpFileName, $this->encDataShortKey );
// // 		
// // 		$this->assertNotEquals( $this->dataShort, $encrypted );
// 
// 		$decrypted = $this->proxy->postFile_get_contents( 'data/admin/files/enc-test.txt', $this->data1 );
// 		
// 	}
// 
// }

// class Test_CryptProxy extends PHPUnit_Framework_TestCase {
// 	private $oldConfig;
// 	private $oldKey;
// 	
// 	public function setUp(){
// 		$user=OC_User::getUser();
// 
// 		$this->oldConfig=OCP\Config::getAppValue('files_encryption','enable_encryption','true');
// 		OCP\Config::setAppValue('files_encryption','enable_encryption','true');
// 		$this->oldKey=isset($_SESSION['privateKey'])?$_SESSION['privateKey']:null;
// 	
// 		
// 		//set testing key
// 		$_SESSION['privateKey']=md5(time());
// 	
// 		//clear all proxies and hooks so we can do clean testing
// 		OC_FileProxy::clearProxies();
// 		OC_Hook::clear('OC_Filesystem');
// 
// 		//enable only the encryption hook
// 		OC_FileProxy::register(new OC_FileProxy_Encryption());
// 
// 		//set up temporary storage
// 		OC_Filesystem::clearMounts();
// 		OC_Filesystem::mount('OC_Filestorage_Temporary',array(),'/');
// 
// 		OC_Filesystem::init('/'.$user.'/files');
// 
// 		//set up the users home folder in the temp storage
// 		$rootView=new OC_FilesystemView('');
// 		$rootView->mkdir('/'.$user);
// 		$rootView->mkdir('/'.$user.'/files');
// 	}
// 
// 	public function tearDown(){
// 		OCP\Config::setAppValue('files_encryption','enable_encryption',$this->oldConfig);
// 		if(!is_null($this->oldKey)){
// 			$_SESSION['privateKey']=$this->oldKey;
// 		}
// 	}
// 
// 	public function testSimple(){
// 		$file=OC::$SERVERROOT.'/3rdparty/MDB2.php';
// 		$original=file_get_contents($file);
// 
// 		OC_Filesystem::file_put_contents('/file',$original);
// 		
// 		OC_FileProxy::$enabled=false;
// 		$stored=OC_Filesystem::file_get_contents('/file');
// 		OC_FileProxy::$enabled=true;
// 		
// 		$fromFile=OC_Filesystem::file_get_contents('/file');
// 		$this->assertNotEquals($original,$stored);
// 		$this->assertEquals(strlen($original),strlen($fromFile));
// 		$this->assertEquals($original,$fromFile);
// 
// 	}
// 
// 	public function testView(){
// 		$file=OC::$SERVERROOT.'/3rdparty/MDB2.php';
// 		$original=file_get_contents($file);
// 
// 		$rootView=new OC_FilesystemView('');
// 		$view=new OC_FilesystemView('/'.OC_User::getUser());
// 		$userDir='/'.OC_User::getUser().'/files';
// 
// 		$rootView->file_put_contents($userDir.'/file',$original);
// 
// 		OC_FileProxy::$enabled=false;
// 		$stored=$rootView->file_get_contents($userDir.'/file');
// 		OC_FileProxy::$enabled=true;
// 
// 		$this->assertNotEquals($original,$stored);
// 		$fromFile=$rootView->file_get_contents($userDir.'/file');
// 		$this->assertEquals($original,$fromFile);
// 
// 		$fromFile=$view->file_get_contents('files/file');
// 		$this->assertEquals($original,$fromFile);
// 	}
// 
// 	public function testBinary(){
// 		$file=__DIR__.'/binary';
// 		$original=file_get_contents($file);
// 
// 		OC_Filesystem::file_put_contents('/file',$original);
// 
// 		OC_FileProxy::$enabled=false;
// 		$stored=OC_Filesystem::file_get_contents('/file');
// 		OC_FileProxy::$enabled=true;
// 
// 		$fromFile=OC_Filesystem::file_get_contents('/file');
// 		$this->assertNotEquals($original,$stored);
// 		$this->assertEquals(strlen($original),strlen($fromFile));
// 		$this->assertEquals($original,$fromFile);
// 
// 		$file=__DIR__.'/zeros';
// 		$original=file_get_contents($file);
// 
// 		OC_Filesystem::file_put_contents('/file',$original);
// 
// 		OC_FileProxy::$enabled=false;
// 		$stored=OC_Filesystem::file_get_contents('/file');
// 		OC_FileProxy::$enabled=true;
// 
// 		$fromFile=OC_Filesystem::file_get_contents('/file');
// 		$this->assertNotEquals($original,$stored);
// 		$this->assertEquals(strlen($original),strlen($fromFile));
// 	}
// }
