<?php 
echo $form->input('forum', array(
	'options' => $forums, 
	'empty' => __('All forums', true),
	'name' => 'forum'
));
?>