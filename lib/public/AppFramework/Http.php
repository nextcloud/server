<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\AppFramework;

/**
 * Base class which contains constants for HTTP status codes
 * @since 6.0.0
 */
class Http {
	/**
	 * @since 6.0.0
	 */
	public const STATUS_CONTINUE = 100;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_SWITCHING_PROTOCOLS = 101;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_PROCESSING = 102;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_OK = 200;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_CREATED = 201;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_ACCEPTED = 202;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_NON_AUTHORATIVE_INFORMATION = 203;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_NO_CONTENT = 204;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_RESET_CONTENT = 205;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_PARTIAL_CONTENT = 206;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_MULTI_STATUS = 207;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_ALREADY_REPORTED = 208;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_IM_USED = 226;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_MULTIPLE_CHOICES = 300;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_MOVED_PERMANENTLY = 301;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_FOUND = 302;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_SEE_OTHER = 303;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_NOT_MODIFIED = 304;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_USE_PROXY = 305;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_RESERVED = 306;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_TEMPORARY_REDIRECT = 307;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_BAD_REQUEST = 400;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_UNAUTHORIZED = 401;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_PAYMENT_REQUIRED = 402;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_FORBIDDEN = 403;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_NOT_FOUND = 404;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_METHOD_NOT_ALLOWED = 405;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_NOT_ACCEPTABLE = 406;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_PROXY_AUTHENTICATION_REQUIRED = 407;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_REQUEST_TIMEOUT = 408;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_CONFLICT = 409;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_GONE = 410;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_LENGTH_REQUIRED = 411;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_PRECONDITION_FAILED = 412;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_REQUEST_ENTITY_TOO_LARGE = 413;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_REQUEST_URI_TOO_LONG = 414;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_UNSUPPORTED_MEDIA_TYPE = 415;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_REQUEST_RANGE_NOT_SATISFIABLE = 416;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_EXPECTATION_FAILED = 417;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_IM_A_TEAPOT = 418;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_UNPROCESSABLE_ENTITY = 422;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_LOCKED = 423;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_FAILED_DEPENDENCY = 424;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_UPGRADE_REQUIRED = 426;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_PRECONDITION_REQUIRED = 428;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_TOO_MANY_REQUESTS = 429;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_INTERNAL_SERVER_ERROR = 500;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_NOT_IMPLEMENTED = 501;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_BAD_GATEWAY = 502;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_SERVICE_UNAVAILABLE = 503;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_GATEWAY_TIMEOUT = 504;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_HTTP_VERSION_NOT_SUPPORTED = 505;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_VARIANT_ALSO_NEGOTIATES = 506;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_INSUFFICIENT_STORAGE = 507;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_LOOP_DETECTED = 508;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_BANDWIDTH_LIMIT_EXCEEDED = 509;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_NOT_EXTENDED = 510;

	/**
	 * @since 6.0.0
	 */
	public const STATUS_NETWORK_AUTHENTICATION_REQUIRED = 511;
}
