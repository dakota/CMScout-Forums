<?php echo $html->link('Delete Forum', array('action' => 'deleteForum', $forum['ForumForum']['id']), array('id' => 'delete'));?>
<h2>Forum information</h2>
<table width="100%">
	<tr>
		<th width="30%" style="text-align:right;padding-right: 10px;">Forum title</th>
		<td style="text-align:left;">
			<div style="float:right;">
				<a href="#" id="editTitle">Edit</a>
			</div>
			<span id="title"><?php echo $forum['ForumForum']['title'];?></span>
		</td>
	</tr>
	<tr class="altrow">
		<th width="30%" style="text-align:right;padding-right: 10px;">Forum description</th>
		<td style="text-align:left;">
			<div style="float:right;">
				<a href="#" id="editForumDesc">Edit</a>
			</div>
			<span id="forumDesc"><?php echo $forum['ForumForum']['description'];?></span>
		</td>
	</tr>
	<tr>
		<th width="30%" style="text-align:right;padding-right: 10px;">Number of threads</th>
		<td style="text-align:left;"><?php echo $numberThreads;?></td>
	</tr>
	<tr class="altrow">
		<th width="30%" style="text-align:right;padding-right: 10px;">Number of posts</th>
		<td style="text-align:left;"><?php echo $numberPosts;?></td>
	</tr>
	<tr>
		<th width="30%" style="text-align:right;padding-right: 10px;">Number of thread views</th>
		<td style="text-align:left;"><?php echo $numberViews[0]['Views'];?></td>
	</tr>
	<tr class="altrow">
		<th width="30%" style="text-align:right;padding-right: 10px;">Most active user</th>
		<td style="text-align:left;"><?php if (isset($activeUser['User']['username'])):?><?php echo $activeUser['User']['username'];?> with <?php echo $activeUser[0]['UserPosts'];?> posts<?php else :?>No posts<?php endif;?></td>
	</tr>
	<tr>
		<th width="30%" style="text-align:right;padding-right: 10px;">Average posts per day</th>
		<td style="text-align:left;"><?php echo round($postsPerDay, 2);?></td>
	</tr>	
</table>

<?php if ($forumAco !== false) :?>
<h2>Forum permissions</h2>
<table width="100%">
<tr>
	<th>User/Group</th>
<?php 
 	$details = explode(',', $forumAco[0]['explanation']);		
	
 	$useItems = array();
 	foreach ($details as $key => $detail)
 	{
 		if (isset($detail) && $detail != '')
 		{
 			echo '<th>' . $detail . '</th>';
 			$useItems[] = $key;
 		}
 	}
?>
</tr>
<?php $i=0;foreach ($forumAco as $item) :?>
	<tr <?php echo ($i++%2==1)?'class="altrow"':'';?>>
		<td style="text-align:left;"><?php echo $item['info'][$item['Aro']['model'] == 'User' ? 'username' : 'title'] . ' ('.$item['Aro']['model'].')';?></td>
		<?php $k=0;foreach ($item['permissions'] as $key => $permission):?>
			<?php if (in_array($k, $useItems)) :?>
				<td><?php echo ($permission == 1) ? 'Allowed' : 'Denied';?></td>
			<?php endif; $k++;?>
		<?php endforeach;?>
	</tr>
<?php endforeach;?>
</table>
<?php endif;?>