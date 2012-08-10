		<section id="main">
		<div id="oauth">
			<h2><img src="<?php echo image_path('', 'remoteStorage-big.png'); ?>" alt="remoteStorage" /></h2>
			<p><strong><?php echo $_['host'] ?></strong>
			requests read &amp; write access to your 
			<?php
				$categories = $_['categories'];
				if(!count($categories)) {
					echo $categories[0];
				} else {
					echo '<em>'.$categories[0].'</em>';
					if(count($categories)==2) {
						echo ' and <em>'.$categories[1].'</em>';
					} else if(count($categories)>2) {
						for($i=1; $i<count($categories)-1; $i++) {
							echo ', <em>'.$categories[$i].'</em>';
						}
						echo ', and <em>'.$categories[$i].'</em>';
					}
				}
			?>.
			</p>
			<form accept-charset="UTF-8" method="post">
				<input id="allow-auth" name="allow" type="submit" value="Allow" />
				<input id="deny-auth" name="deny" type="submit" value="Deny" />
			</form>
		</div>
		</section>
