<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
\OC_JSON::checkAppEnabled('files_external');
\OC_JSON::checkLoggedIn();
\OC_JSON::callCheck();
$l = \OC::$server->getL10N('files_external');

// TODO: implement redirect to which storage backend requested this
