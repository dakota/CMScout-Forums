<?php 
echo $form->input('forum', array(
	'options' => $forums, 
	'empty' => __('All forums', true), 
	'name' => $this->params['named']['fieldName'] . '[forum]',
	'selected' => $values['forum']
));
?>