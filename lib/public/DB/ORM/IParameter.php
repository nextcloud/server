<?php

namespace OCP\DB\ORM;

interface IParameter {

	/**
	 * Retrieves the Parameter name.
	 */
	public function getName(): string

	/**
	 * Retrieves the Parameter value.
	 *
	 * @return mixed
	 */
	public function getValue();

	/**
	 * Retrieves the Parameter type.
	 *
	 * @return mixed
	 */
	public function getType();

	/**
	 * Defines the Parameter value.
	 *
	 * @param mixed $value Parameter value.
	 * @param mixed $type  Parameter type.
	 *
	 * @return void
	 */
	public function setValue($value, $type = null): void

	public function typeWasSpecified(): bool;
}
