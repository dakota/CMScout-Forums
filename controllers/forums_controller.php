<?php
class ForumsController extends ForumsAppController
{
	var $name = 'Forums';
	var $uses = array("Forums.ForumCategory", "Forums.ForumThread", "Forum.ForumPost");
	var $helpers = array("Bbcode", "ForumTree", "ForumGeneral");
	/**
	 * @var SessionComponent
	 */
	var $Session;
	/**
	 * @var AclComponent
	 */
	var $Acl;
	/**
	 * @var AuthComponent
	 */
	var $Auth;

	var $paginate = array('ForumPost' =>
	array(
 								'contain' => array('User', 'EditUser'),
 								'limit' => 15
	),
 							'ForumThread' =>
	array(
 								'limit' => 15
	));

	function index($slug = null)
	{
		$this->set('categories', $this->ForumCategory->fetchCategories($slug, $this->Auth->user('id')));
		if ($slug != null)
		$this->set('category', $this->ForumCategory->findBySlug($slug));
	}

	function forum($slug = null)
	{
		if ($slug != null)
		{
			$this->ForumCategory->ForumForum->recursive = -1;
			$forum = $this->ForumCategory->ForumForum->findBySlug($slug);
			$this->paginate = array('ForumThread' =>
			array(
 								'contain' => array('User',
													'ForumPost' => array('User', 'order' => 'ForumPost.created DESC', 'limit' => 1),
 													'ForumUnreadPost' => array('conditions' => array('ForumUnreadPost.user_id' => $this->Auth->user('id'))))
			));

			$this->set('breadcrumbs', $this->ForumCategory->ForumForum->fetchBreadcrumbs($slug));
			$this->set('announcementThreads', $this->ForumThread->findThreads($forum['ForumForum']['id'], $this->Auth->user('id'), 'ANNOUNCEMENT'));
			$this->set('threads', $this->paginate('ForumThread', array('ForumThread.forum_forum_id' => $forum['ForumForum']['id'], 'ForumThread.thread_type' => 'NORMAL')));
			$this->set('subForums', $this->ForumCategory->ForumForum->fetchSubForums($slug, $this->Auth->user('id')));
			$this->set('forum', $forum);
				
			if (!isset($this->params['named']['page']) || $this->params['named']['page'] = 1)
				$this->set('stickyThreads', $this->ForumThread->findThreads($forum['ForumForum']['id'], $this->Auth->user('id'), 'STICKY'));

			$this->set('permissions', $this->AclExtend->permissionArray('ForumForum', $forum['ForumForum']['id']));
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
			$this->ForumThread->recursive = -1;
			$thread = $this->ForumThread->findBySlug($slug);

			$this->ForumThread->id = $thread['ForumThread']['id'];
			$this->ForumThread->saveField('views', $thread['ForumThread']['views'] + 1);
			$this->ForumThread->ForumUnreadPost->deleteAll(array('ForumUnreadPost.forum_thread_id' => $thread['ForumThread']['id'], 'ForumUnreadPost.user_id' => $this->Auth->user('id')));
			$this->ForumThread->ForumSubscriber->updateAll(array('ForumSubscriber.active' => 1), array('ForumSubscriber.user_id' => $this->Auth->user('id'), 'ForumSubscriber.forum_thread_id' => $thread['ForumThread']['id']));

			if ($post != null)
			{
				$this->paginate['ForumPost']['page'] = $this->ForumPost->getPageNumber($post, $this->paginate['ForumPost']['limit']);
			}

			$this->set('breadcrumbs', $this->ForumThread->fetchBreadcrumbs($slug));
			$this->set('posts', $this->paginate('ForumPost', array('ForumPost.forum_thread_id' => $thread['ForumThread']['id'])));
			$this->set('thread', $thread);
			$this->set('subscribed', $this->ForumThread->ForumSubscriber->find('count', array('contain' => false, 'conditions' => array('ForumSubscriber.user_id' => $this->Auth->user('id'), 'ForumSubscriber.forum_thread_id' => $thread['ForumThread']['id']))));
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
			$forum =  $this->ForumCategory->ForumForum->findBySlug($forumSlug);

			if (isset($this->data))
			{
				$this->data['ForumPost'][0]['title'] = $this->data['ForumThread']['title'];
				$this->data['ForumPost'][0]['text'] = $this->data['ForumThread']['post'];
				$this->data['ForumPost'][0]['tags'] = $this->data['ForumThread']['tags'];
				$this->data['ForumPost'][0]['user_id'] = $this->Auth->user('id');
				$this->data['ForumThread']['user_id'] = $this->Auth->user('id');
				$this->data['ForumThread']['forum_forum_id'] = $forum['ForumForum']['id'];

				if ($this->ForumThread->saveAll($this->data))
				{
					$thread = $this->ForumThread->read();

					$this->redirect(array('action' => 'thread', $thread['ForumThread']['slug']));
				}
			}
			else
			{
				$this->set(compact('forumSlug'));
				$this->set('breadcrumbs', $this->ForumCategory->ForumForum->fetchBreadcrumbs($forumSlug));
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
			$thread = $this->ForumThread->findBySlug($threadSlug);

			if (isset($this->params['form']['reply']))
			{
				$this->data['ForumPost']['forum_thread_id'] = $thread['ForumThread']['id'];
				$this->data['ForumPost']['user_id'] = $this->Auth->user('id');
				if (!isset($this->data['ForumPost']['title']))
				{
					$this->data['ForumPost']['title'] = 'Re: ' . $thread['ForumThread']['title'];
				}
				if ($this->ForumPost->save($this->data))
				{
					$post = $this->ForumPost->find('first', array('contain' => array('ForumThread' => array('ForumForum'), 'User'), 'conditions' => array('ForumPost.id' => $this->ForumPost->id)));
					if ($this->data['ForumPost']['subscribe'])
					{
						if (!$this->ForumThread->ForumSubscriber->find('count', array('contain' => false, 'conditions' => array('ForumSubscriber.user_id' => $this->Auth->user('id'), 'ForumSubscriber.forum_thread_id' => $thread['ForumThread']['id']))))
						{
							$subscribe['ForumSubscriber']['user_id'] = $this->Auth->user('id');
							$subscribe['ForumSubscriber']['forum_thread_id'] = $thread['ForumThread']['id'];
							$subscribe['ForumSubscriber']['active'] = 1;
							$this->ForumThread->ForumSubscriber->save($subscribe);
						}
						else
						{
							$this->ForumThread->ForumSubscriber->updateAll(array('ForumSubscriber.active' => 1), array('ForumSubscriber.user_id' => $this->Auth->user('id'), 'ForumSubscriber.forum_thread_id' => $thread['ForumThread']['id']));
						}
					}
					else
					{
						if ($this->ForumThread->ForumSubscriber->find('count', array('contain' => false, 'conditions' => array('ForumSubscriber.user_id' => $this->Auth->user('id'), 'ForumSubscriber.forum_thread_id' => $thread['ForumThread']['id']))))
						{
							$this->ForumThread->ForumSubscriber->deleteAll(array('ForumSubscriber.user_id' => $this->Auth->user('id'), 'ForumSubscriber.forum_thread_id' => $thread['ForumThread']['id']));
						}
					}

					$users = $this->ForumThread->User->find('all', array('contain' => false, 'fields' => array('id'), 'conditions' => array('User.id <>' => $this->Auth->user('id'))));
					$unreads = array();
					foreach ($users as $user)
					{
						if ($this->ForumThread->ForumUnreadPost->find('count', array('conditions' => array('ForumUnreadPost.user_id' => $user['User']['id'], 'ForumUnreadPost.forum_thread_id' => $thread['ForumThread']['id']))) == 0)
						$unreads[]['ForumUnreadPost'] = array('user_id' => $user['User']['id'], 'forum_thread_id' => $thread['ForumThread']['id']);
					}
					$this->ForumThread->ForumUnreadPost->saveAll($unreads);

					$this->ForumThread->ForumSubscriber->displayField = 'user_id';
					$subscribedUsers = $this->ForumThread->ForumSubscriber->find('list', array('contain' => false, 'conditions' => array('ForumSubscriber.active' => 1, 'ForumSubscriber.user_id <>' => $this->Auth->user('id'), 'ForumSubscriber.forum_thread_id' => $thread['ForumThread']['id'])));

					$this->Notification->sendNotification('thread_reply', $post, true, array_values($subscribedUsers));

					$this->ForumThread->ForumSubscriber->updateAll(array('ForumSubscriber.active' => 0), array('ForumSubscriber.user_id <>' => $this->Auth->user('id'), 'ForumSubscriber.forum_thread_id' => $thread['ForumThread']['id']));

					$this->redirect(array('action' => 'thread', $threadSlug, $this->ForumPost->id, '#' => $this->ForumPost->id));
				}
			}
			elseif (isset($this->params['form']['advanced']))
			{
				$this->set('thread', $thread);
				$this->set('breadcrumbs', $this->ForumThread->fetchBreadcrumbs($threadSlug));
			}
		}
	}

	function editPost($postId = null)
	{
		$post = $this->ForumPost->find('first', array('conditions' => array('ForumPost.id' => $postId), 'contain' => array('ForumThread')));
		if(!isset($this->data) || isset($this->params['form']['advanced']))
		{
			$this->data = $post;
			$thread['ForumThread'] = $post['ForumThread'];
			$this->set('thread', $thread);
			$this->set('breadcrumbs', $this->ForumThread->fetchBreadcrumbs($thread['ForumThread']['slug']));
		}
		else
		{
			if (isset($this->params['form']['cancel']))
			{
				$this->redirect(array('action' => 'thread', $post['ForumThread']['slug'], $postId, '#' => $postId));
			}
			else
			{
				$this->data['ForumPost']['edit_user'] = $this->Auth->user('id');
				if ($this->ForumPost->save($this->data))
				{
					$this->redirect(array('action' => 'thread', $post['ForumThread']['slug'], $postId, '#' => $postId));
				}
			}
		}
	}

	function deletePost($postId = null)
	{
		$post = $this->ForumPost->find('first', array('conditions' => array('ForumPost.id' => $postId), 'contain' => array('ForumThread')));
		$this->ForumPost->delete($postId);
		$previousPost = $this->ForumPost->find('first', array('order' => 'ForumPost.created DESC', 'conditions' => array('ForumPost.id < ' => $postId), 'contain' => false));
		$this->redirect(array('action' => 'thread', $post['ForumThread']['slug'], $previousPost['ForumPost']['id'], '#' => $previousPost['ForumPost']['id']));
	}

	function autoTag()
	{
		App::import('Component', 'Keywords');
		$keywords = new KeywordsComponent();

		echo $keywords->keywordIt($this->params['form']['text']);

		exit;
	}

	function admin_index()
	{	
		if($this->ForumCategory->ForumForum->verify() !== true)
		{
			$this->ForumCategory->ForumForum->recover();
		}
		
		$categories = $this->ForumCategory->find('all', array('contain' => false));

		foreach ($categories as $key => $category)
		{
			$categories[$key]['children'] = $this->ForumCategory->ForumForum->find('threaded', array('conditions' => array('ForumForum.forum_category_id' => $category['ForumCategory']['id'], 'ForumForum.category' => 0), 'contain' => false));
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
			$movingForum = $this->ForumCategory->ForumForum->find('first', array('conditions' => array('ForumForum.id' => $nodeToMove[1]), 'contain' => false));
				
			if ($refNode[0] == 'forum')
			{
				$refForum = $this->ForumCategory->ForumForum->find('first', array('conditions' => array('ForumForum.id' => $refNode[1]), 'contain' => false));
				if ($movingForum['ForumForum']['forum_category_id'] != $refForum['ForumForum']['forum_category_id'])
				{
					$movingForum['ForumForum']['forum_category_id'] = $refForum['ForumForum']['forum_category_id'];
					$movingForum['ForumForum']['parent_id'] = $refForum['ForumForum']['forum_category_id'];
					$changed = true;
				}

				switch($moveType)
				{
					case 'inside' :
						$movingForum['ForumForum']['parent_id'] = $refForum['ForumForum']['id'];
						$this->ForumCategory->ForumForum->save($movingForum);
						break;
					case 'after' :
					case 'before' :
						if ($refForum['ForumForum']['parent_id'] != $movingForum['ForumForum']['parent_id'])
						{
							$movingForum['ForumForum']['parent_id'] = $refForum['ForumForum']['parent_id'];
							
							$changed = true;
						}
						
						if ($changed)
						{
							$this->ForumCategory->ForumForum->save($movingForum);
							$movingForum = $this->ForumCategory->ForumForum->find('first', array('contain' => false, 'conditions' => array('ForumForum.id' => $nodeToMove[1]), 'contain' => false));
						}
						
						$between = array($movingForum['ForumForum']['lft'], $moveType == 'before' ? $refForum['ForumForum']['lft'] : $refForum['ForumForum']['rght']);
						sort($between);
						$moveDistance = $this->ForumCategory->ForumForum->find('count', array('contain' => false, 'conditions' => array
						('ForumForum.forum_category_id' => $movingForum['ForumForum']['forum_category_id'], 'ForumForum.parent_id' => $movingForum['ForumForum']['parent_id'], 'ForumForum.id <>' => $movingForum['ForumForum']['id'], 'ForumForum.lft BETWEEN ? AND ?' => $between)));
						$moveDistance += $changed ? (1) : 0;
					
						if($movingForum['ForumForum']['lft'] > $refForum['ForumForum']['lft'])
						{
							$this->ForumCategory->ForumForum->moveup($movingForum['ForumForum']['id'], $moveDistance);
						}
						else
						{
							$this->ForumCategory->ForumForum->movedown($movingForum['ForumForum']['id'], $moveDistance);
						}						
						break;
				}
			}
			else
			{
				$refCategory = $this->ForumCategory->find('first', array('conditions' => array('ForumCategory.id' => $refNode[1]), 'contain' => false));

				$movingForum['ForumForum']['forum_category_id'] = $refCategory['ForumCategory']['id'];
				$movingForum['ForumForum']['parent_id'] = $refCategory['ForumCategory']['id'];
				$this->ForumCategory->ForumForum->save($movingForum);
				$this->ForumCategory->ForumForum->updateAll(array('ForumForum.forum_category_id' => $refCategory['ForumCategory']['id']), array('ForumForum.parent_id' => $movingForum['ForumForum']['id']));
			}
		}
		else
		{
			$moveCategory = $this->ForumCategory->find('first', array('conditions' => array('ForumCategory.id' => $nodeToMove[1]), 'contain' => false));
			$refCategory = $this->ForumCategory->find('first', array('conditions' => array('ForumCategory.id' => $refNode[1]), 'contain' => false));
							
			$moveDistance = $moveCategory['ForumCategory']['order'] - $refCategory['ForumCategory']['order'];

			if($moveDistance > 0)
			{
				$this->ForumCategory->moveup($moveCategory['ForumCategory']['id'], $moveDistance);
			}
			else
			{
				$this->ForumCategory->movedown($moveCategory['ForumCategory']['id'], abs($moveDistance));
			}	
		}
		exit;
	}

	function admin_addCategory()
	{
		$this->data['ForumCategory']['title'] = trim($this->data['ForumCategory']['title']);
		$this->ForumCategory->save($this->data);
		
		$fakeForum = array();
		$fakeForum['ForumForum']['forum_category_id'] = $this->ForumCategory->id;
		$fakeForum['ForumForum']['category'] = 1;
		$fakeForum['ForumForum']['parent_id'] = null;
		
		$this->ForumCategory->ForumForum->Behaviors->detach('Acl');
		$this->ForumCategory->ForumForum->save($fakeForum);
		
		$return = array('title' => $this->data['ForumCategory']['title'], 'id' => 'category_' . $this->ForumCategory->id);
		$this->view = 'Json';
		$this->set('data', $return);
		$this->set('json', 'data');
	}

	function admin_addForum()
	{
		if (isset($this->data['ForumForum']['parent_id']))
		{
			$parent = $this->ForumCategory->ForumForum->find('first', array('contain' => false, 'fields' => array('ForumForum.forum_category_id'), 'conditions' => array('ForumForum.id' => $this->data['ForumForum']['parent_id'])));
			$this->data['ForumForum']['forum_category_id'] = $parent['ForumForum']['forum_category_id'];
		}
		elseif (isset($this->data['ForumForum']['forum_category_id']))
		{
			$categoryParent = $this->ForumCategory->ForumForum->find('first', array('contain' => false, 'fields' => array('ForumForum.id'), 'conditions' => array('ForumForum.forum_category_id' => $this->data['ForumForum']['forum_category_id'], 'ForumForum.category' => 1)));
			$this->data['ForumForum']['parent_id'] = $categoryParent['ForumForum']['id'];
		}
		$this->data['ForumForum']['title'] = trim($this->data['ForumForum']['title']);
		
		$this->ForumCategory->ForumForum->save($this->data);
		$return = array('title' => $this->data['ForumForum']['title'], 'id' => 'forum_' . $this->ForumCategory->ForumForum->id);
		$this->view = 'Json';
		$this->set('data', $return);
		$this->set('json', 'data');
	}
	
	function admin_information()
	{
		$node = explode('_', $this->params['named']['node']);
		
		if ($node[0] == 'forum')
		{
			$forum = $this->ForumCategory->ForumForum->find('first', array('contain' => false, 'conditions' => array('ForumForum.id' => $node[1])));
			$numberThreads = $this->ForumCategory->ForumForum->ForumThread->find('count', array('contain' => false, 'conditions' => array('ForumThread.forum_forum_id' => $forum['ForumForum']['id'])));
			$numberViews = $this->ForumCategory->ForumForum->ForumThread->find('first', array('fields' => 'sum(ForumThread.views) as Views', 'contain' => false, 'conditions' => array('ForumThread.forum_forum_id' => $forum['ForumForum']['id'])));
			$threadList = $this->ForumCategory->ForumForum->ForumThread->find('list', array('contain' => false, 'conditions' => array('ForumThread.forum_forum_id' => $forum['ForumForum']['id'])));
			$numberPosts = $this->ForumCategory->ForumForum->ForumThread->ForumPost->find('count', array('contain' => false, 'conditions' => array('ForumPost.forum_thread_id' => array_keys($threadList))));
			$lastPost = $this->ForumCategory->ForumForum->ForumThread->ForumPost->find('first', array('contain' => array('User', 'ForumThread'), 'conditions' => array('ForumPost.forum_thread_id' => array_keys($threadList)), 'order' => array('ForumPost.created DESC')));
			$firstPost = $this->ForumCategory->ForumForum->ForumThread->ForumPost->find('first', array('contain' => false, 'conditions' => array('ForumPost.forum_thread_id' => array_keys($threadList)), 'order' => array('ForumPost.created ASC')));
			$activeUser = $this->ForumCategory->ForumForum->ForumThread->ForumPost->find('first', array('contain' => array('User'), 'conditions' => array('ForumPost.forum_thread_id' => array_keys($threadList)), 'fields' => 'count(user_id) as UserPosts, ForumPost.user_id', 'group' => 'ForumPost.user_id', 'order' => 'UserPosts DESC'));
			$days = ceil((strtotime($lastPost['ForumPost']['created']) - strtotime($firstPost['ForumPost']['created']))/86400);
			$postsPerDay = $days > 0 ? $numberPosts/$days : 0;

			$forumAco = $this->AclExtend->AcoInfo(array('Aco.foreign_key' => $node[1], 'Aco.model' => 'ForumForum'));
			
			$this->set(compact('forum', 'numberThreads', 'numberPosts', 'numberViews', 'lastPost', 'activeUser', 'postsPerDay', 'forumAco'));
		}
		else
		{
			$category = $this->ForumCategory->find('first', array('contain' => false, 'conditions' => array('ForumCategory.id' => $node[1])));
			$forumList = $this->ForumCategory->ForumForum->find('list', array('contain' => false, 'conditions' => array('ForumForum.category' => 0, 'ForumForum.forum_category_id' => $node[1])));
			$numberThreads = $this->ForumCategory->ForumForum->ForumThread->find('count', array('contain' => false, 'conditions' => array('ForumThread.forum_forum_id' => array_keys($forumList))));
			$numberViews = $this->ForumCategory->ForumForum->ForumThread->find('first', array('fields' => 'sum(ForumThread.views) as Views', 'contain' => false, 'conditions' => array('ForumThread.forum_forum_id' => array_keys($forumList))));
			$threadList = $this->ForumCategory->ForumForum->ForumThread->find('list', array('contain' => false, 'conditions' => array('ForumThread.forum_forum_id' => array_keys($forumList))));
			$numberPosts = $this->ForumCategory->ForumForum->ForumThread->ForumPost->find('count', array('contain' => false, 'conditions' => array('ForumPost.forum_thread_id' => array_keys($threadList))));
			$lastPost = $this->ForumCategory->ForumForum->ForumThread->ForumPost->find('first', array('contain' => array('User', 'ForumThread'), 'conditions' => array('ForumPost.forum_thread_id' => array_keys($threadList)), 'order' => array('ForumPost.created DESC')));
			$firstPost = $this->ForumCategory->ForumForum->ForumThread->ForumPost->find('first', array('contain' => false, 'conditions' => array('ForumPost.forum_thread_id' => array_keys($threadList)), 'order' => array('ForumPost.created ASC')));
			$activeUser = $this->ForumCategory->ForumForum->ForumThread->ForumPost->find('first', array('contain' => array('User'), 'conditions' => array('ForumPost.forum_thread_id' => array_keys($threadList)), 'fields' => 'count(user_id) as UserPosts, ForumPost.user_id', 'group' => 'ForumPost.user_id', 'order' => 'UserPosts DESC'));
			$days = ceil((strtotime($lastPost['ForumPost']['created']) - strtotime($firstPost['ForumPost']['created']))/86400);
			$postsPerDay = $days > 0 ? $numberPosts/$days : 0;
			
			$this->set(compact('category', 'numberThreads', 'numberPosts', 'numberViews', 'lastPost', 'activeUser', 'postsPerDay'));
			$this->render('admin_category_information');
		}
	}
	
	function admin_editTitle()
	{
		if(isset($this->data['ForumForum']))
		{
			$this->ForumCategory->ForumForum->save($this->data);
			echo trim($this->data['ForumForum']['title']);
		}
		elseif(isset($this->data['ForumCategory']))
		{
			$this->ForumCategory->save($this->data);
			echo trim($this->data['ForumCategory']['title']);
		}
		exit;
	}
	
	function admin_editDescription()
	{
		if(isset($this->data['ForumForum']))
		{
			$this->ForumCategory->ForumForum->save($this->data);
			echo trim($this->data['ForumForum']['description']);
		}
		exit;
	}	
	
	function admin_deleteForum($id = null)
	{
		if (!count($this->params['form']))
		{
			if ($id != null)
			{
				$thisForum = $this->ForumCategory->ForumForum->find('first', array('contain' => false, 'conditions' => array('ForumForum.id' => $id)));
				$numberThreads = $this->ForumCategory->ForumForum->ForumThread->find('count', array('contain' => false, 'conditions' => array('ForumThread.forum_forum_id' => $thisForum['ForumForum']['id'])));
				$totalChildren = $this->ForumCategory->ForumForum->childCount($id);
				
				$forums = $this->ForumCategory->ForumForum->generatetreelist(array('ForumForum.category' => 0, 'ForumForum.id <>' => $id, 'ForumForum.parent_id <> ' => $id), null,null,'>');
				$forumList = array();
				foreach($forums as $key => $forum)
				{
					$forumList['forum_'.$key] = $forum;
				}
				
				$categories = $this->ForumCategory->find('list', array('contain' => false));
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
					$this->ForumCategory->ForumForum->ForumThread->updateAll(array('ForumThread.forum_forum_id' => $newForum), array('ForumThread.forum_forum_id' => $id));
				}
	
				if ($this->params['form']['forum'] != '')
				{
					$newPlace = explode('_', $this->params['form']['forum']);
					$newPlaceId = Sanitize::paranoid($newPlace[1]);
					
					$update = array();
					if ($newPlace[0] == 'category')
					{
						$category = $this->ForumCategory->ForumForum->find('first', array('fields' => array('ForumForum.id'), 'contain' => false, 'conditions' => array('ForumForum.category' => 1, 'ForumForum.forum_category_id' => $newPlaceId)));
						
						$update['ForumForum.forum_category_id'] = $newPlaceId;
						$update['ForumForum.parent_id'] = $category['ForumForum']['id'];
					}
					else
					{
						$forum = $this->ForumCategory->ForumForum->find('first', array('fields' => array('ForumForum.forum_category_id'), 'contain' => false, 'conditions' => array('ForumForum.id' => $newPlaceId)));
						
						$update['ForumForum.forum_category_id'] = $forum['ForumForum']['forum_category_id'];
						$update['ForumForum.parent_id'] = $newPlaceId;
					}
	
					$this->ForumCategory->ForumForum->updateAll($update, array('ForumForum.parent_id' => $id));
					
					$return['moved'] = $this->params['form']['forum'];
				}
				
				$this->ForumCategory->ForumForum->delete($id, true);
				
				$this->view = 'Json';
				$this->set('data', $return);
				$this->set('json', 'data');
			}
		}
	}
	
	function admin_deleteCategory($id = null)
	{
		if (!count($this->params['form']))
		{
			if ($id != null)
			{
				$thisCategory = $this->ForumCategory->find('first', array('contain' => array('ForumForum' => array('conditions' => array('ForumForum.category' => 0), 'fields' => array('id'))), 
																		'conditions' => array('ForumCategory.id' => $id)));
				$totalChildren = count($thisCategory['ForumForum']);
				
				$categories = $this->ForumCategory->find('list', array('contain' => false, 'conditions' => array('ForumCategory.id <>' => $id)));
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
				$currentCategory = $this->ForumCategory->ForumForum->find('first', array('fields' => array('ForumForum.id'), 'contain' => false, 'conditions' => array('ForumForum.category' => 1, 'ForumForum.forum_category_id' => $id)));
				$newCategory = $this->ForumCategory->ForumForum->find('first', array('fields' => array('ForumForum.id'), 'contain' => false, 'conditions' => array('ForumForum.category' => 1, 'ForumForum.forum_category_id' => $newPlaceId)));
				
				$update['ForumForum.forum_category_id'] = $newPlaceId;
				$update['ForumForum.parent_id'] = $newCategory['ForumForum']['id'];

				$this->ForumCategory->ForumForum->updateAll($update, array('ForumForum.parent_id' => $currentCategory['ForumForum']['id']));

				$updateNew['ForumForum.forum_category_id'] = $newPlaceId;
				$this->ForumCategory->ForumForum->updateAll($updateNew, array('ForumForum.forum_category_id' => $id));
				
				$return['moved'] = $this->params['form']['forum'];
			}
			
			$this->ForumCategory->ForumForum->delete($id, true);
			
			$this->view = 'Json';
			$this->set('data', $return);
			$this->set('json', 'data');
		}
	}
	
	function lock($threadSlug)
	{
		$threadSlug = Sanitize::paranoid($threadSlug);
		$this->ForumThread->updateAll(array('ForumThread.locked' => 'NOT ForumThread.locked'), array('ForumThread.slug' => $threadSlug));

		if($this->_isAjax)
		{
			exit;
		}
		else
		{
			$thread = $this->ForumThread->find('first', array('conditions' => array('ForumThread.slug' => $threadSlug), 'contain' => array('ForumForum')));
			$this->redirect(array('action' => 'forum', $thread['ForumForum']['slug']));
		}
	}
	
	function threadType($threadSlug, $type)
	{
		$thread = $this->ForumThread->find('first', array('conditions' => array('ForumThread.slug' => $threadSlug), 'contain' => array('ForumForum')));

		if ($type == 'sticky' && $thread['ForumThread']['thread_type'] != 'STICKY')
		{
			$newType = 'STICKY';
		}
		elseif ($type == 'announce' && $thread['ForumThread']['thread_type'] != 'ANNOUNCEMENT')
		{
			$newType = 'ANNOUNCEMENT';
		}
		else
		{
			$newType = 'NORMAL';
		}

		$this->ForumThread->id = $thread['ForumThread']['id'];
		$this->ForumThread->saveField('thread_type', $newType);			
		
		if($this->_isAjax)
		{
			exit;
		}
		else
		{
			$this->redirect(array('action' => 'forum', $thread['ForumForum']['slug']));
		}
	}	
}
?>