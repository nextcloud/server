<?php
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

$reasonsPdfLink = $_['reasons-use-nextcloud-pdf-link'] ?? '';

$aboutPlaceholders = [
	'{communityopen}',
	'{githubopen}',
	'{licenseopen}',
	'{linkclose}',
];
$aboutReplacements = [
	'<a href="https://nextcloud.com/contribute" target="_blank" rel="noreferrer noopener">',
	'<a href="https://github.com/nextcloud" target="_blank" rel="noreferrer noopener">',
	'<a href="https://www.gnu.org/licenses/agpl-3.0.html" target="_blank" rel="noreferrer noopener">',
	'</a>',
];
$aboutText = $l->t(
	'Developed by the {communityopen}Nextcloud community{linkclose}, the {githubopen}source code{linkclose} is licensed under the {licenseopen}AGPL{linkclose}.'
);

$socialLinks = [
	[
		'href' => 'https://www.facebook.com/Nextclouders/',
		'icon' => image_path('core', 'facebook-light.svg'),
		'label' => $l->t('Like our Facebook page'),
	],
	[
		'href' => 'https://bsky.app/profile/nextcloud.bsky.social',
		'icon' => image_path('core', 'bluesky-light.svg'),
		'label' => $l->t('Follow us on Bluesky'),
	],
	[
		'href' => 'https://mastodon.xyz/@nextcloud',
		'icon' => image_path('core', 'mastodon-light.svg'),
		'label' => $l->t('Follow us on Mastodon'),
	],
	[
		'href' => 'https://nextcloud.com/blog/',
		'icon' => image_path('core', 'rss.svg'),
		'label' => $l->t('Check out our blog'),
	],
	[
		'href' => 'https://newsletter.nextcloud.com/?p=subscribe&id=1',
		'icon' => image_path('core', 'mail.svg'),
		'label' => $l->t('Subscribe to our newsletter'),
	],
];
?>

<div class="section development-notice">
	<p>
		<a href="<?php p($reasonsPdfLink); ?>" id="open-reasons-use-nextcloud-pdf" class="link-button" target="_blank">
			<span class="icon-file-text" aria-hidden="true"></span>
			<?php p($l->t('Reasons to use Nextcloud in your organization')); ?>
		</a>
	</p>

	<p>
		<?php print_unescaped(str_replace($aboutPlaceholders, $aboutReplacements, $aboutText)); ?>
	</p>

	<p class="social-button">
		<?php foreach ($socialLinks as $socialLink): ?>
			<a target="_blank" rel="noreferrer noopener" href="<?php p($socialLink['href']); ?>">
				<img
					width="50"
					height="50"
					src="<?php p($socialLink['icon']); ?>"
					title="<?php p($socialLink['label']); ?>"
					alt="<?php p($socialLink['label']); ?>">
			</a>
		<?php endforeach; ?>
	</p>
</div>
