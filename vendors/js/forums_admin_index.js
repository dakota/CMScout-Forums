$(function()
{
	var saveQueue = function(item) {
		    var that = this;
			$.get(controllerLink + 'move/node:'+item['node'].id+'/ref_node:'+(item['ref_node'] === -1 ? 0 : item['ref_node'].id)+'/move_type:'+item['move_type'], function(response) {
				$.qqNext(that.id);
			});	
		  };
	var doneQueue = function() {
		$.jGrowl('Saved');
	};
	
	var queue_id = $.qq({ oneach: saveQueue, ondone: doneQueue, delay: null });

 	tree1 = $.tree_create(); 
 	tree1.init($("#forums"), {
 			lang : {
 				new_node: "New category/forum"
 			},
 			ui : {
 				theme_path: rootLink + 'themed/' + themeDir + '/img/'
 			},
 			rules : {
 				use_inline : true, 
 				deletable: ['category', 'forum'],
 				clickable: ['category', 'forum'],
 				draggable : ['category', 'forum'], 
 				dragrules : [ "forum inside forum", "forum inside category","forum after forum", "forum before forum", "category after category", "category before category", 'category inside root'],
 				creatable: ['root', 'category', 'forum'],
 				renameable: ['category', 'forum']
 			}, 
 			callback : { 
 				onmove : function(node,ref_node,move_type) {
 							var item = {};
 							item['node'] = node;
 							item['ref_node'] = ref_node;
 							item['move_type'] = move_type;
 							$.jGrowl('Saving');
 							$.qqAdd(queue_id, item);
						},
 				onchange: function(node, tree_obj) {
										$("#sideInfo").html('<div class="loading">&nbsp;</div>');
										$("#sideInfo").load(controllerLink + 'information/node:' + node.id);
 									},
 				error       : function(TEXT, TREE_OBJ) { console.log(TEXT);}
 			}
 		}); 

	$("#addCategory").click(function(e){
		$("#newCatDialog").dialog('open');

		return false;
	});

	$("#addForum").click(function(e){
		if (typeof tree1.selected != 'undefined')
			$("#newForumDialog").dialog('open');
		else
			jAlert('Please select a category or forum');

		return false;
	});
	
	$("#newCatDialog").dialog({
		autoOpen: false,
		bgiframe: true,
		height: 175,
		modal: true,
		title: 'Add new category',
		resizable: false,
		overlay: {
			backgroundColor: '#000',
			opacity: 0.5
		},
		buttons: {
			'Add': function() {
				$(this).dialog('close');

				var postData = 'data[ForumCategory][title]=' + $("#catTitle").val();

				$("#catTitle").val('');
				
				$.blockUI({message: '<img src="' +rootLink + '/img/throbber.gif" /> Saving...'});

				$.post(controllerLink + 'addCategory', postData, function(returnData) {
					$.unblockUI();
					$.jGrowl('Saved');
					tree1.create({data: {title: returnData['title']}, attributes: {id : returnData['id'], rel : "category"}}, $("#root"));
				}, 'json');
			},
			'Cancel': function() {
				$(this).dialog('close');
			}
		}
	});	

	
	$("#newForumDialog").dialog({
		autoOpen: false,
		bgiframe: true,
		height: 420,
		modal: true,
		title: 'Add new forum',
		resizable: false,
		overlay: {
			backgroundColor: '#000',
			opacity: 0.5
		},
		buttons: {
			'Add': function() {
				$(this).dialog('close');

				var selectedId = tree1.selected.attr('id').split('_');
				
				if (selectedId[0] == 'category')
				{
					var postData = 'data[ForumForum][title]=' + $("#forumTitle").val() + '&data[ForumForum][description]=' + $("#forumDescription").val() + '&data[ForumForum][forum_category_id]=' + selectedId[1];
				}
				else
				{
					var postData = 'data[ForumForum][title]=' + $("#forumTitle").val() + '&data[ForumForum][description]=' + $("#forumDescription").val() + '&data[ForumForum][parent_id]=' + selectedId[1];
				}

				$("#forumTitle").val('');
				$("#forumDescription").val('');
				
				$.blockUI({message: '<img src="' +rootLink + '/img/throbber.gif" /> Saving...'});
				$.post(controllerLink + 'addForum', postData, function(returnData) {
					$.unblockUI(); 
					$.jGrowl('Saved');
					tree1.create({data: {title: returnData['title']}, attributes: {id : returnData['id'], rel : "forum"}});
				}, 'json');
			},
			'Cancel': function() {
				$(this).dialog('close');
			}
		}
	});
	
	$("#editTitle").live('click', function(){
		var titleSpan = $(this).parents('div').siblings('#title');
		var currentTitle = $(this).parents('div').siblings('#title').html();
		
		titleSpan.html('<input id="titleEdit" type="text" value="'+currentTitle+'">');
		$("#titleEdit").focus();
		$("#titleEdit").select();
		
		var saveFunction = function(_this){
			if (!$(_this).parents('span')) _this = this;
			var titleSpan = $(_this).parents('span');
			var itemId = tree1.selected.attr('id').split('_');
			var value = $(_this).val();
			
			if (value != currentTitle)
			{
				titleSpan.html('<img src="' +rootLink + '/img/throbber.gif" /> Saving...');
				if (itemId[0] == 'category')
				{
					var postData = 'data[ForumCategory][title]=' + value + '&data[ForumCategory][id]=' + itemId[1];
				}
				else
				{
					var postData = 'data[ForumForum][title]=' + value + '&data[ForumForum][id]=' + itemId[1];
				}
				
				$.post(controllerLink + 'editTitle', postData, function(returnData){
					titleSpan.html(returnData);
					tree1.rename(null, returnData);
				});
			}
			else
			{
				titleSpan.html(currentTitle);
			}
			$(this).unbind();
		};
		
		$("#titleEdit").bind('blur', saveFunction)
						.bind('keypress', function(e){
							var key = e.keyCode || e.which;
							if (key == 13)
							{
								saveFunction(this);
							}
						});
		
		return false;
	});
	
	$("#editForumDesc").live('click', function(){
		var titleSpan = $(this).parents('div').siblings('#forumDesc');
		var currentTitle = $(this).parents('div').siblings('#forumDesc').html();
		
		titleSpan.html('<input id="forumDescEdit" type="text" value="'+currentTitle+'">');
		$("#forumDescEdit").focus();
		$("#forumDescEdit").select();
		
		var saveFunction = function(_this){
			if (!$(_this).parents('span')) _this = this;
			var titleSpan = $(_this).parents('span');
			var itemId = tree1.selected.attr('id').split('_');
			var value = $(_this).val();
			
			if (value != currentTitle)
			{
				titleSpan.html('<img src="' +rootLink + '/img/throbber.gif" /> Saving...');

				var postData = 'data[ForumForum][description]=' + value + '&data[ForumForum][id]=' + itemId[1];
				
				$.post(controllerLink + 'editDescription', postData, function(returnData){
					titleSpan.html(returnData);
				});
			}
			else
			{
				titleSpan.html(currentTitle);
			}
			$(this).unbind();
		};
		
		$("#forumDescEdit").bind('blur', saveFunction)
						.bind('keypress', function(e){
							var key = e.keyCode || e.which;
							if (key == 13)
							{
								saveFunction(this);
							}
						});
		
		return false;
	});
	
	$("#delete").live('click', function(){
		var _this = this;
		$("#genericDialog").html('<div class="loading">&nbsp;</div>');
		$("#genericDialog").load($(this).attr('href'), function(){
			var buttons = {
					'Cancel' : function() {
						$("#genericDialog").dialog('option', 'buttons', {});
						$("#genericDialog").dialog('close');
					},
					'Delete' : function() {
						var threads = $("#deleteThreads").val();
						var forums = $("#deleteForums").val();
						$("#genericDialog").dialog('option', 'buttons', {});
						
						$("#genericDialog").html('<div class="loading">&nbsp;</div>');
						$.post($(_this).attr('href'), {'thread': threads, 'forum' : forums}, function (response) {
							$("#genericDialog").dialog('close');
							$.jGrowl('Deleted');

							if (typeof response['moved'] != 'undefined')
							{
								var children = tree1.children(tree1.selected);
								
								for(var i=0;i<children.length;i++)
								{
									tree1.cut(children[i]);
									tree1.paste($("#" + response['moved']), 'inside', false);
								}
							}
							
							tree1.remove();
							$("#genericDialog").dialog('option', 'buttons', {});
						}, 'json');
					}
				};
			$("#genericDialog").dialog('option', 'buttons', buttons);			
		});
		$("#genericDialog").dialog('option', 'position', 'middle');
		$("#genericDialog").dialog('option', 'min-height', '0px');
		$("#genericDialog").dialog('option', 'height', 'auto');
		$("#genericDialog").dialog('option', 'title', 'Forum delete');
		$("#genericDialog").dialog('open');
		return false;
	});
});