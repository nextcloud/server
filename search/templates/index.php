<ul id='searchresults'>
	<?php foreach($_['resultTypes'] as $resultType):?>
		<li class='resultHeader'>
			<p><?php echo $resultType[0]->type?></p>
		</li>
		<?php foreach($resultType as $result):?>
			<li class='result'>
				<p>
					<a href='<?php echo $result->link?>' title='<?php echo $result->name?>'><?php echo $result->name?></a>
				</p>
				<p>
					<?php echo $result->text?>
				</p>
			</li>
		<?php endforeach;?>
	<?php endforeach;?>
</ul>
