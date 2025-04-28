<?php

namespace OC\Async;

use Laravel\SerializableClosure\SerializableClosure;
use Laravel\SerializableClosure\Serializers\Native;
use OC\Async\Enum\BlockType;
use OCP\Async\Enum\ProcessExecutionTime;
use OCP\Async\IAsyncProcess;
use ReflectionFunction;
use ReflectionMethod;

class AsyncProcess implements IAsyncProcess {
	public function __construct(
		private AsyncManager $asyncManager,
		private readonly ForkManager $forkManager,
	) {
	}

	public function exec(\Closure $closure, ...$params): IBlockInterface {
		return $this->asyncManager->asyncBlock(
			BlockType::CLOSURE,
			serialize(new SerializableClosure($closure)),
			new ReflectionFunction($closure),
			$params,
			[SerializableClosure::class, Native::class]
		);
	}

	public function invoke(callable $obj, ...$params): IBlockInterface {
		return $this->asyncManager->asyncBlock(
			BlockType::INVOKABLE,
			serialize($obj),
			new ReflectionMethod($obj, '__invoke'),
			$params,
			[$obj::class]
		);
	}

	public function call(string $class, ...$params): IBlockInterface {
		// abstract ?
		if (!method_exists($class, 'async')) {
			throw new \Exception('class ' . $class . ' is missing async() method');
		}

		return $this->asyncManager->asyncBlock(
			BlockType::CLASSNAME,
			$class,
			new ReflectionMethod($class, 'async'),
			$params,
		);
	}

	/**
	 * close the creation of the session and start async as soon as possible
	 *
	 * @param ProcessExecutionTime $time preferred urgency to start the async process
	 *
	 * @return string session token, empty if no opened session
	 */
	public function async(ProcessExecutionTime $time = ProcessExecutionTime::NOW): string {
		return $this->asyncManager->async($time);
	}
}
