<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Lockdown;

use OC\Authentication\Token\TokenScopes;
use OCP\Authentication\Token\IToken;
use OCP\ISession;
use OCP\Lockdown\ILockdownManager;

class LockdownManager implements ILockdownManager {
	/** @var ISession */
	private $sessionCallback;

	private $enabled = false;

	/** @var array|null */
	private $scope;

	/**
	 * LockdownManager constructor.
	 *
	 * @param callable $sessionCallback we need to inject the session lazily to avoid dependency loops
	 */
	public function __construct(
		callable $sessionCallback,
		private TokenScopes $tokenScopes,
	) {
		$this->sessionCallback = $sessionCallback;
	}

	#[\Override]
	public function enable() {
		$this->enabled = true;
	}

	/**
	 * @return ISession
	 */
	private function getSession() {
		$callback = $this->sessionCallback;
		return $callback();
	}

	private function getScopeAsArray() {
		if (!$this->scope) {
			$session = $this->getSession();
			$sessionScope = $session->get('token_scope');
			if ($sessionScope) {
				$this->scope = $sessionScope;
			}
		}
		return $this->scope;
	}

	#[\Override]
	public function setToken(IToken $token) {
		$this->scope = $token->getScopeAsArray();
		$session = $this->getSession();
		$session->set('token_scope', $this->scope);
		$this->enable();
	}

	#[\Override]
	public function canAccessFilesystem() {
		$scope = $this->getScopeAsArray();
		if ($scope && $this->tokenScopes->fromBlob($scope) === null) {
			return $scope[IToken::SCOPE_FILESYSTEM] ?? false;
		}
		return $this->hasScope(TokenScopes::FILES_READ);
	}

	/**
	 * True for every scope when there is no token or it has no scopes (full access)
	 */
	public function hasScope(string $scope): bool {
		$scopes = $this->tokenScopes->fromBlob($this->getScopeAsArray() ?? []);
		return $scopes === null || in_array($scope, $scopes, true);
	}
}
