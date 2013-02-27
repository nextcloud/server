<form id="encryption">
	<fieldset class="personalblock">
		
		<p>
			<strong><?php p($l->t( 'Encryption' )); ?></strong>
			
			<?php p($l->t( "Exclude the following file types from encryption:" )); ?>
			<br />
			
			<select 
			id='encryption_blacklist' 
			title="<?php p($l->t( 'None' ))?>" 
			multiple="multiple">
			<?php foreach($_["blacklist"] as $type): ?>
				<option selected="selected" value="<?php p($type); ?>"> <?php p($type); ?> </option>
			<?php endforeach;?>
			</select>
		</p>
	</fieldset>
</form>
