<?php
class ForumsEvents extends Object
{

	function onAdminLinks($event)
	{
		$adminLinks = array(
			array(
				'title' => 'Forum Manager',
				'plugin' => Inflector::underscore($event->plugin['name']),
				'controller' => 'forums',
				'action' => 'index'
			)
		);
		
		return $adminLinks;
	}

	function onGetMenuLinks($event)
	{
		$menuLinks = array(
			array(
				'title' => 'Forum',
				'plugin' => $event->plugin,
				'controller' => 'forums',
				'action' => 'index',
				'edit_action' => 'menuEdit'
			)
		);

		return $menuLinks;
	}

	function onInstall($event)
	{
		$event->controller->AclExtend->addAcoNode('Administration panel/Forum Manager', 'Create|Create forums,Read|Access Forum Manager,Update|Edit forums,Delete|Delete forums');
		$event->controller->AclExtend->addAcoNode($event->installInfo['Plugin']['title'], 'Create|Post new thread,Read|View forum,Update|Edit own posts,Delete|Delete own posts,Reply|Reply to thread,Moderate|Moderate Forum,Sticky|Create stick threads,Announcement|Create announcement threads');

		$configOptions = array(
			array(
				'name' => 'PageTopics',
				'value' => '20',
				'category_name' => $event->installInfo['Plugin']['title'],
				'input_type' => 'number',
				'label' => 'Number of threads per page'
			),
			array(
				'name' => 'PagePosts',
				'value' => '20',
				'category_name' => $event->installInfo['Plugin']['title'],
				'input_type' => 'number',
				'label' => 'Number of posts per page'
			),
			array(
				'name' => 'InlineReply',
				'value' => '1',
				'category_name' => $event->installInfo['Plugin']['title'],
				'input_type' => 'checkbox',
				'label' => 'Show quick reply box'
			),
			array(
				'name' => 'editorType',
				'value' => '0',
				'category_name' => $event->installInfo['Plugin']['title'],
				'input_type' => 'select',
				'label' => 'Type of editor',
				'options' => 'BBCode,Simple,Advanced,None'
			)
		);

		$event->controller->CmscoutCore->addConfigurationOptions($configOptions);
	}
}
?>