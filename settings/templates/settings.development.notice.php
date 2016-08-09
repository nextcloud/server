<?php
/** @var \OCP\IL10N $l */
if (OC_Util::getEditionString() === ''): ?>
	<p>
		<?php print_unescaped(str_replace(
			[
				'{communityopen}',
				'{githubopen}',
				'{licenseopen}',
				'{linkclose}',
			],
			[
				'<a href="https://nextcloud.com/contribute" target="_blank" rel="noreferrer">',
				'<a href="https://github.com/nextcloud" target="_blank" rel="noreferrer">',
				'<a href="https://www.gnu.org/licenses/agpl-3.0.html" target="_blank" rel="noreferrer">',
				'</a>',
			],
			$l->t('Developed by the {communityopen}Nextcloud community{linkclose}, the {githubopen}source code{linkclose} is licensed under the {licenseopen}AGPL{linkclose}.')
		)); ?>
	</p>
<?php endif; ?>
