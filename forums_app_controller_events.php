<?php
class ForumsAppControllerEvents extends AppControllerEvents
{
	function onAdminLinks($event)
	{
		$adminLinks = array();
		if(in_array($this->params['plugin'], $event->installedPlugins))
		{
			$adminLinks[] = array('name' => 'Forum Manager',
									'plugin' => $this->params['plugin'],
									'controller' => 'forums',
									'action' => 'index');
		}

		return $adminLinks;
	}
}
?>