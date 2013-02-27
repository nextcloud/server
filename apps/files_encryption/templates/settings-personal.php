<form id="encryption">
	<fieldset class="personalblock">
		<legend>
			<?php p($l->t( 'Encryption' )); ?>
		</legend>
		<p>
			<?php p($l->t( 'File encryption is enabled.' )); ?>
		</p>
		<?php if ( ! empty( $_["blacklist"] ) ): ?>
		<p>
			<?php p($l->t( 'The following file types will not be encrypted:' )); ?>
		</p>
		<ul>
			<?php foreach( $_["blacklist"] as $type ): ?>
			<li>
				<?php p($type); ?>
			</li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>
	</fieldset>
</form>
