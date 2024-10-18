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
				'{mastodonimage}',
				'{rssimage}',
				'{mailimage}',
				'{facebookopen}',
				'{xopen}',
				'{mastodonopen}',
				'{rssopen}',
				'{newsletteropen}',
				'{linkclose}',
				'{facebooktext}',
				'{xtext}',
				'{mastodontext}',
				'{rsstext}',
				'{mailtext}',
			],
			[
				'<svg aria-hidden="true" width="50" height="50" version="1.1" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><circle cx="8" cy="8" r="8" fill="var(--color-text-maxcontrast)"/><path d="m7 5c0-1.1 0.9-2 2-2h1.5v2h-1c-0.27 0-0.5 0.23-0.5 0.5v1h1.5v2h-1.5v4.5h-2v-4.5h-1.5v-2h1.5z" fill="var(--color-main-background)"/></svg>',
				'<svg aria-hidden="true" width="50" height="50" version="1.1" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><circle cx="8" cy="8" r="8" fill="var(--color-text-maxcontrast)"/><path d="m 3.384891,2.6 c -0.3882,0 -0.61495,0.4362184 -0.39375,0.7558594 L 6.5841098,8.4900156 2.9770785,12.707422 C 2.7436785,12.979821 2.9370285,13.4 3.2958285,13.4 H 3.694266 c 0.176,0 0.3430313,-0.07714 0.4570313,-0.210938 L 7.294266,9.5065156 9.6602817,12.887891 C 9.8762817,13.208984 10.25229,13.4 10.743485,13.4 h 1.900391 c 0.3882,0 0.61575,-0.436688 0.39375,-0.754688 L 9.2466097,7.2195156 12.682547,3.1941408 C 12.881744,2.9601408 12.715528,2.6 12.407473,2.6 h -0.506566 c -0.175,0 -0.34186,0.076453 -0.45586,0.2197656 L 8.5405785,6.2058438 6.3790317,3.1132812 C 6.1568442,2.7913687 5.6965004,2.6 5.3958285,2.6 Z" fill="var(--color-main-background)"/></svg>',
				'<svg aria-hidden="true" width="50" height="50" version="1.1" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><circle cx="8" cy="8" r="8" fill="var(--color-text-maxcontrast)"/><path d="m13.183 9.2819c-0.1566 0.80567-1.4026 1.6874-2.8336 1.8583-0.74623 0.08903-1.4809 0.17088-2.2644 0.13494-1.2813-0.05872-2.2923-0.30582-2.2923-0.30582 0 0.12473 0.0077 0.24349 0.02307 0.35456 0.16657 1.2645 1.2538 1.3402 2.2837 1.3755 1.0395 0.03557 1.9651-0.25629 1.9651-0.25629l0.0427 0.93975s-0.72709 0.39044-2.0223 0.46224c-0.71423 0.03926-1.6011-0.01798-2.634-0.29136-2.2402-0.59294-2.6255-2.9809-2.6844-5.4039-0.01797-0.7194-0.00687-1.3977-0.00687-1.9651 0-2.4776 1.6233-3.2038 1.6233-3.2038 0.81852-0.37591 2.223-0.53399 3.6832-0.54593h0.035867c1.4601 0.011937 2.8656 0.17002 3.6841 0.54593 0 0 1.6233 0.72623 1.6233 3.2038 0 0 0.02036 1.828-0.22639 3.0971" fill="var(--color-main-background)" stroke-width=".049227"/><path d="m11.494 6.377v3h-1.1885v-2.9118c0-0.6138-0.25826-0.92535-0.77484-0.92535-0.57116 0-0.85742 0.36957-0.85742 1.1004v1.5938h-1.1815v-1.5938c0-0.73078-0.28632-1.1004-0.85748-1.1004-0.51658 0-0.77484 0.31155-0.77484 0.92535v2.9118h-1.1885v-3c0-0.61313 0.15611-1.1004 0.46969-1.4608 0.32336-0.36047 0.74684-0.54525 1.2725-0.54525 0.6082 0 1.0688 0.23377 1.3733 0.70137l0.29604 0.49627 0.2961-0.49627c0.30447-0.4676 0.76505-0.70137 1.3733-0.70137 0.52563 0 0.9491 0.18479 1.2725 0.54525 0.31352 0.36047 0.46963 0.84769 0.46963 1.4608" fill="var(--color-text-maxcontrast)" stroke-width=".049227"/></svg>',
				'<svg aria-hidden="true" width="50" height="50" version="1.1" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><circle cx="8" cy="8" r="8" fill="var(--color-text-maxcontrast)"/><path d="m4.5 3.5v1.7c3.563 0 6.3 2.735 6.3 6.3h1.7c0-4.4-3.58-8-8-8zm0 2.5v1.7c2.326 0 3.774 1.468 3.8 3.8h1.7c0-3-2.492-5.5-5.5-5.5zm1.25 3c-0.69 0-1.25 0.56-1.25 1.25s0.56 1.25 1.25 1.25 1.25-0.56 1.25-1.25-0.56-1.25-1.25-1.25z" fill="var(--color-main-background)"/></svg>',
				'<svg aria-hidden="true" width="50" height="50" version="1.1" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><circle cx="8" cy="8" r="8" fill="var(--color-text-maxcontrast)"/><path d="m3.556 4.875c-0.306 0-0.556 0.248-0.556 0.555v5.14c0 0.31 0.25 0.555 0.556 0.555h8.89c0.304 0 0.554-0.245 0.554-0.555v-5.14c0-0.307-0.25-0.555-0.556-0.555zm0.47 0.643 3.8 3.8h0.33l3.82-3.8 0.38 0.38-2.274 2.31 1.82 1.753-0.38 0.38-1.753-1.753-1.267 1.285h-0.8l-1.368-1.283-1.754 1.77-0.38-0.4 1.734-1.76-2.292-2.3z" fill="var(--color-main-background)"/></svg>',
				'<a target="_blank" aria-label="{facebooktext}" rel="noreferrer noopener" href="https://www.facebook.com/Nextclouders/">',
				'<a target="_blank" aria-label="{xtext}" rel="noreferrer noopener" href="https://x.com/nextclouders">',
				'<a target="_blank" aria-label="{mastodontext}" rel="noreferrer noopener" href="https://mastodon.xyz/@nextcloud">',
				'<a target="_blank" aria-label="{rsstext}" rel="noreferrer noopener" href="https://nextcloud.com/blog/">',
				'<a target="_blank" aria-label="{mailtext}" rel="noreferrer noopener" href="https://newsletter.nextcloud.com/?p=subscribe&amp;id=1">',
				'</a>',
				$l->t('Like our Facebook page'),
				$l->t('Follow us on X'),
				$l->t('Follow us on Mastodon'),
				$l->t('Check out our blog'),
				$l->t('Subscribe to our newsletter'),

			],
			'{facebookopen}{facebookimage}{linkclose}
			{xopen}{ximage}{linkclose}
			{mastodonopen}{mastodonimage}{linkclose}
			{rssopen}{rssimage}{linkclose}
			{newsletteropen}{mailimage}{linkclose}'
		)); ?>
	</p>
</div>
