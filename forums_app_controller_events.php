<?php
class ForumsAppControllerEvents extends AppControllerEvents
{
	function onAdminLinks($event)
	{
		if(isset($event->Controller->_enabledPlugins) && isset($event->Controller->_enabledPlugins[$this->params['plugin']]))
		{
			$adminLinks = array();
			$adminLinks[] = array('name' => 'Forum Manager',
									'plugin' => $this->params['plugin'],
									'controller' => 'forums',
									'action' => 'index');
			return $adminLinks;
		}
	}
}
?>