<?php
/**
 * SPDX-FileCopyrightText: 2018 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
