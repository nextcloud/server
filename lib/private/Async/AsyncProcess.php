<?php

namespace OC\Async;

use Laravel\SerializableClosure\SerializableClosure;
use Laravel\SerializableClosure\Serializers\Native;
use OC\Async\Enum\ProcessExecutionTime;
use OC\Async\Enum\ProcessType;
use OC\Async\Model\Process;
use OC\Async\Model\ProcessInterface;
use OCP\Server;
use ReflectionFunction;
use ReflectionMethod;

// TODO: rename ?
class AsyncProcess {
	private const TRACE_LIMIT = 0;

	public static function exec(\Closure $closure, ...$params): IProcessInterface {
		/** @var AsyncManager $asyncManager */
		$asyncManager = Server::get(AsyncManager::class);

		return $asyncManager->asyncProcess(
			ProcessType::CLOSURE,
			serialize(new SerializableClosure($closure)),
			new ReflectionFunction($closure),
			$params,
			[SerializableClosure::class, Native::class]
		);
	}

	public static function invoke(callable $obj, ...$params): IProcessInterface {
		/** @var AsyncManager $asyncManager */
		$asyncManager = Server::get(AsyncManager::class);

		return $asyncManager->asyncProcess(
			ProcessType::INVOKABLE,
			serialize($obj),
			new ReflectionMethod($obj, '__invoke'),
			$params,
			[$obj::class]
		);
	}

	public static function call(string $class, ...$params): IProcessInterface {
		// abstract ?
		if (!method_exists($class, 'async')) {
			throw new \Exception('class ' . $class . ' is missing async() method');
		}

		/** @var AsyncManager $asyncManager */
		$asyncManager = Server::get(AsyncManager::class);

		return $asyncManager->asyncProcess(
			ProcessType::CLASSNAME,
			$class,
			new ReflectionMethod($class, 'async'),
			$params,
		);
	}

	public static function async(ProcessExecutionTime $time = ProcessExecutionTime::NOW): string {
		/** @var AsyncManager $asyncManager */
		$asyncManager = Server::get(AsyncManager::class);
		return $asyncManager->async($time);
	}

	public static function setWrapper(?AProcessWrapper $wrapper): void {
		/** @var AsyncManager $asyncManager */
		$asyncManager = Server::get(AsyncManager::class);
		$asyncManager->setWrapper($wrapper);
	}
}
