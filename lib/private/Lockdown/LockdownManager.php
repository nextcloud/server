<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Lockdown;

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
	public function __construct(callable $sessionCallback) {
		$this->sessionCallback = $sessionCallback;
	}


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

	public function setToken(IToken $token) {
		$this->scope = $token->getScopeAsArray();
		$session = $this->getSession();
		$session->set('token_scope', $this->scope);
		$this->enable();
	}

	public function canAccessFilesystem() {
		$scope = $this->getScopeAsArray();
		return !$scope || $scope[IToken::SCOPE_FILESYSTEM];
	}
}
