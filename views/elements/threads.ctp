<?php
if(count($threads) > 0)
{
	$i = 0;
	foreach ($threads as $thread):?>
		<tr <?php echo ($i++ % 2 ? 'class="altrow"':'')?>>
			<td><?php echo (isset($thread['UnreadPost'][0]) ? 'Unread' : 'Read')?> </td>
			<td>
				<?php echo $html->link($thread['Thread']['title'], array('controller' => 'forums', 'plugin' => 'forums', 'action' => 'thread', $thread['Thread']['slug']));?>
			
				<?php if($thread['Thread']['description'] != '') ?>
					<br><span class="description"><?php echo $thread['Thread']['description']?></span>
					
				<span class="threadActions">
				<?php 
				if(($permissions['sticky'] && $thread['CreatedBy']['id'] == $userId) || $permissions['moderate'])
				{
					echo $html->link('&nbsp;', array('controller' => 'forums', 'plugin' => 'forums', 'action' => 'threadType', $thread['Thread']['slug'], 'sticky'), array('class' => 'stickyAction icon ' . (($thread['Thread']['thread_type'] == 'STICKY') ? 'sprite-star' : 'sprite-star_gray')), false, false);
				}
				
				if(($permissions['announce'] && $thread['CreatedBy']['id'] == $userId) || $permissions['moderate'])
				{
					echo $html->link('&nbsp;', array('controller' => 'forums', 'plugin' => 'forums', 'action' => 'threadType', $thread['Thread']['slug'], 'announce'), array('class' => 'announceAction icon ' . (($thread['Thread']['thread_type'] == 'ANNOUNCEMENT') ? 'sprite-important' : 'sprite-important_gray')), false, false);
				}
				
				if($permissions['moderate'])	
				{
					echo $html->link('&nbsp;', array('controller' => 'forums', 'plugin' => 'forums', 'action' => 'moveThread', $thread['Thread']['slug']), array('class' => 'moveAction icon sprite-move'), false, false);
					echo $html->link('&nbsp;', array('controller' => 'forums', 'plugin' => 'forums', 'action' => 'lock', $thread['Thread']['slug']), array('class' => 'lockAction icon ' . (($thread['Thread']['locked']) ? 'sprite-lock' : 'sprite-unlock')), false, false);
					echo $html->link('&nbsp;', array('controller' => 'forums', 'plugin' => 'forums', 'action' => 'deleteThread', $thread['Thread']['slug']), array('class' => 'deleteAction icon sprite-delete'), false, false);
				}
				?>
				</span>
				
				<br><span class="user"><?php echo $thread['CreatedBy']['username']?></span>
			</td>
			<td>
				<div class="lastPost"><?php echo $time->niceShort($thread['LastPost'][0]['created'])?></div>
				<div class="lastPostDate">by <?php echo $thread['LastPost'][0]['CreatedBy']['username']?>&nbsp;
				<?php echo $html->link('Last', array('action' => 'thread', $thread['Thread']['slug'], $thread['LastPost'][0]['id'], '#' => $thread['LastPost'][0]['id']))?></div>
			</td>
			<td><?php echo $thread['Thread']['post_count']?></td>
			<td><?php echo $thread['Thread']['views']?></td>
		</tr>
<?php
	endforeach;
}
?>