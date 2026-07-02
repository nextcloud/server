<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// ext-openssl padding mode constants for psalm. PSS omitted: PHP 8.5+ only.
const OPENSSL_PKCS1_PADDING = 1;
const OPENSSL_SSLV23_PADDING = 2;
const OPENSSL_NO_PADDING = 3;
const OPENSSL_PKCS1_OAEP_PADDING = 4;
