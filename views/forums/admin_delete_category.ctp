<p>
	This will permentaly delete the <?php $thisCategory['ForumCategory']['title'];?> category.
</p>
<?php if ($totalChildren > 0):?>
<p>
The <?php $thisCategory['ForumCategory']['title'];?> category has <?php echo $totalChildren?> forums. Where do you wish to move these forums (and any threads that they may have)?
<?php 
	echo $form->select('deleteForums', $categoryList, null, array(), 'Nowhere, just delete them');
?>
</p>
<?php endif; ?>