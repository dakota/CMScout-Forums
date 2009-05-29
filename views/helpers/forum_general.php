<?php
class ForumGeneralHelper extends AppHelper
{
	var $helpers = array('Html', 'Time');
	
	function drawThreads($threadArray, $permissions, $currentUserId)
	{
		$output = '';
		$i = 0; 
		foreach ($threadArray as $thread)
		{
			$output .= "<tr ".($i++ % 2 ? 'class="altrow"':'').">";
			$output .= "	<td>".($thread['unreadPost'] ? 'Unread' : 'Read')."</td>";
			$output .= "	<td>";
			$output .= $this->Html->link($thread['title'], array('controller' => 'forums', 'plugin' => 'forums', 'action' => 'thread', $thread['slug']));
		
			if($thread['description'] != '')
				$output .= '<br><span class="description">'.$thread['description'].'</span>';
				
			$output .= '<span class="threadActions">';
			if(($permissions['sticky'] && $thread['userPost']['id'] == $currentUserId) || $permissions['moderate'])
			{
				$output .= $this->Html->link('&nbsp;', array('controller' => 'forums', 'plugin' => 'forums', 'action' => 'threadType', $thread['slug'], 'sticky'), array('class' => 'stickyAction icon ' . (($thread['type'] == 'STICKY') ? 'sprite-star' : 'sprite-star_gray')), false, false);
			}
			
			if(($permissions['announce'] && $thread['userPost']['id'] == $currentUserId) || $permissions['moderate'])
			{
				$output .= $this->Html->link('&nbsp;', array('controller' => 'forums', 'plugin' => 'forums', 'action' => 'threadType', $thread['slug'], 'announce'), array('class' => 'announceAction icon ' . (($thread['type'] == 'ANNOUNCEMENT') ? 'sprite-important' : 'sprite-important_gray')), false, false);
			}
			
			if($permissions['moderate'])	
			{
				$output .= $this->Html->link('&nbsp;', array('controller' => 'forums', 'plugin' => 'forums', 'action' => 'lock', $thread['slug']), array('class' => 'lockAction icon ' . (($thread['locked']) ? 'sprite-lock' : 'sprite-unlock')), false, false);
				$output .= $this->Html->link('&nbsp;', array('controller' => 'forums', 'plugin' => 'forums', 'action' => 'deleteThread', $thread['slug']), array('class' => 'deleteAction icon sprite-delete'), false, false);
			}
				
			$output .= '</span>';
			
			$output .= '<br><span class="user">'.$thread['userPost']['username'].'</span>';
			
			$output .= "	</td>";
			$output .= "	<td>".
								'<div class="lastPost">'.$this->Time->niceShort($thread['lastPost']['created']).'</div>'.
								'<div class="lastPostDate">by '.$thread['lastPost']['User']['username'].'&nbsp;'.
								$this->Html->link('Last', array('action' => 'thread', $thread['slug'], $thread['lastPost']['id'], '#' => $thread['lastPost']['id'])).'</div>'.
							"</td>";
			$output .= "	<td>".$thread['number_posts']."</td>";
			$output .= "	<td>".$thread['views']."</td>";
			$output .= "</tr>";
		}
		
		return $output;
	}
}
?>