<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\OneTimePassword;

use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IDBConnection;
use OCP\OneTimePassword\Exceptions\OTPNotFoundException;
use OCP\OneTimePassword\Exceptions\OTPProviderNotFoundException;
use OCP\OneTimePassword\Exceptions\OTPSendException;
use OCP\OneTimePassword\Exceptions\OTPThrottledException;
use OCP\OneTimePassword\IManager;
use OCP\OneTimePassword\IOneTimePassword;
use OCP\OneTimePassword\IOneTimePasswordProvider;
use OCP\Security\Events\GenerateSecurePasswordEvent;
use OCP\Security\IHasher;
use OCP\Security\ISecureRandom;
use OCP\Security\PasswordContext;
use Psr\Log\LoggerInterface;

class Manager implements IManager {
	private const VALIDITY = 'PT15M';
	private const RESEND_DELAY = 'PT1M';

	private array $providers = [];

	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly ISecureRandom $secureRandom,
		private readonly IEventDispatcher $dispatcher,
		private readonly IDBConnection $connection,
		private readonly IHasher $hasher,
	) {
	}

	/**
	 * @inheritdoc
	 */
	#[\Override]
	public function registerOTPProvider(IOneTimePasswordProvider $provider): void {
		$this->providers[] = $provider;
		$this->logger->debug('OTP provider registered: ' . $provider->getProviderId(), ['app' => 'core/otp']);
	}

	/**
	 * @inheritdoc
	 * @throws \DateInvalidTimeZoneException
	 * @throws Exception
	 */
	#[\Override]
	public function updateOTP(IOneTimePassword $otp): void {
		$qb = $this->connection->getQueryBuilder();
		$expirationDate = $otp->getExpirationTime();
		if ($expirationDate !== null) {
			$expirationDate = clone $expirationDate;
			$expirationDate->setTimezone(new \DateTimeZone(date_default_timezone_get()));
		}

		$qb->update('one_time_password')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($otp->getId())))
			->set('password', $qb->createNamedParameter($otp->getPassword()))
			->set('recipient', $qb->createNamedParameter($otp->getRecipient()))
			->set('provider', $qb->createNamedParameter($otp->getProviderId()))
			->set('expiration', $qb->createNamedParameter($expirationDate, IQueryBuilder::PARAM_DATETIME_MUTABLE))
			->executeStatement();
	}

	/**
	 * @inheritdoc
	 */
	#[\Override]
	public function createOTP(string $provider, string $recipient): IOneTimePassword {
		$otpProvider = $this->getOTPProviderById($provider);
		$pattern = '#' . str_replace('#', '\#', $otpProvider->getRecipientPattern()) . '#u';
		if (preg_match($pattern, $recipient) !== 1) {
			throw new \InvalidArgumentException('Invalid recipient for one-time password provider ' . $provider);
		}

		$qb = $this->connection->getQueryBuilder();
		$qb->insert('one_time_password')
			->setValue('provider', $qb->createNamedParameter($provider))
			->setValue('recipient', $qb->createNamedParameter($recipient));
		$qb->executeStatement();
		$otpId = $qb->getLastInsertId();
		return (new OneTimePassword($provider, $recipient))
			->setId($otpId);
	}

	private function loadOTPData(array $data): IOneTimePassword {
		$otp = new OneTimePassword($data['provider'], $data['recipient']);
		$otp->setId($data['id'])
			->setPassword($data['password']);
		if ($data['expiration'] !== null) {
			$expiration = \DateTime::createFromFormat('Y-m-d H:i:s', $data['expiration']);
			$otp->setExpirationTime($expiration);
		} else {
			$otp->setExpirationTime(null);
		}
		return $otp;
	}

	/**
	 * @inheritdoc
	 */
	#[\Override]
	public function getOTP(int $otpId): IOneTimePassword {
		$qb = $this->connection->getQueryBuilder();
		$qb->select('*')
			->from('one_time_password')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($otpId)));
		$cursor = $qb->executeQuery();

		$data = $cursor->fetch();
		$cursor->closeCursor();
		if ($data === false) {
			throw new OTPNotFoundException($otpId);
		}
		return $this->loadOTPData($data);
	}

	/**
	 * @inheritdoc
	 * @throws \DateInvalidTimeZoneException
	 * @throws OTPNotFoundException
	 * @throws OTPSendException
	 * @throws Exception
	 */
	#[\Override]
	public function sendOTP(IOneTimePassword $otp, ?string $message = null): void {
		$this->logger->debug('sending OTP to \'' . $otp->getRecipient() . '\' with provider \'' . $otp->getProviderId() . '\'.', ['app' => 'core/otp']);
		$provider = $this->getOTPProviderById($otp->getProviderId());

		$expiration = $otp->getExpirationTime();
		$resendAllowedFrom = (new \DateTime())->add(new \DateInterval(self::VALIDITY))->sub(new \DateInterval(self::RESEND_DELAY));
		if ($otp->getPassword() !== null && $expiration !== null && $expiration > $resendAllowedFrom) {
			throw new OTPThrottledException('A one-time password was sent less than a minute ago');
		}

		$event = new GenerateSecurePasswordEvent(PasswordContext::OTP);
		$this->dispatcher->dispatchTyped($event);
		$password = $event->getPassword() ?? $this->secureRandom->generate(12, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
		$pwHash = $this->hasher->hash($password);
		$otp->setPassword($pwHash);
		$otp->setExpirationTime((new \DateTime())->add(new \DateInterval(self::VALIDITY)));

		$this->updateOTP($otp);

		try {
			$provider->sendOTP($otp->getRecipient(), $password, $message);
		} catch (OTPSendException $e) {
			$this->logger->debug('sending OTP failed (' . $e->getMessage() . ')');
			$otp->setExpirationTime(new \DateTime())->setPassword(null);
			$this->updateOTP($otp);
			throw new OTPSendException("Failed to send one time password to '" . $otp->getProviderId() . '(' . $otp->getRecipient() . "'): " . $e->getMessage(), 0, $e);
		}
	}

	/**
	 * @inheritdoc
	 */
	#[\Override]
	public function getOTPProviders(): array {
		return $this->providers;
	}

	/**
	 * @inheritdoc
	 */
	#[\Override]
	public function getOTPProviderById(string $providerId): IOneTimePasswordProvider {
		$filtered = array_find($this->providers, function ($provider) use ($providerId) {
			return $provider->getProviderId() === $providerId;
		});
		if (!$filtered) {
			$this->logger->debug('no OTP providers found for id \'' . $providerId . '\'', ['app' => 'core/otp']);
			throw new OTPProviderNotFoundException($providerId);
		}
		return $filtered;
	}

	/**
	 * @inheritdoc
	 * @throws \DateInvalidTimeZoneException
	 * @throws Exception
	 */
	#[\Override]
	public function validateOTP(IOneTimePassword $otp, ?string $password): bool {
		$hash = $otp->getPassword();
		$expiration = $otp->getExpirationTime();
		if ($password === null || $hash === null || ($expiration !== null && $expiration < new \DateTime())) {
			return false;
		}
		if (!$this->hasher->verify($password, $hash)) {
			return false;
		}

		$qb = $this->connection->getQueryBuilder();
		$qb->update('one_time_password')
			->set('password', $qb->createNamedParameter(null, IQueryBuilder::PARAM_NULL))
			->where($qb->expr()->eq('id', $qb->createNamedParameter($otp->getId(), IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('password', $qb->createNamedParameter($hash)));
		if ($qb->executeStatement() !== 1) {
			return false;
		}

		$otp->setPassword(null);
		return true;
	}

	/**
	 * @inheritdoc
	 * @throws Exception
	 */
	#[\Override]
	public function deleteOTP(int $otpId): void {
		$qb = $this->connection->getQueryBuilder();
		$qb->delete('one_time_password')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($otpId)))
			->executeStatement();
	}
}
