<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** @var \OCP\IL10N $l */
/** @var array $_ */

?>

<?php foreach ($_['forms'] as $form): ?>
	<?php
	$formHtml = $form['form'] ?? null;
	if ($formHtml === null) {
		continue;
	}

	$anchor = $form['anchor'] ?? '';
	?>
	<div<?php if ($anchor !== ''): ?> id="<?php p($anchor); ?>"<?php endif; ?>>
		<?php print_unescaped($formHtml); ?>
	</div>
<?php endforeach; ?>
