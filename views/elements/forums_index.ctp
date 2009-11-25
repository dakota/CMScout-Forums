<?php
	$cacheName = $plugin . '.forums.index';
	
	if(($results = Cache::read($cacheName, 'core')) === false)
	{
		$Category = ClassRegistry::init($plugin . '.Category');
		
		$categories = $Category->fetchCategories(null, $userInfo['User']['id']);
		
		$results = compact('categories');
		
		Cache::write($cacheName, $results, 'core');
	}
	
	echo $this->element('../forums/index', array_merge(array('plugin' => 'forums'), $results));