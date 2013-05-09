<?php
/**
 * ownCloud
 *
 * @author Florin Peter
 * @copyright 2013 Florin Peter <owncloud@florin-peter.de>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

require_once realpath( dirname(__FILE__).'/../../../3rdparty/Crypt_Blowfish/Blowfish.php' );
require_once realpath( dirname(__FILE__).'/../../../lib/base.php' );
require_once realpath( dirname(__FILE__).'/../lib/crypt.php' );
require_once realpath( dirname(__FILE__).'/../lib/keymanager.php' );
require_once realpath( dirname(__FILE__).'/../lib/proxy.php' );
require_once realpath( dirname(__FILE__).'/../lib/stream.php' );
require_once realpath( dirname(__FILE__).'/../lib/util.php' );
require_once realpath( dirname(__FILE__).'/../lib/helper.php' );
require_once realpath( dirname(__FILE__).'/../appinfo/app.php' );

use OCA\Encryption;

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

        // Sharing related hooks
        \OCA\Encryption\Helper::registerShareHooks();

        // Filesystem related hooks
        \OCA\Encryption\Helper::registerFilesystemHooks();

        \OC_FileProxy::register( new OCA\Encryption\Proxy() );

        \OC::registerShareHooks();

        OCP\Util::connectHook('OC_Filesystem', 'setup', '\OC\Files\Storage\Shared', 'setup');

        // remember files_trashbin state
        $this->stateFilesTrashbin = OC_App::isEnabled('files_trashbin');

        // we don't want to tests with app files_trashbin
        \OC_App::disable('files_trashbin');

        $this->loginHelper('user1', true);
        $this->loginHelper('user2', true);
        $this->loginHelper('user3', true);
        $this->loginHelper('user4', true);
    }
	
	function tearDown() {
        if($this->stateFilesTrashbin) {
            OC_App::enable('files_trashbin');
        } else {
            OC_App::disable('files_trashbin');
        }

        \OC_User::deleteUser('user1');
        \OC_User::deleteUser('user2');
        \OC_User::deleteUser('user3');
        \OC_User::deleteUser('user4');
	}

    function testShareFile($withTeardown = true) {
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

            // cleanup
            $this->view->unlink('/admin/files/'.$filename);

            // check if share key not exists
            $this->assertFalse($this->view->file_exists('/admin/files_encryption/share-keys/'.$filename.'.admin.shareKey'));
        }
    }

    function testReShareFile($withTeardown = true) {
        $this->testShareFile(false);

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

            // share the file
            \OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, 'user1');

            // check if share key not exists
            $this->assertFalse($this->view->file_exists('/admin/files_encryption/share-keys/'.$filename.'.user1.shareKey'));

            // cleanup
            $this->view->unlink('/admin/files/'.$filename);

            // check if share key not exists
            $this->assertFalse($this->view->file_exists('/admin/files_encryption/share-keys/'.$filename.'.admin.shareKey'));
        }
    }

    function testShareFolder($withTeardown = true) {
        // create user1
        $this->loginHelper('user1');

        // login as admin
        $this->loginHelper('admin');

        $folder1 = '/folder1';
        $subfolder = '/subfolder1';
        $subsubfolder = '/subsubfolder1';

        $filename = 'share-tmp.test';

        $this->view->mkdir('/admin/files'.$folder1);
        $this->view->mkdir('/admin/files'.$folder1.$subfolder);
        $this->view->mkdir('/admin/files'.$folder1.$subfolder.$subsubfolder);

        $cryptedFile = file_put_contents( 'crypt://' . $folder1.$subfolder.$subsubfolder.'/'.$filename, $this->dataShort );

        // Test that data was successfully written
        $this->assertTrue( is_int( $cryptedFile ) );

        // Disable encryption proxy to prevent recursive calls
        $proxyStatus = \OC_FileProxy::$enabled;
        \OC_FileProxy::$enabled = false;

        // get the file infos
        $fileInfo = $this->view->getFileInfo('/admin/files/folder1/');

        // check if we have fileInfos
        $this->assertTrue(is_array($fileInfo));

        \OC_FileProxy::$enabled = $proxyStatus;

        // share the file
        \OCP\Share::shareItem('folder', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, 'user1', OCP\PERMISSION_ALL);

        $this->loginHelper('admin');

        // check if share key exists
        $this->assertTrue($this->view->file_exists('/admin/files_encryption/share-keys'.$folder1.$subfolder.$subsubfolder.'/'.$filename.'.user1.shareKey'));

        // login as user1
        $this->loginHelper('user1');

        // Get file contents without using any wrapper to get it's actual contents on disk
        $retreivedCryptedFile = $this->view->file_get_contents('/user1/files/Shared'.$folder1.$subfolder.$subsubfolder.'/'.$filename);

        // check if data is the same
        $this->assertEquals($this->dataShort, $retreivedCryptedFile);

        if($withTeardown) {
            // login as admin
            $this->loginHelper('admin');

            // share the file
            \OCP\Share::unshare('folder', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, 'user1');

            // check if share key not exists
            $this->assertFalse($this->view->file_exists('/admin/files_encryption/share-keys'.$folder1.$subfolder.$subsubfolder.'/'.$filename.'.user1.shareKey'));

            // cleanup
            $this->view->unlink('/admin/files'.$folder1.$subfolder.$subsubfolder.'/'.$filename);

            // check if share key not exists
            $this->assertFalse($this->view->file_exists('/admin/files_encryption/share-keys'.$folder1.$subfolder.$subsubfolder.'/'.$filename.'.admin.shareKey'));
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
