<?php
function __compareCreated($a, $b)
{
	$a = strtotime($a['lastPost']['created']);
	$b = strtotime($b['lastPost']['created']);

	if ($a == $b)
		return 0;

	return ($a > $b) ? 1 : -1 ;
}

function __compareNumber($a, $b)
{
	$a = $a['number_posts'];
	$b = $b['number_posts'];

	if ($a == $b)
		return 0;

	return ($a > $b) ? 1 : -1 ;
}

class ForumThread extends ForumsAppModel
{
 var $name = 'ForumThread';
 var $belongsTo = array('ForumForum' => array('className' => 'Forums.ForumForum', 'counterCache' => true), 'User' => array('fields' => array("id", "username")));
 var $hasMany = array('ForumPost' => array (
 							'className' => 'Forums.ForumPost',
 							'dependent' => true
 						),
 						'ForumUnreadPost' => array (
 							'className' => 'Forums.ForumUnreadPost',
 							'dependent' => true
 						),
 						'ForumSubscriber' => array (
 							'className' => 'Forums.ForumSubscriber',
 							'dependent' => true
 						));
 var $actsAs = array('Sluggable');

	 function __configureThreads($threads)
	 {
	 	$returnData = array();
	 	foreach($threads as $thread)
		{
			$returnThread = array();
	
			$returnThread['title'] = $thread['ForumThread']['title'];
			$returnThread['slug'] = $thread['ForumThread']['slug'];
			$returnThread['description'] = $thread['ForumThread']['description'];
			$returnThread['views'] = $thread['ForumThread']['views'];
			$returnThread['number_posts'] = $thread['ForumThread']['forum_post_count'];
			$returnThread['unreadPost'] = isset($thread['ForumUnreadPost'][0]['forum_thread_id']) ? 1 : 0;
			$returnThread['type'] = $thread['ForumThread']['thread_type'];
			$returnThread['locked'] = $thread['ForumThread']['locked'];
			$returnThread['userPost'] = $thread['User'];
			$returnThread['lastPost'] = $thread['ForumPost'][0];
	
			$returnData[] = $returnThread;
		}
		
		return $returnData;
	}
 
	function findThreads($forumId, $userId, $type = 'ANNOUNCEMENT')
	{
		$returnData = array();
	
		$this->recursive = -1;
	
		$threads = $this->find('all', array('conditions' => array('ForumThread.thread_type' => $type, 'ForumThread.forum_forum_id' => $forumId), 
											'contain' => array('User',
														'ForumPost' => array('User', 'order' => 'ForumPost.created DESC', 'limit' => 1),
	 													'ForumUnreadPost' => array('conditions' => array('ForumUnreadPost.user_id' => $userId)))));
	
	
		$returnData = $this->__configureThreads($threads);
		usort($returnData, '__compareCreated');
	
		return $returnData;
	}
 
	function paginate($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = array())
	{
		if (is_array($order) && in_array(key($order), array('number_posts', 'lastPost')))
		{
			$useOrder['field'] = key($order);
			$useOrder['direction'] = $order[$useOrder['field']];
			$order = array();
		}
		elseif (!is_array($order))
		{
			$useOrder['field'] = 'lastPost';
			$useOrder['direction'] = 'desc';
			$order = array();
		}
		else
		{
			$useOrder = '';
		}

		$this->recursive = -1;

		$threads = $this->find('all', compact('conditions', 'fields', 'limit', 'page', 'group', 'order') + $extra);

		$returnData = $this->__configureThreads($threads);

		if (isset($useOrder['field']) && $useOrder['field'] == 'lastPost')
			usort($returnData, '__compareCreated');
		elseif (isset($useOrder['field']) && $useOrder['field'] == 'number_posts')
			usort($returnData, '__compareNumber');

		if (isset($useOrder['direction']) && $useOrder['direction'] == 'desc')
			$returnData = array_reverse($returnData);

		return $returnData;
	}

	function fetchBreadcrumbs($slug)
	{
		$returnData = array();

		$this->recursive = -1;
		$thread = $this->findBySlug($slug);

		
		$returnData = array();

		$this->recursive = 0;
		$forum = $this->ForumForum->findById($thread['ForumThread']['forum_forum_id']);

		$returnData[0]['title'] = $forum['ForumCategory']['title'];
		$returnData[0]['slug'] = $forum['ForumCategory']['slug'];

		$parentForums = $this->ForumForum->getpath($forum['ForumForum']['id']);
		$i = 1;
		foreach($parentForums as $parentForum)
		{
			if ($parentForum['ForumForum']['category'] != 1)
			{
				$returnData[$i]['title'] = $parentForum['ForumForum']['title'];
				$returnData[$i++]['slug'] = $parentForum['ForumForum']['slug'];
			}
		}

		ksort($returnData);

		return $returnData;
	}
}
?>