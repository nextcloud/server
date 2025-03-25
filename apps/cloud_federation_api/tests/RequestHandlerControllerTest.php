<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CloudFederationApi\Tests;

use OCA\CloudFederationAPI\Controller\RequestHandlerController;
use OCA\CloudFederationAPI\Db\FederatedInvite;
use OCA\CloudFederationAPI\Db\FederatedInviteMapper;
use OCA\Federation\TrustedServers;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use Test\TestCase;

/**
 * Class RequestHandlerControllerTest
 *
 * @group DB
 *
 */
class RequestHandlerControllerTest extends TestCase {
	/** @var FederatedInviteMapper */
	private $federatedInviteMapper;
	/** @var TrustedServers */
	private $trustedServers;
	/** @var RequestHandlerController */
	private $requestHandlerController;

	protected function setUp(): void {
		parent::setUp();

		$this->federatedInviteMapper = \OC::$server->get(FederatedInviteMapper::class);
		$this->trustedServers = \OC::$server->get(TrustedServers::class);
		$this->requestHandlerController = \OC::$server->get(RequestHandlerController::class);
	}

	public function testInviteAccepted(): void {
		$token = 'token';
		$invite = new FederatedInvite();
		$invite->setCreatedAt(1);
		$invite->setUserId('admin');
		$invite->setToken($token);
		$this->federatedInviteMapper->insert($invite);
		$trusted_server = 'http://127.0.0.1';
		$this->trustedServers->addServer($trusted_server);
		$recipientProvider = $trusted_server;
		$userId = 'remote';
		$email = 'remote@example.org';
		$name = 'Remote Remoteson';
		$response = ['userID' => 'admin', 'email' => null, 'name' => 'admin'];
		$json = new JSONResponse($response, Http::STATUS_OK);
		$this->assertEquals($json, $this->requestHandlerController->inviteAccepted($recipientProvider, $token, $userId, $email, $name));
	}
}
