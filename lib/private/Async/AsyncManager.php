<?php

namespace OC\Async;

use OC\Async\Db\BlockMapper;
use OC\Async\Enum\BlockType;
use OC\Async\Model\Block;
use OC\Async\Model\BlockInterface;
use OC\Config\Lexicon\CoreConfigLexicon;
use OCP\Async\Enum\ProcessExecutionTime;
use OCP\Async\Enum\BlockStatus;
use OCP\IAppConfig;
use ReflectionFunctionAbstract;

class AsyncManager {
	private ?string $sessionToken = null;
	/** @var BlockInterface[] */
	private array $interfaces = [];
	/** @var array<string, ReflectionFunctionAbstract> } */
	private array $blocksReflexion = [];

	public function __construct(
		private IAppConfig $appConfig,
		private BlockMapper $blockMapper,
		private ForkManager $forkManager,
	) {
	}

	/**
	 * @param BlockType $type
	 * @param string $serializedBlock
	 * @param ReflectionFunctionAbstract $reflection
	 * @param array $params
	 * @param array $allowedClasses
	 *
	 * @return IBlockInterface
	 * @see \OCP\Async\IAsyncProcess
	 * @internal prefer using public interface {@see \OCP\Async\IAsyncProcess}
	 */
	public function asyncBlock(
		BlockType $type,
		string $serializedBlock, // serialize() on the code - className if type is ::CLASS
		\ReflectionFunctionAbstract $reflection, // reflection about the method/function called as unserialization
		array $params, // parameters to use at unserialization
		array $allowedClasses = [] // list of class included in the serialization of the 'block'
	): IBlockInterface {
		$this->sessionToken ??= $this->generateToken();

		$data = $this->serializeReflectionParams($reflection, $params);
		$data['blockClasses'] = $allowedClasses;
		if ($type === BlockType::CLASSNAME) {
			$data['className'] = $serializedBlock;
		}

		$token = $this->generateToken();
		$block = new Block();
		$block->setToken($token);
		$block->setSessionToken($this->sessionToken);
		$block->setBlockType($type);
		$block->setBlockStatus(BlockStatus::PREP);
		$block->setCode($serializedBlock);
		$block->setParams($data);
		$block->setOrig([]);
		$block->setCreation(time());


		// needed !?
//		$process->addMetadataEntry('_links', $this->interfaces);

//		$this->processMapper->insert($process);

		$iface = new BlockInterface($this, $block);
		$this->interfaces[] = $iface;
		$this->blocksReflexion[$token] = $reflection;

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
			$process = $iface->getBlock();
			$process->addMetadataEntry('_iface', $iface->jsonSerialize());
			$process->setProcessExecutionTime($time);

			$dataset = [];
			foreach ($iface->getDataset() as $params) {
				$dataset[] = $this->serializeReflectionParams(
					$this->blocksReflexion[$process->getToken()], $params
				);
			}
			$process->setDataset($dataset);

			$this->blockMapper->insert($process);
		}

		$this->blockMapper->updateSessionStatus(
			$this->sessionToken, BlockStatus::STANDBY, BlockStatus::PREP
		);
		$this->processes = $this->interfaces = [];
		$this->sessionToken = null;

		return $current;
	}

	/**
	 * @internal
	 */
	public function async(ProcessExecutionTime $time): string {
		$current = $this->endSession($time);
		if ($current === null) {
			return '';
		}

		if ($time === ProcessExecutionTime::NOW) {
			$this->forkManager->forkSession($current);
		}

		return $current;
	}

	private function serializeReflectionParams(ReflectionFunctionAbstract $reflection, array $params): array {
		$i = 0;
		$processWrapper = false;
		$filteredParams = [];

		foreach ($reflection->getParameters() as $arg) {
			$argType = $arg->getType();
			$param = $params[$i];
			if ($argType !== null) {
				if ($i === 0 && $argType->getName() === ABlockWrapper::class) {
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

//
//	public function unserializeParams(array $data): array {
//		return $data;
//	}
//

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

	private function generateToken(int $length = 15): string {
		$result = '';
		for ($i = 0; $i < $length; $i++) {
			$result .= 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'[random_int(0, 61)];
		}

		return $result;
	}

	public function dropAllBlocks(): void {
		$this->blockMapper->deleteAll();
	}

	public function resetConfig(): void {
		$this->appConfig->deleteKey('core', CoreConfigLexicon::ASYNC_LOOPBACK_ADDRESS);
		$this->appConfig->deleteKey('core', CoreConfigLexicon::ASYNC_LOOPBACK_PING);
		$this->appConfig->deleteKey('core', CoreConfigLexicon::ASYNC_LOOPBACK_TEST);
	}
}
