<noscript>
	<div id="nojavascript">
		<div>
			<?php print_unescaped(str_replace(
					['{linkstart}', '{linkend}'],
					['<a href="https://www.enable-javascript.com/" target="_blank" rel="noreferrer noopener">', '</a>'],
					$l->t('This application requires JavaScript for correct operation. Please {linkstart}enable JavaScript{linkend} and reload the page.')
				)); ?>
		</div>
	</div>
</noscript>
