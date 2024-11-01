<?php
/**
 * PHP相关
 */
// AJAX
if(!function_exists('__is_ajax')) {
    function __is_ajax() {
        return !empty( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ]) && strtolower( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ]) == 'xmlhttprequest';
    }
}
// json_encode能显示中文
if (!function_exists('json_encode_zh_cn')) {
	function json_encode_zh_cn($var) {
		if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
			return json_encode($var, JSON_UNESCAPED_UNICODE);
		} else {
			return preg_replace("/\\\\u([a-f0-9]{4})/e", "iconv('UCS-4LE','UTF-8',pack('V', hexdec('U$1')))", json_encode($var));
		} 
	} 
} 
// 不过滤掉0: array_filter($array, 'no_filter_0')
if (!function_exists('no_filter_0')) {
	function no_filter_0($a) {
		return ($a || ($a == '0' && $a !== false)) ? true : false;
	}
}
// 当前URL
if (!function_exists('get_current_url')) {
	function get_current_url() {
		return (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	} 
} 
if (!function_exists('class_http')) {
	function close_curl() {
		if (!extension_loaded('curl')) {
			return " <span style=\"color:blue\">请在php.ini中打开扩展extension=php_curl.dll</span>";
		} else {
			$func_str = '';
			if (!function_exists('curl_init')) {
				$func_str .= "curl_init() ";
			} 
			if (!function_exists('curl_setopt')) {
				$func_str .= "curl_setopt() ";
			} 
			if (!function_exists('curl_exec')) {
				$func_str .= "curl_exec()";
			} 
			if ($func_str)
				return " <span style=\"color:blue\">不支持 $func_str 等函数，请在php.ini里面的disable_functions中删除这些函数的禁用！</span>";
		} 
	} 
	// SSL
	function http_ssl($url) {
		$arrURL = parse_url($url);
		$r['ssl'] = $arrURL['scheme'] == 'https' || $arrURL['scheme'] == 'ssl';
		$is_ssl = isset($r['ssl']) && $r['ssl'];
		if ($is_ssl && !extension_loaded('openssl'))
			return wp_die('您的主机不支持openssl，请查看<a href="' . WP_CONNECT_URL . '/check.php" target="_blank">环境检查</a>');
	} 
	function class_http($url, $params = array()) {
		if ($params['http']) {
			$class = 'WP_Http_' . ucfirst($params['http']);
		} else {
			if (!close_curl()) {
				global $wp_version; 
				// $class = 'WP_Http_Curl';
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_USERAGENT, ($params['user-agent']) ? $params['user-agent'] : 'WordPress/' . $wp_version . '; ' . home_url());
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
				curl_setopt($ch, CURLOPT_TIMEOUT, ($params['timeout']) ? (int)$params['timeout'] : 30);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, ($params['sslverify']) ? $params['sslverify'] : false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, ($params['sslverify'] === true) ? 2 : false);
				curl_setopt($ch, CURLOPT_HEADER, false);
				if ($params['referer']) {
					curl_setopt($ch, CURLOPT_REFERER, $params['referer']);
				} 
				switch ($params['method']) {
					case 'POST':
						curl_setopt($ch, CURLOPT_POST, true);
						if (!empty($params['body'])) {
							curl_setopt($ch, CURLOPT_POSTFIELDS, $params['body']);
						} 
						break;
					case 'DELETE':
						curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
						if (!empty($params['body'])) {
							$url = $url . $params['body'];
						} 
				} 
				if (!empty($params['headers'])) {
					$headers = array();
					foreach ($params['headers'] as $k => $v) {
						$headers[] = "{$k}: $v";
					} 
					curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				} 
				curl_setopt($ch, CURLINFO_HEADER_OUT, true);
				curl_setopt($ch, CURLOPT_URL, $url);
				$response = curl_exec($ch);
				curl_close ($ch);
				return $response;
			} else {
				http_ssl($url);
				if (@ini_get('allow_url_fopen') && function_exists('fopen')) {
					$class = 'WP_Http_Streams';
				} elseif (function_exists('fsockopen')) {
					$class = 'WP_Http_Fsockopen';
				} else {
					return wp_die('没有可以完成请求的 HTTP 传输器，请查看<a href="' . WP_CONNECT_URL . '/check.php" target="_blank">环境检查</a>');
				} 
			} 
		} 
		$http = new $class;
		$response = $http -> request($url, $params);
		if (!is_array($response)) {
			if ($params['method'] == 'GET' && @ini_get('allow_url_fopen') && function_exists('file_get_contents')) {
				return file_get_contents($url . '?' . $params['body']);
			} 
			$errors = $response -> errors;
			$error = $errors['http_request_failed'][0];
			if (!$error)
				$error = $errors['http_failure'][0];
			if ($error == "couldn't connect to host" || strpos($error, 'timed out') !== false) {
				return;
			} 
			wp_die('出错了: ' . $error . '<br /><br />可能是您的主机不支持，请查看<a href="' . WP_CONNECT_URL . '/check.php" target="_blank">环境检查</a>');
		} 
		return $response['body'];
	} 
} 
if (!function_exists('get_remote_contents')) {
	function get_remote_contents($url, $timeout = 30, $referer = '', $useragent = '') {
		if (!close_curl()) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_TIMEOUT, $timeout ? $timeout : 30);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			if ($referer) {
				curl_setopt($ch, CURLOPT_REFERER, $referer);
			} 
			if ($useragent) {
				curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
			} 
			$content = curl_exec($ch);
			curl_close($ch);
			return $content;
		} else {
			$params = array();
			if (@ini_get('allow_url_fopen')) {
				if (function_exists('file_get_contents')) {
					return file_get_contents($url);
				} 
				if (function_exists('fopen')) {
					$params['http'] = 'streams';
				} 
			} elseif (function_exists('fsockopen')) {
				$params['http'] = 'fsockopen';
			} else {
				return wp_die('没有可以完成请求的 HTTP 传输器，请查看<a href="' . WP_CONNECT_URL . '/check.php" target="_blank">环境检查</a>');
			} 
			$params += array("method" => 'GET',
				"timeout" => $timeout,
				"sslverify" => false
				);
			if ($useragent) $params['user-agent'] = $useragent;
			return class_http($url, $params);
		} 
	} 
} 
if (!function_exists('key_authcode')) {
	function key_authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
		$ckey_length = 4;
		$key = ($key) ? md5($key) : '';
		$keya = md5(substr($key, 0, 16));
		$keyb = md5(substr($key, 16, 16));
		$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), - $ckey_length)) : '';

		$cryptkey = $keya . md5($keya . $keyc);
		$key_length = strlen($cryptkey);

		$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
		$string_length = strlen($string);

		$result = '';
		$box = range(0, 255);

		$rndkey = array();
		for($i = 0; $i <= 255; $i++) {
			$rndkey[$i] = ord($cryptkey[$i % $key_length]);
		} 

		for($j = $i = 0; $i < 256; $i++) {
			$j = ($j + $box[$i] + $rndkey[$i]) % 256;
			$tmp = $box[$i];
			$box[$i] = $box[$j];
			$box[$j] = $tmp;
		} 

		for($a = $j = $i = 0; $i < $string_length; $i++) {
			$a = ($a + 1) % 256;
			$j = ($j + $box[$a]) % 256;
			$tmp = $box[$a];
			$box[$a] = $box[$j];
			$box[$j] = $tmp;
			$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
		} 

		if ($operation == 'DECODE') {
			if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
				return substr($result, 26);
			} else {
				return '';
			} 
		} else {
			return $keyc . str_replace('=', '', base64_encode($result));
		} 
	} 
}

/**
 * 淘宝客相关
 */
// 淘宝客数据
function get_post_tbk($id, $get_data = 1) {
	global $wptao_options;
	$fields = $wptao_options['caiji'];
	$tbk = array();
	if ($fields) {
		$metas = get_post_meta($id);
		if ($metas['_menu_item_object_id']) return $tbk; // 过滤掉菜单 V4.1
		$fields['time'] = '_wptao_time';
		foreach ($fields as $k => $v) {
			$tbk[$k] = $metas[$v][0];
		} 
		$tbk = array_filter($tbk);
		if ($tbk) {
			$tbk['status'] = $tbk['sellout'] == 1 ? -1 : 1;
		}
	} 
	return $tbk;
}
/**
 * 红色标题
 */
// 添加到文章标题后面
if ($wptao_options['red_title_add'] && $wptao_options['caiji']['red_title']) {
	add_filter('the_title', '_wptao_red_title', 100, 2);
	add_filter('single_post_title', '_wptao_red_title', 100, 2);
}
function _wptao_red_title($title, $post) {
	if (is_object($post)) {
		$id = $post->ID;
	} else {
		$id = $post;
	}
	$red_title = wptao_red_title($id, 0);
	return $title . ($red_title ? ' ' . $red_title : '');
}
// 自定义函数
function wptao_red_title($post_id = 0, $color = 1) {
	if (!$post_id) $post_id = get_the_ID();
	if (!$post_id) return '';
	$tbk = get_post_tbk($post_id);
	$red_title = $tbk['red_title'];
	return $red_title ? ($color ? '<span class="wptao-red">' . $red_title . '</span>' : $red_title) : '';
}
function get_red_title($tbk) {
	if ($tbk['red_title']) {
		$title = $tbk['red_title'];
	} elseif ($tbk['price']) {
		$title = '';
		if ($tbk['coupon'] && $tbk['coupon_value']) {
			$tbk['price'] -= $tbk['coupon_value'];
			$title .= '券后';
		}
		$title .= $tbk['price'] . '元';
		if ($tbk['postFree']) $title .= '包邮';
	} 
	return $title;
} 
// 显示商品失效(自定义，直接插入代码)
function wptao_soldout($post_id = 0) {
	if (!$post_id) $post_id = get_the_ID();
	if ($post_id) {
		$tbk = get_post_tbk($post_id, 0);
		if ($tbk['status'] == -1) return '<span class="wptao_soldout"></span>';
	} 
}
function wptao_ensign($site, $url = '', $str = 0, $post_id = 0) {
	global $wptao_options;
	$keys = wptao_authorize_code();
	if ($keys['apikey'] && $keys['secret']) {
		if (!empty($keys['bought'])) {
			$code = DOMAIN_CURRENT_SITE;
		} elseif (strpos($keys['apikey'], '.') >= 1) {
			if (strpos($_SERVER["HTTP_HOST"], $keys['apikey']) !== false) {
				$code = $keys['apikey'];
			} else {
				$code = $_SERVER['HTTP_HOST'];
			} 
		} else {
			$code = $keys['apikey'];
		} 
		$op = '';
		if ($str) { // 手动获取
			if ($wptao_options['tkl']) {
				$op .= 'tkl=1&';
			} 
		}
		$op .= 'pid=' . $wptao_options['pid'] . '&';
		$op .= http_build_query($wptao_options['union'], '', '&');
		$sign = $code . '|' . key_authcode($op ? $op : 1, 'ENCODE', $keys['secret'], 600) . '|' . time();
		if ($str) {
			return $sign;
		} 
		return '&from=' . urlencode(home_url()) . '&sign=' . urlencode($sign) . '&cv=' . $wptao_options['cv'] . '&v=' . WPTAO_V . '&c=p';
	} 
} 

/**
 * 【淘】商品模块 前台
 */
add_action('wp_head', 'wptao_wp_head');
function wptao_wp_head() {
	wp_register_style('wptao-theme', WPTAO_URL . '/css/theme.css', array(), WPTAO_V);
	wp_print_styles('wptao-theme');
} 
// 简码
add_shortcode('wptao', 'wptao_shortcode');
function wptao_shortcode($atts, $content = '', $is_code = 1) {
	if (wptao_is_tested() || !in_the_loop()) return '';
	if (empty($atts['url']) && empty($atts['_url'])) return '';
	if (isset($atts['_title'])) { // v1.6.4
		$atts['title'] = $atts['_title'];
	} 
	if (empty($atts['src'])) {
		if (isset($atts['image'])) { // < v1.5
			$atts['src'] = $atts['image'];
			unset($atts['image']);
		} elseif (isset($atts['data-original'])) { // v1.6.4
			$atts['src'] = $atts['data-original'];
			unset($atts['data-original']);
		} 
	} 
	if (!empty($content)) { // v1.4.8
		$atts['content'] = $content;
	} else {
		unset($atts[0], $atts[1], $atts['class'], $atts['alt']);
	} 
	$atts['link'] = $atts['url']; // 原始链接 V2.0
	// 聚划算到期后替换为商品链接 V1.5.2
	if (isset($atts['end_time']) && $end_time = strtotime($atts['end_time'])) {
		if (current_time('timestamp') > $end_time) {
			preg_match('/item_id=(\d+)+/', $atts['url'], $matches);
			if ($itemid = trim($matches[1])) {
				if ($atts['site'] == '天猫') {
					$atts['url'] = 'https://detail.tmall.com/item.htm?id=' . $itemid;
				} elseif ($atts['site'] == '淘宝网') {
					$atts['url'] = 'https://item.taobao.com/item.htm?id=' . $itemid;
				} 
			} 
			if (isset($atts['_url'])) unset($atts['_url']);
		} 
	} 
	if ($is_code) {
		$atts = apply_filters('wptao_atts', $atts);
	} 
	global $wptao_options;
	$out = apply_filters('wptao_html', '', $atts); // 自定义html，写在主题functions.php
	if (!$out) {
		// 选择模版
		if (isset($atts['theme'])) {
			$theme = (int)$atts['theme'];
		} else {
			$theme = (int)$wptao_options['theme'];
		} 
		if (!$theme) $theme = 2; 
		// 图片尺寸
		if ($theme == 1) {
			$size = 140;
		} else {
			$size = 400;
		} 
		// 图片
		if (!empty($atts['src'])) {
			if (strpos($atts['src'], '.alicdn.com') || strpos($atts['src'], '.taobaocdn.com')) { // 淘宝天猫
				$atts['src'] = preg_replace('/_(\d+)x(\d+).+/', '', $atts['src']);
				$atts['src'] .= '_' . $size . 'x' . $size . '.jpg';
				if (is_ssl()) $atts['src'] = preg_replace('/http(|s):\/\/(\w.+).com/', 'https://img.alicdn.com', $atts['src']);
			} elseif (strpos($image, '360buyimg.com/')) { // 京东
				$atts['src'] = str_replace('/n1/s430x430_', '/n1/s' . $size . 'x' . $size . '_', $atts['src']);
				if (is_ssl()) $atts['src'] = str_replace('http://', 'https://', $atts['src']);
			} 
		} 
		// URL
		if (!empty($atts['_url']) && strpos($atts['_url'], 'redirect.simba.taobao.com') === false) {
			$atts['_url'] = str_replace(array(' ', '+'), '%2B', $atts['_url']);
			if (!wp_is_mobile()) {
				$atts['url'] = 'javascript:;" onclick="window.open(\'' . $atts['_url'] . '\');return false'; // 隐藏链接,，点击时跳到推广链接
			} else { // 移动端不转换
				$atts['url'] = $atts['_url'];
			} 
		} 
		$quan_link = '';
		$texts = array('view' => '查看详情', 'read' => '阅读全文', 'buy' => '去购买');
		if (empty($atts['price'])) {
			$atts['price'] = $atts['red_title'] ? $atts['red_title'] : $atts['zk_price'];
		}
		if (!empty($atts['price'])) {
			if (!$wptao_options['exchange'] && $atts['coupon']) {
				$quan_link = '<div class="wptao-quan"><a rel="nofollow" target="_blank" href="' . $atts['coupon'] . '">';
				if ($atts['coupon_value']) {
					$quan_link .= '<span class="quan">领</span><span class="num">' . round($atts['coupon_value'], 2) . '元券</span>';
					if (is_numeric($atts['price'])) {
						$price = round($atts['price'] - $atts['coupon_value'], 2);
						$atts['price'] = $price > 0 ? '券后价' . $price . '元' : '￥' . $atts['price'];
					} 
				} else {
					$quan_link .= '点击领券';
					$atts['price'] = '￥' . $atts['price'];
				} 
				$quan_link .= '</a></div>';
				if (strpos($atts['coupon'], '//uland.taobao.com/coupon/')) $atts['url'] = $atts['coupon'];
			} else {
				if ($wptao_options['exchange']) {
					$unit = $wptao_options['currency']['symbol'];
					if ($wptao_options['currency']['code']) {
						if (in_array($wptao_options['currency']['code'], array('TWD', 'HKD'))) {
							$texts = array('view' => '查看詳情', 'read' => '閱讀全文', 'buy' => '去購買');
						}
					}
				} else {
					$unit = '￥';
				}
				$atts['price'] = '<em>' . $unit . '</em>' . wptao_price($atts['price'], !$is_code);
			} 
		} 
		$class_position = !empty($atts['position']) ? ' wptao-' . $atts['position'] : '';
		if (empty($atts['src'])) { // 没有图片不加模版
			return '<a rel="nofollow" target="_blank" href="' . $atts['url'] . '">' . $atts['title'] . '</a> ';
		} elseif (empty($atts['title']) && $atts['src']) { // 没有标题不加模版
			if (!$atts['price']) {
				return '<a rel="nofollow" target="_blank" href="' . $atts['url'] . '"><img src="' . $atts['src'] . '" alt="" /></a> ';
			} else {
				$theme = 3;
			}
		}
		if ($theme == 1) { // 模版1
			$out = '<div class="wptao-item wptao-item-' . $theme . $class_position . '">';
			$out .= '<a rel="nofollow" target="_blank" href="' . $atts['url'] . '"><img itemprop="image" class="wptao-res" src="' . $atts['src'] . '" alt="' . $atts['title'] . '" /></a>';
			$out .= '<a rel="nofollow" target="_blank" href="' . $atts['url'] . '" title="' . $atts['title'] . '"><span class="wptao-res wptao-title">' . ($atts['site'] ? '<em>[' . $atts['site'] . ']</em> ' : '') . $atts['title'] . '</span></a>';
			$out .= '<div class="wptao-res wptao-des">' . $atts['content'] . '</div>';
			if (!empty($atts['price'])) {
				$out .= '<div class="wptao-res wptao-oth">';
				$out .= '<a rel="nofollow" target="_blank" href="' . $atts['url'] . '"><span class="wptao-res wptao-price">' . $atts['price'] . '</span></a>';
				$out .= $quan_link;
				$out .= '</div>';
			} 
			$out .= '</div>';
		} elseif ($theme == 3 || $theme == 5) { // 模版3/5
			$out = '';
			if ($theme == 5) {
				$out .= '<div class="wptao-item-' . $theme . '">';
				$out .= '<h3>' . $atts['title'] . '</h3>';
				$out .= '<div class="wptao-des">' . $atts['content'] . '</div></div>';
				$theme = 3;
			}
			$out .= '<div class="wptao-item wptao-item-' . $theme . $class_position . '"><a rel="nofollow" target="_blank" href="' . $atts['url'] . '" title="' . $atts['title'] . '"><div class="item-img"><img itemprop="image" class="wptao-res" src="' . $atts['src'] . '" alt="' . $atts['title'] . '" /><div class="item-bg"></div></div><div class="wptao-des">';
			if (!empty($atts['price'])) {
				$out .= '<span class="item-price">' . $atts['price'] . '</span><span class="item-sep">|</span>';
			} 
			$out .= '<span class="item-view">'.$texts['view'].'</span></div></a></div>';
		} else { // 模版2
			if ($theme != 4) $theme = 2;
			$buylink = $atts['url'];
			if ($atts['post_id']) $atts['url'] = get_permalink($atts['post_id']);
			$out = '<div class="wptao-item wptao-item-' . $theme . $class_position . '">';
			$out .= '<a rel="nofollow" target="_blank" href="' . $atts['url'] . '"><img itemprop="image" class="wptao-res" src="' . $atts['src'] . '" alt="' . $atts['title'] . '" /></a>';
			$out .= '<a rel="nofollow" target="_blank" href="' . $atts['url'] . '" title="' . $atts['title'] . '"><span class="wptao-res wptao-title">' . ($atts['site'] ? '<em>[' . $atts['site'] . ']</em> ' : '') . $atts['title'] . '</span></a>';
			$out .= '<div class="wptao-res wptao-des">' . $atts['content'] . '&nbsp;</div>';
			if ($theme == 4 && $atts['post_id']) {
				$out .= '<a rel="noopener" target="_blank" href="' . $atts['url'] . '"><div class="wptao-morelink">' . $texts['read'] . '<em>&raquo;</em></div></a>';
			}
			if (!empty($atts['price'])) {
				$out .= '<div class="wptao-res wptao-oth">';
				$out .= $quan_link;
				$out .= '<div class="wptao-res wptao-price">' . $atts['price'] . '</div>';
				// if (!empty($atts['_price'])) $out .= '<div class="wptao-res wptao-old-price">￥' . $atts['_price'] . '</div>';
				$out .= '<div class="wptao-buybtn"><a rel="nofollow" target="_blank" href="' . $buylink . '"><span class="wptao-res wptao-btn">' . $texts['buy'] . '</span></a></div></div>';
			} 
			$out .= '</div>';
		} 
		$out .= '<div class="wptao-clear"></div>';
	} 
	return $out;
} 

/**
 * 内容相关
 */
if (!function_exists('wp_get_meta_data')) {
	function wp_get_meta_data($meta_type, $object_id, $meta_key = '', $single = false) {
		if (! $meta_type || ! is_numeric($object_id)) {
			return false;
		} 
		$object_id = absint($object_id);
		if (! $object_id) {
			return false;
		} 
		/*
		$check = apply_filters("wp_get_{$meta_type}_metadata", null, $object_id, $meta_key, $single);
		if (null !== $check) {
			if ($single && is_array($check))
				return $check[0];
			else
				return $check;
		} */
		$meta_cache = wp_cache_get($object_id, $meta_type . '_meta');
		if (!$meta_cache) {
			$meta_cache = update_meta_cache($meta_type, array($object_id));
			$meta_cache = $meta_cache[$object_id];
		} 
		if (! $meta_key) {
			return $meta_cache;
		} 
		if (isset($meta_cache[$meta_key])) {
			if ($single)
				return maybe_unserialize($meta_cache[$meta_key][0]);
			else
				return array_map('maybe_unserialize', $meta_cache[$meta_key]);
		} 

		if ($single)
			return '';
		else
			return array();
	} 
	function wp_get_post_meta($post_id, $key = '', $single = false) {
		return wp_get_meta_data('post', $post_id, $key, $single);
	} 
} 
// 获取postmeta key
function __get_meta_keys() {
	global $wpdb;
	$sql = "SELECT DISTINCT meta_key
		FROM $wpdb->postmeta
		WHERE meta_key NOT BETWEEN '_' AND '_z'
		HAVING meta_key NOT LIKE %s
		ORDER BY meta_key";
	return $wpdb->get_col( $wpdb->prepare( $sql, $wpdb->esc_like( '_' ) . '%' ) );
} 
function wptao_is_tested($wptao = array()) {
	if (!$wptao || !$wptao['code']) {
		$wptao = wptao_authorize_code();
	} else {
		$wptao = $wptao['code'];
	} 
	if (!$wptao || !$wptao['authorize_code']) {
		return true;
	} elseif (substr($wptao['authorize_code'], -4) == 'TEST') {
		$time = time() - $wptao['apikey'];
		if ($time < -3600 || $time > 2592000) {
			return true;
		} 
	} 
	return false;
} 
function wptao_authorize_code() {
	global $wptao_options;
	if (is_array($wptao_options) && $wptao_options['code']['authorize_code']) {
		return $wptao_options['code'];
	} 
	if (is_multisite()) { // WPMU
		$option = get_site_option('wptao_code');
		if ($option && $option['bought']) {
			return $option;
		} 
	} 
}

/**
 * 分类、标签相关
 */
// 创建分类，标签，自定义分类
function __wp_create_term($name, $slug = '', $taxonomy = 'category', $parent = null) {
	if ($id = term_exists($name, $taxonomy, $parent)) {
		return (int)$id['term_id'];
	} 
	$term = wp_insert_term($name, $taxonomy, array('slug' => $slug, 'parent' => $parent));
	if (is_array($term)) {
		return (int)$term['term_id'];
	} else {
		return '';
	}
} 
// 获取一个分类ID
function _get_term_id($name, $taxonomy) {
	global $wpdb;
	return (int)$wpdb -> get_var("SELECT tt.term_id FROM $wpdb->term_taxonomy AS tt INNER JOIN $wpdb->terms AS t ON tt.term_id = t.term_id WHERE tt.taxonomy = '$taxonomy' AND t.name LIKE '$name%' ORDER BY tt.term_taxonomy_id ASC");
}

/**
 * 图片相关
 */
// 得到图片url
if (!function_exists('get_image_by_content')) {
	function get_image_by_content($content, $post_ID = '', $size = 'full') { // thumbnail, medium, large or full
		$picurl = apply_filters('get_image_by_content', '', $content, $post_ID, $size);
		if ($picurl) {
			return $picurl;
		} 
		if ($post_ID) {
			if (is_numeric($post_ID) && function_exists('has_post_thumbnail') && has_post_thumbnail($post_ID)) { // 特色图像 WordPress v2.9.0
				if ($image_url = wp_get_attachment_image_src(get_post_thumbnail_id($post_ID), $size))
					$picurl = $image_url[0];
			} 
		} 
		if (!$picurl && $content) {
			preg_match('/<img[^>]+src=[\'"](http[^\'"]+)[\'"].*>/isU', $content, $image);
			$picurl = $image[1];
		} 
		return $picurl;
	} 
} 
// 获取商品图片
function get_imgurl($post, $size = '', $default = '') {
	if (is_array($post)) {
		$image = $post['image'];
	} elseif (is_numeric($post)) {
		$tbk = get_post_tbk($post, 0);
		$image = $tbk['image'];
	} elseif (strpos($post, 'http') === 0) {
		$image = $post;
	}
	if (!$size) return $image;
	if (strpos($image, '.alicdn.com/') || strpos($image, '.taobaocdn.com/')) {
		$image = preg_replace('/_(\d+)x(\d+).+/', '', $image);
		$image = preg_replace('/http(|s):\/\/(\w.+).com/', 'https://img.alicdn.com', $image);
		$image .= '_' . $size . 'x' . $size . '.jpg';
	} elseif (strpos($image, '360buyimg.com/')) { // 京东
		$image = str_replace('/n1/s430x430_', '/n1/s' . $size . 'x' . $size . '_', $image);
		if (is_ssl()) $image = str_replace('http://', 'https://', $image);
	}
	return $image;
}
// 特色图片
function wptao_get_image($content, $post_ID = '', $size = '') {
	if (!$size) $size = 'post-thumbnail'; // 主题自定义，见上面的 set_post_thumbnail_size( 220, 220, true );
	elseif ($size <= 150) $size = 'thumbnail';
	elseif ($size <= 220) $size = 'post-thumbnail'; // 主题自定义
	elseif ($size <= 300) $size = 'medium';
	elseif ($size <= 768) $size = 'medium_large';
	elseif ($size <= 1024) $size = 'large';
	else $size = 'full';
	return get_image_by_content($content, $post_ID, $size);
}
// 替代特色图片，方便主题调用特色图片 V3.4
if (!empty($wptao_options['caiji']['image']) && $wptao_options['caiji']['image'] == 'wptao_img') {
	add_filter("get_post_metadata", 'wptao_get_post_thumbnail_id', 1, 4);
}
function wptao_get_post_thumbnail_id($data, $post_id, $meta_key, $single) {
	if ($single && $meta_key == '_thumbnail_id') {
		global $wptao_options;
		if ($wptao_options['single_nopic'] && is_single()) return $data;
		if (!wp_get_post_meta($post_id, $meta_key, $single)) {
			add_filter('image_downsize', 'wptao_image_downsize', 10, 3);
			return $post_id;
		} 
	}
	return $data;
} 
function wptao_image_downsize($image, $attachment_id, $size) {
	if (!$image) {
		$src = get_post_meta($attachment_id, 'wptao_img', true);
		if (!$src) {
			$post = get_post($attachment_id);
			if ($content = $post->post_content) { // V4.5.5
				if (strpos($content, '[wptao ') !== false) {
					$check = 1;
				}
				if ($check) {
					preg_match('/<img[^>]+src=[\'"]([^\'"]+)[\'"].*>/isU', $content, $image);
					$src = $image[1];
				}
			}
		}
		if ($src) {
			// @list($width, $height) = getimagesize($src);
			if (in_array($size, array('full', 'large', 'medium_large'))) {
				list($width, $height) = array(600, 600);
			} elseif (in_array($size, array('thumb', 'thumbnail', 'post-thumbnail', '50x50'))) {
				list($width, $height) = array(300, 300);
			} else {
				list($width, $height) = array(430, 430);
			}
			$src = get_imgurl($src, $width);
			return array($src, $width, $height);
		}
	} 
	return $image;
}
// 图片URL V4.1
function wptao_thumbnail_url($image, $post_id, $thumbnail_id, $size) {
	if (!$image && !$thumbnail_id) {
		$img = wptao_image_downsize('', $post_id, $size);
		if ($img && $img[0]) return $img[0];
	}
	return $image;
}
// 插件兼容
add_filter('wpcom_thumbnail_url', 'wptao_thumbnail_url', 10, 4);

/**
 * JS
 */
// js var
function wptao_js_var() {
	global $wptao_options;
	$code = $wptao_options['code'];
	$var = array('pid' => $wptao_options['pid'],
		'v' => WPTAO_V,
		'c' => 'p',
		'api' => (is_ssl() ? 'https://' : 'http://') . ((!$code['authorize_code'] || substr($code['authorize_code'], -4) == 'TEST') ? 'js.tk.wptao.cn/test/shop' : 'js.api.wptao.com/2'),
		'blog_url' => home_url(),
		'ajax_url' => admin_url('admin-ajax.php'),
		'login' => is_user_logged_in() ? true : false
		);
	return $var;
}
// ajax
add_action('wp_ajax_wptao_ajax', 'wptao_ajax');
add_action('wp_ajax_nopriv_wptao_ajax', 'wptao_ajax');
function wptao_ajax() {
	$action = isset($_REQUEST['type']) ? sanitize_key($_REQUEST['type']) : '';
	if (!$action) exit;
	if (__is_ajax()) {
		if ($action == 'sign') {
			echo wptao_ensign('', esc_url($_GET['link']), 1);
		}
	} else {
		if ($action == 'editor_wptao') {
			include(dirname(__FILE__) . '/admin/wptao/index.php');
		}
	} 
	exit;
} 
// 广告位
function wptao_ad($k = '') {
	global $wptao_options;
	if ($k == 'top') {
		if ($wptao_options['ad_top'] && $wptao_options['ad_top'][0] && $wptao_options['ad_top'][1] && (!$wptao_options['ad_top'][2] || time() < strtotime($wptao_options['ad_top'][2]))) {
			$wptao_options['ad_top'][2] = $wptao_options['ad_top'][3] ? ($wptao_options['ad_top'][3] == 1 ? "show-pc" : $wptao_options['ad_top'][3]) : '';
			unset($wptao_options['ad_top'][3]);
		?>
<script type="text/javascript">
var wptao_top=<?php echo json_encode($wptao_options['ad_top']);?>;
if(location.href.indexOf(wptao_top[1])===-1){
var div=document.createElement("div");
div.innerHTML = "<a href=\""+wptao_top[1]+"\" target=\"_blank\" class=\""+wptao_top[2]+"\"><img style=\"width:100%\" src=\""+wptao_top[0]+"\"></a>";
document.body.insertBefore(div, document.body.childNodes[0]);
}
</script>
<?php
		}
	} 
}
// 添加底部
add_action('wp_footer', 'wptao_add_js');
function wptao_add_js() {
	if (wptao_is_tested()) return;
	global $wptao_options;
	// 弹窗广告
	if ($wptao_options['ad_pop'] && $wptao_options['ad_pop'][2] && ($wptao_options['ad_pop'][0] || $wptao_options['ad_pop'][1]) && (!$wptao_options['ad_pop'][4] || time() < strtotime($wptao_options['ad_pop'][4]))) {
		if ($wptao_options['ad_pop'][0]) {
			$pop_num = (int)$wptao_options['ad_pop'][3];
	?>
	<div id="ad_pop" style="display:none">
	<div style="width:100%;height:100%;z-index:100000;top:0px;left:0px;position:fixed;opacity:0.8;transition:all 0.3s;background:radial-gradient(rgba(0,0,0,0.498039), rgba(0,0,0,0.8));"></div>
	<div style="position:fixed;width:340px;height:443px;top:50%;left:50%;margin-top:-221.5px;margin-left:-170px;z-index:999999;"><a target="_blank" href="<?php echo stripslashes($wptao_options['ad_pop'][2]);?>"><img style="max-height:100%;max-width:100%;" src="<?php echo $wptao_options['ad_pop'][0];?>"></a><span style="position:absolute;top:-10px;right:-10px;color:#eee;font-size:30px;cursor:pointer;" class="ad_pop_close"><img src="<?php echo WPTAO_URL;?>/images/close1.png"></span></div>
	</div>
	<?php if ($wptao_options['ad_pop'][1]) {//右侧小图 ?>
	<div class="ad_pop_left" style="display:none;position:fixed;right:4%;bottom:100px;width:100px;height:100px;text-align:center;cursor:pointer;z-index:999;"><a target="_blank" href="<?php echo stripslashes($wptao_options['ad_pop'][2]);?>"><img style="max-width:100%;max-height:100%;" src="<?php echo $wptao_options['ad_pop'][1];?>"></a></div>
	<?php } ?>
	<script type="text/javascript">
	var pop_num = '<?php echo $pop_num ? $pop_num : 4;?>';
	function wp_get_aCookie(a){var b,c,d=document.cookie,e=a+"=";if(d){if(c=d.indexOf("; "+e),-1===c){if(c=d.indexOf(e),0!==c)return null}else c+=2;return b=d.indexOf(";",c),-1===b&&(b=d.length),decodeURIComponent(d.substring(c+e.length,b))}}function wp_set_aCookie(a,b,c,d,e,f){if("number"==typeof c){var g=c,h=c=new Date;h.setTime(+h+864e5*g),c=h.toGMTString()}document.cookie=a+"="+encodeURIComponent(b)+(c?"; expires="+c:"")+(d?"; path="+d:"; path=/")+(e?"; domain="+e:"")+(f?"; secure":"")}function wp_clear_aCookie(a,b,c,d){wp_set_aCookie(a,"",-1,b,c,d)}jQuery(function(a){if(!wp_get_aCookie("ad_pop")){var b=a("#ad_pop a").attr("href");-1===location.href.indexOf(b)&&a("#ad_pop").show()}a(".ad_pop_left").show(),a(".ad_pop_close").click(function(){a("#ad_pop").hide(),wp_set_aCookie("ad_pop","1",pop_num/24)})});
	</script>
	<?php } else { ?>
	<div class="ad_pop_left" style="position:fixed;right:4%;bottom:100px;width:100px;height:100px;text-align:center;cursor:pointer;z-index:999;"><a target="_blank" href="<?php echo stripslashes($wptao_options['ad_pop'][2]);?>"><img style="max-width:100%;max-height:100%;" src="<?php echo $wptao_options['ad_pop'][1];?>"></a></div>
	<?php }}
	wptao_ad('top'); // 顶部广告条
} 
?>