<?php
/**
 * SPDX-FileCopyrightText: 2016-2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

style('settings', 'settings');

$activeSectionId = $_['activeSectionId'] ?? '';
$activeSectionType = $_['activeSectionType'] ?? '';

$mainAttributes = '';

if (!empty($activeSectionId)) {
	$mainAttributes .= ' data-active-section-id="' . $activeSectionId . '"';
}

if (!empty($activeSectionType)) {
	$mainAttributes .= ' data-active-section-type="' . $activeSectionType . '"';
}
?>

<div id="app-navigation"></div>
<main id="app-content"<?php print_unescaped($mainAttributes); ?>>
	<?php print_unescaped($_['content']); ?>
</main>
