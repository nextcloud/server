<?php

declare(strict_types=1);

namespace OCP\Security\Signature\Model;

/**
 * @since 30.0.0
 */
enum SignatoryType: int {
	/** @since 30.0.0 */
	case FORGIVABLE = 1; // no notice on refresh
	/** @since 30.0.0 */
	case REFRESHABLE = 4; // notive on refresh
	/** @since 30.0.0 */
	case TRUSTED = 8; // warning on refresh
	/** @since 30.0.0 */
	case STATIC = 9; // error on refresh
}
