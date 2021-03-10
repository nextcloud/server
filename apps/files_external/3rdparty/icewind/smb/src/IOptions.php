<?php
/**
 * @copyright Copyright (c) 2018 Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Icewind\SMB;

interface IOptions {
	const PROTOCOL_NT1 = 'NT1';
	const PROTOCOL_SMB2 = 'SMB2';
	const PROTOCOL_SMB2_02 = 'SMB2_02';
	const PROTOCOL_SMB2_22 = 'SMB2_22';
	const PROTOCOL_SMB2_24 = 'SMB2_24';
	const PROTOCOL_SMB3 = 'SMB3';
	const PROTOCOL_SMB3_00 = 'SMB3_00';
	const PROTOCOL_SMB3_02 = 'SMB3_02';
	const PROTOCOL_SMB3_10 = 'SMB3_10';
	const PROTOCOL_SMB3_11 = 'SMB3_11';

	public function getTimeout(): int;

	public function getMinProtocol(): ?string;

	public function getMaxProtocol(): ?string;
}
