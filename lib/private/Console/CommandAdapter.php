<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OC\Console;

use OC\Core\Command\Base;
use OCP\Console\Attribute\Argument;
use OCP\Console\Attribute\AsCommand;
use OCP\Console\Attribute\Option;
use OCP\Console\ExitCode;
use OCP\Console\IInput;
use OCP\Console\IOutput;
use OCP\Console\ISignalHandler;
use OCP\Console\OutputFormat;
use OCP\Defaults;
use OCP\Server;
use Override;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @template T
 */
class CommandAdapter extends Base {
	/** Union types allowed for an option whose value is itself optional */
	private const OPTION_VALUE_OPTIONAL_UNION_TYPES = ['bool|float', 'bool|int', 'bool|string'];

	private AsCommand $asCommand;

	private \ReflectionMethod $reflectionMethod;

	/** @var array<string, array{arg: Argument, parameter: \ReflectionParameter}> */
	private array $arguments = [];

	/** @var array<string, array{option: Option, parameter: \ReflectionParameter}> */
	private array $options = [];

	/**
	 * @param class-string<T> $className
	 */
	public function __construct(
		private readonly string $className,
		private readonly ?string $method,
		private readonly ContainerInterface $container,
	) {

		if ($method !== null) {
			$reflectionMethod = new \ReflectionMethod($className, $method);
			$asCommands = $reflectionMethod->getAttributes(AsCommand::class);
			if ($asCommands === []) {
				throw new \RuntimeException('Missing #[AsCommand] attribute on method: ' . $method . ' from class: ' . $className);
			}

			$this->asCommand = $asCommands[0]->newInstance();
		} else {
			$reflectionClass = new \ReflectionClass($className);
			$asCommands = $reflectionClass->getAttributes(AsCommand::class);
			if ($asCommands === []) {
				throw new \RuntimeException('Missing #[AsCommand] attribute on class: ' . $className);
			}

			$this->asCommand = $asCommands[0]->newInstance();

			$reflectionMethod = new \ReflectionMethod($className, '__invoke');
		}

		$this->reflectionMethod = $reflectionMethod;

		foreach ($reflectionMethod->getParameters() as $parameter) {
			$args = $parameter->getAttributes(Argument::class);
			if ($args !== []) {
				/** @var Argument $argument */
				$argument = $args[0]->newInstance();
				if ($argument->name === '') {
					$argument->name = $parameter->getName();
				}

				$this->arguments[$parameter->getName()] = ['arg' => $argument, 'parameter' => $parameter];
			}

			$args = $parameter->getAttributes(Option::class);
			if ($args !== []) {
				/** @var Option $option */
				$option = $args[0]->newInstance();
				if ($option->name === '') {
					$option->name = $parameter->getName();
				}

				$this->options[$parameter->getName()] = ['option' => $option, 'parameter' => $parameter];
			}
		}

		parent::__construct();
	}

	#[Override]
	public function configure(): void {
		$this->setHelp('More extensive and thorough documentation may be found at ' . Server::get(Defaults::class)->getDocBaseUrl() . PHP_EOL);

		if ($this->asCommand->supportsOutputFormat) {
			$this->addOption(
				'output',
				null,
				InputOption::VALUE_OPTIONAL,
				'Output format (plain, json or json_pretty, default is plain)',
				$this->defaultOutputFormat
			);
		}

		$this->setName($this->asCommand->name);

		if ($this->asCommand->description) {
			$this->setDescription($this->asCommand->description);
		}

		foreach ($this->arguments as $argument) {
			/** @var Argument $arg */
			$arg = $argument['arg'];
			$parameter = $argument['parameter'];
			$reflection = new ReflectionMember($parameter);
			$type = $reflection->getType();
			$name = $reflection->getName();
			if (!$type instanceof \ReflectionNamedType) {
				throw new \LogicException(\sprintf('The %s "$%s" of "%s" must have a named type. Untyped, Union or Intersection types are not supported for command arguments.', $reflection->getMemberName(), $name, $reflection->getSourceName()));
			}
			$isOptional = $reflection->hasDefaultValue() || $reflection->isNullable() || $reflection->isVariadic();
			$typeName = $type->getName();
			$mode = $isOptional ? InputArgument::OPTIONAL : InputArgument::REQUIRED;
			if ($typeName === 'array' || $reflection->isVariadic()) {
				$mode |= InputArgument::IS_ARRAY;
			}
			$default = $reflection->hasDefaultValue() ? $reflection->getDefaultValue() : null;

			$this->addArgument($arg->name, $mode, $arg->description, $default);
		}

		foreach ($this->options as $option) {
			$parameter = $option['parameter'];
			/** @var Option $option */
			$option = $option['option'];
			$reflection = new ReflectionMember($parameter);
			$type = $reflection->getType();
			$name = $reflection->getName();
			$default = $reflection->hasDefaultValue() ? $reflection->getDefaultValue() : null;

			if ($type instanceof \ReflectionUnionType) {
				// A union of bool with string/int/float declares an option whose value
				// is itself optional, e.g. "--foo" (true), "--foo=bar" (bar) or omitted (false)
				$typeNames = array_map(
					static fn (\ReflectionType $t) => $t instanceof \ReflectionNamedType ? $t->getName() : null,
					$type->getTypes(),
				);
				sort($typeNames);
				$unionTypeName = implode('|', array_filter($typeNames));

				if (!\in_array($unionTypeName, self::OPTION_VALUE_OPTIONAL_UNION_TYPES, true)) {
					throw new \LogicException(\sprintf('The union type for option "$%s" of "%s" is not supported as a command option. Only "%s" types are allowed.', $name, $reflection->getSourceName(), implode('", "', self::OPTION_VALUE_OPTIONAL_UNION_TYPES)));
				}

				if ($default !== false) {
					throw new \LogicException(\sprintf('The option "$%s" of "%s" must have a default value of false.', $name, $reflection->getSourceName()));
				}

				$this->addOption($option->name, $option->shortcut, InputOption::VALUE_OPTIONAL, $option->description, $default);
				continue;
			}

			if (!$type instanceof \ReflectionNamedType) {
				throw new \LogicException(\sprintf('The %s "$%s" of "%s" must have a named type. Untyped or Intersection types are not supported for command options.', $reflection->getMemberName(), $name, $reflection->getSourceName()));
			}

			$allowNull = $reflection->isNullable();
			$typeName = $type->getName();

			if ($typeName === 'bool' && $allowNull && \in_array($default, [true, false], true)) {
				throw new \LogicException(\sprintf('The option %s "$%s" of "%s" must not be nullable when it has a default boolean value.', $reflection->getMemberName(), $name, $reflection->getSourceName()));
			}

			if ($allowNull && $default !== null) {
				throw new \LogicException(\sprintf('The option %s "$%s" of "%s" must either be not-nullable or have a default of null.', $reflection->getMemberName(), $name, $reflection->getSourceName()));
			}

			if ($typeName === 'bool') {
				$mode = InputOption::VALUE_NONE;
				if ($default !== false) {
					$mode |= InputOption::VALUE_NEGATABLE;
				} else {
					$default = null;
				}
			} elseif ($typeName === 'array' || $reflection->isVariadic()) {
				$mode = InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY;
			} else {
				$mode = InputOption::VALUE_REQUIRED;
			}

			$this->addOption($option->name, $option->shortcut, $mode, $option->description, $default);
		}
	}

	#[Override]
	public function execute(InputInterface $input, OutputInterface $output): int {
		/** @var T $instance */
		$instance = $this->container->get($this->className);

		$symfonyStyle = new SymfonyStyle($input, $output);

		$parameters = [];
		foreach ($this->reflectionMethod->getParameters() as $parameter) {
			$name = $parameter->getName();

			if (isset($this->arguments[$name])) {
				$parameters[] = $input->getArgument($this->arguments[$name]['arg']->name);
				continue;
			}

			if (isset($this->options[$name])) {
				$value = $input->getOption($this->options[$name]['option']->name);
				if ($value === null && $parameter->getType() instanceof \ReflectionUnionType) {
					// The option was passed without a value, e.g. "--foo"
					$value = true;
				}
				$parameters[] = $value;
				continue;
			}

			$type = $parameter->getType();
			if ($type instanceof \ReflectionNamedType && $type->getName() === IOutput::class) {
				$parameters[] = new OutputAdapter($output, $input, $symfonyStyle, $this);
				continue;
			}

			if ($type instanceof \ReflectionNamedType && $type->getName() === IInput::class) {
				$parameters[] = new InputAdapter($input, $symfonyStyle);
				continue;
			}

			if ($type instanceof \ReflectionNamedType && $type->getName() === ISignalHandler::class) {
				$parameters[] = new SignalHandlerAdapter($this);
				continue;
			}

			if ($type instanceof \ReflectionNamedType && $type->getName() === OutputFormat::class) {
				$output = OutputFormat::tryFrom($input->getOption('output'));
				if ($output === null) {
					$output = OutputFormat::Plain;
				}
				$parameters[] = $output;
				continue;
			}

			throw new \LogicException(\sprintf('Unable to resolve parameter "$%s" of "%s": it is neither an #[Argument], an #[Option], nor an %s, %s, %s or %s.', $name, $this->reflectionMethod->getName(), IOutput::class, IInput::class, ISignalHandler::class, OutputFormat::class));
		}

		if ($this->method !== null) {
			$result = $instance->{$this->method}(...$parameters);
		} else {
			$result = $instance(...$parameters);
		}

		return $result instanceof ExitCode ? $result->value : $result;
	}

	#[Override]
	public function abortIfInterrupted(): void {
		// To make it public
		parent::abortIfInterrupted();
	}

	#[Override]
	public function writeArrayInOutputFormat(InputInterface $input, OutputInterface $output, iterable $items, string $prefix = '  - '): void {
		// To make it public
		parent::writeArrayInOutputFormat($input, $output, $items, $prefix);
	}

	#[Override]
	public function writeTableInOutputFormat(InputInterface $input, OutputInterface $output, array $items): void {
		// To make it public
		parent::writeTableInOutputFormat($input, $output, $items);
	}

	#[Override]
	public function writeStreamingTableInOutputFormat(InputInterface $input, OutputInterface $output, \Iterator $items, int $tableGroupSize): void {
		// To make it public
		parent::writeStreamingTableInOutputFormat($input, $output, $items, $tableGroupSize);
	}
}
