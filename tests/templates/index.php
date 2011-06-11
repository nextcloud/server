<?php foreach($_['tests'] as $name=>$results):?>
	<h2><?php echo $name;?></h2>
	<ul>
		<?php foreach($results as $test=>$result):?>
			<li>
				<b><?php echo $test;?></b>
				<?php echo $result;?>
			</il>
		<?php endforeach ?>
	</ul>
<?php endforeach ?>