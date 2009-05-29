<p>
	This will permentaly delete the <?php $thisForum['ForumForum']['title'];?> forum.
</p>
<?php if ($numberThreads > 0):?>
<p>
The <?php $thisForum['ForumForum']['title'];?> forum has <?php echo $numberThreads?> threads. Where do you wish to move these threads?
<?php 
	echo $form->select('deleteThreads', $forumList, null, array(), 'Nowhere, just delete them');
?>
</p>
<?php endif; ?>
<?php if ($totalChildren > 0):?>
<p>
The <?php $thisForum['ForumForum']['title'];?> forum has <?php echo $totalChildren?> sub forums. Where do you wish to move these sub forums (and any threads that they may have)?
<?php 
	echo $form->select('deleteForums', array('Categories' => $categoryList, 'Forums' => $forumList), null, array(), 'Nowhere, just delete them');
?>
</p>
<?php endif; ?>