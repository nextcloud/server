<!--[if IE 8]><style>input[type="checkbox"]{padding:0;}table td{position:static !important;}</style><![endif]-->
<div id="controls">
	<?php print_unescaped($_['breadcrumb']); ?>
		<div id="file_action_panel"></div>
</div>
<div id='notification'></div>

<?php if (isset($_['files']) && count($_['files'])==0 && $_['dirlisting'] == false):?>
	<div id="emptyfolder"><?php p($l->t('Nothing in here. Your trash bin is empty!'))?></div>
<?php endif; ?>

<table id="filestable">
	<thead>
		<tr>
			<th id='headerName'>
				<input type="checkbox" id="select_all" />
				<span class='name'><?php p($l->t( 'Name' )); ?></span>
				<span class='selectedActions'>
						<a href="" class="undelete">
							<img class="svg" alt="<?php p($l->t( 'Restore' )); ?>"
								 src="<?php print_unescaped(OCP\image_path("core", "actions/undelete.png")); ?>" />
							<?php p($l->t('Restore'))?>
						</a>
				</span>
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
		<?php print_unescaped($_['fileList']); ?>
	</tbody>
</table>
