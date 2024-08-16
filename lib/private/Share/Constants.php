<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Share;

use OCP\Share\IShare;

class Constants {
	/**
	 * @deprecated 17.0.0 - use IShare::TYPE_USER instead
	 */
	public const SHARE_TYPE_USER = 0;
	/**
	 * @deprecated 17.0.0 - use IShare::TYPE_GROUP instead
	 */
	public const SHARE_TYPE_GROUP = 1;
	// const SHARE_TYPE_USERGROUP = 2; // Internal type used by DefaultShareProvider
	/**
	 * @deprecated 17.0.0 - use IShare::TYPE_LINK instead
	 */
	public const SHARE_TYPE_LINK = 3;
	/**
	 * @deprecated 17.0.0 - use IShare::TYPE_EMAIL instead
	 */
	public const SHARE_TYPE_EMAIL = 4;
	public const SHARE_TYPE_CONTACT = 5; // ToDo Check if it is still in use otherwise remove it
	/**
	 * @deprecated 17.0.0 - use IShare::TYPE_REMOTE instead
	 */
	public const SHARE_TYPE_REMOTE = 6;
	/**
	 * @deprecated 17.0.0 - use IShare::TYPE_CIRCLE instead
	 */
	public const SHARE_TYPE_CIRCLE = 7;
	/**
	 * @deprecated 17.0.0 - use IShare::TYPE_GUEST instead
	 */
	public const SHARE_TYPE_GUEST = 8;
	/**
	 * @deprecated 17.0.0 - use IShare::REMOTE_GROUP instead
	 */
	public const SHARE_TYPE_REMOTE_GROUP = 9;
	/**
	 * @deprecated 17.0.0 - use IShare::TYPE_ROOM instead
	 */
	public const SHARE_TYPE_ROOM = 10;
	// const SHARE_TYPE_USERROOM = 11; // Internal type used by RoomShareProvider
	/**
	 * @deprecated 21.0.0 - use IShare::TYPE_DECK instead
	 */
	public const SHARE_TYPE_DECK = 12;
	// const SHARE_TYPE_DECK_USER = 13; // Internal type used by DeckShareProvider

	// Note to developers: Do not add new share types here

	public const FORMAT_NONE = -1;
	public const FORMAT_STATUSES = -2;
	public const FORMAT_SOURCES = -3;  // ToDo Check if it is still in use otherwise remove it

	public const RESPONSE_FORMAT = 'json'; // default response format for ocs calls

	public const TOKEN_LENGTH = 15; // old (oc7) length is 32, keep token length in db at least that for compatibility

	protected static $shareTypeUserAndGroups = -1;
	protected static $shareTypeGroupUserUnique = 2;
	protected static $backends = [];
	protected static $backendTypes = [];
	protected static $isResharingAllowed;
}
