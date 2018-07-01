<?php
/**
 * @copyright Copyright (c) 2016 Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test\Authentication\Token;

use OC\Authentication\Token\DefaultToken;
use Test\TestCase;

class DefaultTokenTest extends TestCase {
	public function testSetScopeAsArray() {
		$scope = ['filesystem' => false];
		$token = new DefaultToken();
		$token->setScope($scope);
		$this->assertEquals(json_encode($scope), $token->getScope());
		$this->assertEquals($scope, $token->getScopeAsArray());
	}

	public function testDefaultScope() {
		$scope = ['filesystem' => true];
		$token = new DefaultToken();
		$this->assertEquals($scope, $token->getScopeAsArray());
	}

	public function testComment() {
		$tComment = $this->generateRandomString(300);
		$token = new DefaultToken();
		$token->setComment($tComment);
		$this->assertEquals(
			substr($tComment,0,250),$token->getComment());
	}

// with credit to Stephen Watkins on StackOVerflow
// https://stackoverflow.com/a/4356295/3436542
	private function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ {}();:/\\,.!@#$%^&*-_=+~`\'"';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
	}

}
