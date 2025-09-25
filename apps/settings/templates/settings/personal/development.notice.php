<?php
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
?>
<div class="section development-notice">
	<p>
		<a href="<?php p($_['reasons-use-nextcloud-pdf-link']); ?>" id="open-reasons-use-nextcloud-pdf" class="link-button" target="_blank">
			<span class="icon-file-text" aria-hidden="true"></span>
			<?php p($l->t('Reasons to use Nextcloud in your organization'));?>
		</a>
	</p>
	<p>
		<?php print_unescaped(str_replace(
			[
				'{communityopen}',
				'{githubopen}',
				'{licenseopen}',
				'{linkclose}',
			],
			[
				'<a href="https://nextcloud.com/contribute" target="_blank" rel="noreferrer noopener">',
				'<a href="https://github.com/nextcloud" target="_blank" rel="noreferrer noopener">',
				'<a href="https://www.gnu.org/licenses/agpl-3.0.html" target="_blank" rel="noreferrer noopener">',
				'</a>',
			],
			$l->t('Developed by the {communityopen}Nextcloud community{linkclose}, the {githubopen}source code{linkclose} is licensed under the {licenseopen}AGPL{linkclose}.')
		)); ?>
	</p>

	<p class="social-button">
		<?php print_unescaped(str_replace(
			[
				'{facebookimage}',
				'{ximage}',
				'{blueskyimage}',
				'{mastodonimage}',
				'{rssimage}',
				'{mailimage}',
				'{facebookopen}',
				'{xopen}',
				'{blueskyopen}',
				'{mastodonopen}',
				'{rssopen}',
				'{newsletteropen}',
				'{linkclose}',
				'{facebooktext}',
				'{xtext}',
				'{blueskytext}',
				'{mastodontext}',
				'{rsstext}',
				'{mailtext}',
			],
			[
				image_path('core', 'facebook-light.svg'),
				image_path('core', 'x-dark.svg'),
				image_path('core', 'bluesky-light.svg'),
				image_path('core', 'mastodon-light.svg'),
				image_path('core', 'rss.svg'),
				image_path('core', 'mail.svg'),
				'<a target="_blank" rel="noreferrer noopener" href="https://www.facebook.com/Nextclouders/">',
				'<a target="_blank" rel="noreferrer noopener" href="https://x.com/nextclouders">',
				'<a target="_blank" rel="noreferrer noopener" href="https://bsky.app/profile/nextcloud.bsky.social">',
				'<a target="_blank" rel="noreferrer noopener" href="https://mastodon.xyz/@nextcloud">',
				'<a target="_blank" rel="noreferrer noopener" href="https://nextcloud.com/blog/">',
				'<a target="_blank" rel="noreferrer noopener" href="https://newsletter.nextcloud.com/?p=subscribe&amp;id=1">',
				'</a>',
				$l->t('Like our Facebook page'),
				$l->t('Follow us on X'),
				$l->t('Follow us on Bluesky'),
				$l->t('Follow us on Mastodon'),
				$l->t('Check out our blog'),
				$l->t('Subscribe to our newsletter'),

			],
			'{facebookopen}<img width="50" height="50" src="{facebookimage}" title="{facebooktext}" alt="{facebooktext}">{linkclose}
			{xopen}<img width="50" height="50" src="{ximage}" style="filter: var(--background-invert-if-dark);" title="{xtext}" alt="{xtext}">{linkclose}
			{blueskyopen}<img width="50" height="50" src="{blueskyimage}" title="{blueskytext}" alt="{blueskytext}">{linkclose}
			{mastodonopen}<img width="50" height="50" src="{mastodonimage}" title="{mastodontext}" alt="{mastodontext}">{linkclose}
			{rssopen}<img width="50" height="50" src="{rssimage}" title="{rsstext}" alt="{rsstext}">{linkclose}
			{newsletteropen}<img width="50" height="50" src="{mailimage}" title="{mailtext}" alt="{mailtext}">{linkclose}'
		)); ?>
	</p>
</div>
