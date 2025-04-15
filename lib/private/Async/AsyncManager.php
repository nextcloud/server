<?php

namespace OC\Async;

use OC\Async\Db\ProcessMapper;
use OC\Async\Enum\ProcessActivity;
use OC\Async\Enum\ProcessExecutionTime;
use OC\Async\Enum\ProcessStatus;
use OC\Async\Enum\ProcessType;
use OC\Async\Exceptions\LoopbackEndpointException;
use OC\Async\Exceptions\ProcessAlreadyRunningException;
use OC\Async\Model\Process;
use OC\Async\Model\ProcessInterface;
use OC\Async\Model\SessionInterface;
use OC\Async\Wrappers\DummyProcessWrapper;
use OC\Async\Wrappers\LoggerProcessWrapper;
use OC\DB\Connection;
use OCP\Http\Client\IClientService;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;
use ReflectionFunctionAbstract;

class AsyncManager {
	private ?string $sessionToken = null;
	/** @var IProcessInterface[] */
	private array $interfaces = [];
	/** @var array<string, ReflectionFunctionAbstract> } */
	private array $processesReflexion = [];
	private ?AProcessWrapper $wrapper;

	/** @var int[] */
	private array $forks = [];
	private const FORK_LIMIT = 3; // maximum number of child process
	private const FORK_SLEEP = 500000; // wait for msec when too many fork have been created

	public function __construct(
		private ProcessMapper $processMapper,
		private Connection $conn,
		private IAppConfig $appConfig,
		private IConfig $config,
		private IClientService $clientService,
		private IURLGenerator $urlGenerator,
		LoggerProcessWrapper $loggerProcessWrapper,
		private LoggerInterface $logger,
	) {
		$this->wrapper = $loggerProcessWrapper;
	}


	public function asyncProcess(
		ProcessType $type,
		string $serializedProcess, // serialize() on the code - className if type is ::CLASS
		\ReflectionFunctionAbstract $reflection, // reflection about the method/function called as unserialization
		array $params, // parameters to use at unserialization
		array $allowedClasses = [] // list of class included in the serialization of the 'process'
	): IProcessInterface {
		$this->sessionToken ??= $this->generateToken();

		$data = $this->serializeReflectionParams($reflection, $params);
		$data['processClasses'] = $allowedClasses;
		if ($type === ProcessType::CLASSNAME) {
			$data['className'] = $serializedProcess;
		}

		$token = $this->generateToken();
		$process = new Process();
		$process->setToken($token);
		$process->setSessionToken($this->sessionToken);
		$process->setProcessType($type);
		$process->setProcessStatus(ProcessStatus::PREP);
		$process->setCode($serializedProcess);
		$process->setParams($data);
		$process->setOrig([]);
		$process->setCreation(time());
		$process->addMetadataEntry('_links', $this->interfaces);

//		$this->processMapper->insert($process);

		$iface = new ProcessInterface($this, $process);
		$this->interfaces[] = $iface;
		$this->processesReflexion[$token] = $reflection;

		return $iface;
	}

//	public function updateDataset(ProcessInfo $processInfo) {
//		$token = $processInfo->getToken();
//		$dataset = [];
//		foreach($processInfo->getDataset() as $params) {
//			$dataset[] = $this->serializeReflectionParams($this->processesReflexion[$token], $params);
//		}
//		$this->processMapper->updateDataset($token, $dataset);
//	}

	private function endSession(ProcessExecutionTime $time): ?string {
		if ($this->sessionToken === null) {
			return null;
		}

		$current = $this->sessionToken;
		foreach ($this->interfaces as $iface) {
			$process = $iface->getProcess();
			$process->addMetadataEntry('_iface', $iface->jsonSerialize());
			$process->setProcessExecutionTime($time);

			$dataset = [];
			foreach ($iface->getDataset() as $params) {
				$dataset[] = $this->serializeReflectionParams($this->processesReflexion[$process->getToken()], $params);
			}
			$process->setDataset($dataset);

			$this->processMapper->insert($process);
		}

		$this->processMapper->updateSessionStatus($this->sessionToken, ProcessStatus::STANDBY, ProcessStatus::PREP);
		$this->processes = $this->interfaces = [];
		$this->sessionToken = null;

		return $current;
	}


	/**
	 * @return Process[]
	 */
	public function listStandByProcess(): array {
		return $this->processMapper->getByStatus(ProcessStatus::STANDBY);
	}

	/**
	 * @return string[]
	 */
	public function listStandBySessions(): array {
		return $this->processMapper->getSessionOnStandBy();
	}

	public function setWrapper(?AProcessWrapper $wrapper): void {
		$this->wrapper = $wrapper;
	}

	public function async(ProcessExecutionTime $time): string {
		$current = $this->endSession($time);
		if ($time === ProcessExecutionTime::NOW) {
			$this->forkSession($current);
		}

		return $current;
	}

	/**
	 * @param bool $useWebIfNeeded switch to web async concept if available and posix not loaded
	 */
	public function forkSession(string $session, bool $useWebIfNeeded = false): void {
		if (\OC::$CLI) {
			if ($useWebIfNeeded && !extension_loaded('posix')) {
				try {
					$this->forkSessionLoopback($session);
					return;
				} catch (\Exception) {
				}
			}

			$this->forkSessionCli($session);
			return;
		}

		try {
			$this->forkSessionLoopback($session);
		} catch (LoopbackEndpointException) {
			// session will be processed later
		}
	}


	private function forkSessionCli(string $session): void {
		if (!extension_loaded('posix')) {
			// log/notice that posix is not loaded
			return;
		}
		$slot = $this->getFirstAvailableSlot();
		$metadata = [
			'slot' => $slot,
			'forkCount' => count($this->forks),
			'forkLimit' => self::FORK_LIMIT,
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
			} catch (ProcessAlreadyRunningException) {
				// TODO: logger->debug() ?
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
					unset($this->forks[$slot]);
				}
				if (count($this->forks) < self::FORK_LIMIT) {
					return;
				}
				usleep(self::FORK_SLEEP);
			}
		}
	}

	/**
	 * @throws ProcessAlreadyRunningException
	 */
	public function runSession(string $session, array $metadata = []): void {
		$sessionProcesses = $this->processMapper->getBySession($session);
		$metadata['sessionToken'] = $session;

		if ($this->wrapper !== null) {
			$wrapper = clone $this->wrapper;
		} else {
			$wrapper = null;
		}

		// might be need to avoid some conflict/race condition
		//		usleep(10000);
		$iface = new SessionInterface($this->asProcessInterfaces($sessionProcesses));
		if ($iface->getStatus() !== ProcessStatus::STANDBY) {
			throw new ProcessAlreadyRunningException();
		}

		$wrapper?->setSessionInterface($iface);
		$wrapper?->session($metadata);

		try {
			foreach ($sessionProcesses as $process) {
				$process->addMetadata($metadata);
				$this->runProcess($process, $wrapper);
			}
			$wrapper?->end();
		} catch (ProcessAlreadyRunningException) {
			$wrapper?->end('already running');
			throw new ProcessAlreadyRunningException();
		}
	}

	/**
	 * @param string $session
	 *
	 * @return bool FALSE if loopback is not usable
	 * @throws LoopbackEndpointException
	 */
	private function forkSessionLoopback(string $session): void {
		$client = $this->clientService->newClient();
		try {
			$client->post(
				$this->linkToLoopbackEndpoint(),
				[
					'headers' => [],
					'verify' => false,
					'timeout' => 4,
					'http_errors' => true,
					'body' => ['token' => $session],
					'nextcloud' => [
						'allow_local_address' => true,
						'allow_redirects' => true,
					]
				]
			);
		} catch (LoopbackEndpointException $e) {
			$this->logger->debug('loopback endpoint not configured', ['exception' => $e]);
			throw $e;
		} catch (\Exception $e) {
			$this->logger->warning('could not reach loopback endpoint to initiate fork', ['exception' => $e]);
			throw $e;
		}
	}


	/**
	 * @throws ProcessAlreadyRunningException
	 */
	private function runProcess(Process $process, ?AProcessWrapper $wrapper = null): void {
		if ($process->getProcessStatus() !== ProcessStatus::STANDBY) {
			return;
		}

		$this->lockProcess($process);

		$data = $process->getParams();
		$serialized = $process->getCode();
		$params = unserialize($data['params'], ['allowed_classes' => $data['paramsClasses']]);
		$obj = unserialize($serialized, ['allowed_classes' => $data['processClasses'] ?? []]);

		if ($data['processWrapper'] ?? false) {
			array_unshift($params, ($wrapper ?? new DummyProcessWrapper()));
		}

		$wrapper?->init($process);
		$wrapper?->activity(ProcessActivity::STARTING);
		try {
			$result = null;
			switch ($process->getProcessType()) {
				case ProcessType::CLOSURE:
					$c = $obj->getClosure();
					$result = $c(...$params);
					break;

				case ProcessType::INVOKABLE:
					$result = $obj(...$params);
					break;

				case ProcessType::CLASSNAME:
					$obj = new $data['className']();
					$result = $obj->async(...$params);
					break;
			}
			if (is_array($result)) {
				$process->setResult($result);
			}

		} catch (\Exception $e) {
			$wrapper?->activity(ProcessActivity::ERROR, $e->getMessage());
			$process->setResult(
				[
					'exception' => get_class($e),
					'message' => $e->getMessage(),
					'trace' => $e->getTrace(),
					'code' => $e->getCode()
				]
			);
			$process->setProcessStatus(ProcessStatus::ERROR);
		}

		$wrapper?->activity(ProcessActivity::ENDING);
		$this->processMapper->updateStatus($process);
	}

	/**
	 * @throws ProcessAlreadyRunningException
	 */
	private function lockProcess(Process $process): void {
		if ($process->getProcessStatus() !== ProcessStatus::STANDBY) {
			throw new ProcessAlreadyRunningException('process not in standby');
		}
		$lockToken = $this->generateToken(7);
		$process->setProcessStatus(ProcessStatus::RUNNING);
		if (!$this->processMapper->updateStatus($process, ProcessStatus::STANDBY, $lockToken)) {
			throw new ProcessAlreadyRunningException('process is locked');
		}
		$process->setLockToken($lockToken);
	}

	private function serializeReflectionParams(ReflectionFunctionAbstract $reflection, array $params): array {
		$i = 0;
		$processWrapper = false;
		$filteredParams = [];

		foreach ($reflection->getParameters() as $arg) {
			$argType = $arg->getType();
			$param = $params[$i];
			if ($argType !== null) {
				if ($i === 0 && $argType->getName() === AProcessWrapper::class) {
					$processWrapper = true;
					continue; // we ignore IProcessWrapper as first argument, as it will be filled at execution time
				}

				// TODO: compare $argType with $param
//			foreach($argType->getTypes() as $t) {
//				echo '> ' . $t . "\n";
//			}
// $arg->allowsNull()
// $arg->isOptional()

			}

			// TODO: we might want to filter some params ?
			$filteredParams[] = $param;

//			echo '..1. ' . json_encode($param) . "\n";
//			echo '..2. ' . serialize($param) . "\n";
//			echo '? ' . gettype($param) . "\n";

			$i++;
		}

		try {
			$serializedParams = serialize($params);
		} catch (\Exception $e) {
			throw $e;
		}

		return [
			'params' => $serializedParams,
			'paramsClasses' => $this->extractClassFromArray($filteredParams),
			'processWrapper' => $processWrapper,
		];
	}


	public function unserializeParams(array $data): array {
		return $data;
	}


	private function extractClassFromArray(array $arr, array &$classes = []): array {
		foreach ($arr as $entry) {
			if (is_array($entry)) {
				$this->extractClassFromArray($entry, $classes);
			}

			if (is_object($entry)) {
				$class = get_class($entry);
				if (!in_array($class, $classes, true)) {
					$classes[] = $class;
				}
			}
		}

		return $classes;
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
		}

		if ($slot === -1) {
			// TODO: should not happens: log warning
		}

		return -1;
	}

	/**
	 * @param Process[] $processes
	 *
	 * @return ProcessInterface[]
	 */
	private function asProcessInterfaces(array $processes): array {
		$interfaces = [];
		foreach($processes as $process) {
			$interfaces[] = new ProcessInterface($this, $process);
		}

		return $interfaces;
	}

	/**
	 * @throws LoopbackEndpointException
	 */
	private function linkToLoopbackEndpoint(): string {
		return $this->getLoopbackInstance() . $this->urlGenerator->linkToRoute('core.AsyncProcess.processFork');
	}

	/**
	 * @return string
	 * @throws LoopbackEndpointException if 'async_loopback_instance' is not set or empty
	 */
	private function getLoopbackInstance(): string {
		if (!$this->appConfig->hasKey('core', 'async_loopback_instance', true)) {
			throw new LoopbackEndpointException('loopback not configured');
		}

		$instance = $this->config->getSystemValueString('core', 'async_loopback_instance', '');
		if ($instance === '') {
			throw new LoopbackEndpointException('empty config');
		}

		return $instance;
	}

	private function guessLoopbackInstance(): bool {
		try {
			$this->getLoopbackInstance();
			return true;
		} catch (LoopbackEndpointException) {
		}

		$cliUrl = $this->config->getSystemValueString('overwrite.cli.url', '');
		if ($this->testLoopbackInstance($cliUrl, true)) {
			return true;
		}

		foreach($this->config->getSystemValue('trusted_domains', []) as $url) {
			if ($this->testLoopbackInstance('https://' . $url, true)) {
				return true;
			}
		}

		return false;
	}


	private function testLoopbackInstance(string $url, bool $save): bool {
		$url = rtrim($url, '/');
		$result = true;

		if ($save && $result) {
			$this->appConfig->setValueString('core', 'async_loopback_instance', $url, true);
		}

		return $result;
	}



	private function generateToken(int $length = 15): string {
		$result = '';
		for ($i = 0; $i < $length; $i++) {
			$result .= 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'[random_int(0, 61)];
		}

		return $result;
	}

	public function dropAll(): void {
		$this->processMapper->deleteAll();
	}

}
