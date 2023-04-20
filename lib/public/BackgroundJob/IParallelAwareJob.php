<?php

namespace OCP\BackgroundJob;

interface IParallelAwareJob {
	/**
	 * Set this to false to prevent two Jobs from the same class from running in parallel
	 *
	 * @param bool $allow
	 * @return void
	 * @since 27.0.0
	 */
	public function setAllowParallelRuns(bool $allow): void;

	/**
	 * @return bool
	 * @since 27.0.0
	 */
	public function getAllowParallelRuns(): bool;
}
