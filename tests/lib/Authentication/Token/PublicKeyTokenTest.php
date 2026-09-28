<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Authentication\Token;

use OC\Authentication\Token\PublicKeyToken;
use OCP\Authentication\Token\IToken;
use Test\TestCase;

class PublicKeyTokenTest extends TestCase {
	public function testSetScopeAsArray(): void {
		$scope = [IToken::SCOPE_FILESYSTEM => false];
		$token = new PublicKeyToken();
		$token->setScope($scope);
		$this->assertEquals(json_encode($scope), $token->getScope());
		$this->assertEquals($scope, $token->getScopeAsArray());
	}

	public function testDefaultScope(): void {
		$scope = [IToken::SCOPE_FILESYSTEM => true];
		$token = new PublicKeyToken();
		$this->assertEquals($scope, $token->getScopeAsArray());
	}
}
