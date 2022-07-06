<?php

namespace OC\DB\ORM;

use Doctrine\ORM\Query\Parameter;
use OCP\DB\ORM\IParameter;

class ParameterAdapter implements IParameter {
	private Parameter $parameter;

	public function __construct(Parameter $parameter) {
		$this->parameter = $parameter;
	}

	public function getName(): string {
		return $this->parameter->getName();
	}

	public function getValue() {
		return $this->parameter->getValue();
	}

	public function getType() {
		return $this->parameter->getType();
	}

	public function setValue($value, $type = null): void {
		$this->parameter->setValue($value, $type);
	}

	public function typeWasSpecified(): bool {
		return $this->parameter->typeWasSpecified();
	}
}
