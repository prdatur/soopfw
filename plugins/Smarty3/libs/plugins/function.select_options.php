<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty {select_options} function plugin
 *
 * Type:     function<br>
 * Name:     select_options<br>
 * Purpose:  print out page bar
 * @author Christian Ackermann
 * @param array parameters
 * 									count = complete entry count
 * 									link = the link which will be used , use %count% for pagelink
 * 									currentPage = current page number
 * 									range = the max range ( default 10 )
 * 									maxEntriesPerPage = self explaining ( default 10 )
 * @return string|null
 */
function smarty_function_select_options($params)
{

	if(empty($params['from']))
    {
    	return;
    }

	$selectVal = "k";
	if(!empty($params['select_type']) && $params['select_type'] = "value")
	{
		$selectVal = "v";
	}

	if(isset($params['from']['data']) && isset($params['from']['default']))
	{
		$params['select_type'] = $params['from']['default'];
		$params['lang_key'] = $params['from']['lang_key'];
		$tmpArr = array();
		foreach($params['from']['data'] AS $v)
		{
			$tmpArr[$v] = $v;
		}
		$params['from'] = $tmpArr;
		unset($tmpArr);
	}

	if(isset($params['post_name']) && isset($_POST[$params['post_name']]))
	{
		$params['post'] = $_POST[$params['post_name']];
	}

	if(!isset($params['post']))
	{
		$params['post'] = "";
	}
	
	$options = array();
	foreach($params['from'] AS $k=>$v)
	{
		$checkVal = ($selectVal=="k") ? $k : $v;
		$selected = "";
		if((!empty($params['default']) && empty($params['post']) && $checkVal == $params['default']) ||
		   (!empty($params['post']) && $checkVal == $params['post']))
		{
			$selected = " selected='selected'";
		}

		if(isset($params['lang_key']))
		{
			$v = $GLOBALS['core']->lng->get($params['lang_key'], $v);
		}
		$options[] = "<option value=\"".$k."\"".$selected.">".$v."</option>";
	}
	return implode("\n", $options);
}

/* vim: set expandtab: */

?>
