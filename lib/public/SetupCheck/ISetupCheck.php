<?php

declare(strict_types=1);

namespace OCP\SetupCheck;

/**
 * This interface needs to be implemented if you want to provide custom
 * setup checks in your application. The results of these checks will them
 * be displayed in the admin overview.
 *
 * @since 28.0.0
 */
interface ISetupCheck {
	/**
	 * @since 28.0.0
	 */
	public function getCategory(): string;

	/**
	 * @since 28.0.0
	 */
	public function getName(): string;

	/**
	 * @since 28.0.0
	 */
	public function run(): SetupResult;
}
