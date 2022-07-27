<?php /** @var \OCP\IL10N $l */ ?>
<div class="files-controls">
	<div></div>
</div>

<div class="emptyfilelist emptycontent hidden">
	<div class="icon-delete"></div>
	<h2><?php p($l->t('No deleted files')); ?></h2>
	<p><?php p($l->t('You will be able to recover deleted files from here')); ?></p>
</div>

<div class="nofilterresults emptycontent hidden">
	<div class="icon-search"></div>
	<h2><?php p($l->t('No entries found in this folder')); ?></h2>
	<p></p>
</div>

<table class="files-filestable list-container <?php p($_['showgridview'] ? 'view-grid' : '') ?>">
	<thead>
		<tr>
			<th class="hidden column-selection">
				<input type="checkbox" id="select_all_trash" class="select-all checkbox"/>
				<label for="select_all_trash">
					<span class="hidden-visually"><?php p($l->t('Select all'))?></span>
				</label>
			</th>
			<th class="hidden column-name">
				<div class="column-name-container">
					<a class="name sort columntitle" data-sort="name"><span><?php p($l->t('Name')); ?></span><span class="sort-indicator"></span></a>
					<span class='selectedActions'>
						<a href="#" class="actions-selected">
							<span class="icon icon-more"></span>
							<span><?php p($l->t('Actions'))?></span>
						</a>
					</span>
				</div>
			</th>
			<th class="hidden column-mtime">
				<a class="columntitle" data-sort="mtime"><span><?php p($l->t('Deleted')); ?></span><span class="sort-indicator"></span></a>
			</th>
		</tr>
	</thead>
	<tbody class="files-fileList">
	</tbody>
	<tfoot>
	</tfoot>
</table>
