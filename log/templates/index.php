<?php /*<div class="controls">
	<form id="logs_options" method='post'>
		<p>
			<span><?php echo $l->t( 'Filter:' ); ?></span>

			<input type="checkbox" checked="" name="all" id="all" /> <label for="all">All</label>
			<input type="checkbox" class='action' <?php echo $_['showActions']['login']?> name="login" id="logins" /> <label for="logins"><?php echo $l->t( 'Logins' ); ?></label>
			<input type="checkbox" class='action' <?php echo $_['showActions']['logout']?> name="logout" id="logouts" /> <label for="logouts"><?php echo $l->t( 'Logouts' ); ?></label>
			<input type="checkbox" class='action' <?php echo $_['showActions']['read']?> name="read" id="downloads" /> <label for="downloads"><?php echo $l->t( 'Downloads' ); ?></label>
			<input type="checkbox" class='action' <?php echo $_['showActions']['write']?> name="write" id="uploads" /> <label for="uploads"><?php echo $l->t( 'Uploads' ); ?></label>
			<input type="checkbox" class='action' <?php echo $_['showActions']['create']?> name="create" id="creations" /> <label for="creations"><?php echo $l->t( 'Creations' ); ?></label>
			<input type="checkbox" class='action' <?php echo $_['showActions']['delete']?> name="delete" id="deletions" /> <label for="deletions"><?php echo $l->t( 'Deletions' ); ?></label>
		</p>
		<p>
			<span><?php echo $l->t( 'Show:' ); ?></span>
			<input type="text" maxlength="3" size="3" value="<?php echo $_['size']?>" name='size'/>&nbsp;<?php echo $l->t( 'entries per page.' ); ?>
			<input class="prettybutton" type="submit" name="save" value="Save" />

		</p>
	</form>
</div>

<table cellspacing="0">
	<thead>
		<tr>
			<th><?php echo $l->t( 'What' ); ?></th>
			<th><?php echo $l->t( 'When' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach($_["logs"] as $entry): ?>
			<tr>
				<td class="<?php echo $entry["action"]; ?>"><em><?php echo $entry["action"]; ?> <?php echo $entry["user"]; ?></em> <?php echo $entry["info"]; ?></td>
				<td class="date"><?php echo $l->l('datetime', $entry["date"] ); ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<?php echo $_['pager'];?>

<div class="controls">
	<form id="logs_options" method='post'>
		<p>
			<span><?php echo $l->t( 'Clear log entries before' ); ?> </span>
			<input type="date" id="removeBeforeDate" name="removeBeforeDate"/>
			<input class="prettybutton nofloat" type="submit" name="clear" value="Clear" />
			<input class="prettybutton" type="submit" name="clearall" value="Clear All" />

		</p>
	</form>
</div>
*/ ?>
