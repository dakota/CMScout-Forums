<?php
class Post extends ForumsAppModel
{
 var $name = 'Post';
 var $belongsTo = array('Thread' => array('className' => 'Forums.Thread', 'counterCache' => true),
 						'Forum' => array('className' => 'Forums.Forum', 'counterCache' => true));
 
 var $actsAs = array('Tag'=>array('table_label'=>'tags', 'tags_label'=>'tag', 'separator'=>','), 'Sluggable', 'WhoDidIt');

 var $hasAndBelongsToMany = array('Tag' => array('joinTable' => 'forum_posts_tags'));

 function getPageNumber($pageId, $perPage=25)
 {
 	if(is_numeric($pageId))
  		$viewPost = $this->find('first', array('conditions' => array('Post.id' => $pageId), 'fields' => array('id', 'thread_id','created'),
 										'contain' => false));
  	else
  		$viewPost = $this->find('first', array('conditions' => array('Post.slug' => $pageId), 'fields' => array('id', 'thread_id','created'),
 										'contain' => false));
  	
  	$numberOfPost = $this->find('count', array("conditions" => array ('Post.thread_id' => $viewPost['Post']['thread_id'], 'Post.id <=' => $pageId)));
  	return ceil($numberOfPost / $perPage);
 }
}
?>