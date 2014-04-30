<?php /** @var $l OC_L10N */ ?>
<div id="controls">
	<div id="file_action_panel"></div>
</div>
<div id='notification'></div>

<div id="emptycontent" class="hidden"><?php p($l->t('Nothing in here.'))?></div>

<input type="hidden" name="dir" value="" id="dir">

<table id="filestable">
	<thead>
		<tr>
			<th id='headerName' class="hidden column-name">
				<div id="headerName-container">
					<input type="checkbox" id="select_all_trash" class="select-all"/>
					<label for="select_all_trash"></label>
					<a class="name sort columntitle" data-sort="name"><span><?php p($l->t( 'Name' )); ?></span><span class="sort-indicator"></span></a>
					<span id="selectedActionsList" class='selectedActions'>
						<a href="" class="undelete">
							<img class="svg" alt="<?php p($l->t( 'Restore' )); ?>"
								 src="<?php print_unescaped(OCP\image_path("core", "actions/history.svg")); ?>" />
							<?php p($l->t('Restore'))?>
						</a>
					</span>
				</div>
			</th>
			<th id="headerSharedWith" class="hidden column-mtime">
				<a id="sharedwith" class="columntitle" data-sort="shareWith"><span><?php p($l->t( 'Shared with' )); ?></span><span class="sort-indicator"></span></a>
			</th>
			<th id="headerSharedWith" class="hidden column-mtime">
				<a id="shareType" class="columntitle" data-sort="shareType"><span><?php p($l->t( 'Type' )); ?></span><span class="sort-indicator"></span></a>
			</th>
			<th id="headerDate" class="hidden column-mtime">
				<a id="modified" class="columntitle" data-sort="mtime"><span><?php p($l->t( 'Shared since' )); ?></span><span class="sort-indicator"></span></a>
			</th>
		</tr>
	</thead>
	<tbody id="fileList">
	</tbody>
	<tfoot>
	</tfoot>
</table>
