<?php /** @var \OCP\IL10N $l */ ?>
<div class="files-controls">
	<div></div>
</div>

<div class="emptyfilelist emptycontent hidden">
	<div class="icon-external"></div>
	<h2><?php p($l->t('No external storage configured or you don\'t have the permission to configure them')); ?></h2>
</div>

<table class="files-filestable list-container <?php p($_['showgridview'] ? 'view-grid' : '') ?>">
	<thead>
		<tr>
			<th class="hidden column-name">
				<div class="column-name-container">
					<a class="name sort columntitle" data-sort="name"><span><?php p($l->t('Name')); ?></span><span class="sort-indicator"></span></a>
				</div>
			</th>
			<th id="headerBackend" class="hidden column-backend">
				<a class="backend sort columntitle" data-sort="backend"><span><?php p($l->t('Storage type')); ?></span><span class="sort-indicator"></span></a>
			</th>
			<th id="headerScope" class="hidden column-scope column-last">
				<a class="scope sort columntitle" data-sort="scope"><span><?php p($l->t('Scope')); ?></span><span class="sort-indicator"></span></a>
			</th>
		</tr>
	</thead>
	<tbody class="files-fileList">
	</tbody>
	<tfoot>
	</tfoot>
</table>
