<?php
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\User\AuthTokens;

use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\IToken;
use OC\Core\Command\Base;
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

		$tokens = array_map(function (IToken $token) use ($input): mixed {
			$sensitive = [
				'password',
				'password_hash',
				'token',
				'public_key',
				'private_key',
			];
			$data = array_diff_key($token->jsonSerialize(), array_flip($sensitive));

			if ($input->getOption('output') === self::OUTPUT_FORMAT_PLAIN) {
				$data = $this->formatTokenForPlainOutput($data);
			}

			return $data;
		}, $tokens);

		$this->writeTableInOutputFormat($input, $output, $tokens);

		return 0;
	}

	public function formatTokenForPlainOutput(array $token): array {
		$token['scope'] = implode(', ', array_keys(array_filter($token['scope'] ?? [])));

		$token['lastActivity'] = date(DATE_ATOM, $token['lastActivity']);

		$token['type'] = match ($token['type']) {
			IToken::TEMPORARY_TOKEN => 'temporary',
			IToken::PERMANENT_TOKEN => 'permanent',
			IToken::WIPE_TOKEN => 'wipe',
			default => $token['type'],
		};

		return $token;
	}
}
