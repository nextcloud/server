<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Share;

class Constants {
	public const FORMAT_NONE = -1;
	public const FORMAT_STATUSES = -2;
	public const FORMAT_SOURCES = -3;  // ToDo Check if it is still in use otherwise remove it

	public const RESPONSE_FORMAT = 'json'; // default response format for ocs calls

	public const MIN_TOKEN_LENGTH = 6; // 19,770,609,664 different possible variations
	public const DEFAULT_TOKEN_LENGTH = 15; // 54,960,434,128,018,667,122,720,768 different possible variations
	public const MAX_TOKEN_LENGTH = 32; // 8,167,835,760,036,914,488,254,418,108,462,708,901,695,678,621,570,564,096 different possible variations
	public const TOKEN_LENGTH = self::DEFAULT_TOKEN_LENGTH; // old (oc7) length is 32, keep token length in db at least that for compatibility

	protected static $shareTypeUserAndGroups = -1;
	protected static $shareTypeGroupUserUnique = 2;
	protected static $backends = [];
	protected static $backendTypes = [];
	protected static $isResharingAllowed;
}
