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
namespace OC\Core\Command\User;

use OC\Core\Command\Base;
use OC\Authentication\Token\IProvider;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteAuthToken extends Base {
	public function __construct(
		protected IProvider $tokenProvider,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('user:delete-auth-token')
			->setDescription('Deletes an authentication token')
			->addArgument(
				'id',
				InputArgument::REQUIRED,
				'ID of the auth token to delete'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$token = $this->tokenProvider->getTokenById($input->getArgument('id'));

		$this->tokenProvider->invalidateTokenById($token->getUID(), $token->getId());

		return 0;
	}
}
