<form id="encryption">
	<fieldset class="personalblock">
		<legend>
			<?php echo $l->t( 'Encryption' ); ?>
		</legend>
		<p>
			<?php echo $l->t( 'File encryption is enabled.' ); ?>
		</p>
		<?php if ( ! empty( $_["blacklist"] ) ): ?>
		<p>
			<?php $l->t( 'The following file types will not be encrypted:' ); ?>
		</p>
		<ul>
			<?php foreach( $_["blacklist"] as $type ): ?>
			<li>
				<?php echo $type; ?>
			</li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>
	</fieldset>
</form>
