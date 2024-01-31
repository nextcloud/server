<?php

declare(strict_types=1);

namespace OCP\TextToImage;

/**
 * @since 29.0.0
 */
interface IProviderWithUserId extends IProvider {
	/**
	 * @since 29.0.0
	 */
	public function setUserId(?string $userId): void;
}
