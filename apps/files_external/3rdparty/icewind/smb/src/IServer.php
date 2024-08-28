<?php
/**
 * SPDX-FileCopyrightText: 2018 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Icewind\SMB;

interface IServer {
	public function getAuth(): IAuth;

	public function getHost(): string;

	/**
	 * @return \Icewind\SMB\IShare[]
	 *
	 * @throws \Icewind\SMB\Exception\AuthenticationException
	 * @throws \Icewind\SMB\Exception\InvalidHostException
	 */
	public function listShares(): array;

	public function getShare(string $name): IShare;

	public function getTimeZone(): string;

	public function getSystem(): ISystem;

	public function getOptions(): IOptions;

	public static function available(ISystem $system): bool;
}
