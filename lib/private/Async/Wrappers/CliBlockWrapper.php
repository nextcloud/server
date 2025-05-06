<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Async\Wrappers;

use OC\Async\ABlockWrapper;
use OC\Async\Model\Block;
use OCP\Async\Enum\BlockActivity;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;

class CliBlockWrapper extends ABlockWrapper {
	private const TAB_SIZE = 12;
	private const HEXA = '258ad';
	private int $hexaLength = 0;
	private string $prefix = '';
	private int $slot = 0;
	public function __construct(
		private ?OutputInterface $output = null,
	) {
		$this->hexaLength = strlen(self::HEXA);
	}

	public function session(array $metadata): void {
		$this->slot = $metadata['_cli']['slot'] ?? 0;
		$this->output->writeln(
			str_repeat(' ', $this->slot * self::TAB_SIZE) . ' ' .
			'<open>++</>' . ' initiating session ' .
			($metadata['sessionToken'] ?? '')
		);
	}

	public function init(): void {
		$this->prefix = $this->generatePrefix($this->block->getToken());
	}

	public function activity(BlockActivity $activity, string $line = ''): void {
		$act = match ($activity) {
			BlockActivity::STARTING => '<open>>></>',
			BlockActivity::ENDING => '<close><<</>',
			BlockActivity::DEBUG, BlockActivity::NOTICE  => '  ',
			BlockActivity::WARNING  => '<warn>!!</>',
			BlockActivity::ERROR  => '<error>!!</>',
		};

		$this->output->writeln(
			str_repeat(' ', $this->slot * self::TAB_SIZE) . ' ' .
			$act . ' ' .
			$this->prefix . ' ' .
			$line
		);
	}

	public function end(string $line = ''): void {
		if ($line === '') {
			$this->output->writeln('');
			return;
		}

		$this->output->writeln(
			str_repeat(' ', $this->slot * self::TAB_SIZE) . ' ' .
			'<warn>--</>' . ' ' . $line
		);
	}

	private function generatePrefix(string $token): string {
		$color = 's' . random_int(1, $this->hexaLength - 1) .
				 random_int(1, $this->hexaLength - 1) .
				 random_int(1, $this->hexaLength - 1);

		return '<' . $color . '>' . $token . '</>';
	}

	public static function initStyle(OutputInterface $output): void {
		$output->getFormatter()->setStyle('open', new OutputFormatterStyle('#0f0', '', ['bold']));
		$output->getFormatter()->setStyle('close', new OutputFormatterStyle('#f00', '', ['bold']));
		$output->getFormatter()->setStyle('warn', new OutputFormatterStyle('#f00', '', []));
		$output->getFormatter()->setStyle('error', new OutputFormatterStyle('#f00', '', ['bold']));

		$hexaLength = strlen(self::HEXA);
		for ($i = 0; $i < $hexaLength; $i++) {
			for ($j = 0; $j < $hexaLength; $j++) {
				for ($k = 0; $k < $hexaLength; $k++) {
					$output->getFormatter()->setStyle(
						's' . $i . $j . $k,
						new OutputFormatterStyle(
							'#' . self::HEXA[$i] . self::HEXA[$j] . self::HEXA[$k],
							'',
							['bold']
						)
					);
				}
			}
		}
	}

}
