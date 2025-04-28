<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Async;

use OC\Async\AsyncManager;
use OC\Async\Db\BlockMapper;
use OC\Async\ForkManager;
use OC\Async\Model\Block;
use OC\Async\Model\BlockInterface;
use OC\Async\Model\SessionInterface;
use OCP\Async\Enum\BlockExecutionTime;
use OCP\Async\Enum\BlockStatus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class Manage extends Command {
	private bool $noCrop = false;

	public function __construct(
		private AsyncManager $asyncManager,
		private ForkManager $forkManager,
		private BlockMapper $blockMapper,
	) {
		parent::__construct();
	}

	protected function configure() {
		parent::configure();
		$this->setName('async:manage')
			 ->addOption('clean', '', InputOption::VALUE_NONE, 'remove successful session')
			 ->addOption('session', '', InputOption::VALUE_REQUIRED, 'list all blocks from a session', '')
			 ->addOption( 'details', '', InputOption::VALUE_REQUIRED, 'get details about a specific block', '')
			 ->addOption( 'full-details', '', InputOption::VALUE_NONE, 'get full details')
			 ->addOption( 'replay', '', InputOption::VALUE_REQUIRED, 'replay a specific block', '')
			 ->setDescription('manage');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		if ($input->getOption('clean')) {
			$count = $this->blockMapper->removeSuccessfulBlock();
			$output->writeln('deleted ' . $count . ' blocks');
			return 0;
		}

		$replay = $input->getOption('replay');
		if ($replay !== '') {
			$this->replayBlock($input, $output, $replay);
			return 0;
		}

		$this->noCrop = $input->getOption('full-details');
		$output->getFormatter()->setStyle('data', new OutputFormatterStyle('#666', '', []));
		$output->getFormatter()->setStyle('prep', new OutputFormatterStyle('#ccc', '', []));
		$output->getFormatter()->setStyle('standby', new OutputFormatterStyle('#aaa', '', ['bold']));
		$output->getFormatter()->setStyle('running', new OutputFormatterStyle('#0a0', '', []));
		$output->getFormatter()->setStyle('blocker', new OutputFormatterStyle('#c00', '', ['bold']));
		$output->getFormatter()->setStyle('error', new OutputFormatterStyle('#d00', '', []));
		$output->getFormatter()->setStyle('success', new OutputFormatterStyle('#0c0', '', ['bold']));

		$details = $input->getOption('details');
		if ($details !== '') {
			$this->displayBlock($output, $details);
			return 0;
		}

		$session = $input->getOption('session');
		if ($session !== '') {
			$this->displaySession($output, $session);
			return 0;
		}

		$this->summary($output);
		return 0;
	}

	private function summary(OutputInterface $output): void {
		$statuses = [];
		foreach ($this->blockMapper->getSessions() as $token) {
			$sessionBlockes = $this->blockMapper->getBySession($token);
			$sessionIface = new SessionInterface(BlockInterface::asBlockInterfaces($sessionBlockes));

			$status = $sessionIface->getGlobalStatus()->value;
			if (!array_key_exists($status, $statuses)) {
				$statuses[$status] = [];
			}
			$statuses[$status][] = $token;
		}

		if (!empty($success = $statuses[(string)BlockStatus::SUCCESS->value] ?? [])) {
			$output->writeln('<comment>Successful</comment> session to be removed next cron (' . count($success) . '): <info>' . implode('</info>,<info> ', $success) . '</info>');
		}

		if (!empty($prep = $statuses[(string)BlockStatus::PREP->value] ?? [])) {
			$output->writeln('Session with <comment>PREP</comment> status (' . count($prep) . '): <info>' . implode('</info>,<info> ', $prep) . '</info>');
		}

		if (!empty($stand = $statuses[BlockStatus::STANDBY->value] ?? [])) {
			$output->writeln('Session in <comment>stand-by</comment> (' . count($stand) . '): <info>' . implode('</info>,<info> ', $stand) . '</info>');
		}

		if (!empty($running = $statuses[BlockStatus::RUNNING->value] ?? [])) {
			$output->writeln('Currently <comment>running</comment> session (' . count($running) . '): <info>' . implode('</info>,<info> ', $running) . '</info>');
		}

		if (!empty($err = $statuses[BlockStatus::ERROR->value] ?? [])) {
			$output->writeln('<comment>Erroneous</comment> session (' . count($err) . '): <info>' . implode('</info>,<info> ', $err) . '</info>');
		}

		if (!empty($blocker = $statuses[BlockStatus::BLOCKER->value] ?? [])) {
			$output->writeln('<comment>Blocked</comment> session (' . count($blocker) . '): <info>' . implode('</info>,<info> ', $blocker) . '</info>');
		}
	}


	private function displaySession(OutputInterface $output, string $token): void {
		foreach ($this->blockMapper->getBySession($token) as $block) {
			$output->writeln('BlockToken: <data>' . $block->getToken() . '</data>');
			$output->writeln('BlockType: <data>' . $block->getBlockType()->name . '</data>');
			$output->writeln('Interface: <data>' . $this->displayInterface($block) . '</data>');
			$output->writeln('BlockStatus: ' . $this->displayStatus($block));
			$output->writeln('Replay: <data>' . $block->getReplayCount() . '</data>');

			$output->writeln('');
		}

	}


	private function displayBlock(OutputInterface $output, string $token): void {
		$block = $this->blockMapper->getByToken($token);

		$output->writeln('SessionToken: <data>' . $block->getSessionToken() . '</data>');
		$output->writeln('BlockToken: <data>' . $block->getToken() . '</data>');
		$output->writeln('BlockType: <data>' . $block->getBlockType()->name . '</data>');
		$output->writeln('Interface: <data>' . $this->displayInterface($block) . '</data>');

		$output->writeln('Code: <data>' . $this->cropContent($block->getCode(), 3000, 15) . '</data>');
		$output->writeln('Params: <data>' . $this->cropContent(json_encode($block->getParams()), 3000, 15) . '</data>');
		$output->writeln('Metadata: <data>' . $this->cropContent(json_encode($block->getMetadata()), 3000, 15) . '</data>');
		$output->writeln('Result: <data>' . $this->cropContent(json_encode($block->getResult()), 3000, 15)  . '</data>');

		$output->writeln('Dataset: <data>' . $this->cropContent(json_encode($block->getDataset()), 3000, 15)  . '</data>');
		$output->writeln('Links: <data>' . $this->cropContent(json_encode($block->getLinks()), 3000, 15)  . '</data>');
		$output->writeln('Orig: <data>' . $this->cropContent(json_encode($block->getOrig()), 3000, 15)  . '</data>');

		$output->writeln('ExecutionTime: <data>' . BlockExecutionTime::tryFrom($block->getExecutionTime())?->name . '</data>');
		$output->writeln('Creation: <data>' . $block->getCreation() . '</data>');
		$output->writeln('LastRun: <data>' . $block->getLastRun() . '</data>');
		$output->writeln('NextRun: <data>' . $block->getNextRun() . '</data>');
		$output->writeln('BlockStatus: ' . $this->displayStatus($block));
		$output->writeln('Replay: <data>' . $block->getReplayCount() . '</data>');
	}


	private function displayStatus(Block $block): string {
		$name = $block->getBlockStatus()->name;
		$color = strtolower($name);
		return '<' . $color . '>' . $name . '</' . $color . '>';
	}

	private function displayInterface(Block $block): string {
		$iface = new BlockInterface(null, $block);

		$data = [];
		$data[] = ($iface->getId() === '') ? '' : 'id=' . $iface->getId();
		$data[] = ($iface->getName() === '') ? '' : 'name=' . $iface->getName();
		$data[] = (!$iface->isReplayable()) ? '' : 'replayable=true';
		$data[] = (!$iface->isBlocker()) ? '' : 'blocker=true';
		$data[] = (empty($iface->getRequire())) ? '' : 'require=' . implode('.', $iface->getRequire());

		return implode(', ', array_filter($data));
	}




	private function replayBlock(InputInterface $input, OutputInterface $output, string $token): void {
		$block = $this->blockMapper->getByToken($token);
		if (!in_array($block->getBlockStatus(), [BlockStatus::ERROR, BlockStatus::BLOCKER], true)) {
			$output->writeln('only Block set as ERROR or BLOCKER can be replayed');
			return;
		}

		$iface = new BlockInterface(null, $block);
		if (!$iface->isReplayable()) {
			$output->writeln('');
			$output->writeln('Block is not set as <comment>replayable</comment>.');
			$output->writeln('Replaying this Block can create issues.');
			$output->writeln('');
			$question = new ConfirmationQuestion(
				'<comment>Do you really want to replay the Block ' . $token . ' ?</comment> (y/N) ',
				false,
				'/^(y|Y)/i'
			);

			/** @var QuestionHelper $helper */
			$helper = $this->getHelper('question');
			if (!$helper->ask($input, $output, $question)) {
				$output->writeln('aborted.');
				return;
			}
		}

		$block->replay(true);
		$this->blockMapper->update($block);
	}


	/**
	 * crop content after n lines or n chars
	 */
	private function cropContent(string $content, int $maxChars, int $maxLines): string {
		if ($this->noCrop) {
			return $content;
		}
		preg_match_all("/\\n/", utf8_decode($content), $matches, PREG_OFFSET_CAPTURE);
		return substr($content, 0, min($matches[0][$maxLines-1][1] ?? 99999, $maxChars));
	}
}

