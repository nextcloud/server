<?php
/** @var array $_ */
/** @var OC_L10N $l */
script('theming', 'settings-admin');
style('theming', 'settings-admin')
?>
<div id="theming" class="section">
	<h2><?php p($l->t('Theming')); ?></h2>

	<p>
		<input class="theming-name" type="text" placeholder="<?php p($l->t('Name')); ?>"></input>
	</p>
	<p>
		<input class="theming-address" type="text" placeholder="<?php p($l->t('Web address https://…')); ?>"></input>
	</p>
	<p>
		<input class="theming-slogan" type="text" placeholder="<?php p($l->t('Slogan')); ?>"></input>
	</p>
	<p>
		<input class="theming-color" type="text" placeholder="<?php p($l->t('Color #0082c9')); ?>"></input>
	</p>
	<p>
		<input class="theming-logo" type="text" placeholder="<?php p($l->t('Logo')); ?>"></input>
	</p>
</div>
