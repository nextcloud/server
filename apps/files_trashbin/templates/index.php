<div id="controls">
	<div id="file_action_panel"></div>
</div>
<div id='notification'></div>

<div id="emptycontent" class="hidden"><?php p($l->t('Nothing in here. Your trash bin is empty!'))?></div>

<input type="hidden" id="permissions" value="0"></input>
<input type="hidden" name="dir" value="<?php p($_['dir']) ?>" id="dir">

<table id="filestable">
	<thead>
		<tr>
			<th id='headerName'>
				<div id="headerName-container">
				<input type="checkbox" id="select_all" />
				<label for="select_all"></label>
				<span class='name'><?php p($l->t( 'Name' )); ?></span>
				<span class='selectedActions'>
						<a href="" class="undelete">
							<img class="svg" alt="<?php p($l->t( 'Restore' )); ?>"
								 src="<?php print_unescaped(OCP\image_path("core", "actions/history.svg")); ?>" />
							<?php p($l->t('Restore'))?>
						</a>
				</span>
				</div>
			</th>
			<th id="headerDate">
				<span id="modified"><?php p($l->t( 'Deleted' )); ?></span>
				<span class="selectedActions">
					<a href="" class="delete">
						<?php p($l->t('Delete'))?>
						<img class="svg" alt="<?php p($l->t('Delete'))?>"
							src="<?php print_unescaped(OCP\image_path("core", "actions/delete.svg")); ?>" />
					</a>
				</span>
			</th>
		</tr>
	</thead>
	<tbody id="fileList">
	</tbody>
</table>
