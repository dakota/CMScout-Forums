<?php
class ForumsForumPostEvents extends AppModelEvents
{
	function onGetTags($event)
	{
		$tags = ClassRegistry::init('ForumPostsTag')->find('all', array('fields' => array('ForumPostsTag.tag_id', 'count(ForumPostsTag.tag_id) as `tagCount`'), 'group' => 'ForumPostsTag.tag_id'));
		
		$returnData = array();
		foreach($tags as $tag)
		{
			$returnData[$tag['ForumPostsTag']['tag_id']] = $tag[0]['tagCount'];	
		}
		
		return $returnData;
	}
	
	function onGetTagItems($event)
	{
		$tagModel = ClassRegistry::init('ForumPostsTag');
		
		$tagModel->bindModel(array('belongsTo' => array('ForumPost' => array('className' => 'Forums.ForumPost'))));
		
		$tagItems = $tagModel->find('all', array('conditions' => array('ForumPostsTag.tag_id' => $event->tagId), 'contain' => array('ForumPost' => 
																																		array('fields' => array('ForumPost.id', 
																																								'ForumPost.forum_thread_id', 
																																								'ForumPost.title',
																																								'ForumPost.slug'),
																																								'ForumThread' => array('fields' => 'ForumThread.slug')))));																														
		
		$returnData = array();
		foreach($tagItems as $tagItem)
		{
			$item = $tagItem['ForumPost'];
			$item['slug'] = $tagItem['ForumPost']['ForumThread']['slug'];
			$item['plugin'] = $this->params['plugin'];
			$item['controller'] = 'forums';
			$item['action'] = 'thread';
			$item['params'] = array($tagItem['ForumPost']['slug']);
			
			$returnData[] = $item;
		}

		return $returnData;
	}
	
	function onSearch($event)
	{
		$searchResults = ClassRegistry::init('Forums.ForumPost')->find('all', array('fields' => array('ForumPost.slug', 'ForumPost.title', 'ForumPost.text', 'ForumPost.created', "MATCH (ForumPost.title, ForumPost.text, ForumPost.tags) AGAINST ('".$event->query."' IN boolean MODE) AS score"),
 																	'conditions' =>  "MATCH(ForumPost.title, ForumPost.text, ForumPost.tags) AGAINST('".$event->query."' IN boolean MODE) HAVING score >= 1",
 																	'order' => 'score DESC', 'contain' => array('ForumThread' => array('fields' => 'ForumThread.slug'))));
		
		
		$returnData = array();
		foreach($searchResults as $result)
		{
			$returnData[] = array('slug' => $result['ForumThread']['slug'],
									'title' => $result['ForumPost']['title'],
									'text' => $result['ForumPost']['text'],
									'created' => $result['ForumPost']['created'],
									'score' => $result[0]['score'],
									'controller' => 'forums', 
									'action' => 'thread',
									'plugin' => $this->params['plugin'],
									'params' => array($result['ForumPost']['slug']));
		}
		
		return $returnData;
	}
	
	
	function onGetIndex($event)
	{
		$items = ClassRegistry::init('Forums.ForumPost')->find('all', array('contain' => array('ForumThread' => array('fields' => array('ForumThread.slug', 'ForumThread.forum_forum_id'),
																														'ForumForum' => array('fields'=>'ForumForum.id')))));
		
		$returnData = array();
		foreach($items as $item)
		{
			$returnData[] = array('slug' => array('type'=>'unindexed', 'value'=>$item['ForumThread']['slug']),
									'title' => array('type'=>'indexed', 'value'=>$item['ForumPost']['title']),
									'text' => array('type'=>'indexed', 'value'=>$item['ForumPost']['text']),
									'tags' => array('type'=>'indexed', 'value'=>$item['ForumPost']['tags']),
									'created' => array('type'=>'unindexed', 'value'=>$item['ForumPost']['created']),
									'params' => array('type'=>'unindexed', 'value' => array($item['ForumPost']['slug'])),
									'controller' => array('type'=>'unindexed', 'value'=>'forums'), 
									'action' => array('type'=>'unindexed', 'value'=>'thread'),
									'plugin' => array('type' => 'unindexed','value'=>$this->params['plugin']),
									'AclModel' => array('type'=>'unindexed', 'value'=>'ForumForum'),
									'AclId' => array('type'=>'unindexed', 'value' => $item['ForumThread']['ForumForum']['id']));	
		}
		
		return $returnData;
	}	
}
?>