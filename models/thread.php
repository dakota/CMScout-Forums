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

class Thread extends ForumsAppModel
{
 var $name = 'Thread';
 var $belongsTo = array('Forum' => array('className' => 'Forums.Forum', 'counterCache' => true));
 var $hasMany = array('Post' => array (
 							'className' => 'Forums.Post',
 							'dependent' => true
 						),
 						'UnreadPost' => array (
 							'className' => 'Forums.UnreadPost',
 							'dependent' => true
 						),
 						'Subscriber' => array (
 							'className' => 'Forums.Subscriber',
 							'dependent' => true
 						),
 						'LastPost' => array(
 							'className' => 'Forums.Post',
 							'order' => 'LastPost.created DESC',
 							'limit' => 1));
 var $actsAs = array('Sluggable', 'WhoDidIt');

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
	
		$threads = $this->find('all', array('conditions' => array('Thread.thread_type' => $type, 'Thread.forum_id' => $forumId), 
											'contain' => array('CreatedBy',
														'LastPost',
	 													'UnreadPost' => array('conditions' => array('UnreadPost.user_id' => $userId)))));
	
	
		$returnData = $this->__configureThreads($threads);
		usort($returnData, '__compareCreated');
	
		return $returnData;
	}
 
	/*function paginate($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = array())
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

		$threads = $this->find('all', compact('conditions', 'fields', 'limit', 'page', 'group', 'order') + $extra);

		$returnData = $this->__configureThreads($threads);

		if (isset($useOrder['field']) && $useOrder['field'] == 'lastPost')
			usort($returnData, '__compareCreated');
		elseif (isset($useOrder['field']) && $useOrder['field'] == 'number_posts')
			usort($returnData, '__compareNumber');

		if (isset($useOrder['direction']) && $useOrder['direction'] == 'desc')
			$returnData = array_reverse($returnData);

		return $returnData;
	}*/

	function fetchBreadcrumbs($slug)
	{
		$returnData = array();

		$thread = $this->findBySlug($slug);

		
		$returnData = array();

		$forum = $this->Forum->findById($thread['Thread']['forum_id'], array('Category'));

		$returnData[0]['title'] = $forum['Category']['title'];
		$returnData[0]['slug'] = $forum['Category']['slug'];

		$parentForums = $this->Forum->getpath($forum['Forum']['id']);

		$i = 1;
		foreach($parentForums as $parentForum)
		{
			if ($parentForum['Forum']['category'] != 1)
			{
				$returnData[$i]['title'] = $parentForum['Forum']['title'];
				$returnData[$i++]['slug'] = $parentForum['Forum']['slug'];
			}
		}

		ksort($returnData);

		return $returnData;
	}
}
?>