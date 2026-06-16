<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

style('settings', 'settings');
?>

<div id="settings-app"></div>
<div class="hidden-visually" id="original-settings-content" <?php if (!empty($_['activeSectionId'])) { ?> data-active-section-id="<?php print_unescaped($_['activeSectionId']) ?>" <?php } if (!empty($_['activeSectionType'])) { ?> data-active-section-type="<?php print_unescaped($_['activeSectionType']) ?>" <?php } ?>>
	<?php print_unescaped($_['content']); ?>
</div>
