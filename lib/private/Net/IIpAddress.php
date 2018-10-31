<?php
declare(strict_types=1);

namespace OC\Net;

interface IIpAddress {
	public function getOriginal(): string;
	public function getAddress(): string;
	public function getNetmaskBits(): int;
	public function isRange(): string;
	public function containsAddress(IIpAddress $other): bool;
}

