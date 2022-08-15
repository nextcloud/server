<?php /** @var \OCP\IL10N $l */ ?>

<div class="emptyfilelist emptycontent hidden"></div>

<div class="nofilterresults emptycontent hidden">
	<div class="icon-search"></div>
	<h2><?php p($l->t('No entries found in this folder')); ?></h2>
</div>

<table class="files-filestable list-container <?php p($_['showgridview'] ? 'view-grid' : '') ?>">
	<thead>
		<tr>
			<th class="hidden column-name">
				<div class="column-name-container">
					<a class="name sort columntitle" data-sort="name"><span><?php p($l->t('Name')); ?></span><span class="sort-indicator"></span></a>
				</div>
			</th>
			<th class="hidden column-mtime">
				<a class="columntitle" data-sort="mtime"><span><?php p($l->t('Share time')); ?></span><span class="sort-indicator"></span></a>
			</th>
			<th class="hidden column-expiration">
				<a class="columntitle"><span><?php p($l->t('Expiration date')); ?></span></a>
			</th>
		</tr>
	</thead>
	<tbody class="files-fileList">
	</tbody>
	<tfoot>
	</tfoot>
</table>
