<?php
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
?>
<div id="initial-state-container" style="display: none;">
	<?php foreach ($_['initialStates'] as $app => $initialState) { ?>
		<input type="hidden" id="initial-state-<?php p($app); ?>" value="<?php p(base64_encode($initialState)); ?>">
	<?php }?>
</div>
