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

namespace OCA\Encryption;

/**
 * @brief Class to manage registration of hooks an various helper methods
 */
class Helper {
		
	/**
	 * @brief register share related hooks
	 * 
	 */
	public static function registerShareHooks() {

        \OCP\Util::connectHook( 'OCP\Share', 'pre_shared', 'OCA\Encryption\Hooks', 'preShared' );
		\OCP\Util::connectHook( 'OCP\Share', 'post_shared', 'OCA\Encryption\Hooks', 'postShared' );
        \OCP\Util::connectHook( 'OCP\Share', 'post_unshare', 'OCA\Encryption\Hooks', 'postUnshare' );
	}

    /**
     * @brief register user related hooks
     *
     */
    public static function registerUserHooks() {

        \OCP\Util::connectHook( 'OC_User', 'post_login', 'OCA\Encryption\Hooks', 'login' );
        \OCP\Util::connectHook( 'OC_User', 'pre_setPassword', 'OCA\Encryption\Hooks', 'setPassphrase' );
        \OCP\Util::connectHook( 'OC_User', 'post_createUser', 'OCA\Encryption\Hooks', 'postCreateUser' );
        \OCP\Util::connectHook( 'OC_User', 'post_deleteUser', 'OCA\Encryption\Hooks', 'postDeleteUser' );
    }

    /**
     * @brief register webdav related hooks
     *
     */
    public static function registerWebdavHooks() {


    }

    /**
     * @brief register filesystem related hooks
     *
     */
    public static function registerFilesystemHooks() {

        \OCP\Util::connectHook('OC_Filesystem', 'post_rename', 'OCA\Encryption\Hooks', 'postRename');
    }

    /**
     * @brief setup user for files_encryption
     *
     * @param Util $util
     * @param string $password
     * @return bool
     */
    public static function setupUser($util, $password) {
        // Check files_encryption infrastructure is ready for action
        if ( ! $util->ready() ) {

            \OC_Log::write( 'Encryption library', 'User account "' . $util->getUserId() . '" is not ready for encryption; configuration started', \OC_Log::DEBUG );

            if(!$util->setupServerSide( $password )) {
                return false;
            }
        }

        return true;
    }
}