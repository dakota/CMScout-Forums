<?php
class Category extends ForumsAppModel
{
 var $name = 'Category';
 var $hasMany = array('Forum' => array (
 							'className' => 'Forums.Forum',
 							'dependent' => true
 						));
 var $actsAs = array('Sluggable', 'Orderable');
 var $order = "Category.order ASC";

 function fetchCategories($slug, $userId)
 {
 	$returnData = array();

 	$conditions = array();
 	if ($slug != null)
 	{
 		$conditions['Category.slug'] = $slug;
 	}

 	$allCategories = $this->Forum->find('list', array('contain' => false, 'conditions' => array('Forum.category' => 1)));
 	$categories = $this->find('all', array('conditions' => $conditions, 
 											'contain' => array(
 												'Forum' => array(
 													'LastPost' => array('Thread', 'CreatedBy'),
 													'order' => 'Forum.lft ASC', 
 													'conditions'=>array(
 														'Forum.category' => 0, 
 														'Forum.parent_id' => array_keys($allCategories))))));
 	
 	foreach($categories as $categoryKey => $category)
 	{
 		foreach($category['Forum'] as $forumKey => $forum)
 		{
			$threadList = $this->Forum->Thread->find('list', array('contain' => false, 'conditions' => array('Thread.forum_id' => $forum['id'])));
			$hasUnread = $this->Forum->Thread->UnreadPost->find('count', array('conditions' => array('UnreadPost.user_id' => $userId, 'UnreadPost.thread_id' => array_keys($threadList))));

			$categories[$categoryKey]['Forum'][$forumKey]['unreadPost'] = $hasUnread;
			$categories[$categoryKey]['Forum'][$forumKey]['ChildForum'] = $this->Forum->find('all', array('order' => 'Forum.lft ASC', 'contain' => false, 'conditions' => array('Forum.parent_id' => $forum['id'])));
 		}
 	}

 	return $categories;
 }

 function addCategory($data)
 {
	$data['Category']['title'] = trim($data['Category']['title']);
	$this->save($data);

	$fakeForum = array();
	$fakeForum['Forum']['category_id'] = $this->id;
	$fakeForum['Forum']['category'] = 1;
	$fakeForum['Forum']['parent_id'] = null;

	$this->Forum->Behaviors->detach('Acl');
	$this->Forum->save($fakeForum);

	return array('title' => $data['Category']['title'], 'id' => 'category_' . $Category->id);
 }
}
?>