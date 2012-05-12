<?php foreach( $_['tasks'] as $task ): ?>
	<li data-id="<?php echo $task['id']; ?>"><a href="index.php?id=<?php echo $task['id']; ?>"><?php echo $task['name']; ?></a> </li>
<?php endforeach; ?>
