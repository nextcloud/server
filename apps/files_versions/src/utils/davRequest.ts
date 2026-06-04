/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export default `<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:"
	xmlns:oc="http://owncloud.org/ns"
	xmlns:nc="http://nextcloud.org/ns"
	xmlns:ocs="http://open-collaboration-services.org/ns">
	<d:prop>
		<d:getcontentlength />
		<d:getcontenttype />
		<d:getlastmodified />
		<d:getetag />
		<nc:version-label />
		<nc:version-author />
		<nc:has-preview />
	</d:prop>
</d:propfind>`
