<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal Nextcloud classes

namespace OCP;

/**
 * This class remains only for use with the ::class namespace used for various hooks
 *
 * It provides the following hooks:
 *  - post_shared
 * @since 5.0.0
 * @deprecated 17.0.0
 */
class Share extends \OC\Share\Constants {
}
