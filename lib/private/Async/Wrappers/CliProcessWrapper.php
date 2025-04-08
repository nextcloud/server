<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Async\Wrappers;

use OC\Async\AProcessWrapper;
use OC\Async\Enum\ProcessActivity;
use OC\Async\Model\Process;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;

class CliProcessWrapper extends AProcessWrapper {
	private const TAB_SIZE = 8;
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
		$this->slot = $metadata['slot'] ?? 0;
		$this->output->writeln(
			str_repeat(' ', $this->slot * self::TAB_SIZE) . ' ' .
			'<open>++</>' . ' new session ' .
			($metadata['sessionToken'] ?? '')
		);
	}

	public function init(Process $process): void {
		$this->prefix = $this->generatePrefix($process->getToken());
	}

	public function activity(ProcessActivity $activity, string $line = ''): void {
		$act = match ($activity) {
			ProcessActivity::STARTING => '<open>>></>',
			ProcessActivity::ENDING => '<close><<</>',
			ProcessActivity::DEBUG, ProcessActivity::NOTICE  => '  ',
			ProcessActivity::WARNING  => '<warn>!!</>',
			ProcessActivity::ERROR  => '<error>!!</>',
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
