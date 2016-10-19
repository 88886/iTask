<?php
/**
 * template_lite {capture}{/capture} block plugin
 *
 * Type:     block function
 * Name:     capture
 * Purpose:  removes content and stores it in a variable
 */
function tpl_block_capture($params, $content, &$tpl)
{
    if($content===null) return false;

	extract($params);

	if (isset($name)){
		$buffer = $name;
	}else{
		$buffer = "'default'";
	}

	$tpl->_iTPL_VARS['capture'][$buffer] = $content;
	if (isset($assign)){
		$tpl->assign($assign, $content);
	}
	return true;
}
?>
