<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\OAuth2\Migration;

use OC\Authentication\Token\IProvider as TokenProvider;
use OCA\OAuth2\Db\AccessToken;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Authentication\Exceptions\InvalidTokenException;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class SetTokenExpiration implements IRepairStep {

	/** @var IDBConnection */
	private $connection;

	/** @var ITimeFactory */
	private $time;

	/** @var TokenProvider */
	private $tokenProvider;

	public function __construct(IDBConnection $connection,
		ITimeFactory $timeFactory,
		TokenProvider $tokenProvider) {
		$this->connection = $connection;
		$this->time = $timeFactory;
		$this->tokenProvider = $tokenProvider;
	}

	public function getName(): string {
		return 'Update OAuth token expiration times';
	}

	public function run(IOutput $output) {
		$qb = $this->connection->getQueryBuilder();
		$qb->select('*')
			->from('oauth2_access_tokens');

		$cursor = $qb->execute();

		while ($row = $cursor->fetch()) {
			$token = AccessToken::fromRow($row);
			try {
				$appToken = $this->tokenProvider->getTokenById($token->getTokenId());
				$appToken->setExpires($this->time->getTime() + 3600);
				$this->tokenProvider->updateToken($appToken);
			} catch (InvalidTokenException $e) {
				//Skip this token
			}
		}
		$cursor->closeCursor();
	}
}
