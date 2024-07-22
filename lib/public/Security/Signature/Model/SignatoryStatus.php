<?php

declare(strict_types=1);

namespace OCP\Security\Signature\Model;

/**
 * current status of signatory. is it trustable or not ?
 *
 * - SYNCED = the remote instance is trustable.
 * - BROKEN = the remote instance does not use the same key pairs
 *
 * @since 30.0.0
 */
enum SignatoryStatus: int {
	/** @since 30.0.0 */
	case SYNCED = 1;
	/** @since 30.0.0 */
	case BROKEN = 9;
}
