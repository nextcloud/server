<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024, Marcel Müller <marcel.mueller@nextcloud.com>
 *
 * @author Marcel Müller <marcel.mueller@nextcloud.com>
 *
 * @license AGPL-3.0-or-later
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

namespace OC\Core\Command\User\Keys;

use OC\Security\IdentityProof\Manager;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Verify extends Command {
	public function __construct(
		protected IUserManager $userManager,
		protected Manager $keyManager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('user:keys:verify')
			->setDescription('Verify if the stored public key matches the stored private key')
			->addArgument(
				'user-id',
				InputArgument::REQUIRED,
				'User ID of the user to verify'
			)
		;
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$userId = $input->getArgument('user-id');

		$user = $this->userManager->get($userId);
		if (!$user instanceof IUser) {
			$output->writeln('Unknown user');
			return static::FAILURE;
		}

		$key = $this->keyManager->getKey($user);
		$publicKey = $key->getPublic();
		$privateKey = $key->getPrivate();

		$output->writeln('User public key size: ' . strlen($publicKey));
		$output->writeln('User private key size: ' . strlen($privateKey));

		// Derive the public key from the private key again to validate the stored public key
		$opensslPrivateKey = openssl_pkey_get_private($privateKey);
		$publicKeyDerived = openssl_pkey_get_details($opensslPrivateKey);
		$publicKeyDerived = $publicKeyDerived['key'];
		$output->writeln('User derived public key size: ' . strlen($publicKeyDerived));

		$output->writeln('');

		$output->writeln('Stored public key:');
		$output->writeln($publicKey);
		$output->writeln('Derived public key:');
		$output->writeln($publicKeyDerived);

		if ($publicKey != $publicKeyDerived) {
			$output->writeln('<error>Stored public key does not match stored private key</error>');
			return static::FAILURE;
		}

		$output->writeln('<info>Stored public key matches stored private key</info>');

		return static::SUCCESS;
	}
}
