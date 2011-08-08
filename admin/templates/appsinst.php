<ul id="apps">
<?php foreach($_["apps"] as $app): ?>
	<li x-uid="<?php echo($app['id']); ?>"><strong><?php echo($app['name']); ?></strong> <?php echo($app['version']); ?> <em>by <?php echo($app['author']); ?></em>
	<input x-use="appenablebutton" type="submit" value="<?php echo $l->t( $app['enabled'] ? 'enabled' : 'disabled' ); ?>" class="appbutton <?php echo( $app['enabled'] ? 'enabled' : 'disabled' ); ?>" />
	</li>
<?php endforeach; ?>
</ul>
