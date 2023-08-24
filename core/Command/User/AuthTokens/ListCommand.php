<?php
/**
 * @copyright Copyright (c) 2023 Lucas Azevedo <lhs_azevedo@hotmail.com>
 *
 * @author Lucas Azevedo <lhs_azevedo@hotmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Core\Command\User\AuthTokens;

use OC\Core\Command\Base;
use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\IToken;
use OCP\IUserManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends Base {
	public function __construct(
		protected IUserManager $userManager,
		protected IProvider $tokenProvider,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();

		$this
			->setName('user:auth-tokens:list')
			->setDescription('List authentication tokens of an user')
			->addArgument(
				'user',
				InputArgument::REQUIRED,
				'User to list auth tokens for'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$user = $this->userManager->get($input->getArgument('user'));

		if (is_null($user)) {
			$output->writeln('<error>user not found</error>');
			return 1;
		}

		$tokens = $this->tokenProvider->getTokenByUser($user->getUID());

		$data = array_map(function (IToken $token): mixed {
			$filtered = [
				'password',
				'password_hash',
				'token',
				'public_key',
				'private_key',
			];
			return array_diff_key($token->jsonSerialize(), array_flip($filtered));
		}, $tokens);

		$this->writeArrayInOutputFormat($input, $output, $data);

		return 0;
	}
}
