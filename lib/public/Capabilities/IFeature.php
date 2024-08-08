<?php

namespace OCP\Capabilities;

/**
 * Interface for apps to expose their available features.
 *
 * @since 30.0.0
 */
interface IFeature {
	/**
	 * Returns the available features.
	 *
	 * ```php
	 * return [
	 *   'myapp' => [
	 *     'feature1',
	 *     'feature2',
	 *   ],
	 *   'otherapp' => [
	 *     'feature3',
	 *   ],
	 * ];
	 * ```
	 *
	 * @return array<string, list<string>>
	 * @since 30.0.0
	 */
	public function getFeatures(): array;
}
