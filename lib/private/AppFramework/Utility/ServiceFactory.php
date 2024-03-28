<?php

namespace OC\AppFramework\Utility;

use Psr\Container\ContainerInterface;

class ServiceFactory {
	private $factory;
	private ContainerInterface $container;

	public function __construct(ContainerInterface $container, callable $factory) {
		$this->container = $container;
		$this->factory = $factory;
	}

	public function get() {
		return ($this->factory)($this->container);
	}
}
