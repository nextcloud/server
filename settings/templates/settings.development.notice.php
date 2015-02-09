<?php if (OC_Util::getEditionString() === ''): ?>
	<p>
		<?php print_unescaped(str_replace(
			[
				'{communityopen}',
				'{githubopen}',
				'{licenseopen}',
				'{linkclose}',
			],
			[
				'<a href="https://owncloud.org/contact" target="_blank" rel="noreferrer">',
				'<a href="https://github.com/owncloud" target="_blank" rel="noreferrer">',
				'<a href="https://www.gnu.org/licenses/agpl-3.0.html" target="_blank" rel="noreferrer">',
				'</a>',
			],
			$l->t('Developed by the {communityopen}ownCloud community{linkclose}, the {githubopen}source code{linkclose} is licensed under the {licenseopen}<abbr title="Affero General Public License">AGPL</abbr>{linkclose}.')
		)); ?>
	</p>
<?php endif; ?>
