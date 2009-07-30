<?php
	$css->link('/forums/css/forums', null, array(), false);

	 $javascript->link('tiny_mce/tiny_mce_gzip', false);
	 $javascript->link('tinyMCE.gz.bbcode', false);
	 $javascript->link('tinyMCE.init.bbcode', false);

	$html->addCrumb('Forums', array('controller' => 'forums', 'plugin' => 'forums', 'action' => 'index'));
	foreach ($breadcrumbs as $key => $crumb)
	{
		if ($key == 0)
			$html->addCrumb($crumb['title'], array('controller' => 'forums', 'plugin' => 'forums', 'action' => 'index', $crumb['slug']));
		else
			$html->addCrumb($crumb['title'], array('controller' => 'forums', 'plugin' => 'forums', 'action' => 'forum', $crumb['slug']));
	}

	echo $html->getCrumbs(' > ');

	$paginator->options(array('url'=>$this->passedArgs));
?>
<table class="forumTable">
	<tr>
		<th colspan="2"><?php echo $thread['Thread']['title'];?></th>
	</tr>
<?php foreach($posts as $post) :?>
	<tr class="postRow">
		<td>
			<div class="username"><?php echo $post['CreatedBy']['username'];?></div>

			<?php if($post['CreatedBy']['avatar'] != ''):?>
				<div class="avatar">
					<?php echo $html->image('/avatars/' . $post['CreatedBy']['avatar']); ?>
				</div>
			<?php endif;?>
		</td>
		<td width="80%">
			<a name="<?php echo $post['Post']['id']; ?>"></a>

			<?php if ($post['Post']['title'] != '') :?>
				<div class="subject">
					<?php echo $post['Post']['title'];?>
				</div>
			<?php endif; ?>

			<div class="created">
				<?php echo $time->niceShort($post['Post']['created']); ?>
			</div>

			<div class="post" rel="<?php echo $post['CreatedBy']['username'];?>" id="<?php echo $post['Post']['id']; ?>">
				<?php echo $bbcode->parse($post['Post']['text'], $html->url(array('controller' => 'forums', 'plugin' => 'forums', 'action' => 'thread', $thread['Thread']['slug'])));?>
			</div>
			<div class="editor" style="display:none;">
			</div>

			<div class="actions">
				<?php echo $html->link('Edit', array('controller' => 'forums', 'plugin' => 'forums', 'action' => 'editPost', $post['Post']['id']), array('class' => 'edit'))?>&nbsp;
				<a href="#quickReply" class="quickReply">Quick reply to this message</a>
			</div>
			<?php if($post['CreatedBy']['signature'] != ''):?>
				<div class="signature">
					<?php echo $post['CreatedBy']['signature']; ?>
				</div>
			<?php endif;?>
			<?php if(isset($post['ModifiedBy']['username']) && $post['Post']['created'] != $post['Post']['modified']) :?>
				<div class="edited">
					Last edited by: <?php echo $post['ModifiedBy']['username']; ?> on <?php echo $time->nice($post['Post']['modified']);?>
					<?php if ($post['Post']['edit_reason'] != ''):?>
					<br><em>Reason for edit: <?php echo $post['Post']['edit_reason']; ?></em>
					<?php endif;?>
				</div>
			<?php endif; ?>
		</td>
	</tr>
<?php endforeach;?>
</table>
<div class="paginate">
	<ul>
		<?php
			echo '<li class="count">'.$paginator->counter('Page %page% of %pages%').'</li> ';
			echo $paginator->hasPrev() ? $paginator->first('<< First',array('separator' => null, 'tag' => 'li')) : '';
			echo $paginator->hasPrev() ? '<li>' . $paginator->prev('<', array('tag' => 'li')) . '</li>' : '';
			echo $paginator->numbers(array('separator' => null, 'tag' => 'li'));
			echo $paginator->hasNext() ? '<li>' . $paginator->next('>', array('tag' => 'li')) . '</li>' : '';
			echo $paginator->hasNext() ? $paginator->last('Last >>',array('separator' => null, 'tag' => 'li')) : '';
		?>
	</ul>
</div>

<div id="quickReply">
<h2>Quick Reply</h2><a name="quickReply"></a>
<?php
	 echo $form->create('Post', array('url' => array('controller' => 'forums', 'action' => 'reply', $thread['Thread']['slug'])));
	 echo $form->input('text', array('label' => 'Message', 'type' => 'textbox', 'rows' => 15, 'style' => 'width: 100%','class' => 'mceEditor', 'id' => 'postText'));
	 echo $form->input('subscribe', array('label' => 'Notify me if anybody replies to this thread.', 'type' => 'checkbox', 'checked' => 1));
?>
	<div class="submit">
		<input type="submit" id="replyButton" name="reply" value="Post reply">&nbsp;
		<input type="submit" name="advanced" value="Go Advanced">
	</div>
</div>

<script type="text/javascript">
String.prototype.trim = function() {
	return this.replace(/^\s+|\s+$/g,"");
}

	<?php if ($paginator->hasNext()) :?>
	$("#replyButton").click(function(){
		jConfirm('You are not on the last page of this thread, are you sure you wish to post your message?', 'Post message', function(selection){
			if (selection)
			{
				$("#PostAddForm").submit();
			}
		});
		return false;
	});
	<?php endif;?>

	$(".edit").click(function() {
		var post = $(this).parent('div').siblings('.post');
		var editor = $(this).parent('div').siblings('.editor');
		var _this = $(this);

		if (editor.css('display') == 'none')
		{
			_this.before('<span class="loading">Loading</span>');
			editor.load($(this).attr('href'), function() {
				_this.siblings('.loading').remove();
				editor.show();
				post.hide();
				editor.find('.cancel').click(function() {
					var textBox = editor.find('textarea');
					tinyMCE.execCommand('mceRemoveControl', null, textBox.attr('id'));
					editor.hide().html('');
					post.show();
				});

				editor.find('.delete').click(function() {
					jConfirm('Are you sure you want to delete this post?', 'Delete post', function(selection){
						if (selection)
						{
							window.location = '<?php echo $html->url(array('controller' => 'forums', 'plugin' => 'forums', 'action' => 'deletePost')); ?>/' + post.attr('id');
						}
					});
				});
			});
			return false;
		}
	});

	$(".quickReply").click(function() {
		var post = $(this).parent('div').siblings('.post')

		var quote = '[quote=' + post.attr('rel') + ';' + post.attr('id') + ']' +
							post.html().trim() + '[/quote]';
		tinyMCE.execInstanceCommand('postText', 'mceInsertContent', false, quote, false);
	});

	$("#autoFill").click(function() {
		tinyMCE.triggerSave();

		var value = 'text=' + $("#postText").val().replace(/<[^>]*>/g, "");

		$.post('<?php echo $html->url(array('plugin' => 'forums', 'controller' => 'forums', 'action' => 'autoTag', 'admin' => false)); ?>', value, function(response) {
			$("#postTags").val(response);
		});

		return false;
	});
</script>