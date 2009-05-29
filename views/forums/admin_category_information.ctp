<?php echo $html->link('Delete Category', array('action' => 'deleteCategory', $category['ForumCategory']['id']), array('id' => 'delete'));?>
<h2>Category information</h2>
<table width="100%">
	<tr>
		<th width="30%" style="text-align:right;padding-right: 10px;">Category title</th>
		<td style="text-align:left;">
			<div style="float:right;">
				<a href="#" id="editTitle">Edit</a>
			</div>
			<span id="title"><?php echo $category['ForumCategory']['title'];?></span>
		</td>
	</tr>
	<tr class="altrow">
		<th width="30%" style="text-align:right;padding-right: 10px;">Number of threads</th>
		<td style="text-align:left;"><?php echo $numberThreads;?></td>
	</tr>
	<tr>
		<th width="30%" style="text-align:right;padding-right: 10px;">Number of posts</th>
		<td style="text-align:left;"><?php echo $numberPosts;?></td>
	</tr>
	<tr class="altrow">
		<th width="30%" style="text-align:right;padding-right: 10px;">Number of thread views</th>
		<td style="text-align:left;"><?php echo $numberViews[0]['Views'];?></td>
	</tr>
	<tr>
		<th width="30%" style="text-align:right;padding-right: 10px;">Most active user</th>
		<td style="text-align:left;"><?php if (isset($activeUser['User']['username'])):?><?php echo $activeUser['User']['username'];?> with <?php echo $activeUser[0]['UserPosts'];?> posts<?php else :?>No posts<?php endif;?></td>
	</tr>
	<tr class="altrow">
		<th width="30%" style="text-align:right;padding-right: 10px;">Average posts per day</th>
		<td style="text-align:left;"><?php echo round($postsPerDay, 2);?></td>
	</tr>	
</table>