<div class="controls">
	<form id="logs_options" method='post'>
		<p>
			<span>Filter :</span>

			<input type="checkbox" checked="" name="all" id="all" /> <label for="all">All</label>
			<input type="checkbox" class='action' <?php echo $_['showActions']['login']?> name="login" id="logins" /> <label for="logins">Logins</label>
			<input type="checkbox" class='action' <?php echo $_['showActions']['logout']?> name="logout" id="logouts" /> <label for="logouts">Logouts</label>
			<input type="checkbox" class='action' <?php echo $_['showActions']['read']?> name="read" id="downloads" /> <label for="downloads">Downloads</label>
			<input type="checkbox" class='action' <?php echo $_['showActions']['write']?> name="write" id="uploads" /> <label for="uploads">Uploads</label>
			<input type="checkbox" class='action' <?php echo $_['showActions']['create']?> name="create" id="creations" /> <label for="creations">Creations</label>
			<input type="checkbox" class='action' <?php echo $_['showActions']['delete']?> name="delete" id="deletions" /> <label for="deletions">Deletions</label>
		</p>
		<p>
			<span>Show :</span>
			<input type="text" maxlength="3" size="3" value="<?php echo $_['size']?>" name='size'/>&nbsp;entries per page.
			<input class="prettybutton" type="submit" name="save" value="Save" />

		</p>
	</form>
</div>

<table cellspacing="0">
	<thead>
		<tr>
			<th>What</th>
			<th>When</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach($_["logs"] as $entry): ?>
			<tr>
				<td class="<?php echo $entry["action"]; ?>"><em><?php echo $entry["action"]; ?> <?php echo $entry["user"]; ?></em> <?php echo $entry["info"]; ?></td>
				<td class="date"><?php echo $entry["date"]; ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<div class="controls">
	<form id="logs_options" method='post'>
		<p>
			<span>Clear log entries before </span>
			<input type="date" id="removeBeforeDate" name="removeBeforeDate"/>
			<input class="prettybutton nofloat" type="submit" name="clear" value="Clear" />
			<input class="prettybutton" type="submit" name="clearall" value="Clear All" />

		</p>
	</form>
</div>

<?php echo $_['pager'];?>
