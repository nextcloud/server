<noscript>
	<div id="nojavascript">
		<div>
			<?php print_unescaped(str_replace(
					['{linkstart}', '{linkend}'],
					['<a href="http://enable-javascript.com/" target="_blank" rel="noreferrer">', '</a>'],
					$l->t('This application requires JavaScript for correct operation. Please {linkstart}enable JavaScript{linkend} and reload the page.')
				)); ?>
		</div>
	</div>
</noscript>
