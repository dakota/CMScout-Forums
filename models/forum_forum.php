<?php
class ForumForum extends ForumsAppModel
{
	var $name = 'ForumForum';
	var $actsAs = array('Acl'=>'controlled', 'Sluggable', 'Tree' => array('scope' => 'ForumCategory'));
	var $hasMany = array('ForumThread' => array (
 							'className' => 'Forums.ForumThread',
 							'dependent' => true
						)/*,
 						'ChildForum' => array('className' => 'Forums.ForumForum',
								'foreignKey' => 'parent_id',
								'dependent' => true,
								'conditions' => '',
								'fields' => '',
								'order' => 'ChildForum.`order` ASC',
								'limit' => '',
								'offset' => '',
								'exclusive' => '',
								'finderQuery' => '',
								'counterQuery' => ''
								)*/);

	var $belongsTo = array('ForumCategory'/*,
		'ParentForum' => array('className' => 'Forums.ForumForum',
			'foreignKey' => 'parent_id',
			'conditions' => '',
			'fields' => '',
			'order' => 'ParentForum.`order` ASC'
	)*/);
	var $order = "ForumForum.lft ASC";
	var $recursive = 0;

	function fetchSubForums($slug, $userId)
	{
		$returnData = array();

		$this->recursive = -1;
		$forum = $this->findBySlug($slug);

		$childForums = $this->find('all', array('conditions' => array('ForumForum.parent_id' => $forum['ForumForum']['id'])));

		foreach($childForums as $childForum)
		{
			$returnForum = array();

			$numberThreads = $this->ForumThread->find('count', array('contain' => false, 'conditions' => array('ForumThread.forum_forum_id' => $childForum['ForumForum']['id'])));
			$threadList = $this->ForumThread->find('list', array('contain' => false, 'conditions' => array('ForumThread.forum_forum_id' => $childForum['ForumForum']['id'])));
			$numberPosts = $this->ForumThread->ForumPost->find('count', array('contain' => false, 'conditions' => array('ForumPost.forum_thread_id' => array_keys($threadList))));
			$lastPost = $this->ForumThread->ForumPost->find('first', array('contain' => array('User', 'ForumThread'), 'conditions' => array('ForumPost.forum_thread_id' => array_keys($threadList)), 'order' => array('ForumPost.created DESC')));
			$hasUnread = $this->ForumThread->ForumUnreadPost->find('count', array('conditions' => array('ForumUnreadPost.user_id' => $userId, 'ForumUnreadPost.forum_thread_id' => array_keys($threadList))));

			$returnForum['title'] = $childForum['ForumForum']['title'];
			$returnForum['slug'] = $childForum['ForumForum']['slug'];
			$returnForum['description'] = $childForum['ForumForum']['description'];
			$returnForum['number_threads'] = $numberThreads;
			$returnForum['number_posts'] = $numberPosts;
			$returnForum['unreadPost'] = $hasUnread;
			$returnForum['lastPost'] = $lastPost;
			$returnForum['ChildForum'] = $this->find('all', array('contain' => false, 'conditions' => array('ForumForum.parent_id' => $childForum['ForumForum']['id'])));

			$returnData[] = $returnForum;
		}

		return $returnData;
	}

	function fetchBreadcrumbs($slug)
	{
		$returnData = array();

		$this->recursive = 0;
		$forum = $this->findBySlug($slug);

		$returnData[0]['title'] = $forum['ForumCategory']['title'];
		$returnData[0]['slug'] = $forum['ForumCategory']['slug'];
		
		$parentForums = $this->getpath($forum['ForumForum']['id']);
		unset($parentForums[count($parentForums)-1]);
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

	function parentNode()
	{
		return "CMScout Forums";
	}
}
?>
