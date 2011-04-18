<div id="login">
	<img src="<?php echo image_path("", "weather-clear.png"); ?>" alt="ownCloud" />
	<ul>
		<li class='error'>
			Error 404, Cloud not found<br/>
			<p class='hint'><?php if(isset($_['file'])) echo $_['file']?></p>
		</li>
	</ul>
</div>