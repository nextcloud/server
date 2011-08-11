<fieldset>
	<legend>Please choose your default view:</legend>
	<input type="radio" id="oneday" name="radio"/><label for="oneday">One Day</label>
	<input type="radio" id="oneweek" name="radio"/><label for="oneweek">One Week</label>
	<input type="radio" id="fourweek" name="radio"/><label for="fourweek">Four Weeks</label>
	<input type="radio" id="onemonth" name="radio"/><label for="onemonth">One Month</label>
	<input type="radio" id="list" name="radio"/><label for="list">List</label>
</fieldset>

<fieldset>
	<legend>Calender Files</legend>
	<ul>
	<?php
	showicsfiles();
	?>
	</ul>
</fieldset>