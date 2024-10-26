/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export const getProtocol = () => window.location.protocol.split(':')[0]

/**
 * Returns the host used to access this Nextcloud instance
 * Host is sometimes the same as the hostname but now always.
 *
 * Examples:
 * http://example.com => example.com
 * https://example.com => example.com
 * http://example.com:8080 => example.com:8080
 *
 * @return {string} host
 *
 * @since 8.2.0
 * @deprecated 17.0.0 use window.location.host directly
 */
export const getHost = () => window.location.host

/**
 * Returns the hostname used to access this Nextcloud instance
 * The hostname is always stripped of the port
 *
 * @return {string} hostname
 * @since 9.0.0
 * @deprecated 17.0.0 use window.location.hostname directly
 */
export const getHostName = () => window.location.hostname

/**
 * Returns the port number used to access this Nextcloud instance
 *
 * @return {number} port number
 *
 * @since 8.2.0
 * @deprecated 17.0.0 use window.location.port directly
 */
export const getPort = () => window.location.port
