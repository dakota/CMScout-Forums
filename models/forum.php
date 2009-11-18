<?php
class Forum extends ForumsAppModel
{
	var $name = 'Forum';
	var $actsAs = array('Acl'=>'controlled', 'Sluggable', 'Tree' => array('scope' => 'Category'));
	var $hasMany = array('Thread' => array (
 							'className' => 'Forums.Thread',
 							'dependent' => true
						),
						'LastPost' => array(
							'className' => 'Forums.Post',
							'order' => 'LastPost.created DESC',
							'limit' => 1));

	var $belongsTo = array('Category' => array('className' => 'Forums.Category'));

	var $order = "Forum.lft ASC";

	function fetchSubForums($slug, $userId)
	{
		$returnData = array();

		$forum = $this->findBySlug($slug);

	 	$childForums = $this->find('all', array('conditions' => array('Forum.parent_id' => $forum['Forum']['id']), 
 											'contain' => array(
 													'LastPost' => array('Thread', 'CreatedBy'))));
		
 		foreach($childForums as $forumKey => $forum)
 		{
			$threadList = $this->Forum->Thread->find('list', array('contain' => false, 'conditions' => array('Thread.forum_id' => $forum['Forum']['id'])));
			$hasUnread = $this->Forum->Thread->UnreadPost->find('count', array('conditions' => array('UnreadPost.user_id' => $userId, 'UnreadPost.thread_id' => array_keys($threadList))));

			$childForums[$forumKey]['Forum']['unreadPost'] = $hasUnread;
			$childForums[$forumKey]['Forum']['ChildForum'] = $this->Forum->find('all', array('order' => 'Forum.lft ASC', 'contain' => false, 'conditions' => array('Forum.parent_id' => $forum['Forum']['id'])));
 		}

		return $returnData;
	}

	function fetchBreadcrumbs($slug)
	{
		$returnData = array();

		$forum = $this->findBySlug($slug, array('Category'));

		$returnData[0]['title'] = $forum['Category']['title'];
		$returnData[0]['slug'] = $forum['Category']['slug'];
		
		$parentForums = $this->getpath($forum['Forum']['id']);
		unset($parentForums[count($parentForums)-1]);
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

	function parentNode()
	{
		return "CMScout Forums";
	}

	function addForum($data)
	{
		if (isset($data['Forum']['parent_id']))
		{
			$parent = $this->find('first', array('contain' => false, 'fields' => array('Forum.category_id'), 'conditions' => array('Forum.id' => $data['Forum']['parent_id'])));
			$data['Forum']['category_id'] = $parent['Forum']['category_id'];
		}
		elseif (isset($data['Forum']['category_id']))
		{
			$categoryParent = $this->find('first', array('contain' => false, 'fields' => array('Forum.id'), 'conditions' => array('Forum.category_id' => $data['Forum']['category_id'], 'Forum.category' => 1)));
			$data['Forum']['parent_id'] = $categoryParent['Forum']['id'];
		}
		$data['Forum']['title'] = trim($data['Forum']['title']);

		$this->save($data);
		return array('title' => $data['Forum']['title'], 'id' => 'forum_' . $this->id);
	}
}
?>
