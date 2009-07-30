<?php 
class ForumTreeHelper extends AppHelper
{
  var $tab = "	";
  
  function show($data)
  {
    $output = $this->list_element($data, 0);
    
    return $this->output($output);
  }
  
  function list_element($data, $level)
  {
  	if (count($data))
  	{
	  	$modelName = $level == 0 ? 'Category' : 'Forum';
	  	$levelType = $level == 0 ? 'category' : 'forum';
	  	
	    $tabs = "\n" . str_repeat($this->tab, $level * 2);
	    $li_tabs = $tabs . $this->tab;
	    
	    $output = $tabs. "<ul>";
	    foreach ($data as $key=>$val)
	    {
	      $output .= $li_tabs . '<li rel="'.$levelType . '" '.($level == 0 ? 'class="open"' : '').' id="'.$levelType.'_'.$val[$modelName]['id'].'">';
	      $output .= '<a href="#" class="'.$levelType.'Tree">';
	      $output .= $val[$modelName]['title'];
	      $output .= '</a>';
	      if(isset($val['children'][0]))
	      {
	        $output .= $this->list_element($val['children'], $level+1);
	        $output .= $li_tabs . "</li>";
	      }
	      else
	      {
	        $output .= "</li>";
	      }
	    }
	    $output .= $tabs . "</ul>";
	    
	    return $output;
  	}
  }
}
?> 