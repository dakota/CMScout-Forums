<?php 
/* SVN FILE: $Id$ */
/* Cmscout schema generated on: 2009-11-13 07:11:50 : 1258095950*/
class ForumsSchema extends CakeSchema {
	var $name = 'Forums';

	function before($event = array()) {
		return true;
	}

	function after($event = array()) {
	}

	var $categories = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
		'slug' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 300),
		'title' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 400),
		'order' => array('type' => 'integer', 'null' => false, 'default' => NULL),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1)),
		'tableParameters' => array('charset' => 'latin1', 'collate' => 'latin1_swedish_ci', 'engine' => 'MyISAM')
	);
	var $forums = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
		'slug' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 300, 'key' => 'index'),
		'title' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 400),
		'description' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 512),
		'category_id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'index'),
		'parent_id' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'key' => 'index'),
		'lft' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'index'),
		'rght' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'index'),
		'category' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 4),
		'thread_count' => array('type' => 'integer', 'null' => false, 'default' => NULL),
		'post_count' => array('type' => 'integer', 'null' => false, 'default' => NULL),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'forum_category_id' => array('column' => 'category_id', 'unique' => 0), 'parent_id' => array('column' => 'parent_id', 'unique' => 0), 'lft' => array('column' => 'lft', 'unique' => 0), 'rght' => array('column' => 'rght', 'unique' => 0), 'slug' => array('column' => 'slug', 'unique' => 0)),
		'tableParameters' => array('charset' => 'latin1', 'collate' => 'latin1_swedish_ci', 'engine' => 'MyISAM')
	);
	var $posts = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
		'slug' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 300, 'key' => 'index'),
		'title' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 400),
		'text' => array('type' => 'text', 'null' => false, 'default' => NULL),
		'tags' => array('type' => 'string', 'null' => false, 'default' => NULL),
		'edit_reason' => array('type' => 'string', 'null' => false, 'default' => NULL),
		'thread_id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'index'),
		'forum_id' => array('type' => 'integer', 'null' => false, 'default' => NULL),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'created_by' => array('type' => 'integer', 'null' => false, 'default' => NULL),
		'modified_by' => array('type' => 'integer', 'null' => false, 'default' => NULL),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'forum_thread_id' => array('column' => 'thread_id', 'unique' => 0), 'slug' => array('column' => 'slug', 'unique' => 0)),
		'tableParameters' => array('charset' => 'latin1', 'collate' => 'latin1_swedish_ci', 'engine' => 'MyISAM')
	);
	var $subscribers = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
		'user_id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'index'),
		'thread_id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'index'),
		'active' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 4),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'user_id' => array('column' => 'user_id', 'unique' => 0), 'forum_thread_id' => array('column' => 'thread_id', 'unique' => 0)),
		'tableParameters' => array('charset' => 'latin1', 'collate' => 'latin1_swedish_ci', 'engine' => 'MyISAM')
	);
	var $threads = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
		'slug' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 300, 'key' => 'index'),
		'title' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 400),
		'description' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 512),
		'views' => array('type' => 'integer', 'null' => false, 'default' => NULL),
		'thread_type' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => 2),
		'locked' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 4),
		'forum_id' => array('type' => 'integer', 'null' => false, 'default' => NULL),
		'post_count' => array('type' => 'integer', 'null' => false, 'default' => NULL),
		'created_by' => array('type' => 'integer', 'null' => false, 'default' => NULL),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'slug' => array('column' => 'slug', 'unique' => 0)),
		'tableParameters' => array('charset' => 'latin1', 'collate' => 'latin1_swedish_ci', 'engine' => 'MyISAM')
	);
	var $unread_posts = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
		'user_id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'index'),
		'thread_id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'index'),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'user_id' => array('column' => 'user_id', 'unique' => 0), 'forum_thread_id' => array('column' => 'thread_id', 'unique' => 0)),
		'tableParameters' => array('charset' => 'latin1', 'collate' => 'latin1_swedish_ci', 'engine' => 'MyISAM')
	);
}
?>