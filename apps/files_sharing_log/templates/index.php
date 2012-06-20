<table id="files_sharing_log">
	<thead>
		<tr>
			<th><?php echo $l->t('File') ?></th>
			<th><?php echo $l->t('Who') ?></th>
			<th><?php echo $l->t('When') ?></th>
			<th><?php echo $l->t('What') ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach($_['log'] as $log): ?>
		<tr>
			<td>
			<?php echo $log['source'] ?>
			</td>
			<td>
			<?php echo $log['uid_who'] ?>
			</td>
			<td>
			<?php echo date('Y-m-d H:i:s', $log['when']) ?>
			</td>
			<td>
			<?php switch ($log['mode']):
				case 'get':
					echo $l->t('Read');
					break;
				case 'put':
					echo $l->t('Write');
					break;
				default:
					if (strpos('r', $log['mode']) !== false):
						echo $l->t('Read');
					else:
						echo $l->t('Write');
					endif;
			      endswitch;
			?>
			</td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>
