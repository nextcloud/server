<?php

namespace OC\Async;

use OC\Async\Db\BlockMapper;
use OC\Async\Enum\BlockType;
use OC\Async\Exceptions\AsyncProcessException;
use OC\Async\Exceptions\LoopbackEndpointException;
use OC\Async\Exceptions\BlockAlreadyRunningException;
use OC\Async\Exceptions\SessionBlockedException;
use OC\Async\Model\Block;
use OC\Async\Model\BlockInterface;
use OC\Async\Model\SessionInterface;
use OC\Async\Wrappers\DummyBlockWrapper;
use OC\Async\Wrappers\LoggerBlockWrapper;
use OC\Config\Lexicon\CoreConfigLexicon;
use OC\DB\Connection;
use OCP\Async\Enum\BlockActivity;
use OCP\Async\Enum\ProcessExecutionTime;
use OCP\Async\Enum\BlockStatus;
use OCP\Async\IAsyncProcess;
use OCP\Http\Client\IClientService;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ForkManager {
	private ?ABlockWrapper $wrapper;

	/** @var int[] */
	private array $forks = [];
	private const FORK_LIMIT = 3; // maximum number of child process
	private const FORK_SLEEP = 500000; // wait for msec when too many fork have been created

	public function __construct(
		private BlockMapper $blockMapper,
		private Connection $conn,
		private IAppConfig $appConfig,
		private IConfig $config,
		private IClientService $clientService,
		private IURLGenerator $urlGenerator,
		LoggerBlockWrapper $loggerProcessWrapper,
		private LoggerInterface $logger,
	) {
		$this->wrapper = $loggerProcessWrapper;
	}

	public function setWrapper(?ABlockWrapper $wrapper): void {
		$this->wrapper = $wrapper;
	}


	/**
	 * @throws BlockAlreadyRunningException
	 * @throws AsyncProcessException
	 */
	public function runSession(string $token, array $metadata = []): void {
		$sessionBlocks = $this->blockMapper->getBySession($token);
		$metadata['sessionToken'] = $token;

		if ($this->wrapper !== null) {
			$wrapper = clone $this->wrapper;
		} else {
			$wrapper = null;
		}

		// might be need to avoid some conflict/race condition
		//		usleep(10000);
		$sessionIface = new SessionInterface(BlockInterface::asBlockInterfaces($sessionBlocks));

		if ($sessionIface->getGlobalStatus() !== BlockStatus::STANDBY) {
			throw new AsyncProcessException();
		}

		$wrapper?->setSessionInterface($sessionIface);
		$wrapper?->session($metadata);

		try {
			foreach ($sessionBlocks as $block) {
				if (!$this->confirmBlockRequirement($wrapper, $sessionIface, $block)) {
					$block->replay();
					$this->blockMapper->update($block);
					continue;
				}

				$block->addMetadata($metadata);
				$this->runBlock($block, $wrapper);

				if ($block->getBlockStatus() === BlockStatus::BLOCKER) {
					$wrapper?->end('Fail process ' . $block->getToken() . ' block the rest of the session');
					throw new SessionBlockedException();
				}
			}
			$wrapper?->end();
		} catch (BlockAlreadyRunningException) {
			$wrapper?->end('already running');
			throw new BlockAlreadyRunningException();
		}
	}

	private function confirmBlockRequirement(
		?ABlockWrapper $wrapper,
		SessionInterface $sessionIface,
		Block $block
	): bool {
		$procIface = new BlockInterface(null, $block);
		foreach ($procIface->getRequire() as $requiredProcessId) {
			$requiredBlock = $sessionIface->byId($requiredProcessId);
			if ($requiredBlock === null) {
				$wrapper?->activity(BlockActivity::NOTICE, 'could not initiated block as it requires block ' . $requiredProcessId . ' which is not defined');
				return false;
			}
			if ($requiredBlock?->getStatus() !== BlockStatus::SUCCESS) {
				$wrapper?->activity(BlockActivity::NOTICE, 'could not initiated block as it requires block ' . $requiredProcessId . ' to be executed and successful');
				return false;
			}
		}
		return true;
	}

	/**
	 * @throws BlockAlreadyRunningException
	 */
	private function runBlock(Block $block, ?ABlockWrapper $wrapper = null): void {
		if ($block->getBlockStatus() !== BlockStatus::STANDBY) {
			return;
		}

		$this->lockBlock($block);

		$data = $block->getParams();
		$serialized = $block->getCode();
		$params = unserialize($data['params'], ['allowed_classes' => $data['paramsClasses']]);
		$obj = unserialize($serialized, ['allowed_classes' => $data['blockClasses'] ?? []]);

		if ($data['processWrapper'] ?? false) {
			array_unshift($params, ($wrapper ?? new DummyBlockWrapper()));
		}

		$wrapper?->setBlock($block);
		$wrapper?->init();
		$wrapper?->activity(BlockActivity::STARTING);
		$result = [
			'executionTime' => ProcessExecutionTime::NOW->value,
			'startTime' => time(),
		];
		$iface = new BlockInterface(null, $block);
		try {
			$returnedData = null;
			switch ($block->getBlockType()) {
				case BlockType::CLOSURE:
					$c = $obj->getClosure();
					$returnedData = $c(...$params);
					break;

				case BlockType::INVOKABLE:
					$returnedData = $obj(...$params);
					break;

				case BlockType::CLASSNAME:
					$obj = new $data['className']();
					$returnedData = $obj->async(...$params);
					break;
			}
			if (is_array($returnedData)) {
				$result['result'] = $returnedData;
			}
			$block->setBlockStatus(BlockStatus::SUCCESS);
			if ($block->getReplayCount() > 0) {
				// in case of success after multiple tentative, we reset next run to right now
				// on all block waiting for replay. Easiest solution to find block dependant of
				// this current successful run
				$this->blockMapper->resetSessionNextRun($block->getSessionToken());
			}
		} catch (\Exception $e) {
			$wrapper?->activity(BlockActivity::ERROR, $e->getMessage());
			$result['error'] = [
				'exception' => get_class($e),
				'message' => $e->getMessage(),
				'trace' => $e->getTrace(),
				'code' => $e->getCode()
			];

			if ($iface->isReplayable()) {
				$block->replay(); // we mark the block as able to be back to STANDBY status
			} else {
				$block->setNextRun(0);
			}
			if ($iface->isBlocker()) {
				$block->setBlockStatus(BlockStatus::BLOCKER);
			} else {
				$block->setBlockStatus(BlockStatus::ERROR);
			}
		} finally {
			$result['endTime'] = time();
		}

		$block->setResult($result);
		$wrapper?->activity(BlockActivity::ENDING);
		$this->blockMapper->updateStatus($block, BlockStatus::RUNNING);
	}

	/**
	 * @throws BlockAlreadyRunningException
	 */
	private function lockBlock(Block $block): void {
		if ($block->getBlockStatus() !== BlockStatus::STANDBY) {
			throw new BlockAlreadyRunningException('block not in standby');
		}
		$lockToken = $this->generateToken(7);
		$block->setBlockStatus(BlockStatus::RUNNING);
		if (!$this->blockMapper->updateStatus($block, BlockStatus::STANDBY, $lockToken)) {
			throw new BlockAlreadyRunningException('block is locked');
		}
		$block->setLockToken($lockToken);
	}

	public function forkSession(string $session, array $metadata = []): void {
		if (\OC::$CLI) {
			$useWebIfNeeded = $metadata['useWebIfNeeded'] ?? false;
			if ($useWebIfNeeded && !extension_loaded('posix')) {
				try {
					$this->forkSessionLoopback($session);
					return;
				} catch (\Exception) {
				}
			}

			$this->forkSessionCli($session, $metadata);
			return;
		}

		try {
			$this->forkSessionLoopback($session);
		} catch (LoopbackEndpointException) {
			// session will be processed later
		}
	}


	private function forkSessionCli(string $session, array $metadata = []): void {
		if (!extension_loaded('posix')) {
			// log/notice that posix is not loaded
			return;
		}
		$slot = $this->getFirstAvailableSlot();
		$metadata += [
			'_cli' => [
				'slot' => $slot,
				'forkCount' => count($this->forks),
				'forkLimit' => self::FORK_LIMIT,
			]
		];

		$pid = pcntl_fork();

		// work around as the parent database connection is inherited by the child.
		// when child process is over, parent process database connection will drop.
		// The drop can happen anytime, even in the middle of a running request.
		// work around is to close the connection as soon as possible after forking.
		$this->conn->close();

		if ($pid === -1) {
			// TODO: manage issue while forking
		} else if ($pid === 0) {
			// forked process
			try {
				$this->runSession($session, $metadata);
			} catch (AsyncProcessException) {
				// failure to run session can be part of the process
			}
			exit();
		} else {
			// store slot+pid
			$this->forks[$slot] = $pid;

			// when fork limit is reach, cycle until space freed
			while (true) {
				$exitedPid = pcntl_waitpid(0, $status, WNOHANG);
				if ($exitedPid > 0) {
					$slot = array_search($exitedPid, $this->forks, true);
					if ($slot) {
						unset($this->forks[$slot]);
					}
				}
				if (count($this->forks) < self::FORK_LIMIT) {
					return;
				}
				usleep(self::FORK_SLEEP);
			}
		}
	}

	/**
	 * Request local loopback endpoint.
	 * We expect the request to be closed remotely.
	 *
	 * Ignored if:
	 * - the endpoint is not fully configured and tested,
	 * - the server is on heavy load (timeout at 1 second)
	 *
	 * @return string result from the loopback endpoint
	 * @throws LoopbackEndpointException if not configured
	 */
	private function forkSessionLoopback(string $session, ?string $loopbackEndpoint = null): string {
		$client = $this->clientService->newClient();
		try {
			$response = $client->post(
				$loopbackEndpoint ?? $this->linkToLoopbackEndpoint(),
				[
					'headers' => [],
					'verify' => false,
					'connect_timeout' => 1.0,
					'timeout' => 1.0,
					'http_errors' => true,
					'body' => ['token' => $session],
					'nextcloud' => [
						'allow_local_address' => true,
						'allow_redirects' => true,
					]
				]
			);

			return (string)$response->getBody();
		} catch (LoopbackEndpointException $e) {
			$this->logger->debug('loopback endpoint not configured', ['exception' => $e]);
			throw $e;
		} catch (\Exception $e) {
			$this->logger->warning('could not reach loopback endpoint to initiate fork', ['exception' => $e]);
			throw new LoopbackEndpointException('loopback endpoint cannot be reach', previous: $e);
		}
	}


	/**
	 * return full (absolute) link to the web-loopback endpoint
	 *
	 * @param string|null $instance if null, stored loopback address will be used.
	 *
	 * @throws LoopbackEndpointException if $instance is null and no stored configuration
	 */
	public function linkToLoopbackEndpoint(?string $instance = null): string {
		return rtrim($instance ?? $this->getLoopbackInstance(), '/') . $this->urlGenerator->linkToRoute('core.AsyncProcess.processFork');
	}

	/**
	 * return loopback address stored in configuration
	 *
	 * @return string
	 * @throws LoopbackEndpointException if config is not set or empty
	 */
	public function getLoopbackInstance(): string {
		if (!$this->appConfig->hasKey('core', CoreConfigLexicon::ASYNC_LOOPBACK_ADDRESS, true)) {
			throw new LoopbackEndpointException('loopback not configured');
		}

		$instance = $this->appConfig->getValueString('core', CoreConfigLexicon::ASYNC_LOOPBACK_ADDRESS);
		if ($instance === '') {
			throw new LoopbackEndpointException('empty config');
		}

		return $instance;
	}


	public function discoverLoopbackEndpoint(?OutputInterface $output = null): ?string {
		$cliUrl = $this->config->getSystemValueString('overwrite.cli.url', '');
		$output?->write('- testing value from \'overwrite.cli.url\' (<comment>' . $cliUrl . '</comment>)... ');

		$reason = '';
		if ($this->testLoopbackInstance($cliUrl, $reason)) {
			$output?->writeln('<info>ok</info>');
			return $cliUrl;
		}

		$output?->writeln('<error>' . $reason . '</error>');

		foreach($this->config->getSystemValue('trusted_domains', []) as $url) {
			$url = 'https://' . $url;
			$output?->write('- testing entry from \'trusted_domains\' (<comment>' . $url . '</comment>)... ');
			if ($this->testLoopbackInstance($url, $reason)) {
				$output?->writeln('<info>ok</info>');
				return $url;
			}
			$output?->writeln('<error>' . $reason . '</error>');
		}

		return null;
	}


	public function testLoopbackInstance(string $url, string &$reason = ''): bool {
		$url = rtrim($url, '/');
		if (!$this->pingLoopbackInstance($url)) {
			$reason = 'failed ping';
			return false;
		}

		$token = $this->generateToken();
		$asyncProcess = \OCP\Server::get(IAsyncProcess::class);
		$asyncProcess->exec(function(string $token) {
			sleep(1); // enforce a delay to confirm asynchronicity
			$appConfig = \OCP\Server::get(IAppConfig::class);
			$appConfig->setValueString('core', CoreConfigLexicon::ASYNC_LOOPBACK_TEST, $token);
		}, $token)->name('test loopback instance')->async();

		$this->appConfig->clearCache(true);
		if ($token === $this->appConfig->getValueString('core', CoreConfigLexicon::ASYNC_LOOPBACK_TEST)) {
			$reason = 'async process already executed';
			return false;
		}

		sleep(3);
		$this->appConfig->clearCache(true);
		$result = ($token === $this->appConfig->getValueString('core', CoreConfigLexicon::ASYNC_LOOPBACK_TEST));
		$this->appConfig->deleteKey('core', CoreConfigLexicon::ASYNC_LOOPBACK_TEST);

		return $result;
	}

	private function pingLoopbackInstance(string $url): bool {
		$pingLoopback = $this->generateToken();
		$this->appConfig->setValueString('core', CoreConfigLexicon::ASYNC_LOOPBACK_PING, $pingLoopback);
		try {
			$result = $this->forkSessionLoopback('__ping__', $this->linkToLoopbackEndpoint($url));
			$result = json_decode($result, true, flags: JSON_THROW_ON_ERROR);
		} catch (\JsonException|LoopbackEndpointException $e) {
			$this->logger->debug('could not ping loopback endpoint', ['exception' => $e]);
		}

		$this->appConfig->deleteKey('core', CoreConfigLexicon::ASYNC_LOOPBACK_PING);
		return (($result['ping'] ?? '') === $pingLoopback);
	}


	/**
	 * note that the fact we fork process to run a session of processes before doing
	 * a check on the fact that maybe one of the process of the session is already
	 * running can create a small glitch when choosing the first available slot as
	 * a previous fork running said check is already made and will exit shortly.
	 *
	 * @return int
	 */
	private function getFirstAvailableSlot(): int {
		$slot = -1;
		for ($i = 0; $i < self::FORK_LIMIT; $i++) {
			if (!array_key_exists($i, $this->forks)) {
				return $i;
			}

			// we confirm child process still exists
			if (pcntl_waitpid($this->forks[$i], $status, WNOHANG) > 0) {
				return $i;
			}
		}

		if ($slot === -1) {
			// TODO: should not happens: log warning
		}

		return -1;
	}

	private function generateToken(int $length = 15): string {
		$result = '';
		for ($i = 0; $i < $length; $i++) {
			$result .= 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'[random_int(0, 61)];
		}

		return $result;
	}

	/**
	 * we wait until all child process are done
	 *
	 * @noinspection PhpStatementHasEmptyBodyInspection
	 */
	public function waitChildProcess(): void {
		while (pcntl_waitpid(0, $status) != -1) {
		}
	}

}
