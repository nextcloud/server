<?php

declare(strict_types=1);

namespace OCP\Security\Signature\Model;

/**
 * @since 30.0.0
 */
enum SignatoryStatus: int {
	/** @since 30.0.0 */
	case SYNCED = 1;
	/** @since 30.0.0 */
	case IDLE = 2;
	/** @since 30.0.0 */
	case BROKEN = 9;
}
