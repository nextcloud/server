<?php
/**
 * Copyright (c) 2012 Sam Tuke <samtuke@owncloud.com>, and
 * Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

//require_once "PHPUnit/Framework/TestCase.php";
require_once realpath( dirname(__FILE__).'/../../../3rdparty/Crypt_Blowfish/Blowfish.php' );
require_once realpath( dirname(__FILE__).'/../../../lib/base.php' );
require_once realpath( dirname(__FILE__).'/../lib/crypt.php' );
require_once realpath( dirname(__FILE__).'/../lib/keymanager.php' );
require_once realpath( dirname(__FILE__).'/../lib/proxy.php' );
require_once realpath( dirname(__FILE__).'/../lib/stream.php' );
require_once realpath( dirname(__FILE__).'/../lib/util.php' );
require_once realpath( dirname(__FILE__).'/../appinfo/app.php' );

use OCA\Encryption;

// This has to go here because otherwise session errors arise, and the private 
// encryption key needs to be saved in the session

/**
 * @note It would be better to use Mockery here for mocking out the session 
 * handling process, and isolate calls to session class and data from the unit 
 * tests relating to them (stream etc.). However getting mockery to work and 
 * overload classes whilst also using the OC autoloader is difficult due to 
 * load order Pear errors.
 */

class Test_Encryption_Share extends \PHPUnit_Framework_TestCase {
	
	function setUp() {
        // reset backend
        \OC_User::clearBackends();
        \OC_User::useBackend('database');

        $this->dataShort = 'hats';
        $this->view = new \OC_FilesystemView( '/' );

        $userHome = \OC_User::getHome('admin');
        $this->dataDir = str_replace('/admin', '', $userHome);

        \OC_Appconfig::setValue('core', 'shareapi_allow_resharing', 'yes');

        OC_Hook::clear('OCP\\Share');
        // Sharing-related hooks
        OCP\Util::connectHook( 'OCP\Share', 'post_shared', 'OCA\Encryption\Hooks', 'postShared' );
        OCP\Util::connectHook( 'OCP\Share', 'post_unshare', 'OCA\Encryption\Hooks', 'postUnshare' );
        OCP\Util::connectHook( 'OCP\Share', 'post_unshareAll', 'OCA\Encryption\Hooks', 'postUnshareAll' );

        OCP\Util::connectHook('OC_Filesystem', 'setup', '\OC\Files\Storage\Shared', 'setup');

        OC_FileProxy::register( new OCA\Encryption\Proxy() );

        OC::registerShareHooks();
    }
	
	function tearDown() {

	}

    function testShareFile($withTeardown = true) {
        // create user1
        $this->loginHelper('user1', true);

        // login as admin
        $this->loginHelper('admin');

        $filename = 'share-tmp.test';

        $cryptedFile = file_put_contents( 'crypt://' . $filename, $this->dataShort );

        // Test that data was successfully written
        $this->assertTrue( is_int( $cryptedFile ) );

        // Disable encryption proxy to prevent recursive calls
        $proxyStatus = \OC_FileProxy::$enabled;
        \OC_FileProxy::$enabled = false;

        // get the file infos
        $fileInfo = $this->view->getFileInfo('/admin/files/'.$filename);

        // check if we have fileInfos
        $this->assertTrue(is_array($fileInfo));

        // check if we have fileInfos
        $this->assertGreaterThan(0, $fileInfo['unencrypted_size']);

        \OC_FileProxy::$enabled = $proxyStatus;

        // share the file
        \OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, 'user1', OCP\PERMISSION_ALL);

        $this->loginHelper('admin');

        // check if share key exists
        $this->assertTrue($this->view->file_exists('/admin/files_encryption/share-keys/'.$filename.'.user1.shareKey'));

        // login as user1
        $this->loginHelper('user1');

        // Get file contents without using any wrapper to get it's actual contents on disk
        $retreivedCryptedFile = $this->view->file_get_contents('/user1/files/Shared/' . $filename);

        // check if data is the same
        $this->assertEquals($this->dataShort, $retreivedCryptedFile);

        if($withTeardown) {
            // login as admin
            $this->loginHelper('admin');

            // share the file
            \OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, 'user1');

            // check if share key not exists
            $this->assertFalse($this->view->file_exists('/admin/files_encryption/share-keys/'.$filename.'.user1.shareKey'));

            // tear down
            \OC_User::deleteUser('user1');
        }
    }

    function testReShareFile($withTeardown = true) {
        $this->testShareFile(false);

        // create user2
        $this->loginHelper('user2', true);

        // login as user1
        $this->loginHelper('user1');

        $filename = 'share-tmp.test';

        // get the file info
        $fileInfo = $this->view->getFileInfo('/user1/files/Shared/'.$filename);

        // share the file
        \OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, 'user2', OCP\PERMISSION_ALL);

        $this->loginHelper('admin');

        // check if share key exists
        $this->assertTrue($this->view->file_exists('/admin/files_encryption/share-keys/'.$filename.'.user2.shareKey'));

        // login as user2
        $this->loginHelper('user2');

        // Get file contents without using any wrapper to get it's actual contents on disk
        $retreivedCryptedFile = $this->view->file_get_contents('/user2/files/Shared/' . $filename);

        // check if data is the same
        $this->assertEquals($this->dataShort, $retreivedCryptedFile);

        if($withTeardown) {
            // login as admin
            $this->loginHelper('user1');

            // share the file
            \OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, 'user2');

            $this->loginHelper('admin');

            // check if share key not exists
            $this->assertFalse($this->view->file_exists('/admin/files_encryption/share-keys/'.$filename.'.user2.shareKey'));

            // tear down
            \OC_User::deleteUser('user2');
        }
    }

    function loginHelper($user, $create=false) {
        if($create) {
            \OC_User::createUser($user, $user);
        }

        \OC_Util::tearDownFS();
        \OC_User::setUserId('');
        \OC_Util::setupFS($user);
        \OC_User::setUserId($user);

        $params['uid'] = $user;
        $params['password'] = $user;
        OCA\Encryption\Hooks::login($params);
    }
}
