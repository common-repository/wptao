<?php
/*
Plugin Name: WordPress淘宝客插件
Author: 水脉烟香
Author URI: https://wptao.com/smyx
Plugin URI: https://wptao.com/wptao.html
Description: 匹配不同的淘宝客主题，实现自动填充商品信息及推广链接(CPS)。（目前支持多麦CPS广告联盟(仅推广链接)、淘宝网、天猫、京东、国美、苏宁、当当网、亚马逊、聚划算、网易考拉等）
Version: 3.6
*/

define('WPTAO_V', '3.6');
define("WPTAO_ULTIMATE", false);
define("WPTAO_URL", plugins_url('wptao'));
define("WPTAO_OPTIONS", 'wptao_options');
$wptao_options = get_option(WPTAO_OPTIONS, array());
// < 3.3
if (!$wptao_options) {
	$wptao_options = get_option('wptao', array());
	if ($wptao_options) {
		if ($wptao_options['cj']) $wptao_options['cj_open'] = 1;
		update_option(WPTAO_OPTIONS, $wptao_options);
	}
}
// 旧数据转换 V4.1
if (!isset($wptao_options['union']) && isset($wptao_options['pid'])) {
	$wptao_options['union'] = array();
	foreach(array('tb_appkey', 'tb_appsecret', 'dm_siteid', 'jd_appkey', 'jd_secret', 'jd_appkey', 'jd_secret', 'jd_pid', 'sn_appkey', 'sn_secret', 'sn_pid', 'pdd_appkey', 'pdd_secret', 'pdd_pid', 'kaola', 'kaola_key', 'dangdang_from', 'z_tag', 'z_tag_com') as $k => $v) {
		if (isset($wptao_options[$v])) {
			if ($v == 'tb_appkey') $wptao_options['union']['appkey'] = $wptao_options[$v];
			elseif ($v == 'tb_appsecret') $wptao_options['union']['secret'] = $wptao_options[$v];
			elseif ($v == 'dangdang_from') $wptao_options['union']['from'] = $wptao_options[$v];
			else $wptao_options['union'][$v] = $wptao_options[$v];
			unset($wptao_options[$v]);
		} 
	} 
	$wptao_options['union'] = array_filter(array_map('trim', $wptao_options['union']));
	update_option(WPTAO_OPTIONS, $wptao_options);
}
if ($wptao_options['caiji']['mm_link']) {
	define("WPTAO_BUYKEY", $wptao_options['caiji']['mm_link']);
} else {
	unset($wptao_options['cj_open']);
}
if ($wptao_options['box']) unset($wptao_options['item']);
// 价格
function wptao_price($price, $before = 0, $after = 0) {
	return ($before ? '￥' : '') . $price . (!$before && $after ? '元' : '');
} 

function wptao_options($field) {
	global $wptao_options;
	return $wptao_options[$field];
}

include(dirname(__FILE__) . '/admin/admin.php');
include(dirname(__FILE__) . '/functions.php');

?>