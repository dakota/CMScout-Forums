<?php
	$css->link('/forums/css/forums', null, array(), false);
	$javascript->link('/forums/js/forums_index', false);
	
	$html->addCrumb('Forums', array('controller' => 'forums', 'plugin' => 'forums', 'action' => 'index'));
	foreach ($breadcrumbs as $key => $crumb)
	{
		if ($key == 0)
			$html->addCrumb($crumb['title'], array('controller' => 'forums', 'plugin' => 'forums', 'action' => 'index', $crumb['slug']));
		else
			$html->addCrumb($crumb['title'], array('controller' => 'forums', 'plugin' => 'forums', 'action' => 'forum', $crumb['slug']));
	}

	echo '<div class="breadcumbs">' . $html->getCrumbs(' > ') . '</div>';
	$paginator->options(array('url'=>$this->passedArgs));
?>
<h2 class="finalBreadcrumb"><?php echo $forum['Forum']['title']; ?></h2>
<?php if (count($subForums)):?>
<table class="forumTable">
	<tr>
		<th colspan="5">Subforums</th>
	</tr>
	<tr>
		<th width="5%"></th>
		<th>Forum</th>
		<th width="20%">Last post</th>
		<th width="10%">Threads</th>
		<th width="10%">Posts</th>
	</tr>
	<?php $i = 0; foreach ($subForums as $subForum) :?>
		<tr <?php echo $i++ % 2 ? 'class="altrow"':'';?>>
			<td>
				<?php echo $subForum['unreadPost'] ? 'Unread' : 'Read';?>
			</td>
			<td>
				<?php echo $html->link($subForum['title'], array('controller' => 'forums', 'plugin' => 'forums', 'action' => 'forum', $subForum['slug'])); ?>
				<br />
				<span class="description"><?php echo $subForum['description']; ?></span>
				<?php if (count($subForum['ChildForum'])) :?>
				<br />
				<span class="subforums">Subforums:
					<?php foreach($subForum['ChildForum'] as $key => $ChildForum) : ?>
						<?php echo $html->link($ChildForum['ForumForum']['title'],array('controller' => 'forums', 'plugin' => 'forums', 'action' => 'forum', $ChildForum['ForumForum']['slug'])); if($key < (count($subForum['ChildForum'])-1)) echo ", ";?>
					<?php endforeach; ?>
				</span>
				<?php endif;?>
			</td>
			<td>
				<?php if (isset($subForum['lastPost']['Thread'])) : ?>
					<div class="lastPost"><?php echo $html->link($subForum['lastPost']['ForumThread']['title'], array('action' => 'thread', $subForum['lastPost']['ForumThread']['slug']));?><br>
					by <?php echo $subForum['lastPost']['User']['username']; ?></div>
					<div class="lastPostDate"><?php echo $time->niceShort($subForum['lastPost']['ForumPost']['created']);?>&nbsp;
							<?php echo $html->link('Last', array('action' => 'thread', $subForum['lastPost']['ForumThread']['slug'], $subForum['lastPost']['ForumPost']['id'], '#' => $subForum['lastPost']['ForumPost']['id']));?>
					</div>
				<?php endif;?>
			</td>
			<td class="number"><?php echo $subForum['number_threads']; ?></td>
			<td class="number"><?php echo $subForum['number_posts']; ?></td>
		</tr>
	<?php endforeach;?>
</table>
<?php endif;?>
<div style="margin: 5px 2px;"><?php echo $html->link('New topic', array('action' => 'newTopic', $forum['Forum']['slug']), array('id' => 'newTopic', 'class'=>'button')); ?></div>
<?php if (count($announcementThreads) || count($stickyThreads) || count($threads)):?>
<table class="forumTable">
	<tr>
		<th width="5%"></th>
		<th><?php echo $paginator->sort('Thread', 'title'); ?></th>
		<th width="20%"><?php echo $paginator->sort('Last Post', 'LastPost.created'); ?></th>
		<th width="10%"><?php echo $paginator->sort('Replies', 'post_count'); ?></th>
		<th width="10%"><?php echo $paginator->sort('Views', 'views'); ?></th>
	</tr>
	<?php if (count($announcementThreads) > 0):?>
	<tr>
		<th colspan="5">Announcements</th>
	</tr>
		<?php echo $this->element('threads', array('threads' => $announcementThreads, 'permissions' => $permissions, 'userId' => $userInfo['User']['id'])); ?>	
	<?php endif;?>
	<tr>
		<th colspan="5">Threads</th>
	</tr>	
	<?php 
		if (isset($stickyThreads) && count($stickyThreads) > 0)
			$threads = am($stickyThreads, $threads);
	?>	
		<?php echo $this->element('threads', array('threads' => $threads, 'permissions' => $permissions, 'userId' => $userInfo['User']['id'])); ?>	
</table>
<div class="paginate">
	<ul>
		<?php
			echo '<li class="count">'.$paginator->counter('Page %page% of %pages%').'</li> ';
			echo $paginator->first('<< First',array('separator' => null, 'tag' => 'li'));
			echo $paginator->hasPrev() ? '<li>' . $paginator->prev('<', array('tag' => 'li')) . '</li>' : '';
			echo $paginator->numbers(array('separator' => null, 'tag' => 'li'));
			echo $paginator->hasNext() ? '<li>' . $paginator->next('>', array('tag' => 'li')) . '</li>' : '';
			echo $paginator->last('Last >>',array('separator' => null, 'tag' => 'li'));
		?>
	</ul>
</div>
<?php endif;?>
<div class="permissionBox">
<h3>Forum Permissions</h3>
	You <strong><?php echo $permissions['create'] == 1 ? 'may' : 'may not'; ?></strong> post new threads in this forum<br>
	You <strong><?php echo $permissions['reply'] == 1 ? 'may' : 'may not'; ?></strong> post replies in this forum<br>
	You <strong><?php echo $permissions['update'] == 1 ? 'may' : 'may not'; ?></strong> edit your posts in this forum<br>
	You <strong><?php echo $permissions['delete'] == 1 ? 'may' : 'may not'; ?></strong> delete your posts in this forum
</div>