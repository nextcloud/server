<?php
/**
 * ownCloud
 *
 * @author Sam Tuke
 * @copyright 2012 Sam Tuke samtuke@owncloud.com
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
 * Class for handling encryption related session data
 */

class Session {

	/**
	 * @brief Sets user private key to session
	 * @return bool
	 *
	 */
	public function setPrivateKey( $privateKey ) {
	
		$_SESSION['privateKey'] = $privateKey;
		
		return true;
		
	}
	
	/**
	 * @brief Gets user private key from session
	 * @returns string $privateKey The user's plaintext private key
	 *
	 */
	public function getPrivateKey() {
	
		if ( 
			isset( $_SESSION['privateKey'] )
			&& !empty( $_SESSION['privateKey'] )
		) {
		
			return $_SESSION['privateKey'];
		
		} else {
		
			return false;
			
		}
		
	}
	
	/**
	 * @brief Sets user legacy key to session
	 * @return bool
	 *
	 */
	public function setLegacyKey( $legacyKey ) {
	
		if ( $_SESSION['legacyKey'] = $legacyKey ) {
		
			return true;
			
		}
		
	}
	
	/**
	 * @brief Gets user legacy key from session
	 * @returns string $legacyKey The user's plaintext legacy key
	 *
	 */
	public function getLegacyKey() {
	
		if ( 
			isset( $_SESSION['legacyKey'] )
			&& !empty( $_SESSION['legacyKey'] )
		) {
		
			return $_SESSION['legacyKey'];
		
		} else {
		
			return false;
			
		}
		
	}

}