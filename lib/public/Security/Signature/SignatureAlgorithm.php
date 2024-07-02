<?php

declare(strict_types=1);

namespace OCP\Security\Signature;

enum SignatureAlgorithm: string {
	case SHA256 = 'sha256';
	case SHA512 = 'sha512';
}
