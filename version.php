<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// We only can count up. The 4. digit is only for the internal patch level to trigger DB upgrades
// between betas, final and RCs. This is _not_ the public version number. Reset minor/patch level
// when updating major/minor version number.

$OC_Version = [31, 0, 8, 1];

// The human-readable string
$OC_VersionString = '31.0.8';

$OC_VersionCanBeUpgradedFrom = [
	'nextcloud' => [
		'30.0' => true,
		'31.0' => true,
	],
	'owncloud' => [
		'10.13' => true,
	],
];

// default Nextcloud channel
$OC_Channel = 'git';

// The build number
$OC_Build = '';

// Vendor of this package
$vendor = 'nextcloud';

// ============================================================================
// CI/CD Build Number Injection (IONOS Nextcloud Workspace)
// ============================================================================
// Injects a 5th element into the $OC_Version array from the .buildnumber file.
// This file is automatically created by the CI/CD pipeline (build-artifact.yml)
// and contains the GitHub workflow run ID for traceability.
//
// NOTE: This is an IONOS-specific customization for Nextcloud Workspace.
//       It is not part of upstream Nextcloud and is used for tracking
//       IONOS Nextcloud Workspace builds in production environments.
//
// @since 31.0.8
//
// Purpose:
//   - Track which specific CI/CD run produced this artifact
//   - Enable direct linking to workflow logs and build details
//   - Distinguish between different builds of the same version
//   - Support debugging by identifying exact build in production
//
// File Format:
//   .buildnumber - Single line containing an integer (GitHub run ID)
//   Example: 12345678901
//
// Result:
//   Without .buildnumber: $OC_Version = [31, 0, 8, 1]
//   With .buildnumber:    $OC_Version = [31, 0, 8, 1, 12345678901]
//
// Accessed via:
//   - ServerVersion::getBuildId() -> 12345678901
//   - ServerVersion::getHumanVersion() -> "31.0.8 (12345678901)"
//   - ServerVersion::getVersion() -> [31, 0, 8, 1, 12345678901]
//
// Workflow URL Construction:
//   https://github.com/{org}/{repo}/actions/runs/{buildId}
//
// ============================================================================
$buildNumberFile = __DIR__ . '/.buildnumber';
if (file_exists($buildNumberFile)) {
	$buildNumberContent = @file_get_contents($buildNumberFile);
	if ($buildNumberContent !== false) {
		$buildId = (int)trim($buildNumberContent);
		if ($buildId > 0) {
			// Append build ID as 5th element in version array
			$OC_Version[] = $buildId;
		}
	}
}
