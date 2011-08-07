<div id='scan'>
	<p id='scancount' style='display:none'><span class='songCount'>0</span> Songs scanned</p>
	<div id="scanprogressbar"></div>
	<input type='button' class='start' value='Recan Collection'></input>
	<input type='button' class='stop' style='display:none' value='Pause'></input>
</div>
<ul id='collection'>
	<li class='artist'>
		<img src="<?php echo image_path('files','loading.gif') ?>" alt='loading'/>Loading Collection...
	</li>
	<li class='template'>
		<span></span>
		<button class='add'>Add</button>
		<button class='play'>Play</button>
	</li>
</ul>

