<tr>
	<th>
		<?php echo $_['label'] ?>
	</th>
	<td>
		<?php
			switch (get_class($_['property']))
			{
				case 'Sabre_VObject_Element_DateTime':
					echo $l->l('datetime', $_['property']->getDateTime());
					break;
				default:
					$value = $_['property']->value;
					if (isset($_['options']))
					{
						$value = $_['options'][$value];
					}
					echo nl2br($value);
			}
		?>
	</td>
</tr>
