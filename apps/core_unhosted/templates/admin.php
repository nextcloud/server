<table id='tokenlist'>
	<thead>
		<tr>
			<td class='appUrl'><?php echo $l->t( 'App-Url' ); ?></td>
			<td class='userAddress'><?php echo $l->t( 'User-Address' ); ?></td>
			<td class='token'><?php echo $l->t( 'Token' ); ?></td>
		</tr>
	</thead>
	<tbody>
		<?php foreach($_['tokens'] as $token=>$details):?>
			<tr class='token' id='<?php echo $token;?>'>
				<td class='appUrl'><?php echo $details['appUrl'];?></td>
				<td class='userAddress'><?php echo $details['userAddress'];?></td>
				<td class='token'><?php echo $token;?></td>
				<td><button class='revoke fancybutton' data-token='<?php echo $token;?>'><?php echo $l->t( 'Revoke' ); ?></button></td>
			</tr>
		<?php endforeach;?>
	</tbody>
</table>
