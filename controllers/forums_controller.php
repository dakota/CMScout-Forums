<?php
class ForumsController extends ForumsAppController
{
	var $name = 'Forums';
	var $uses = array('Forums.Category', 'Forums.Thread', 'Forum.Post');
	var $helpers = array('Bbcode', 'Forums.ForumTree');
	var $components = array('Notification');

	 public	$actionMap = array(
  		'admin_index' => 'read',
	 	'admin_add_category' => 'create',
	 	'admin_add_forum' => 'create',
	 	'admin_homepage_edit' => array('Core Configuration', 'update'),
	 	'admin_menu_edit' => array('Menu Manager', 'update')
	 );
 	
 	public $adminNode = 'Forum Manager'; 

	var $paginate = array('Post' =>
									array(
								 		'contain' => array('CreatedBy', 'ModifiedBy'),
								 		'limit' => 15
									),
						  'Thread' =>
									array(
								 		'limit' => 15
									));

	function index($slug = null)
	{
		$categories = $this->Category->fetchCategories($slug, $this->_userDetails['User']['id']);
		
		if ($slug != null)
			$category = $this->Category->findBySlug($slug);
			
		$this->set(compact('categories', 'category'));
	}

	function forum($slug = null)
	{
		if ($slug != null)
		{
			$forum = $this->Category->Forum->findBySlug($slug);
			$this->paginate = array('Thread' =>
										array(
			 								'contain' => array('CreatedBy',
																'LastPost' => array('CreatedBy'),
			 													'UnreadPost' => array('conditions' => array('UnreadPost.user_id' => $this->_userDetails['User']['id'])))
										));

			$this->set('threads', $this->paginate('Thread', array('Thread.forum_id' => $forum['Forum']['id'], 'Thread.thread_type' => 'NORMAL')));
			$this->set('breadcrumbs', $this->Category->Forum->fetchBreadcrumbs($slug));
			$this->set('announcementThreads', $this->Category->Forum->Thread->findThreads($forum['Forum']['id'], $this->_userDetails['User']['id'], 'ANNOUNCEMENT'));
			$this->set('subForums', $this->Category->Forum->fetchSubForums($slug, $this->_userDetails['User']['id']));
			$this->set('forum', $forum);
				
			if (!isset($this->params['named']['page']) || $this->params['named']['page'] = 1)
				$this->set('stickyThreads', $this->Category->Forum->Thread->findThreads($forum['Forum']['id'], $this->_userDetails['User']['id'], 'STICKY'));

			$this->set('permissions', $this->AclExtend->permissionArray('Forums.Forum', $forum['Forum']['id']));
		}
		else
		{
			$this->redirect(array('controller' => 'forums', 'plugin' => 'forums', 'action' => 'index'));
		}
	}

	function thread($slug = null, $post = null)
	{
		if ($slug != null)
		{
			$thread = $this->Thread->findBySlug($slug);

			$this->Thread->id = $thread['Thread']['id'];
			$this->Thread->saveField('views', $thread['Thread']['views'] + 1);
			$this->Thread->UnreadPost->deleteAll(array('UnreadPost.thread_id' => $thread['Thread']['id'], 'UnreadPost.user_id' => $this->_userDetails['User']['id']));
			$this->Thread->Subscriber->updateAll(array('Subscriber.active' => 1), array('Subscriber.user_id' => $this->_userDetails['User']['id'], 'Subscriber.thread_id' => $thread['Thread']['id']));

			if ($post != null)
			{
				$this->paginate['Post']['page'] = $this->Thread->Post->getPageNumber($post, $this->paginate['Post']['limit']);
			}

			$this->set('breadcrumbs', $this->Thread->fetchBreadcrumbs($slug));
			$this->set('posts', $this->paginate('Post', array('Post.thread_id' => $thread['Thread']['id'])));
			$this->set('thread', $thread);
			$this->set('subscribed', $this->Thread->Subscriber->find('count', array('contain' => false, 'conditions' => array('Subscriber.user_id' => $this->_userDetails['User']['id'], 'Subscriber.thread_id' => $thread['Thread']['id']))));
		}
		else
		{
			$this->redirect(array('controller' => 'forums', 'plugin' => 'forums', 'action' => 'index'));
		}
	}

	function newTopic($forumSlug = null)
	{
		if ($forumSlug != null)
		{
			$forum =  $this->Category->Forum->findBySlug($forumSlug);

			if (isset($this->data))
			{
				$this->data['Post'][0]['title'] = $this->data['Thread']['title'];
				$this->data['Post'][0]['text'] = $this->data['Thread']['post'];
				$this->data['Post'][0]['tags'] = $this->data['Thread']['tags'];
				$this->data['Post'][0]['forum_id'] = $forum['Forum']['id'];
				$this->data['Thread']['forum_id'] = $forum['Forum']['id'];

				if ($this->Thread->saveAll($this->data))
				{
					$thread = $this->Thread->read();

					$this->redirect(array('action' => 'thread', $thread['Thread']['slug']));
				}
			}
			else
			{
				$this->set(compact('forumSlug'));
				$this->set('breadcrumbs', $this->Category->Forum->fetchBreadcrumbs($forumSlug));
				$this->set('forum',$forum);
			}
		}
		else
		{
			$this->redirect(array('controller' => 'forums', 'plugin' => 'forums', 'action' => 'index'));
		}
	}

	function reply($threadSlug = null)
	{
		if ($threadSlug != null)
		{
			$thread = $this->Thread->findBySlug($threadSlug);

			if (isset($this->params['form']['reply']))
			{
				$this->data['Post']['thread_id'] = $thread['Thread']['id'];
				$this->data['Post']['forum_id'] = $thread['Thread']['forum_id'];
				$this->data['Post']['user_id'] = $this->_userDetails['User']['id'];
				if (!isset($this->data['Post']['title']))
				{
					$this->data['Post']['title'] = 'Re: ' . $thread['Thread']['title'];
				}
				if ($this->Post->save($this->data))
				{
					$post = $this->Post->find('first', array('contain' => array('Thread' => array('Forum'), 'CreatedBy'), 'conditions' => array('Post.id' => $this->Post->id)));
					if ($this->data['Post']['subscribe'])
					{
						if (!$this->Thread->Subscriber->find('count', array('contain' => false, 'conditions' => array('Subscriber.user_id' => $this->Auth->user('id'), 'Subscriber.thread_id' => $thread['Thread']['id']))))
						{
							$subscribe['ForumSubscriber']['user_id'] = $this->_userDetails['User']['id'];
							$subscribe['ForumSubscriber']['thread_id'] = $thread['Thread']['id'];
							$subscribe['ForumSubscriber']['active'] = 1;
							$this->Thread->Subscriber->save($subscribe);
						}
						else
						{
							$this->Thread->Subscriber->updateAll(array('Subscriber.active' => 1), array('Subscriber.user_id' => $this->_userDetails['User']['id'], 'Subscriber.thread_id' => $thread['Thread']['id']));
						}
					}
					else
					{
						if ($this->Thread->Subscriber->find('count', array('contain' => false, 'conditions' => array('Subscriber.user_id' => $this->_userDetails['User']['id'], 'Subscriber.thread_id' => $thread['Thread']['id']))))
						{
							$this->Thread->Subscriber->deleteAll(array('Subscriber.user_id' => $this->_userDetails['User']['id'], 'Subscriber.thread_id' => $thread['Thread']['id']));
						}
					}

					$users = $this->Thread->CreatedBy->find('all', array('contain' => false, 'fields' => array('id'), 'conditions' => array('CreatedBy.id <>' => $this->Auth->user('id'))));
					$unreads = array();
					foreach ($users as $user)
					{
						if ($this->Thread->UnreadPost->find('count', array('conditions' => array('UnreadPost.user_id' => $user['CreatedBy']['id'], 'UnreadPost.thread_id' => $thread['Thread']['id']))) == 0)
						$unreads[]['UnreadPost'] = array('user_id' => $user['CreatedBy']['id'], 'thread_id' => $thread['Thread']['id']);
					}
					$this->Thread->UnreadPost->saveAll($unreads);

					$this->Thread->Subscriber->displayField = 'user_id';
					$subscribedUsers = $this->Thread->Subscriber->find('list', array('contain' => false, 'conditions' => array('Subscriber.active' => 1, 'Subscriber.user_id <>' => $this->Auth->user('id'), 'Subscriber.thread_id' => $thread['Thread']['id'])));

					$this->Notification->sendNotification('thread_reply', $post, true, array_values($subscribedUsers));

					$this->Thread->Subscriber->updateAll(array('Subscriber.active' => 0), array('Subscriber.user_id <>' => $this->Auth->user('id'), 'Subscriber.thread_id' => $thread['Thread']['id']));

					$this->redirect(array('action' => 'thread', $threadSlug, $this->Post->id, '#' => $this->Post->id));
				}
			}
			elseif (isset($this->params['form']['advanced']))
			{
				$this->set('thread', $thread);
				$this->set('breadcrumbs', $this->Thread->fetchBreadcrumbs($threadSlug));
			}
		}
	}

	function editPost($postId = null)
	{
		$post = $this->Post->find('first', array('conditions' => array('Post.id' => $postId), 'contain' => array('Thread')));
		if(!isset($this->data) || isset($this->params['form']['advanced']))
		{
			$this->data = $post;
			$thread['Thread'] = $post['Thread'];
			$this->set('thread', $thread);
			$this->set('breadcrumbs', $this->Thread->fetchBreadcrumbs($thread['Thread']['slug']));
		}
		else
		{
			if (isset($this->params['form']['cancel']))
			{
				$this->redirect(array('action' => 'thread', $post['Thread']['slug'], $postId, '#' => $postId));
			}
			else
			{
				$this->data['Post']['edit_user'] = $this->Auth->user('id');
				if ($this->Post->save($this->data))
				{
					$this->redirect(array('action' => 'thread', $post['Thread']['slug'], $postId, '#' => $postId));
				}
			}
		}
	}

	function deletePost($postId = null)
	{
		$post = $this->Post->find('first', array('conditions' => array('Post.id' => $postId), 'contain' => array('Thread')));
		$this->Post->delete($postId);
		$previousPost = $this->Post->find('first', array('order' => 'Post.created DESC', 'conditions' => array('Post.id < ' => $postId), 'contain' => false));
		$this->redirect(array('action' => 'thread', $post['Thread']['slug'], $previousPost['Post']['id'], '#' => $previousPost['Post']['id']));
	}

	function autoTag()
	{
		App::import('Component', 'Keywords');
		$keywords = new KeywordsComponent();

		echo $keywords->keywordIt($this->params['form']['text']);

		exit;
	}
	
	function lock($threadSlug)
	{
		$thread = $this->Thread->find('first', array('conditions' => array('Thread.slug' => $threadSlug), 'contain' => array('Forum')));

		$this->Thread->id = $thread['Thread']['id'];
		$this->Thread->saveField('locked', !$thread['Thread']['locked']);	
		
		if($this->_isAjax)
		{
			exit;
		}
		else
		{
			$this->redirect(array('action' => 'forum', $thread['Forum']['slug']));
		}
	}
	
	function threadType($threadSlug, $type)
	{
		$thread = $this->Thread->find('first', array('conditions' => array('Thread.slug' => $threadSlug), 'contain' => array('Forum')));

		if ($type == 'sticky' && $thread['Thread']['thread_type'] != 'STICKY')
		{
			$newType = 'STICKY';
		}
		elseif ($type == 'announce' && $thread['Thread']['thread_type'] != 'ANNOUNCEMENT')
		{
			$newType = 'ANNOUNCEMENT';
		}
		else
		{
			$newType = 'NORMAL';
		}

		$this->Thread->id = $thread['Thread']['id'];
		$this->Thread->saveField('thread_type', $newType);			
		
		if($this->_isAjax)
		{
			exit;
		}
		else
		{
			$this->Session->setFlash('Thread type changed', null);
			$this->redirect(array('action' => 'forum', $thread['Forum']['slug']));
		}
	}	
	
	function deleteThread($threadSlug)
	{
		$thread = $this->Thread->find('first', array('conditions' => array('Thread.slug' => $threadSlug), 'contain' => array('Forum')));
		
		$this->Thread->del($thread['Thread']['id']);
		
		$this->Session->setFlash('Thread deleted', null);
		$this->redirect(array('action' => 'forum', $thread['Forum']['slug']));
	}
	
	function moveThread($threadSlug, $moveTo = null)
	{
		if ($moveTo == null)
		{
			$thread = $this->Thread->find('first', array('conditions' => array('Thread.slug' => $threadSlug), 'contain' => false));
		
			$this->set('forums', $this->Thread->Forum->find('list', array('contain' => false, 'conditions' => array('Forum.id <>' => $thread['Thread']['forum_id'], 'Forum.category' => 0))));
		}
		else
		{
			$thread = $this->Thread->find('first', array('conditions' => array('Thread.slug' => $threadSlug), 'contain' => array('Forum')));
			
			if ($this->Thread->Forum->find('count', array('conditions' => array('Forum.id' => $moveTo))))
			{
				$this->Thread->id = $thread['Thread']['id'];
				$this->Thread->saveField('forum_id', $moveTo);
				$this->Session->setFlash('Thread moved', null);
			}
			$this->redirect(array('action' => 'forum', $thread['Forum']['slug']));
		}
	}
	
function admin_index()
	{			
		$categories = $this->Category->find('all', array('contain' => false));

		foreach ($categories as $key => $category)
		{
			$categories[$key]['children'] = $this->Category->Forum->find('threaded', array('conditions' => array('Forum.category_id' => $category['Category']['id'], 'Forum.category' => 0), 'contain' => false));
		}
		
		$this->set('categories',$categories);
	}

	function admin_move()
	{
		$nodeToMove = explode('_', $this->params['named']['node']);
		$refNode = explode('_', $this->params['named']['ref_node']);
		$moveType = $this->params['named']['move_type'];
		
		$changed = false;
		if ($nodeToMove[0] == 'forum')
		{
			$movingForum = $this->Category->Forum->find('first', array('conditions' => array('Forum.id' => $nodeToMove[1]), 'contain' => false));
				
			if ($refNode[0] == 'forum')
			{
				$refForum = $this->Category->Forum->find('first', array('conditions' => array('Forum.id' => $refNode[1]), 'contain' => false));
				if ($movingForum['Forum']['category_id'] != $refForum['Forum']['forum_category_id'])
				{
					$movingForum['Forum']['category_id'] = $refForum['Forum']['forum_category_id'];
					$movingForum['Forum']['parent_id'] = $refForum['Forum']['category_id'];
					$changed = true;
				}

				switch($moveType)
				{
					case 'inside' :
						$movingForum['Forum']['parent_id'] = $refForum['Forum']['id'];
						$this->Category->Forum->save($movingForum);
						break;
					case 'after' :
					case 'before' :
						if ($refForum['Forum']['parent_id'] != $movingForum['Forum']['parent_id'])
						{
							$movingForum['Forum']['parent_id'] = $refForum['Forum']['parent_id'];
							
							$changed = true;
						}
						
						if ($changed)
						{
							$this->Category->Forum->save($movingForum);
							$movingForum = $this->Category->Forum->find('first', array('contain' => false, 'conditions' => array('Forum.id' => $nodeToMove[1]), 'contain' => false));
						}
						
						$between = array($movingForum['Forum']['lft'], $moveType == 'before' ? $refForum['Forum']['lft'] : $refForum['Forum']['rght']);
						sort($between);
						$moveDistance = $this->Category->Forum->find('count', array('contain' => false, 'conditions' => array
						('Forum.category_id' => $movingForum['Forum']['forum_category_id'], 'Forum.parent_id' => $movingForum['Forum']['parent_id'], 'Forum.id <>' => $movingForum['Forum']['id'], 'Forum.lft BETWEEN ? AND ?' => $between)));
						$moveDistance += $changed ? (1) : 0;
					
						if($movingForum['Forum']['lft'] > $refForum['Forum']['lft'])
						{
							$this->Category->Forum->moveup($movingForum['Forum']['id'], $moveDistance);
						}
						else
						{
							$this->Category->Forum->movedown($movingForum['Forum']['id'], $moveDistance);
						}						
						break;
				}
			}
			else
			{
				$refCategory = $this->Category->find('first', array('conditions' => array('Category.id' => $refNode[1]), 'contain' => false));

				$movingForum['Forum']['category_id'] = $refCategory['Category']['id'];
				$movingForum['Forum']['parent_id'] = $refCategory['Category']['id'];
				$this->Category->Forum->save($movingForum);
				$this->Category->Forum->updateAll(array('Forum.category_id' => $refCategory['Category']['id']), array('Forum.parent_id' => $movingForum['Forum']['id']));
			}
		}
		else
		{
			$moveCategory = $this->Category->find('first', array('conditions' => array('Category.id' => $nodeToMove[1]), 'contain' => false));
			$refCategory = $this->Category->find('first', array('conditions' => array('Category.id' => $refNode[1]), 'contain' => false));
							
			$moveDistance = $moveCategory['Category']['order'] - $refCategory['Category']['order'];

			if($moveDistance > 0)
			{
				$this->Category->moveup($moveCategory['Category']['id'], $moveDistance);
			}
			else
			{
				$this->Category->movedown($moveCategory['Category']['id'], abs($moveDistance));
			}	
		}
		exit;
	}

	function admin_add_category()
	{
		$this->view = 'Json';
		$this->set('data', $this->Category->addCategory($this->data));
		$this->set('json', 'data');
	}

	function admin_add_forum()
	{
		$this->view = 'Json';
		$this->set('data', $this->Category->Forum->addForum($this->data));
		$this->set('json', 'data');
	}
	
	function admin_information()
	{
		$node = explode('_', $this->params['named']['node']);
		
		if ($node[0] == 'forum')
		{
			$forum = $this->Category->Forum->find('first', array('contain' => false, 'conditions' => array('Forum.id' => $node[1])));
			$numberThreads = $forum['Forum']['thread_count'];
			$numberPosts = $forum['Forum']['post_count'];
			$numberViews = $this->Category->Forum->Thread->find('first', array('fields' => 'sum(Thread.views) as Views', 'contain' => false, 'conditions' => array('Thread.forum_id' => $forum['Forum']['id'])));
			$threadList = $this->Category->Forum->Thread->find('list', array('contain' => false, 'conditions' => array('Thread.forum_id' => $forum['Forum']['id'])));
			$lastPost = $this->Category->Forum->Thread->Post->find('first', array('contain' => false, 'conditions' => array('Post.thread_id' => array_keys($threadList)), 'order' => array('Post.created DESC')));
			$firstPost = $this->Category->Forum->Thread->Post->find('first', array('contain' => false, 'conditions' => array('Post.thread_id' => array_keys($threadList)), 'order' => array('Post.created ASC')));
			$activeUser = $this->Category->Forum->Thread->Post->find('first', array('contain' => array('CreatedBy'), 'conditions' => array('Post.thread_id' => array_keys($threadList)), 'fields' => 'count(created_by) as UserPosts, Post.created_by', 'group' => 'Post.created_by', 'order' => 'UserPosts DESC'));
			$days = ceil((strtotime($lastPost['Post']['created']) - strtotime($firstPost['Post']['created']))/86400);
			$postsPerDay = $days > 0 ? $numberPosts/$days : 0;

			$forumAco = $this->AclExtend->AcoInfo(array('Aco.foreign_key' => $node[1], 'Aco.model' => 'Forums.Forum'));
			
			$this->set(compact('forum', 'numberThreads', 'numberPosts', 'numberViews', 'lastPost', 'activeUser', 'postsPerDay', 'forumAco'));
		}
		else
		{
			$category = $this->Category->find('first', array('conditions' => array('Category.id' => $node[1]),
															'contain' => false));

			$forumStats = $this->Category->Forum->find('all', array('fields' => array('SUM(Forum.thread_count) as thread_count', 'SUM(Forum.post_count) as post_count', 'Forum.id'),
																	'contain' => array('Thread' => array('fields' => array('SUM(Thread.views) as views'))), 
																	'conditions' => array('Forum.category' => 0, 'Forum.category_id' => $node[1])));

			$numberPosts = Set::extract($forumStats, '0.0.post_count');
			$numberThreads = Set::extract($forumStats, '0.0.thread_count');
			$numberViews = Set::extract($forumStats, '0.Thread.0.Thread.0.views');
			$forumList = Set::extract('/Forum/id', $forumStats);
			
			$threadList = $this->Category->Forum->Thread->find('list', array('contain' => false, 'conditions' => array('Thread.forum_id' => $forumList)));
			$lastPost = $this->Category->Forum->Thread->Post->find('first', array('contain' => false, 'conditions' => array('Post.thread_id' => array_keys($threadList)), 'order' => array('Post.created DESC')));
			$firstPost = $this->Category->Forum->Thread->Post->find('first', array('contain' => false, 'conditions' => array('Post.thread_id' => array_keys($threadList)), 'order' => array('Post.created ASC')));
			$activeUser = $this->Category->Forum->Thread->Post->find('first', array('contain' => array('CreatedBy' => array('fields' => array('CreatedBy.username'))), 'conditions' => array('Post.thread_id' => array_keys($threadList)), 'fields' => 'count(created_by) as UserPosts, Post.created_by', 'group' => 'Post.created_by', 'order' => 'UserPosts DESC'));
			$days = ceil((strtotime($lastPost['Post']['created']) - strtotime($firstPost['Post']['created']))/86400);
			$postsPerDay = $days > 0 ? $numberPosts/$days : 0;
			
			$this->set(compact('category', 'numberThreads', 'numberPosts', 'numberViews', 'lastPost', 'activeUser', 'postsPerDay'));
			$this->render('admin_category_information');
		}
	}
	
	function admin_edit_title()
	{
		if(isset($this->data['Forum']))
		{
			$this->Category->Forum->save($this->data);
			echo trim($this->data['Forum']['title']);
		}
		elseif(isset($this->data['Category']))
		{
			$this->Category->save($this->data);
			echo trim($this->data['Category']['title']);
		}
		exit;
	}
	
	function admin_edit_description()
	{
		if(isset($this->data['Forum']))
		{
			$this->Category->Forum->save($this->data);
			echo trim($this->data['Forum']['description']);
		}
		exit;
	}	
	
	function admin_delete_forum($id = null)
	{
		if (!count($this->params['form']))
		{
			if ($id != null)
			{
				$thisForum = $this->Category->Forum->find('first', array('contain' => false, 'conditions' => array('Forum.id' => $id)));
				$numberThreads = $this->Category->Forum->Thread->find('count', array('contain' => false, 'conditions' => array('Thread.forum_id' => $thisForum['Forum']['id'])));
				$totalChildren = $this->Category->Forum->childCount($id);
				
				$forums = $this->Category->Forum->generatetreelist(array('Forum.category' => 0, 'Forum.id <>' => $id, 'Forum.parent_id <> ' => $id), null,null,'>');
				$forumList = array();
				foreach($forums as $key => $forum)
				{
					$forumList['forum_'.$key] = $forum;
				}
				
				$categories = $this->Category->find('list', array('contain' => false));
				$categoryList = array();
				foreach($categories as $key => $category)
				{
					$categoryList['category_'.$key] = $category;
				}
				
				$this->set(compact('thisForum', 'numberThreads', 'totalChildren', 'forumList', 'categoryList'));
			}
		}
		else
		{
			if ($id != null)
			{
				$return = false;
				if ($this->params['form']['thread'] != '')
				{
					$newForum = explode('_', $this->params['form']['thread']);
					$newForum = Sanitize::paranoid($newForum[1]);
					$this->Category->Forum->Thread->updateAll(array('Thread.forum_id' => $newForum), array('Thread.forum_id' => $id));
					$this->Category->Forum->Thread->Post->updateAll(array('Post.forum_id' => $newForum), array('Post.forum_id' => $id));
				}
	
				if ($this->params['form']['forum'] != '')
				{
					$newPlace = explode('_', $this->params['form']['forum']);
					$newPlaceId = Sanitize::paranoid($newPlace[1]);
					
					$update = array();
					if ($newPlace[0] == 'category')
					{
						$category = $this->Category->Forum->find('first', array('fields' => array('Forum.id'), 'contain' => false, 'conditions' => array('Forum.category' => 1, 'Forum.category_id' => $newPlaceId)));
						
						$update['Forum.category_id'] = $newPlaceId;
						$update['Forum.parent_id'] = $category['Forum']['id'];
					}
					else
					{
						$forum = $this->Category->Forum->find('first', array('fields' => array('Forum.category_id'), 'contain' => false, 'conditions' => array('Forum.id' => $newPlaceId)));
						
						$update['Forum.category_id'] = $forum['Forum']['forum_category_id'];
						$update['Forum.parent_id'] = $newPlaceId;
					}
	
					$this->Category->Forum->updateAll($update, array('Forum.parent_id' => $id));
					
					$return['moved'] = $this->params['form']['forum'];
				}
				
				$this->Category->Forum->delete($id, true);
				
				$this->view = 'Json';
				$this->set('data', $return);
				$this->set('json', 'data');
			}
		}
	}
	
	function admin_delete_category($id = null)
	{
		if (!count($this->params['form']))
		{
			if ($id != null)
			{
				$thisCategory = $this->Category->find('first', array('contain' => array('Forum' => array('conditions' => array('Forum.category' => 0), 'fields' => array('id'))), 
																		'conditions' => array('Category.id' => $id)));
				$totalChildren = count($thisCategory['Forum']);
				
				$categories = $this->Category->find('list', array('contain' => false, 'conditions' => array('Category.id <>' => $id)));
				$categoryList = array();
				foreach($categories as $key => $category)
				{
					$categoryList['category_'.$key] = $category;
				}
				
				$this->set(compact('thisCategory', 'totalChildren', 'categoryList'));
			}
		}
		else
		{
			$id =  Sanitize::paranoid($id);
			$return = false;
			if ($this->params['form']['forum'] != '')
			{
				$newPlace = explode('_', $this->params['form']['forum']);
				$newPlaceId = Sanitize::paranoid($newPlace[1]);
				
				$update = array();
				$currentCategory = $this->Category->Forum->find('first', array('fields' => array('Forum.id'), 'contain' => false, 'conditions' => array('Forum.category' => 1, 'Forum.category_id' => $id)));
				$newCategory = $this->Category->Forum->find('first', array('fields' => array('Forum.id'), 'contain' => false, 'conditions' => array('Forum.category' => 1, 'Forum.category_id' => $newPlaceId)));
				
				$update['Forum.category_id'] = $newPlaceId;
				$update['Forum.parent_id'] = $newCategory['Forum']['id'];

				$this->Category->Forum->updateAll($update, array('Forum.parent_id' => $currentCategory['Forum']['id']));

				$updateNew['Forum.category_id'] = $newPlaceId;
				$this->Category->Forum->updateAll($updateNew, array('Forum.category_id' => $id));
				
				$return['moved'] = $this->params['form']['forum'];
			}
			
			$this->Category->Forum->delete($id, true);
			
			$this->view = 'Json';
			$this->set('data', $return);
			$this->set('json', 'data');
		}
	}

	function admin_homepage_edit()
	{
		$this->set('forums', $this->Category->Forum->find('list', array('fields' => array('Forum.slug', 'Forum.title'))));
		
		if(isset($this->params['named']['fieldValue']))
			$this->set('values', unserialize(urldecode($this->params['named']['fieldValue'])));
	}
	
	function admin_menu_edit()
	{
		$this->set('forums', $this->Category->Forum->find('list', array('fields' => array('Forum.slug', 'Forum.title'))));
	}
}
?>