<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCP\AppFramework;

/**
 * Base class which contains constants for HTTP status codes
 * @since 6.0.0
 */
class Http {
	public const STATUS_CONTINUE = 100;
	public const STATUS_SWITCHING_PROTOCOLS = 101;
	public const STATUS_PROCESSING = 102;
	public const STATUS_OK = 200;
	public const STATUS_CREATED = 201;
	public const STATUS_ACCEPTED = 202;
	public const STATUS_NON_AUTHORATIVE_INFORMATION = 203;
	public const STATUS_NO_CONTENT = 204;
	public const STATUS_RESET_CONTENT = 205;
	public const STATUS_PARTIAL_CONTENT = 206;
	public const STATUS_MULTI_STATUS = 207;
	public const STATUS_ALREADY_REPORTED = 208;
	public const STATUS_IM_USED = 226;
	public const STATUS_MULTIPLE_CHOICES = 300;
	public const STATUS_MOVED_PERMANENTLY = 301;
	public const STATUS_FOUND = 302;
	public const STATUS_SEE_OTHER = 303;
	public const STATUS_NOT_MODIFIED = 304;
	public const STATUS_USE_PROXY = 305;
	public const STATUS_RESERVED = 306;
	public const STATUS_TEMPORARY_REDIRECT = 307;
	public const STATUS_BAD_REQUEST = 400;
	public const STATUS_UNAUTHORIZED = 401;
	public const STATUS_PAYMENT_REQUIRED = 402;
	public const STATUS_FORBIDDEN = 403;
	public const STATUS_NOT_FOUND = 404;
	public const STATUS_METHOD_NOT_ALLOWED = 405;
	public const STATUS_NOT_ACCEPTABLE = 406;
	public const STATUS_PROXY_AUTHENTICATION_REQUIRED = 407;
	public const STATUS_REQUEST_TIMEOUT = 408;
	public const STATUS_CONFLICT = 409;
	public const STATUS_GONE = 410;
	public const STATUS_LENGTH_REQUIRED = 411;
	public const STATUS_PRECONDITION_FAILED = 412;
	public const STATUS_REQUEST_ENTITY_TOO_LARGE = 413;
	public const STATUS_REQUEST_URI_TOO_LONG = 414;
	public const STATUS_UNSUPPORTED_MEDIA_TYPE = 415;
	public const STATUS_REQUEST_RANGE_NOT_SATISFIABLE = 416;
	public const STATUS_EXPECTATION_FAILED = 417;
	public const STATUS_IM_A_TEAPOT = 418;
	public const STATUS_UNPROCESSABLE_ENTITY = 422;
	public const STATUS_LOCKED = 423;
	public const STATUS_FAILED_DEPENDENCY = 424;
	public const STATUS_UPGRADE_REQUIRED = 426;
	public const STATUS_PRECONDITION_REQUIRED = 428;
	public const STATUS_TOO_MANY_REQUESTS = 429;
	public const STATUS_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
	public const STATUS_INTERNAL_SERVER_ERROR = 500;
	public const STATUS_NOT_IMPLEMENTED = 501;
	public const STATUS_BAD_GATEWAY = 502;
	public const STATUS_SERVICE_UNAVAILABLE = 503;
	public const STATUS_GATEWAY_TIMEOUT = 504;
	public const STATUS_HTTP_VERSION_NOT_SUPPORTED = 505;
	public const STATUS_VARIANT_ALSO_NEGOTIATES = 506;
	public const STATUS_INSUFFICIENT_STORAGE = 507;
	public const STATUS_LOOP_DETECTED = 508;
	public const STATUS_BANDWIDTH_LIMIT_EXCEEDED = 509;
	public const STATUS_NOT_EXTENDED = 510;
	public const STATUS_NETWORK_AUTHENTICATION_REQUIRED = 511;
}
