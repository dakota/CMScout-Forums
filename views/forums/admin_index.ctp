<?php 
	$html->css('tree_component', null, array(), false);
	$html->css('tree_component_theme', null, array(), false);
	
	$javascript->link('/forums/js/forums_admin_index', false);
	$javascript->link('tree_component', false);
	$javascript->link('css', false);
	$javascript->link('jquery.blockui', false);
	$javascript->link('json_encode', false);
	$javascript->link('jquery.qq', false);
	//pr($categories);
?>
<table style="border:0px;width:100%">
<tr>
<td colspan="2" style="text-align: right;">
<a href="#" id="addCategory"><?php __('Add category'); ?></a>
&nbsp;|&nbsp;
<a href="#" id="addForum"><?php __('Add forum'); ?></a>
</td>
</tr>
<tr style="vertical-align:top">
<td style="width:25%;text-align:left;">
	<div id="forums">
	<ul>
		<li class="open" id="root" rel="root"><a>Forums</a>
				<?php echo $forumTree->show($categories);?>
		</li>
	</ul>
	</div>
</td>
<td id="sideInfo" style="text-align:left;"></td>
</tr>
</table>

<div id="newCatDialog" style="display: none;">
	<div class="input text">
		<label for="catTitle"><?php __('Title'); ?></label>
		<input name="data[Category][title]" type="text" maxlength="255" value="" id="catTitle" style="width: 95%;" />
	</div>
</div>

<div id="newForumDialog" style="display: none;">
	<div class="input text">
		<label for="forumTitle"><?php __('Title'); ?></label>
		<input name="title" type="text" maxlength="255" value="" id="forumTitle" style="width: 95%;" />
		<label for="forumDescription"><?php __('Description'); ?></label>
		<textarea name="description" id="forumDescription" style="width: 95%;" rows="2"></textarea>
	</div>
</div>

<div id="debug">
</div>
