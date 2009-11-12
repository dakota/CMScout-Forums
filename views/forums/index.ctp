<?php
	$html->css('/forums/css/forums', null, array(), false);

	if (isset($category))
	{
		$html->addCrumb('Forums', array('controller' => 'forums', 'plugin' => 'forums', 'action' => 'index'));
		$html->addCrumb($category['Category']['title'], array('controller' => 'forums', 'plugin' => 'forums', 'action' => 'index', $category['Category']['slug']));

		echo $html->getCrumbs(' > ');
	}
?>
<table class="forumTable">
	<tr>
		<th width="5%"></th>
		<th>Forum</th>
		<th width="20%">Last post</th>
		<th width="10%">Threads</th>
		<th width="10%">Posts</th>
	</tr>
<?php foreach ($categories as $category) :?>
	<tr>
		<th colspan="5" class="categoryTitle"><?php echo $category['Category']['title']; ?></th>
	</tr>
	<?php $i = 0; foreach ($category['Forum'] as $forum) :?>
		<tr <?php echo $i++ % 2 ? 'class="altrow"':'';?>>
			<td>
				<?php echo $forum['unreadPost'] ? 'Unread' : 'Read';?>
			</td>
			<td>
				<?php echo $html->link($forum['title'], array('controller' => 'forums', 'plugin' => 'forums', 'action' => 'forum', $forum['slug'])); ?>
				<br />
				<span class="description"><?php echo $forum['description']; ?></span>
				<?php if (count($forum['ChildForum'])) :?>
				<br />
				<span class="subforums">Subforums:
					<?php foreach($forum['ChildForum'] as $key => $ChildForum) : ?>
						<?php echo $html->link($ChildForum['Forum']['title'],array('controller' => 'forums', 'plugin' => 'forums', 'action' => 'forum', $ChildForum['Forum']['slug'])); if($key < (count($forum['ChildForum'])-1)) echo ", ";?>
					<?php endforeach; ?>
				</span>
				<?php endif;?>
			</td>
			<td>
				<?php if (isset($forum['LastPost'][0]['Thread'])) : ?>
					<div class="lastPost"><?php echo $html->link($forum['LastPost'][0]['Thread']['title'], array('action' => 'thread', $forum['LastPost'][0]['Thread']['slug']));?><br>
					by <?php echo $forum['LastPost'][0]['CreatedBy']['username']; ?></div>
					<div class="lastPostDate"><?php echo $time->niceShort($forum['LastPost'][0]['created']);?>&nbsp;
												<?php echo $html->link('Last', array('action' => 'thread', $forum['LastPost'][0]['Thread']['slug'], $forum['LastPost'][0]['id'], '#' => $forum['LastPost'][0]['id']));?>
					</div>
				<?php else:?>
					<div class="lastPost">No posts</div>
				<?php endif;?>
			</td>
			<td class="number"><?php echo $forum['thread_count']; ?></td>
			<td class="number"><?php echo $forum['post_count']; ?></td>
		</tr>
	<?php endforeach;?>
<?php endforeach; ?>
</table>