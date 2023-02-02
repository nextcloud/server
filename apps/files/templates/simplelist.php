<div class="emptyfilelist emptycontent hidden">
	<div class="icon-starred"></div>
	<h2><?php p($l->t('No favorites yet')); ?></h2>
	<p><?php p($l->t('Files and folders you mark as favorite will show up here')); ?></p>
</div>

<div class="nofilterresults emptycontent hidden">
	<div class="icon-search"></div>
	<h2><?php p($l->t('No entries found in this folder')); ?></h2>
	<p></p>
</div>
<table class="files-filestable list-container <?php p($_['showgridview'] ? 'view-grid' : '') ?>">
	<thead>
		<tr>
			<th class="hidden column-name">
				<div class="column-name-container">
					<a class="name sort columntitle" onclick="event.preventDefault()" href="#" data-sort="name"><span><?php p($l->t('Name')); ?></span><span class="sort-indicator"></span></a>
				</div>
			</th>
			<th class="hidden column-size">
				<a class="size sort columntitle" onclick="event.preventDefault()" href="#" data-sort="size"><span><?php p($l->t('Size')); ?></span><span class="sort-indicator"></span></a>
			</th>
			<th class="hidden column-mtime">
				<a class="columntitle" onclick="event.preventDefault()" href="#" data-sort="mtime"><span><?php p($l->t('Modified')); ?></span><span class="sort-indicator"></span></a>
				<span class="selectedActions">
				    <a onclick="event.preventDefault()" href="#" class="delete-selected">
					<img class="svg" alt=""
					     src="<?php print_unescaped(OCP\Template::image_path("core", "actions/delete.svg")); ?>" />
					<?php p($l->t('Delete'))?>
				    </a>
				</span>
			</th>
		</tr>
	</thead>
	<tbody class="files-fileList">
	</tbody>
	<tfoot>
	</tfoot>
</table>
