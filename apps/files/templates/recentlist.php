<?php /** @var \OCP\IL10N $l */ ?>

<div class="emptyfilelist emptycontent hidden"></div>

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
				<a class="name sort columntitle" href="#" onclick="event.preventDefault()"
				   data-sort="name"><span><?php p($l->t('Name')); ?></span></a>
			</div>
		</th>
		<th class="hidden column-size">
			<a class="size sort columntitle" href="#" onclick="event.preventDefault()"
			   data-sort="size"><span><?php p($l->t('Size')); ?></span></a>
		</th>
		<th class="hidden column-mtime">
			<a class="columntitle" href="#" onclick="event.preventDefault()"
			   data-sort="mtime"><span><?php p($l->t('Modified')); ?></span><span
					class="sort-indicator"></span></a>
			<span class="selectedActions">
				<a href="#" onclick="event.preventDefault()" class="delete-selected">
					<span class="icon icon-delete"></span>
					<span><?php p($l->t('Delete')) ?></span>
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
