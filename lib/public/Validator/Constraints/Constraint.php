<?php

namespace OCP\Validator\Constraints;

use OC\Validator\IConstraintValidator;
use OCP\IL10N;
use OCP\L10N\IFactory;

/**
 * Abstract class for validatoin constraint.
 *
 * For the moment, you must not extends this class inside your Nextcloud application.
 * Instead use the already existing public constraints or contribute new constraints
 * to Nextcloud core.
 */
abstract class Constraint {
	protected IL10N $l10n;

	public function __construct() {
		$this->l10n = \OC::$server->get(IFactory::class)->get('core');
	}

	/**
	 * @return class-string<IConstraintValidator>
	 */
	public function validatedBy(): string {
		return str_replace('Constraints\\', '', str_replace('OCP', 'OC', static::class)) . 'Validator';
	}
}
