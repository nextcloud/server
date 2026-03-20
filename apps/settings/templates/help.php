<?php
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

\OCP\Util::addStyle('settings', 'help');

$knowledgebaseEmbedded = ($_['knowledgebaseEmbedded'] ?? false) === true;
$mode = $_['mode'] ?? '';
$isAdmin = (bool)($_['admin'] ?? false);

$url = $_['url'] ?? '';
$urlUserDocs = $_['urlUserDocs'] ?? '';
$urlAdminDocs = $_['urlAdminDocs'] ?? '';
$legalNoticeUrl = $_['legalNoticeUrl'] ?? '';
$privacyUrl = $_['privacyUrl'] ?? '';

$resources = [
	[
		'label' => $l->t('Account documentation'),
		'standaloneLabel' => $l->t('Account documentation'),
		'href' => $urlUserDocs,
		'show' => true,
		'embeddedIcon' => 'icon-user',
		'embeddedMode' => 'user',
		'external' => false,
	],
	[
		'label' => $l->t('Administration documentation'),
		'standaloneLabel' => $l->t('Administration documentation'),
		'href' => $urlAdminDocs,
		'show' => $isAdmin,
		'embeddedIcon' => 'icon-user-admin',
		'embeddedMode' => 'admin',
		'external' => false,
	],
	[
		'label' => $l->t('Documentation'),
		'standaloneLabel' => $l->t('General documentation'),
		'href' => 'https://docs.nextcloud.com',
		'show' => true,
		'embeddedIcon' => 'icon-category-office',
		'external' => true,
	],
	[
		'label' => $l->t('Forum'),
		'standaloneLabel' => $l->t('Forum'),
		'href' => 'https://help.nextcloud.com',
		'show' => true,
		'embeddedIcon' => 'icon-comment',
		'external' => true,
	],
	[
		'label' => $l->t('Legal notice'),
		'standaloneLabel' => $l->t('Legal notice'),
		'href' => $legalNoticeUrl,
		'show' => !empty($legalNoticeUrl),
		'external' => true,
	],
	[
		'label' => $l->t('Privacy policy'),
		'standaloneLabel' => $l->t('Privacy policy'),
		'href' => $privacyUrl,
		'show' => !empty($privacyUrl),
		'external' => true,
	],
];
?>

<?php if ($knowledgebaseEmbedded): ?>
	<div id="app-navigation" role="navigation" tabindex="0">
		<ul>
			<?php foreach ($resources as $resource): ?>
				<?php
				if (!$resource['show'] || !isset($resource['embeddedIcon'])) {
					continue;
				}

				$isCurrent = isset($resource['embeddedMode']) && $resource['embeddedMode'] === $mode;
				$linkClass = 'help-list__link ' . $resource['embeddedIcon'] . ($isCurrent ? ' active' : '');
				$isExternal = $resource['external'] ?? false;
				?>
				<li>
					<a
						class="<?php p($linkClass); ?>"
						<?php if ($isCurrent): ?>aria-current="page"<?php endif; ?>
						href="<?php print_unescaped($resource['href']); ?>"
						<?php if ($isExternal): ?>target="_blank" rel="noreferrer noopener"<?php endif; ?>>
						<span class="help-list__text">
							<?php p($resource['label'] . ($isExternal ? ' ↗' : '')); ?>
						</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>

	<div id="app-content" class="help-includes">
		<iframe src="<?php print_unescaped($url); ?>" class="help-iframe" tabindex="0"></iframe>
	</div>
<?php else: ?>
	<div id="app-content">
		<div class="help-wrapper">
			<div class="help-content">
				<h2 class="help-content__heading">
					<?php p($l->t('Nextcloud help & privacy resources')); ?>
				</h2>
				<div class="help-content__body">
					<?php foreach ($resources as $resource): ?>
						<?php if (!$resource['show']) { 
							continue;
						} ?>
						<a
							class="button"
							target="_blank"
							rel="noreferrer noopener"
							href="<?php print_unescaped($resource['href']); ?>">
							<?php p(($resource['standaloneLabel'] ?? $resource['label']) . ' ↗'); ?>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</div>
<?php endif; ?>
