<?php
function wptao_button_text() {
	echo '支持淘宝/天猫/京东/苏宁/拼多多/考拉等自动获取';
}
// 禁用新编辑器 WordPress V5.0+
if (!empty($wptao_options['editor'])) {
	add_filter('use_block_editor_for_post', '__return_false');
	remove_action('wp_enqueue_scripts', 'wp_common_block_scripts_and_styles');
}

add_filter('admin_page_access_denied', 'admin_page_access_denied_wptao_pids');
function admin_page_access_denied_wptao_pids() {
	global $pagenow;
	if ('admin.php' == $pagenow && $_GET['page'] == 'wptao-pids') {
		wp_die('您没有足够的权限访问该页面。请勾选【填写更多PID】并且保存后打开此页面！', 403);
	} 
} 
add_action('admin_notices', 'wptao_warning');
function wptao_warning() {
	if (current_user_can('manage_options')) {
		global $wptao_options;
		if (!$wptao_options['version'] || $wptao_options['version'] != WPTAO_V) {
			echo '<div class="error"><p><strong>升级淘宝客插件后，请您进入【<a href="admin.php?page=wptao">设置页面</a>】点击【保存更改】。</strong></p></div>';
		} 
		if ($wptao_options['notice']) {
			echo '<div class="error"><p><strong>' . $wptao_options['notice'] . '</strong></p></div>';
		}  
		if ($wptao_options['renew']) {
			if ($wptao_options['renew'] == 1) {
				$error = '您的插件已经到期，请及时续费，目前无法使用API获取商品信息。';
			} else {
				$error = '您的插件将于' . $wptao_options['renew'].'过期，记得及时续费，否则无法使用API获取商品信息。';
			}
			echo '<div class="error"><p><strong>'.$error.'[<a href="https://wptao.com/download" target="_blank">续费</a>]</strong></p></div>';
		}
	} 
} 

/**
 * 插件设置
 */
// 自定义文案-标签
function wptao_cj_desc_tags($quan = 1) {
	$tags = array('title' => '标题',
		'desc' => '文案',
		'content' => '商品详情',
		'price' => '价格',
		'old_price' => '原价',
		'_image' => '商品主图',
		'image' => '商品主图链接',
		'site' => '商城',
		'biz30day' => '月销量'
		);
	if ($quan) {
		$tags['price'] = '券后价';
		$tags['coupon_value'] = '券金额';
		$tags['coupon'] = '券链接';
		//$tags['coupon_end'] = '券截止日期';
	} 
	$tags['internal_link'] = '内链';
	$tags['wptao_code'] = '商品模块';
	return $tags;
} 

add_action('admin_menu', 'wptao_add_page');
function wptao_add_page() {
	if (function_exists('add_menu_page')) {
		add_menu_page('淘宝客插件', '淘宝客插件', 'manage_options', 'wptao', 'wptao_do_page', WPTAO_URL .'/images/icon.png');
	} 
} 

// 设置 Setting
function wptao_do_page() {
	global $wp_version;
	if (isset($_POST['wptao_options'])) {
		$authorize_code = trim($_POST['authorize_code']);
		if ($authorize_code) {
			if (substr($authorize_code, -4) == 'WPMU') {
				$authorizecode = substr($authorize_code, 0, -4);
				$is_wpmu = 1;
			} else {
				$authorizecode = $authorize_code;
				$is_wpmu = '';
			} 
			$_POST['wptao']['code'] = array('apikey' => substr($authorizecode, 0, -32), 'secret' => substr($authorizecode, -32), 'wpmu' => $is_wpmu, 'authorize_code' => $authorize_code);
		} 
		$_POST['wptao']['union'] = array_filter(array_map('trim', $_POST['wptao']['union']));
		if ($_POST['wptao']['item']) {
			$_POST['wptao']['item'] = array_filter($_POST['wptao']['item']);
		}
		if (isset($_POST['wptao']['caiji'])) {
			$_POST['wptao']['caiji'] = array_filter($_POST['wptao']['caiji']);
			if ($_POST['wptao']['caiji']['coupon']) {
				$_POST['wptao']['caiji']['dx'] = 'wptao_dx';
				$_POST['wptao']['caiji']['jh'] = 'wptao_jh';
			}
		}
		unset($_POST['wptao']['cj'], $_POST['wptao']['exchange']);
		$_POST['wptao']['version'] = WPTAO_V;
		update_option(WPTAO_OPTIONS, $_POST['wptao']);
		do_action('wptao_update');
	}
	$wptao = get_option(WPTAO_OPTIONS);
	if (!$wptao) {
		$wptao = array('open' => 1, 'mce' => 1, 'mce_mm' => 1, 'theme' => 2, 'item' => array(), 'tkl' => 1, 'coupon_both' => 1);
	} else {
		if (wptao_is_tested($wptao)) {
			echo '<div class="error"><p><strong>免费试用已经到期 或者 填写正确的插件授权码。</strong></p></div>';
		}
		if ($wptao['error']) {
			echo '<div class="error"><p><strong>'.$wptao['error'].'</strong></p></div>';
		}
		if (function_exists('wptao_update_checker')) {
			$validation = validation_error('wptao');
			if ($validation) {
				echo '<div class="error">' . $validation . '</div>';
			}
		}
	}
	// 新增功能的默认设置
	$default = array('coupon_end' => 1, // V3.0.1
		'coupon_both' => 1, // V3.5.2
		'editor' => 1, // V3.7.3
		'mce_tkl' => 1, // V4.1
		'buy_theme' => 1, // V4.5.1
		'neilian' => 2, // V4.5.2
		'cv' => '1.0',
	);
	foreach($default as $kk => $vv) {
		if (!isset($wptao[$kk])) $wptao[$kk] = $vv;
	}
	if (!is_array($wptao['cj'])) { // V3.0
		$wptao['cj'] = array('yc' => 1, 'views_min' => 1, 'views_max' => 20, 'f' => array('startPrice' => 1, 'startTkRate' => 30, 'startBiz30day' => 100, 'dsr' => 4.8, 'mall' => array(1,2)), 'filter' => 1);
		$wptao['cj']['desc'] = $wptao['cj']['desc_quan'] = "#文案#\r\n#商品详情#";
	}
	if (!isset($wptao['cj']['sticky'])) { // V4.0
		$wptao['cj']['sticky'] = 4;
	} 
	if ($wptao['cj']['k_cats'] && is_array($wptao['cj']['k_cats'])) { // V4.0
		$wptao['cj']['k_cats'] = implode('|', array_keys($wptao['cj']['k_cats']));
	}
	if (isset($wptao['open']) && !is_array($wptao['open'])) { // V1.4
		$wptao['open'] = array('post' => 1, 'page' => 1, 'all' => 1);
	}
	if (!isset($wptao['caiji'])) { // V2.0
		$wptao['caiji'] = array();
		$template = get_template();
		if (strpos($template, 'uctheme') === 0) {
			$meta_keys = array('mm_link' => 'buylink_value', 'image' => 'thumb_value', 'red_title' => 'red_value', 'sellout' => 'is_sellout', 'coupon' => 'youhuiquan');
			$wptao['caiji'] = array_intersect($meta_keys, __get_meta_keys()) + array('link' => 'wptao_link');
		} 
	} 
	// var_dump($wptao);
	$cj_post_type = $wptao['cj']['post_type'] ? $wptao['cj']['post_type'] : 'post';
	if (is_multisite()) {
		$code = get_site_option('wptao_code');
		if ($code && is_array($code) && $code['apikey'] == DOMAIN_CURRENT_SITE) {
			$is_network = true;
			if (!$code['wpmu'] && strpos($_SERVER["HTTP_HOST"], $code['apikey']) === false) {
				$is_network = false;
			}
		}
	}
	if (!WPTAO_ULTIMATE) {
		$ultimate_desc = '<p class="wptao-box yellow">此功能仅限旗舰版及以上用户，免费版/基础版无法测试，购买旗舰版及以上版本后，需要到我网站<a target="_blank" href="https://wptao.com/download">下载新的安装包</a></p>';
	}
	wp_enqueue_media();
	wp_register_script("wptao-admin", WPTAO_URL . "/admin/js/admin.js", array("jquery"), WPTAO_V);
	wp_print_scripts('wptao-admin');
?>
<script type="text/javascript">
function add_value(a,b){document.getElementById(a).value=b.innerHTML}
function openNewWin(a,b){var c=document.createElement("a");c.setAttribute("href",a),c.setAttribute("target","_blank"),c.setAttribute("id","open_"+b),document.getElementById("open_"+b)||document.body.appendChild(c),c.click()}
function select_post_type(a,b){var c=document.getElementById("table_"+b+"_category"),d=document.getElementById("post_type_tips");a==b?(c.style.display="block",d.style.display="none"):(c.style.display="none",d.style.display="block")}
</script>
<style type="text/css">
.postbox label{float:none}
.postbox .hndle{border-bottom:1px solid #eee}
.nav-tab-wrapper{margin-bottom:15px}
.form-table th strong{color:#0073bb}
.wptao-grid a{text-decoration:none}
.wptao-main{width:80%;float:left}
.wptao-sidebar{width:19%;float:right}
.wptao-sidebar ol{margin-left:10px}
.wptao-box{margin:10px 0px;padding:10px;border-radius:3px 3px 3px 3px;border-color:#cc99c2;border-style:solid;border-width:1px;clear:both}
.wptao-box.yellow{background-color:#FFFFE0;border-color:#E6DB55}
@media (max-width:782px){
.wptao-grid{display:block;float:none;width:100%}
}
</style>
<div class="wrap">
  <h2>淘宝客插件<code>v<?php echo WPTAO_V;?></code> <code><a target="_blank" href="https://wptao.com/taoke">官网</a></code></h2>
  <div id="poststuff">
    <div id="post-body">
      <div class="nav-tab-wrapper">
		<?php
		$tabs = array(1 => '基本设置', 2 => '联盟设置', 3 => '对接主题', 20 => '手动(可选)', 4 => '高级设置', 15 => '公众号找券', 5 => '自动采集', 6 => '软件采集', 7 => '广告窗');
		if ($wptao['box'] || !$wptao['item']) unset($tabs[20]);
  		if (!function_exists('wptao_app')) $tabs[77] = 'APP/小程序';
		foreach($tabs as $tabi => $tab) {
			echo '<a id="group-' . $tabi . '-tab" class="nav-tab" title="' . $tab . '" href="#group-' . $tabi . '">' . $tab . '</a>';
		} 
		?>
      </div>
      <div class="wptao-container">
        <div class="wptao-grid wptao-main">
          <form method="post" action="">
            <?php wp_nonce_field('wptao-options');?>
            <div id="group-1" class="group" style="display: block;">
              <div class="postbox">
                <h3 class="hndle">
                  <label for="title">基本设置</label>
                </h3>
                <div class="inside">
                  <table class="form-table">
                    <tbody>
					<?php if (!$is_network) {
						if (!$wptao['code']['authorize_code']) {
							$blogurl = home_url();
							$time = time();
							$getTestCode = 'http://js.tk.wptao.cn/test/getcode.php?id=170&url=' . urlencode($blogurl) . '&sign=' . md5($blogurl . $time) . '&t=' . $time . '&v=' . WPTAO_V;
							$getTestCode = ' <a target="_blank" href="' . $getTestCode . '">申请测试</a>';
						} 
					?>
					<tr>
					  <th scope="row">填写插件授权码（<a target="_blank" href="https://wptao.com/taoke">购买</a>）</th>
					  <td><input type="text" name="authorize_code" id="wptao_code" size="40" value="<?php echo $wptao['code']['authorize_code'];?>"><?php echo $getTestCode;?>
					  <?php if (is_multisite()) echo '<p class="description"><code>您正在使用WPMU，您可以在 管理网络 -> 设置 -> <a target="_blank" href="' . admin_url('network/settings.php?page=wptao') . '">淘宝客</a> 填写插件授权码。<a href="https://wptao.com/wptao.html" target="_blank">如何获得授权码</a></code></p>';?></td>
					</tr>
					<?php } ?>
					<tr>
					  <th scope="row">获取商品信息</th>
					  <td>添加到 <label><input type="checkbox" name="wptao[open][post]" value="1"<?php checked($wptao['open']['post']);?>>文章</label>
						  <label><input type="checkbox" name="wptao[open][page]" value="1"<?php checked($wptao['open']['page']);?>>页面</label>
						<?php
						if (function_exists('get_post_types')) { // 自定义文章类型
							if ($post_types = get_post_types(array('public' => true, '_builtin' => false), 'objects', 'and')) {
								foreach($post_types as $type => $object) {
									echo ' <label><input type="checkbox" name="wptao[open][' . $type . ']" value="1" ' . checked($wptao['open']['all'] || $wptao['open'][$type], 1, false) . '>' . $object -> labels -> name . '</label>';
								} 
							} 
						} 
						?> 等撰写新文章/编辑文章 页面</td>
					</tr>
					<tr>
					  <th scope="row"><strong>微信分身功能<strong></th>
					  <td>为防止微信封杀网站域名A，在微信APP打开时自动跳到B域名，调用A域名的内容，在微信公众号自动返回B域名，在微信群，请自己用B域名分享！B域名被封了，改成C域名，以此类推！在非微信APP打开时，B域名自动跳到A域名。<a target="_blank" href="https://wptao.com/weixin-cloned.html">另外收费，去购买</a></td>
					</tr>
					<tr>
					  <th scope="row">调用其他文章的商品</th>
					  <td><p class="wptao-box"><?php if (!WPTAO_ULTIMATE) { ?>由于依赖内链，仅旗舰版及以上版本才可以使用。<br/><?php } ?>要使用此功能，必须在【对接主题】接入商品链接、推广链接、商品图片、商品价格，建议也接入优惠券链接、面值等信息。</p>
					  <font color="red">可以用编辑器直接插入。</font>[<a target="_blank" href="http://img2.wptao.cn/3/large/62579065gy1fyv3n3zz3hj20mg042dfv.jpg">查看</a>]
					  <br/>1. 添加一个：<code>[shop id=文章ID]</code> 或者 <code>[shop id=文章ID]推荐理由[/shop]</code>，
					  <br/>模板同【淘】插入的相同，如果要指定模板可以用 <code>[shop id=文章ID <font color="red">theme=x</font>]</code> x 是数字。
					  <br/>2. 添加多个：<code>[shop ids="文章ID,多个用英文逗号分开"]</code>，使用专门的清单模板（<a target="_blank" href="http://img2.wptao.cn/3/large/62579065gy1fyuq8iq4f0j20su0jx4jm.jpg">查看演示</a>）
					  <br/>默认进入购买页面，如果要进入文章，可以用<code>[shop ids="文章ID,多个用英文逗号分开" <font color="red">p=1</font>]</code>
					  </td>
					</tr>
					<?php
					if (version_compare($wp_version, '5.0', '>=')) { ?>
					<tr>
					  <th scope="row">使用WordPress旧版编辑器</th>
					  <td><input type="hidden" name="wptao[editor]" value="0"><label><input name="wptao[editor]" type="checkbox" value="1"<?php checked($wptao['editor']); ?>>开启（推荐）</label></td>
					</tr>
					<?php } ?>
                    </tbody>
                  </table>
                </div>
                <!-- end of inside -->
              </div>
              <!-- end of postbox -->
              <div class="postbox">
                <h3 class="hndle">
                  <label for="title">编辑器按钮【淘】插入多个商品</label>
                </h3>
                <div class="inside">
                  <table class="form-table">
                    <tbody>
					<tr>
					  <th scope="row">添加按钮</th>
					  <td><label><input type="checkbox" id="wptao_mce" name="wptao[mce]" value="1"<?php checked($wptao['mce']); ?>>在编辑器添加【插入商品】按钮 [<a target="_blank" href="http://img2.wptao.cn/3/large/62579065gw1exzru5novuj20eb03674i.jpg">查看</a>]</label> （<label><input type="checkbox" id="wptao_mce_mm" name="wptao[mce_mm]" value="1"<?php checked($wptao['mce_mm']); ?>>允许插入推广链接（推荐）</label>）
					  </td>
					</tr>
					<tr>
					  <th scope="row">默认模板</th>
					  <td><p><label><input type="radio" name="wptao[theme]" value="1"<?php checked($wptao['theme'] == 1); ?>>模板1</label> [<a target="_blank" href="http://img2.wptao.cn/3/large/62579065gw1eyflo4e4x2j20eg046js4.jpg">查看</a>]</p>
						<p><label><input type="radio" name="wptao[theme]" value="2"<?php checked(!$wptao['theme'] || $wptao['theme'] == 2); ?>>模板2（推荐）</label> [<a target="_blank" href="http://img2.wptao.cn/3/large/62579065gw1eyflo3r1fzj20ek04wwfd.jpg">查看</a>]</p>
						<p><label><input type="radio" name="wptao[theme]" value="3"<?php checked($wptao['theme'] == 3); ?>>模板3</label> [<a target="_blank" href="http://img2.wptao.cn/3/large/62579065gy1fsntpjk0hij20fn0fnwf5.jpg">查看</a>]</p>
						<p><label><input type="radio" name="wptao[theme]" value="4"<?php checked($wptao['theme'] == 4); ?>>模板4</label> [<a target="_blank" href="http://img2.wptao.cn/3/large/62579065gy1fwij5qr9m5j20op0ma4bw.jpg">查看</a>]</p>
						<p><label><input type="radio" name="wptao[theme]" value="5"<?php checked($wptao['theme'] == 5); ?>>模板5</label> [<a target="_blank" href="http://img2.wptao.cn/3/large/62579065gy1fyth3pdmrfj20u00jb15k.jpg">查看</a>]</p>
						提示：您也可以在插入时任意选择具体的模版</td>
					</tr>
					<tr>
					  <th scope="row">淘口令</th>
					  <td><input type="hidden" name="wptao[mce_tkl]" value="0"><label><input type="checkbox" id="wptao_mce" name="wptao[mce_tkl]" value="1"<?php checked($wptao['mce_tkl']); ?>>存在淘口令时，在[推荐理由]开头输出显示</label>
					  </td>
					</tr>
					<tr>
					  <th scope="row">[推荐理由]自定义内容<br>(支持HTML)</th>
					  <td><p><label>自定义的内容插入到【推荐理由】的</label> <label><input type="radio" name="wptao[mce_desc_p]" value="0"<?php checked(!$wptao['mce_desc_p']); ?>>最前面</label> <label><input type="radio" name="wptao[mce_desc_p]" value="1"<?php checked($wptao['mce_desc_p']); ?>>最后面</label></p>
					  <textarea name="wptao[mce_desc]" rows="4" cols="60"><?php echo stripslashes($wptao['mce_desc']);?></textarea>
					  <br />标签: 插入的自定义内容：<code>#desc#</code>，仅手动添加时有效。
					  </td>
					</tr>
                    </tbody>
                  </table>
                </div>
                <!-- end of inside -->
              </div>
              <!-- end of postbox -->
            </div>
            <div id="group-2" class="group" style="display: none;">
              <div class="postbox">
                <h3 class="hndle">
                  <label for="title">淘宝联盟</label>
                </h3>
                <div class="inside">
                  <table class="form-table">
                    <tbody>
					<tr>
					  <th scope="row">App Key (*)</th>
					  <td><input type="text" name="wptao[union][appkey]" size="40" value="<?php echo $wptao['union']['appkey'];?>" />
					  <br /><code><a target="_blank" href="https://pub.alimama.com/third/manage/record/site.htm?tab=self_web_site">在淘宝联盟-推广管理-媒体备案管理-自有平台-网站</a>，点击【APPKEY申请-查看】</code></td>
					</tr>
					<tr>
					  <th scope="row">App Secret</th>
					  <td><input type="text" name="wptao[union][secret]" size="40" value="<?php echo $wptao['union']['secret'];?>" />
					  <br /><code>获取方法同上，<a target="_blank" href="http://img2.wptao.cn/3/large/62579065gw1f95ppp708jj20dw062q3t.jpg">位置如图</a></code></td>
					</tr>
					<tr>
					  <th scope="row">PID(*)</th>
					  <td><input name="wptao[pid]" type="text" value="<?php echo $wptao['pid'];?>" size="40" /> <a target="_blank" href="https://wptao.com/wptao.html#pid">如何获取？</a></td>
					</tr>
					<tr>
					  <th scope="row"></th>
					  <td><label><input type="checkbox" name="wptao[pids]" value="1"<?php checked($wptao['pids']); ?>>填写更多PID</label> （<a target="_blank" href="?page=wptao-pids">添加</a>）<code><span style="color:#f50">【仅<a target="_blank" href="https://wptao.com/download">尊享版</a>】</span>管理员可以给小编分配PID，方便小编或者合伙人参与分成或者考核小编业绩。</code></td>
					</tr>
					<tr>
					  <th scope="row">高佣金授权</th>
					  <td>到期前邮箱提醒，请填写邮箱地址：<input name="wptao[email]" type="text" value="<?php echo $wptao['email'];?>" size="20" /> 
					  <p><?php echo wptao_taobao_oauth($wptao);?></p>
					  </td>
					</tr>
					<tr>
					  <th scope="row">微信公众号找券pid</th>
					  <td><input type="text" name="wptao[wx_pid]" size="40" value="<?php echo $wptao['wx_pid'];?>" /> <a target="_blank" href="https://wptao.com/wptao.html#pid">如何获取？</a><br /><code>选填，为便于统计收益，建议不要跟其他找券pid相同。(必须写当前网站的pid) 具体看【公众号找券】</code></td>
					</tr>
					<tr>
					  <th scope="row">微博粉丝找券pid</th>
					  <td><input type="text" name="wptao[wb_pid]" size="40" value="<?php echo $wptao['wb_pid'];?>" /> <a target="_blank" href="https://wptao.com/wptao.html#pid">如何获取？</a><br /><code>选填，为便于统计收益，建议不要跟其他找券pid相同。(必须写当前网站的pid) 具体看【公众号找券】</code></td>
					</tr>
                    </tbody>
                  </table>
                </div>
                <!-- end of inside -->
              </div>
              <!-- end of postbox -->
              <div class="postbox">
                <h3 class="hndle">
                  <label for="title">淘宝联盟-淘口令</label>
                </h3>
                <div class="inside">
                  <table class="form-table">
                    <tbody>
					<tr>
					  <th scope="row">获取淘口令</th>
					  <td><label><input type="checkbox" name="wptao[tkl]" value="1"<?php checked($wptao['tkl']); ?>>开启</label>
					  <?php if(isset($wptao['tkl_error'])) echo '<span style="color:#f50">'.$wptao['tkl_error'].'</span>';?><br /><code>提示：淘口令接口目前免费。（需要在【联盟设置】填写App Key和App Secret，淘口令有效期为7天，7天后如果有人访问会换新的淘口令。）</code>
					  </td>
					</tr>
					<tr>
					  <th scope="row"></th>
					  <td><span style="color:#f50">【以下功能仅限旗舰版及以上用户】</span></td>
					</tr>
					<tr>
					  <th scope="row">显示淘口令</th>
					  <td><label><input type="checkbox" checked="checked" disabled="disabled" />微信/微博APP</label>&nbsp;
						<label><input type="checkbox" name="wptao[tkl_qq]" value="1"<?php checked($wptao['tkl_qq']); ?>>QQ/QQ空间APP</label>&nbsp;
						<label><input type="checkbox" name="wptao[tkl_mobile]" value="1"<?php checked($wptao['tkl_mobile']); ?>>所有移动端</label>
						<br /><code>提示：默认情况下，在页面或者进入新链接后显示淘口令</code>
					  </td>
					</tr>
					<tr>
					  <th scope="row">弹出淘口令</th>
					  <td><label><input type="checkbox" name="wptao[tkl_wx]" value="1"<?php checked($wptao['tkl_wx']); ?>>点击购买时直接弹出淘口令复制框，仅存在淘口令时显示。</label></td>
					</tr>
					<tr>
					  <th scope="row">文章中插入淘口令</th>
					  <td>在正文段落插入淘口令，仅存在淘口令时显示。
					  <br />插入到 <select name="wptao[tkl_post]">
					  <option value="0"<?php selected(!$wptao['tkl_post']);?>>不插入</option>
					  <option value="1"<?php selected($wptao['tkl_post'] == 1);?>>第二段落</option>
					  <option value="9"<?php selected($wptao['tkl_post'] == 9);?>>文章末尾</option>
					  </select>
					  </td>
					</tr>
                    </tbody>
                  </table>
                </div>
                <!-- end of inside -->
              </div>
              <!-- end of postbox -->
              <div class="postbox">
                <h3 class="hndle">
                  <label for="title">多麦联盟</label>
                </h3>
                <div class="inside">
                  <table class="form-table">
                    <tbody>
					<tr>
					  <th scope="row"></th>
					  <td>京东/拼多多/考拉/苏宁/国美/当当网等也可以直接使用【多麦联盟】，需要您去多麦手动申请活动。</td>
					</tr>
					<tr>
					  <th scope="row"><label for="wptao_dm_siteid">媒体ID</label></th>
					  <td><input type="text" id="wptao_dm_siteid" name="wptao[union][dm_siteid]" size="40" value="<?php echo $wptao['union']['dm_siteid'];?>" /> 【<a target="_blank" href="https://union.duomai.com/manage/media">去获取</a>】 <a target="_blank" href="http://img2.wptao.cn/3/large/62579065gw1eun9yn5moxj20dn07o0ty.jpg">查看位置</a></td>
					</tr>
                    </tbody>
                  </table>
                </div>
                <!-- end of inside -->
              </div>
              <!-- end of postbox -->
              <div class="postbox">
                <h3 class="hndle">
                  <label for="title">京东联盟（可选，也可以使用多麦联盟）</label>
                </h3>
                <div class="inside">
                  <table class="form-table">
                    <tbody>
					<tr>
					  <th scope="row">appkey</th>
					  <td><input type="text" name="wptao[union][jd_appkey]" size="40" value="<?php echo $wptao['union']['jd_appkey'];?>" />
					  <br /><code>推荐，在京东联盟-<a target="_blank" href="https://union.jd.com/manager/webMng">我的推广-推广管理-网站管理</a>-点击【查看】</code></td>
					</tr>
					<tr>
					  <th scope="row">secretkey</th>
					  <td><input type="text" name="wptao[union][jd_secret]" size="40" value="<?php echo $wptao['union']['jd_secret'];?>" />
					  <br /><code>推荐，获取方法同上</code></td>
					</tr>
					<tr>
					  <th scope="row">PID</th>
					  <td><input type="text" name="wptao[union][jd_pid]" size="40" value="<?php echo $wptao['union']['jd_pid'];?>" />
					  <br /><code>在京东联盟-<a target="_blank" href="https://union.jd.com/manager/promotionSite">我的推广-推广管理-推广位管理</a>-选择当前网站的【PID】</code></td>
					</tr>
                    </tbody>
                  </table>
                </div>
                <!-- end of inside -->
              </div>
              <!-- end of postbox -->
              <div class="postbox">
                <h3 class="hndle">
                  <label for="title">苏宁联盟（可选，也可以使用多麦联盟）</label>
                </h3>
                <div class="inside">
                  <table class="form-table">
                    <tbody>
					<tr>
					  <th scope="row">App key</th>
					  <td><input type="text" name="wptao[union][sn_appkey]" size="40" value="<?php echo $wptao['union']['sn_appkey'];?>" />
					  <br /><code>推荐，在苏宁开放服务-<a target="_blank" href="https://open.suning.com/ospos/appMgt/CShop/toAppList.action">控制台</a>申请，然后在【应用管理-应用证书】获取</code></td>
					</tr>
					<tr>
					  <th scope="row">App Secret</th>
					  <td><input type="text" name="wptao[union][sn_secret]" size="40" value="<?php echo $wptao['union']['sn_secret'];?>" />
					  <br /><code>推荐，获取方法同上</code></td>
					</tr>
					<tr>
					  <th scope="row">PID</th>
					  <td><input type="text" name="wptao[union][sn_pid]" size="40" value="<?php echo $wptao['union']['sn_pid'];?>" />
					  <br /><code>在苏宁联盟-<a target="_blank" href="http://sums.suning.com/union/member/myPromotion/promotionPosition/list.htm">首页-联盟推广-推广管理-推广位管理</a>-选择一个【推广位】，如果没有找到，先去【推广方式-店铺推广】随便找个店铺，点击【立即推广-新建推广位-领取代码】，然后再去推广位管理查看。</code></td>
					</tr>
                    </tbody>
                  </table>
                </div>
                <!-- end of inside -->
              </div>
              <!-- end of postbox -->
              <div class="postbox">
                <h3 class="hndle">
                  <label for="title">拼多多（可选，也可以使用多麦联盟）</label>
                </h3>
                <div class="inside">
                  <table class="form-table">
                    <tbody>
					<tr>
					  <th scope="row"></th>
					  <td>【<a target="_blank" href="http://go.wptao.com/open-pdd">申请教程</a>】申请要填写的信息比较多，建议直接用【多麦联盟】。
					  <br />PS:申请应用时需要的【MRD】和【PRD】上传PDF文件，可以用空白的word另成为PDF文件。</td>
					</tr>
					<tr>
					  <th scope="row">client_id</th>
					  <td><input type="text" name="wptao[union][pdd_appkey]" size="40" value="<?php echo $wptao['union']['pdd_appkey'];?>" />
					  <br /><code>推荐，在拼多多开放平台-<a target="_blank" href="https://open.pinduoduo.com/#/application/index">我的应用</a>，<span style="color:red">同时请在<a target="_blank" href="https://jinbao.pinduoduo.com/third-party/rank">开发者中心</a>绑定【Client ID】</span></code></td>
					</tr>
					<tr>
					  <th scope="row">client_secret</th>
					  <td><input type="text" name="wptao[union][pdd_secret]" size="40" value="<?php echo $wptao['union']['pdd_secret'];?>" />
					  <br /><code>推荐，获取方法同上</code></td>
					</tr>
					<tr>
					  <th scope="row">PID</th>
					  <td><input type="text" name="wptao[union][pdd_pid]" size="40" value="<?php echo $wptao['union']['pdd_pid'];?>" />
					  <br /><code>在多多进宝-<a target="_blank" href="https://jinbao.pinduoduo.com/manage/pid-manage">效果数据-推手推广效果-推广位管理</a>-选择一个【PID】</code></td>
					</tr>
                    </tbody>
                  </table>
                </div>
                <!-- end of inside -->
              </div>
              <!-- end of postbox -->
              <div class="postbox">
                <h3 class="hndle">
                  <label for="title">考拉海购（可选，也可以使用多麦联盟）</label>
                </h3>
                <div class="inside">
                  <table class="form-table">
                    <tbody>
					<tr>
					  <th scope="row">赚客ID</th>
					  <td><input type="text" name="wptao[union][kaola_id]" size="40" value="<?php echo $wptao['union']['kaola_id'];?>" /> <a target="_blank" href="https://pub.kaola.com/accountManagement">去获取</a>【<a target="_blank" href="http://img2.wptao.cn/images/qrcode-kaola.jpg">微信扫一扫加入</a>】</td>
					</tr>
					<tr>
					  <th scope="row">PID</th>
					  <td><input type="text" name="wptao[union][kaola_pid]" size="40" value="<?php echo $wptao['union']['kaola_pid'];?>" /> <a target="_blank" href="https://pub.kaola.com/promotion/promoteManage">去获取</a></td>
					</tr>
                    </tbody>
                  </table>
                </div>
                <!-- end of inside -->
              </div>
              <!-- end of postbox -->
              <div class="postbox">
                <h3 class="hndle">
                  <label for="title">当当网联盟（可选，也可以使用多麦联盟）</label>
                </h3>
                <div class="inside">
                  <table class="form-table">
                    <tbody>
					<tr>
					  <th scope="row">联盟ID</th>
					  <td><input type="text" name="wptao[union][dangdang_from]" size="40" value="<?php echo $wptao['union']['dangdang_from'];?>" /> <a target="_blank" href="https://wptao.com/wp-taomall.html#dangdang">如何获取？</a></td>
					</tr>
                    </tbody>
                  </table>
                </div>
                <!-- end of inside -->
              </div>
              <!-- end of postbox -->
              <div class="postbox">
                <h3 class="hndle">
                  <label for="title">亚马逊</label>
                </h3>
                <div class="inside">
                  <table class="form-table">
                    <tbody>
					<tr>
					  <th scope="row"><label for="wptao_z_tag"><a target="_blank" href="https://associates.amazon.cn/">亚马逊中国</a>-跟踪代码</label></th>
					  <td><input type="text" id="wptao_z_tag" name="wptao[union][z_tag]" size="40" value="<?php echo $wptao['union']['z_tag'];?>" /> <a target="_blank" href="http://img2.wptao.cn/3/large/62579065gw1eund96vttij205c04omxa.jpg">查看</a></td>
					</tr>
					<tr>
					  <th scope="row"><label for="wptao_z_tag_com"><a target="_blank" href="https://affiliate-program.amazon.com/">美国亚马逊</a>-跟踪代码</label></th>
					  <td><input type="text" id="wptao_z_tag_com" name="wptao[union][z_tag_com]" size="40" value="<?php echo $wptao['union']['z_tag_com'];?>" /> <a target="_blank" href="http://img2.wptao.cn/3/large/62579065gw1eund96vttij205c04omxa.jpg">查看</a></td>
					</tr>
                    </tbody>
                  </table>
                </div>
                <!-- end of inside -->
              </div>
              <!-- end of postbox -->
              <div class="postbox">
                <h3 class="hndle">
                  <label for="title">1688分销客</label>
                </h3>
                <div class="inside">
                  <table class="form-table">
                    <tbody>
					<tr>
					  <th scope="row"></th>
					  <td>由于API授权的特殊性，需要另外收费。联系方式见本页右侧边栏。</td>
					</tr>
					<tr>
					  <th scope="row">PID</th>
					  <td><input type="text" name="wptao[union][1_pid]" size="40" value="<?php echo $wptao['union']['1_pid'];?>" /> <a target="_blank" href="https://aliance.1688.com/">去获取</a></td>
					</tr>
                    </tbody>
                  </table>
                </div>
                <!-- end of inside -->
              </div>
              <!-- end of postbox -->
            </div>
            <div id="group-3" class="group" style="display: none;">
              <div class="postbox">
                <h3 class="hndle">
                  <label for="title">对接主题</label>
                </h3>
                <div class="inside">
                  <table class="form-table">
                    <tbody>
					<tr>
					  <th scope="row">是否对接？</th>
					  <td><p style="color:#f50">1.只有你主题有以下字段才在对应的字段配置下，不要乱写，否则不会显示的。
					  <br />2.如果你主题<strong>没有</strong>以下字段，需要用到【自动采集】，你只需要配置【商品链接】，如果需要用到特色图片，就配置下【商品图片】，其它的留空即可。
					  <br />3.如果您使用普通主题，可以看下【高级设置-改造成淘宝客主题】。
					  </p></td>
					</tr>
					<tr>
					  <th scope="row">显示商品信息模块（<a target="_blank" href="http://img2.wptao.cn/3/large/62579065gw1fa4e5fo48wg20i80c1q3q.gif">看效果</a>）</th>
					  <td><label><input type="checkbox" name="wptao[box]" value="1"<?php checked($wptao['box']); ?>> 是 </label>
					  <p><code>根据下面的配置显示相应信息，开启后将关闭【手动】的相关配置，可以替换主题自带的发布商品模块</code></p></td>
					</tr>
					<tr>
					  <th scope="row">创建商城分类</th>
					  <td><label><input type="checkbox" name="wptao[taxonomy_mall]" value="1"<?php checked($wptao['taxonomy_mall']); ?> disabled /> 是 </label>
					  <span style="color:#f50">仅旗舰版及以上版本才可以使用。</span>
					  <p><code>如果原主题没有商城分类，建议开启。首次开启保存后需要刷新一下本页面。</code></p></td>
					</tr>
					<tr>
					  <th scope="row">WooCommerce 对接</th>
					  <td><?php if (function_exists('wc_get_product_types')) { ?><label><input type="checkbox" name="wptao[woocommerce]" value="1"<?php checked($wptao['woocommerce']); ?> disabled /> 是 </label>
					  <span style="color:#f50">仅旗舰版及以上版本才可以使用。</span>
					  <p><code><a target="_blank" href="http://img2.wptao.cn/images/wptao-woocommerce.gif">如何在 WooCommerce 中快速添加商品？看图秒懂，也支持【自动采集】</a></code><?php } else {echo '<code>安装 WooCommerce 后才会显示哦</code>';} ?></p>
					  <p><code>针对使用 WooCommerce 的用户进行必备字段的预设置，不影响下面商品信息字段的修改。</code></p></td>
					</tr>
					<tr>
					  <th scope="row">我用的是普通主题</th>
					  <td><label><input type="checkbox" name="wptao[default_theme]" value="1"<?php checked($wptao['default_theme']); ?> disabled /> 是</label>
					  <span style="color:#f50">仅旗舰版及以上版本才可以使用。</span>
					  <p><code>提示：勾选后要先保存再根据需求设置下面哦，将保存商品信息到自定义栏目的 <strong>tbk</strong> 字段</code></p>
					  <p style="color:#f50">如果您的主题发布文章时不能填写商品信息等，这就属于普通主题，建议开启它。
					  <br />如果您早期版本配置了下面的【商品信息】字段，建议不要清空配置数据，直接开启本功能即可。</p></td>
					</tr>
					<tr>
					  <th scope="row"><strong>商品信息（推荐）</strong></th>
					  <td>下面填写<span style="color:#f50">自定义栏目名称</span>（不要使用中文，具体见发布页面，<a target="_blank" href="https://wptao.com/wptao.html#metaid">看教程</a>）</td>
					</tr>
					<?php
					$options = wptao_save_fields();
					foreach ($options as $key => $value) {
						if ($key == 'text0') {
							echo '<tr><th scope="row"><strong>' . $value['title'] . '</strong></th><td>' . $value['_desc'] . '</td></tr>';
						} else {
							echo '<tr><th scope="row"><label for="wptao_cj_' . $key . '">' . $value['title'] . '</label></th><td><label><input id="wptao_cj_' . $key . '" name="wptao[caiji][' . $key . ']" type="text" value="' . $wptao['caiji'][$key] . '" /></label>';
							echo $value['_desc'] ? '<p class="description"><code>' . $value['_desc'] . '</code></p>' : '';
							if ($key == 'red_title') {
								echo '<p>格式：<select name="wptao[red_title_format]"><option value="1">xxx元或者券后xxx元</option><option value="2"'. selected($wptao['red_title_format'] == 2, true, false) .'>xxx元或者优惠券后xxx元</option></select></p><p><label><input type="checkbox" name="wptao[red_title_add]" value="1"'. checked($wptao['red_title_add'], true, false) .'> 添加到文章标题最后面(不含红色字体) </label><code>或者自定义函数(含红色字体)：&lt;?php if (function_exists(\'wptao_red_title\')) echo wptao_red_title($post->ID);?&gt;</code></p>';
							}
							echo '</td></tr>';
						}
					} 
					?>
                    </tbody>
                  </table>
                </div>
                <!-- end of inside -->
              </div>
              <!-- end of postbox -->
            </div>
            <div id="group-4" class="group" style="display: none;">
              <div class="postbox">
                <h3 class="hndle">
                  <label for="title">高级设置</label>
                </h3>
                <div class="inside">
				  <?php echo $ultimate_desc;?>
                  <table class="form-table">
                    <tbody>
					<tr>
					  <th scope="row">替换主题的内链</th>
					  <td><label><input type="radio" name="wptao[neilian]" value="0"<?php checked(!$wptao['neilian']); ?>>不替换&nbsp;</label>
						<label><input type="radio" name="wptao[neilian]" value="1"<?php checked($wptao['neilian'] == 1); ?>>仅替换淘宝天猫内链&nbsp;</label>
						<label><input type="radio" name="wptao[neilian]" value="2"<?php checked($wptao['neilian'] == 2); ?>>替换全部商品内链</label>
					  </td>
					</tr>
					<tr>
					  <th scope="row">内链格式</th>
					  <td><code><?php echo home_url('/');?><input type="text" name="wptao[detail]" size="8" value="<?php echo $wptao['detail'] ? $wptao['detail'] : 'go';?>" />/xxx</code><br />确定后请不要随意更改。<?php if($wptao['detail']) echo '如果您使用缓存插件，如果可以过滤链接，比如WP Super Cache，可以在该插件的高级中“在这里添加强制禁止缓存的页面的地址关键字”，增加一个 <code>/'.$wptao['detail'].'/</code>';?>
					  <p><label><input type="checkbox" name="wptao[nobuylink]" value="1"<?php checked($wptao['nobuylink']); ?>>禁止搜索引擎收录购买链接（返回404）</label></p></td>
					</tr>
					<tr>
					  <th scope="row">单品+优惠券二合一</th>
					  <td><label><input type="radio" name="wptao[coupon_both]" value="1"<?php checked($wptao['coupon_both'] == 1); ?>>开启（推荐）</label>
						<label><input type="radio" name="wptao[coupon_both]" value="0"<?php checked($wptao['coupon_both'] == '0'); ?>>关闭</label>
						<p><code>仅开启内链时有效，如果你设置了默认货币符号（代购模式），此处无法开启。</code></p>
						</td>
					</tr>
					<?php if ($wptao['tbk']) { ?>
					<tr>
					  <th scope="row">使用旧主题数据</th>
					  <td><label><input type="checkbox" name="wptao[old_data]" value="1"<?php checked($wptao['old_data']); ?>> 开启</label></label> (<code>如果不开启，数据正常可以用，请不要开启</code>)</td>
					</tr>
					<?php } ?>
					<tr>
					  <th scope="row">商品过期后</th>
					  <td><select name="wptao[shop_soldout]">
					  <option value="0">提示已抢光了</option>
					  <option value="1"<?php selected($wptao['shop_soldout']);?>>商品移至回收站（不利于SEO）</option>
					  </select>（<a target="_blank" href="http://img2.wptao.cn/3/large/62579065gw1faaedvncxcg20i605uq3a.gif">看效果</a>）
					  <p><code>会自动检查商品是否下架，优惠券是否过期或者领完。每个商品每2小时检查一次（当打开购买链接时触发）。</code></p>
					  <p>如果原主题不支持，可以在主题适当位置（一般添加在图片后面或者文章内容前面）添加函数:<br /> <code>&lt;?php if (function_exists('wptao_soldout')) echo wptao_soldout();?&gt;</code></p>
					  </td>
					</tr>
					<tr>
					  <th scope="row">优惠券过期后</th>
					  <td><select name="wptao[coupon_end]">
					  <option value="0"<?php selected(!$wptao['coupon_end']);?>>不处理</option>
					  <option value="1"<?php selected($wptao['coupon_end']==1);?>>删除优惠券（推荐）</option>
					  <option value="2"<?php selected($wptao['coupon_end']==2);?>>商品移至回收站（不利于SEO）</option>
					  </select></td>
					</tr>
                    </tbody>
                  </table>
                </div>
                <!-- end of inside -->
              </div>
              <!-- end of postbox -->
              <div class="postbox">
                <h3 class="hndle">
                  <label for="title">一键发布</label>
                </h3>
                <div class="inside">
				  <p class="wptao-box yellow">提示：至少需要编辑及以上权限才可以使用一键发布。</p>
                  <table class="form-table">
                    <tbody>
					<tr>
					  <th scope="row">【推送到网站】验证码</th>
					  <td><textarea rows="3" cols="80">仅旗舰版及以上版本才可以使用。</textarea>
					  <br /><code>用于【wptao】提供的【<a target="_blank" href="https://wptao.com/ku?today=1">商品库</a>】将您选中的商品一键推送到网站。如果验证码失效请刷新本页面。</code></td>
					</tr>
                    </tbody>
                  </table>
                </div>
                <!-- end of inside -->
              </div>
              <!-- end of postbox -->
              <div class="postbox">
                <h3 class="hndle">
                  <label for="title">改造成淘宝客主题</label>
                </h3>
                <div class="inside">
				  <p class="wptao-box yellow">如果您的主题是普通主题（即没有直达链接/购买链接），也可以改造成淘宝客主题，即给文章增加【去购买】链接。
				  <br />1.使用此功能时，您必须在【对接主题】配置【直达链接/推广链接】（可以写<code>buylink</code>）、【商品链接】、【优惠券链接】、【红色标题】、【商品价格】等。
				  <br />2.如果下面设置无效，您可以直接修改主题文件，在适当位置添加函数：<code>&lt;?php if (function_exists('wptao_buylink')) echo wptao_buylink($post->ID);?&gt;</code> 可根据需要添加参数，第2个参数是购买文字（默认：去购买），第3个参数是样式class（默认：wptao-buy）</p>
                  <table class="form-table">
                    <tbody>
					<tr>
					  <th scope="row">网站首页/列表页/归档页</th>
					  <td><select name="wptao[buy_index]">
					  <option value="0"<?php selected(!$wptao['buy_index']);?>>不添加</option>
					  <option value="1"<?php selected($wptao['buy_index']==1);?>>添加【去购买】到文章最前面</option>
					  <option value="2"<?php selected($wptao['buy_index']==2);?>>添加【去购买】到文章最末尾</option>
					  </select></td>
					</tr>
					<tr>
					  <th scope="row">文章页</th>
					  <td><select name="wptao[buy_single]">
					  <option value="0"<?php selected(!$wptao['buy_single']);?>>不添加</option>
					  <option value="1"<?php selected($wptao['buy_single']==1);?>>添加【去购买】到文章最前面</option>
					  <option value="2"<?php selected($wptao['buy_single']==2);?>>添加【去购买】到文章最末尾</option>
					  </select></td>
					</tr>
                    </tbody>
                  </table>
                </div>
                <!-- end of inside -->
              </div>
              <!-- end of postbox -->
              <div class="postbox">
                <h3 class="hndle">
                  <label for="title">默认货币</label>
                </h3>
                <div class="inside">
                  <table class="form-table">
                    <tbody>
					<tr>
					  <th scope="row"></th>
					  <td>适用于代购模式，其他情况请不要设置，设置后，【单品+优惠券二合一】和 【优惠券】会失效。</td>
					</tr>
					<tr>
					  <th scope="row">货币</th>
					  <td><p>货币符号 <input name="wptao[currency][symbol]" type="text" value="<?php echo $wptao['currency']['symbol']; ?>" size="10" /> 默认为<code>￥</code></p><p>货币代码 <input name="wptao[currency][code]" type="text" value="<?php echo $wptao['currency']['code']; ?>" size="10" /> 默认为<code>CNY</code> [<a target="_blank" href="https://baike.baidu.com/item/ISO%204217">参考</a>]</p></td>
					</tr>
					<tr>
					  <th scope="row">汇率</th>
					  <td>1元人民币 等于 <input name="wptao[exchange]" type="text" value="<?php echo $wptao['exchange']; ?>" size="10" onkeyup="value=value.replace(/[^\d.]/g,'')" /> <?php echo $wptao['currency']['symbol']; ?> （即上面设置的默认货币）</td>
					</tr>
					<tr>
					  <th scope="row">价格</th>
					  <td><input type="hidden" name="wptao[currency][save]" value="1" />价格保存用您设置的默认货币，会自动转汇率（支持获取信息、软件采集转换、自动采集等），<br />编辑器【淘插入】的价格保存用人民币，仅在前台输出时转换为您设置默认货币汇率。</p>
					  </td>
					</tr>
                    </tbody>
                  </table>
                </div>
                <!-- end of inside -->
              </div>
              <!-- end of postbox -->
            </div>
            <div id="group-15" class="group" style="display: none;">
              <div class="postbox">
                <h3 class="hndle">
                  <label for="title">微信公众号找券</label>
                </h3>
                <div class="inside">
				  <?php echo $ultimate_desc;?>
				  <p class="wptao-box yellow">1.微信公众号能识别用户发送的 淘宝商品文案+URL（比如微信群/淘宝/联盟APP等分享的内容），并且转链返回该商品。<a target="_blank" href="http://img2.wptao.cn/3/mw690/62579065gy1fyns2siaw5j20u01qgwos.jpg">查看示例图</a>
				  <br /><span<?php echo (!$wptao['wx_pid']) ? ' style="color:red"' : '';?>>2.需要在【联盟设置】填写淘宝联盟-找券pid，以及App Key和App Secret。</span>
				  <br />3.需要安装<a target="_blank" href="https://wptao.com/wechat.html">WordPress连接微信</a>插件V1.8.2及以上版本，也支持<a target="_blank" href="http://www.smyx.net/sina-public-platform.html">微博粉丝服务</a></p>
                  <table class="form-table">
                    <tbody>
					<tr>
					  <th scope="row">默认显示</th>
					  <td><select name="wptao[wx_mp]">
					  <option value="0">不使用</option>
					  <option value="1"<?php selected($wptao['wx_mp'] == 1);?>>先站内搜索文章，找不到再全网找券</option>
					  <option value="2"<?php selected($wptao['wx_mp'] == 2);?>>先全网找券，找不到再站内搜索文章</option>
					  </select>
					  <?php if(isset($wptao['mp_error'])) echo '<p style="color:#f50">'.$wptao['mp_error'].'</p>';?>
					  </td>
					</tr>
					<tr>
					  <th scope="row">显示条数</th>
					  <td><input type="text" name="wptao[wx_item]" value="<?php echo $wptao['wx_item'] ? (int)$wptao['wx_item'] : 6;?>" size="5" onkeyup="value=value.replace(/[^\d]/g,'')"><br /><code>最多8条</code></td>
					</tr>
					<tr>
					  <th scope="row">全网搜索结果</th>
					  <td><select name="wptao[search_sort]">
					  <option value="3"<?php selected($wptao['search_sort'] == 3);?>>销量从高到低</option>
					  <option value="1"<?php selected($wptao['search_sort'] == 1);?>>价格从低到高</option>
					  <option value="4"<?php selected($wptao['search_sort'] == 4);?>>佣金从高到低</option>
					  <option value="5"<?php selected($wptao['search_sort'] == 5);?>>累计推广量从高到低</option>
					  <option value="6"<?php selected($wptao['search_sort'] == 6);?>>总支出佣金从高到低</option>
					  </select></td>
					</tr>
					<tr>
					  <th scope="row"></th>
					  <td><label><input name="wptao[search_tmall]" type="checkbox" value="1"<?php checked($wptao['search_tmall']);?>>搜索结果只显示天猫商品（不推荐，可能会找不到商品哦）</label></td>
					</tr>
					<tr>
					  <th scope="row">结果随机显示</th>
					  <td><label><input name="wptao[wx_rand]" type="checkbox" value="1"<?php checked($wptao['wx_rand']);?>>开启</label>
					  <br /><code>如果商品较多，可以每次输出不同商品</code></td>
					</tr>
					<tr>
					  <th scope="row">允许使用商品URL直接搜索</th>
					  <td><label><input name="wptao[search_url]" type="checkbox" value="1"<?php checked($wptao['search_url']);?>>开启（慎用，有扣分风险）</label>
					  <br /><code>由于淘宝联盟禁止该搜索方式，为了安全，请慎重选择。</code></td>
					</tr>
					<tr>
					  <th scope="row">全网找券返回仅一条时</th>
					  <td><label><input name="wptao[wx_tkl]" type="checkbox" value="1"<?php checked($wptao['wx_tkl']);?>>直接返回淘口令+文案</td>
					</tr>
                    </tbody>
                  </table>
                </div>
                <!-- end of inside -->
              </div>
              <!-- end of postbox -->
            </div>
            <div id="group-5" class="group" style="display: none;">
              <div class="postbox">
                <h3 class="hndle">
                  <label for="title">自动采集</label>
                </h3>
                <div class="inside">
                  <p class="wptao-box">此功能仅尊享版及以上用户，免费版/基础版/旗舰版无法测试，购买后，需要到我网站<a target="_blank" href="https://wptao.com/download">下载新的安装包</a></p>
                  <table class="form-table">
                    <tbody>
					<tr>
					  <th scope="row">自动采集</th>
					  <td><label><input type="checkbox" name="wptao[cj][open]" value="1"<?php checked($wptao['cj']['open']); ?>> 开启 </label>
					  <?php if(isset($wptao['auto_error'])) echo '<p style="color:#f50">'.$wptao['auto_error'].'</p>';?>
					  <p><code>根据价格、销量、佣金比例、分类等自动采集当天优质商品，包括申请高佣金、自动转链入库。商品库由<a target="_blank" href="https://wptao.com/ku">挖品淘</a>每日精心挑选。</p></td>
					</tr>
					<tr>
					  <th scope="row">采集后</th>
					  <td><label><input type="radio" name="wptao[cj][post]" value="0"<?php checked(!$wptao['cj']['post']); ?>>立即发布</label>
						<label><input type="radio" name="wptao[cj][post]" value="1"<?php checked($wptao['cj']['post']==1); ?>>待审核</label>
						<label><input type="radio" name="wptao[cj][post]" value="2"<?php checked($wptao['cj']['post']==2); ?>>每篇延迟</label>
						<input name="wptao[cj][yc]" type="text" value="<?php echo $wptao['cj']['yc'] ? $wptao['cj']['yc'] : 1; ?>" size="5" onkeyup="value=value.replace(/[^\d]/g,'')" />分钟后发布。
						<p><label><input type="checkbox" name="wptao[cj][views]" value="1"<?php checked($wptao['cj']['views']); ?>>随机阅读数</label>（范围：<input name="wptao[cj][views_min]" type="text" value="<?php echo $wptao['cj']['views_min'] ? $wptao['cj']['views_min'] : 1; ?>" size="5" onkeyup="value=value.replace(/[^\d]/g,'')" /> - <input name="wptao[cj][views_max]" type="text" value="<?php echo $wptao['cj']['views_max'] ? $wptao['cj']['views_max'] : 20; ?>" size="5" onkeyup="value=value.replace(/[^\d]/g,'')" />）
						</p>
						<p>自动置顶热门商品 <input name="wptao[cj][sticky]" type="text" value="<?php echo $wptao['cj']['sticky']; ?>" size="5" onkeyup="value=value.replace(/[^\d,]/g,'')" /> 小时</p>
					  </td>
					</tr>
					<tr>
					  <th scope="row">发布作者ID</th>
					  <td><input name="wptao[cj][author]" type="text" value="<?php echo $wptao['cj']['author']; ?>" size="40" onkeyup="value=value.replace(/[^\d,]/g,'')" />
					  <p><code>用户数字ID，多个请用英文逗号（,）分开，如果不填写，以现在您登录的帐号发布，如果填写多个, 会随机分配。</code></p>
					  </td>
					</tr>
					<tr>
					  <th scope="row">全局过滤规则</th>
					  <td><p>提示：由于主题采取增量采集，修改过滤规则后会导致今日符合规则的商品比较少，第二天之后正常。</p>价格 <input name="wptao[cj][f][startPrice]" type="text" value="<?php echo $wptao['cj']['f']['startPrice'];?>" size="5" placeholder="￥" onkeyup="value=value.replace(/[^\d.]/g,'')" /> - <input name="wptao[cj][f][endPrice]" type="text" value="<?php echo $wptao['cj']['f']['endPrice'];?>" size="5" placeholder="￥" onkeyup="value=value.replace(/[^\d.]/g,'')" /> ，佣金比例 >= <input name="wptao[cj][f][startTkRate]" type="text" value="<?php echo $wptao['cj']['f']['startTkRate'];?>" size="5" placeholder="%" onkeyup="value=value.replace(/[^\d.]/g,'')" /> ，销量 >= <input name="wptao[cj][f][startBiz30day]" type="text" value="<?php echo $wptao['cj']['f']['startBiz30day'];?>" size="5" onkeyup="value=value.replace(/[^\d]/g,'')" /> 
						<p>商品/店铺评分 >= <input name="wptao[cj][f][dsr]" type="text" value="<?php echo $wptao['cj']['f']['dsr'];?>" size="5" onkeyup="value=value.replace(/[^\d.]/g,'')" /> (取值范围 4.80~5.00，值越大商品越少)</p>
						<p>选择商城：
						<label><input type="checkbox" name="wptao[cj][f][mall][]" value="2"<?php checked(in_array(2, $wptao['cj']['f']['mall'])); ?>>天猫</label>
						<label><input type="checkbox" name="wptao[cj][f][mall][]" value="1"<?php checked(in_array(1, $wptao['cj']['f']['mall'])); ?>>淘宝</label>
						<label><input type="checkbox" name="wptao[cj][f][mall][]" value="3"<?php checked(in_array(3, $wptao['cj']['f']['mall'])); ?>>京东</label>
						<label><input type="checkbox" name="wptao[cj][f][mall][]" value="4"<?php checked(in_array(4, $wptao['cj']['f']['mall'])); ?>>拼多多</label>
						</p>
						<p><label><input type="checkbox" name="wptao[cj][f][quan]" value="1"<?php checked($wptao['cj']['f']['quan']); ?>>只采集有优惠券的商品</label></p>
						<p><label><input type="checkbox" name="wptao[cj][f][details]" value="1"<?php checked($wptao['cj']['f']['details']); ?>>只采集包含商品详情的商品（仅淘宝/天猫）</label></p>
						<p><strong>过滤标题：</strong><select name="wptao[cj][filter]">
						  <option value="0"<?php selected(!$wptao['cj']['filter']);?>>黑名单（标题中含有以下设置的关键字不采集入库）</option>
						  <option value="1"<?php selected($wptao['cj']['filter']);?>>白名单（标题中仅含有以下设置的关键字采集入库）</option>
						</select></p>
						<p>标题关键字（多个用<code>|</code>隔开，建议两个及以上汉字，不区分大小写）：</p>
						<p><textarea name="wptao[cj][filter_title]" rows="3" cols="80"><?php echo stripslashes($wptao['cj']['filter_title']);?></textarea></p>
					  </td>
					</tr>
					<tr>
					  <th scope="row">采集到</th>
					  <td><select id="wp_cj_post_type" name="wptao[cj][post_type]" onchange="select_post_type(this.value, '<?php echo $cj_post_type;?>')"><option value="post">文章</option><?php
						foreach ($post_types as $type => $object) {
							echo '<option value="'.$type.'" '. selected($type == $cj_post_type, true, false) .'>文章类型-'.$object -> labels -> name.'</option>';
						} ?>
						</select><input type="hidden" name="wptao_cj_post_type" value="<?php echo $cj_post_type; ?>" /><p style="display:none;color:red" id="post_type_tips">请保存后在设置分类对接</p></td>
					</tr>
					<?php
					if ($cj_post_type == 'post') {
						$post_type_mall = $wptao['cj']['post_type_mall'];
						$taxonomies = get_object_taxonomies($cj_post_type, 'objects');
						unset($taxonomies['category'], $taxonomies['post_tag']);
						foreach ($taxonomies as $type => $object) {
							$post_types_tax[$type] = $object->labels->name;
							if (!$post_type_mall && strpos($type, 'mall') !== false) $post_type_mall = $type;
						}
					}
					//var_dump($post_types_tax);
					?>
					<tr><td colspan="2" valign="top" style="padding:0">
				<table class="form-table" id="table_<?php echo $cj_post_type;?>_category">
					<?php if ($cj_post_type != 'post') { ?>
					<tr>
					  <th scope="row">选择分类</th>
					  <td><select name="wptao[cj][post_type_category]"><option value="">选择</option><?php
						foreach ($post_types_tax as $type => $name) {
							echo '<option value="'.$type.'" '. selected($type == $post_type_category, true, false) .'>'.$name.'</option>';
						} ?>
						</select><code>选择/修改分类后，请先保存设置，再配置下面的分类对接</code></td>
					</tr>
					<tr>
					  <th scope="row">选择标签</th>
					  <td><select name="wptao[cj][post_type_tag]"><option value="">选择(无)</option><?php
						foreach ($post_types_tax as $type => $name) {
							echo '<option value="'.$type.'" '. selected($type == $post_type_tag, true, false) .'>'.$name.'</option>';
						} ?>
						</select><code>如果主题没有相关功能，请不设置</code></td>
					</tr>
					<?php } ?>
					<tr>
					  <th scope="row">选择商家</th>
					  <td><select name="wptao[cj][post_type_mall]"><option value="">选择(无)</option><?php
						foreach ($post_types_tax as $type => $name) {
							echo '<option value="'.$type.'" '. selected($type == $post_type_mall, true, false) .'>'.$name.'</option>';
						} ?>
						</select><code>如果主题没有相关功能，可以不设置或者在【对接主题】开启功能</code></td>
					</tr>
					<?php
					if (current_theme_supports('post-formats') && post_type_supports($cj_post_type, 'post-formats')) {
						$post_formats = get_theme_support('post-formats');
						?>
					<tr>
					  <th scope="row">选择形式</th>
					  <td><select name="wptao[cj][post_format]">
							<option value="0"><?php echo get_post_format_string('standard'); ?></option>
							<?php
							if ( is_array( $post_formats[0] ) ) {
								foreach ( $post_formats[0] as $format ) {
									echo '<option value="'.$format.'" '. selected($format == $wptao['cj']['post_format'], true, false) .'>'.esc_html( get_post_format_string( $format ) ).'</option>';
								}
							}
							?>
						</select><code>如果主题没有相关功能或者不懂什么意思，请保持默认（第一个）</code></td>
					</tr>
					<?php } ?>
					<tr>
					  <th scope="row"><strong>分类对接(必须)</strong></th>
					  <td>请选择您网站对应的分类，否则选择不采集，<strong>如果全部选择不采集，意味着关闭自动采集功能</strong>
					  <p>【<a target="_blank" href="https://wptao.com/help/wptao-czhy.html">如何做垂直行业？</a>】</p></td>
					</tr>
					<?php
					$args = array('fields' => 'id=>name', 'hide_empty' => 0);
					if($post_type_category) $args['taxonomy'] = $post_type_category;
					$categories = get_categories($args);
					$cj_cats = array(1 => '女装', 9 => '男装', 10 => '内衣', 2 => '母婴', 3 => '美妆', 4 => '居家日用', 8 => '数码家电', 5 => '鞋靴', 12 => '配饰', 11 => '箱包', 6 => '美食', 7 => '文娱车品', 13 => '户外运动', 14 => '家装家纺', 0 => '*找不到分类时');
					foreach ($cj_cats as $cj_cid => $cj_cat) {
					?>
					<tr>
					  <th scope="row"><?php echo $cj_cat;?></th>
					  <td><select name="wptao[cj][cats][<?php echo $cj_cid;?>]"><option value=""><?php echo $cj_cid == '0' ? '不保存' : '不采集';?></option><?php
						if ($cj_cid && $wptao['cj']['k_cats']) { // 将关键字设置为分类
							echo '<option value="0" '. selected($wptao['cj']['cats'][$cj_cid] == '0', true, false) .'>仅采集,不指定分类</option>';
						}
						foreach ($categories as $catid=>$cat) {
							echo '<option value="'.$catid.'" '. selected($catid == $wptao['cj']['cats'][$cj_cid], true, false) .'>采集到-'.$cat.'</option>';
						} ?>
						</select></td>
					</tr>
					<?php } ?>
				</table></td>
					</tr>
					<tr>
					  <th scope="row">将关键字设置为分类<br />（多个用<code>|</code>隔开）</th>
					  <td><textarea name="wptao[cj][k_cats]" rows="3" cols="80"><?php echo stripslashes($wptao['cj']['k_cats']);?></textarea>
					  <p><code>如果标题中匹配到您设置的关键字，发布后，这些关键字也将创建为分类。</p></td>
					</tr>
					<tr>
					  <th scope="row">将关键字设置为标签<br />（多个用<code>|</code>隔开）</th>
					  <td><textarea name="wptao[cj][k_tags]" rows="3" cols="80"><?php echo stripslashes($wptao['cj']['k_tags']);?></textarea>
					  <p><code>如果标题中匹配到您设置的关键字，发布后，这些关键字也将创建为标签。</p></td>
					</tr>
					<tr>
					  <th scope="row">是否采集文章标签</th>
					  <td><input type="hidden" name="wptao[cj][notags]" value="1"><label><input type="checkbox" name="wptao[cj][notags]" value="0"<?php checked(!$wptao['cj']['notags']); ?>> 是</label>
					  <p><code>提示：标签来自淘宝分类</code></p></td>
					</tr>
					<tr>
					  <th scope="row"><strong>自定义文案【文章内容】</strong></th>
					  <td>（可选）如果留空为默认格式的文案</td>
					</tr>
					<tr>
					  <th scope="row">含有优惠券的模版<br />(支持HTML)</th>
					  <td><textarea name="wptao[cj][desc_quan]" rows="5" cols="80"><?php echo stripslashes($wptao['cj']['desc_quan']);?></textarea><br />标签:
					<?php
					$desc_arr = wptao_cj_desc_tags(1);
					foreach ($desc_arr as $desc_v) {
						echo '<code>#' .$desc_v . '#</code> ';
					} 
					?></td>
					</tr>
					<tr>
					  <th scope="row">不含有优惠券的模版<br />(支持HTML)</th>
					  <td><textarea name="wptao[cj][desc]" rows="5" cols="80"><?php echo stripslashes($wptao['cj']['desc']);?></textarea><br />标签:
					<?php
					$desc_arr = wptao_cj_desc_tags(0);
					foreach ($desc_arr as $desc_v) {
						echo '<code>#' .$desc_v . '#</code> ';
					} 
					?></td>
					</tr>
					<tr>
					  <th scope="row">商品标题</th>
					  <td><label><input type="radio" name="wptao[cj][title]" value="0"<?php checked(!$wptao['cj']['title']); ?>> 短标题</label> 
					  <label><input type="radio" name="wptao[cj][title]" value="1"<?php checked($wptao['cj']['title']); ?>> 长标题</label>
					  </td>
					</tr>
					<tr>
					  <th scope="row">采集遇到商品已经存在时</th>
					  <td><label><input type="checkbox" name="wptao[cj][old_title]" value="1"<?php checked($wptao['cj']['old_title']); ?>> 使用旧标题（不推荐）</label>
					  <label><input type="checkbox" name="wptao[cj][old_desc]" value="1"<?php checked($wptao['cj']['old_desc']); ?>> 使用旧内容（非常不推荐）</label>
					  <p><code>提示：如果您有编辑商品的习惯建议根据需求勾选。正常情况下，如果商品已经存在，将以新的时间重新发布商品，避免重复！</code></p></td>
					</tr>
					<tr>
					  <th scope="row">定时任务触发采集</th>
					  <td><label><input type="radio" name="wptao[cj][cron]" value="1"<?php checked($wptao['cj']['cron'] == 1); ?>>开启(每5分钟一次)</label>
					  <p><code>当前已经使用了网站访问触发采集任务，如果您网站流量(pv)少，采集达不到预期效果，推荐您开启它。（如果依然无效，请选择下面的方式）</code></p></td>
					</tr>
					<tr>
					  <th scope="row">其他方式触发采集</th>
					  <td><label><input type="radio" name="wptao[cj][cron]" value="-1"<?php checked($wptao['cj']['cron'] == -1); ?>>开启</label>
					  <p>如果上述定时任务和网站访问都无法达到预期的采集效果，您可以使用crontab或者监控类网站访问下面的URL，建议每3~10分钟访问一次:</p>
					  <p><textarea rows="2" cols="80"><?php echo admin_url('admin-ajax.php?action=wptao_ajax&admininit=0&type=auto');?></textarea></p></td>
					</tr>
                    </tbody>
                  </table>
                </div>
                <!-- end of inside -->
              </div>
              <!-- end of postbox -->
            </div>
            <div id="group-6" class="group" style="display: none;">
              <div class="postbox">
                <h3 class="hndle">
                  <label for="title">软件采集</label>
                </h3>
                <div class="inside">
				  <?php echo $ultimate_desc;?>
                  <table class="form-table">
                    <tbody>
					<tr>
					  <th scope="row">描述</th>
					  <td>请根据采集插件/软件填写下面对应的【自定义栏目，也叫自定义字段】,采集时把别人的淘宝客链接(即推广链接)转为自己的。</td>
					</tr>
					<tr>
					  <th scope="row">采集链接转换</th>
					  <td><label><input type="checkbox" id="wptao_cj_open" name="wptao[cj_open]" value="1"<?php checked($wptao['cj_open']); ?>> 开启 </label>
					  <?php if(isset($wptao['cj_error'])) echo '<p style="color:#f50">'.$wptao['cj_error'].'</p>';?>
					  <p><code>仅仅是处理采集插件保存的数据，请自己用火车头、<a href="http://go.wptao.com/keydatas" target="_blank">简数采集平台</a>或者相关采集插件。如果需要自动采集，见【自动采集】</code></p>
					  <p style="color:#f50">每个网站每天采集的数量请控制在2000条内，超出部分不返回数据。</p></td>
					</tr>
					<tr>
					  <th scope="row">采集的链接保存到</th>
					  <td><code>tbk_link</code><br />请把目标站的商品链接或者直达链接（<a href="https://wptao.com/wptao.html#union_links" target="_blank">查看支持列表</a>）采集到这个<code>自定义栏目名称</code>里面，插件会调用接口转换为您的推广链接，如果您在【对接主题】有设置，但是您没有采集某个字段，插件会为您自动补充相关商品信息。一旦链接转换完成，此链接将删除。</td>
					</tr>
					<tr>
					  <th scope="row">文章标题</th>
					  <td><p><label><input type="radio" name="wptao[cj_title]" value="0"<?php checked(!$wptao['cj_title']); ?>>仅标题为空或标题为固定值: <code>1</code> 时替换成商品标题（推荐）</label></p>
					  <p><label><input type="radio" name="wptao[cj_title]" value="1"<?php checked($wptao['cj_title']); ?>>都替换成接口返回的商品标题</label></p></td>
					</tr>
					<?php
					if ($is_tbk_data || $wptao['default_theme']) {
						$tbk_arr = array(
							'desc0' => array('<strong>以下为可选采集入库字段</strong>', 1),
							'red_title' => array('红色标题（或券后价）'),
							'price' => array('商品价格（非券后价）'),
							'old_price' => array('商品原价'),
							'image' => array('商品图片URL'),
							'coupon' => array('优惠券链接'),
							//'coupon_value' => array('优惠券面值，即减多少钱', '<br /><code>值为数字，主题会自动显示券后xxx元（即商品价格减去优惠券面值），如果您采集的就是券后价，可以将券后价采集到【红色标题】</code>'),
							//'coupon_end' => array('优惠券截止日期', '<br /><code>例如：' . date('Y-m-d') . '</code>'),
							//'end_time' => array('商品结束日期', '<br /><code>例如：' . date('Y-m-d') . '</code>'),
							//'site' => array('商城名称', '<br /><code>例如：淘宝网，知名商城会自动判断</code>'),
							'mm_link' => array('推广链接', '<br /><code>一旦此字段有值，将不会填充接口拿到的推广链接，请确定拿到的是您的推广链接，否则不要采集到此</code>'),
							);
						foreach ($tbk_arr as $arr => $arrv) {
							echo '<tr><th scope="row">' . $arrv[0] . '</td><td>' . ($arrv[1] && $arrv[1] === 1 ? '' : 'tbk_' . $arr . $arrv[1]) . '</td>';
						} 
					}
					?>
                    </tbody>
                  </table>
                </div>
                <!-- end of inside -->
              </div>
              <!-- end of postbox -->
            </div>
            <div id="group-7" class="group" style="display: none;">
              <div class="postbox">
                <h3 class="hndle">
                  <label for="title">广告窗</label>
                </h3>
                <div class="inside">
                  <table class="form-table">
                    <tbody>
					<tr>
					  <th scope="row">弹窗<br /><code>(可以在插件群共享下载相关活动图片，大图和小图可以同时填写，也可以二选一)</code></th>
					  <td>图片URL（大图，居中显示）<input type="text" name="wptao[ad_pop][0]" size="40" value="<?php echo $wptao['ad_pop'][0];?>" id="upid-ad_pop" /> <input type="button" class="button upload_button" upid="ad_pop" value="上传" />
					  <br />图片URL（小图，靠右显示）<input type="text" name="wptao[ad_pop][1]" size="40" value="<?php echo $wptao['ad_pop'][1];?>" id="upid-ad_pop_1" /> <input type="button" class="button upload_button" upid="ad_pop_1" value="上传" />
					  <br />链接(URL)：<input type="text" name="wptao[ad_pop][2]" size="40" value="<?php echo stripslashes($wptao['ad_pop'][2]);?>" />（必填，留空则不显示）
					  <br />有效期至：<input type="text" id="wptao-ad_pop-4" name="wptao[ad_pop][4]" size="20" value="<?php echo $wptao['ad_pop'][4];?>" />（留空为不限制，格式 <a href="javascript:;" onclick="add_value('wptao-ad_pop-4',this)"><?php echo date('Y-m-d 23:59:59', time()+7*86400);?></a>）
					  <br />24小时内同一个用户间隔 <input type="text" name="wptao[ad_pop][3]" size="5" onkeyup="value=value.replace(/[^\d]/g,'')" value="<?php echo $wptao['ad_pop'][3] ? intval($wptao['ad_pop'][3]) : 4;?>" /> 小时弹1次
					  </td>
					</tr>
					<tr>
					  <th scope="row">网站最顶部</th>
					  <td>图片URL：<input type="text" name="wptao[ad_top][0]" size="40" value="<?php echo $wptao['ad_top'][0];?>" id="upid-ad_top" /> <input type="button" class="button upload_button" upid="ad_top" value="上传" />
					  <br />链接(URL)：<input type="text" name="wptao[ad_top][1]" size="40" value="<?php echo stripslashes($wptao['ad_top'][1]);?>" />（必填，留空则不显示）
					  <br />有效期至：<input type="text" id="wptao-ad_top-2" name="wptao[ad_top][2]" size="20" value="<?php echo $wptao['ad_top'][2];?>" />（留空为不限制，格式 <a href="javascript:;" onclick="add_value('wptao-ad_top-2',this)"><?php echo date('Y-m-d 23:59:59', time()+7*86400);?></a>）
					  <br />CSS类 <input type="text" id="wptao-ad_top-3" name="wptao[ad_top][3]" value="<?php echo $wptao['ad_top'][3] ? ($wptao['ad_top'][3] == 1 ? "show-pc" : $wptao['ad_top'][3]) : '';?>" /> 填写<code><a href="javascript:;" onclick="add_value('wptao-ad_top-3',this)">show-pc</a></code>表示手机端不显示
					  </td>
					</tr>
                    </tbody>
                  </table>
                </div>
                <!-- end of inside -->
              </div>
              <!-- end of postbox -->
            </div>
            <div id="group-20" class="group" style="display: none;">
              <div class="postbox">
                <h3 class="hndle">
                  <label for="title">手动(可选)</label>
                </h3>
                <div class="inside">
                  <p class="wptao-box yellow">此功能为兼容插件旧版本，已经被淘汰，请使用【对接主题】</p>
                  <table class="form-table">
                    <tbody>
					<tr>
					  <th scope="row"><label for="wptao_box">是否对接？</label></th>
					  <td>如果您准备开启【对接主题-显示商品信息模块】下面的配置可以忽略。</td>
					</tr>
					<tr>
					  <th scope="row"><strong>一键获取商品信息</strong></th>
					  <td>如果您的主题可以手动填写商品信息（标题/直达链接等，<a target="_blank" href="http://img2.wptao.cn/3/large/62579065gw1eu5zev9ujmg20fp0mzwfp.gif">看图例</a>）才能使用这个功能，即只要一个<code>商品链接/推广链接</code>就可以自动填充相关信息，提高发布商品效率。<code>否则见【基本设置-编辑器按钮】的功能</code></td>
					</tr>
					<tr>
					  <th scope="row"><strong>商品信息（可选，<a target="_blank" href="https://wptao.com/wptao.html#inputid">看教程</a>）</strong></th>
					  <td>商品信息输入框的节点id, 如果没有请留空: <br />比如：<code>&lt;input name="<span style="color:green">abc</span>" id="<span style="color:blue">xyz</span>" /&gt;</code>，节点id就写<code>xyz</code>，或者使用输入框name值，填写为<code>post-body input[name=abc]</code>，如果是textarea，就写<code>post-body textarea[name=abc]</code></td>
					</tr>
					<?php
					$options = array('url' => array('商品链接', '多个请用英文逗号和#（,#）分开，以下方法相同'),
						'item_click' => array('商品推广链接（CPS）', '即直达链接'),
						'title' => array('商品标题', '如果对应【文章标题】，可以填写<a href="javascript:;" onclick="add_value(\'wptao_title\',this)">titlewrap input</a>'),
						'desc' => array('商品描述', '如果对应【文章内容】，可以填写<a href="javascript:;" onclick="add_value(\'wptao_desc\',this)">wp-content-editor-container textarea</a>'),
						'image' => array('商品图片', '商品图片URL'),
						'preview' => array('商品图片预览', '此处输出格式为 &lt;img src="商品图片URL"/&gt;'),
						'price' => array('商品价格', ''),
						'old_price' => array('商品原价', ''),
						'site' => array('商城名称', ''),
						'shop_name' => array('店铺名称', ''),
						'shop_click' => array('店铺推广链接（CPS）', ''),
						'tags' => array('标签', '如果对应【标签】，可以填写<a href="javascript:;" onclick="add_value(\'wptao_tags\',this)">new-tag-post_tag</a>'),
						);

					foreach ($options as $key => $value) {
						echo '<tr><th scope="row"><label for="wptao_' . $key . '">' . $value[0] . '</label></th><td><label>#<input id="wptao_' . $key . '" name="wptao[item][' . $key . ']" type="text" value="' . $wptao['item'][$key] . '" /></label>';
						echo $value[1] ? '<p class="description"><code>' . $value[1] . '</code></p>' : '';
						echo '</td></tr>';
					} 
					?>
                    </tbody>
                  </table>
                </div>
                <!-- end of inside -->
              </div>
              <!-- end of postbox -->
            </div>
            <div id="group-77" class="group" style="display: none;">
              <div class="postbox">
                <h3 class="hndle">
                  <label for="title">APP/小程序</label>
                </h3>
                <div class="inside">
                  <table class="form-table">
                    <tbody>
					<tr>
					  <td colspan="2">采用API开发，目前支持安卓APP、微信小程序、QQ小程序，<a href="https://wptao.com/wptao-app.html" target="_blank">点击查看详情</a>。</td>
					</tr>
					<tr>
					  <td colspan="2"><p>★安卓app下载链接: <a href="http://go.wptao.com/youhuimeapp" target="_blank">点击下载</a></p>
						<p>★微信小程序：</p>
						<p><a href="http://img2.wptao.cn/images/mpweixin-youhuime.jpg" target="_blank"><img style="margin: 0;display:inline" src="//img2.wptao.cn/images/mpweixin-youhuime.jpg" alt="" /></a></p>
						<p></p>
						<p>如需查看本插件后台请看：<a href="http://youhuime.com/wp-admin/admin.php?page=wptao-app" target="_blank">点击这里</a>（帐号和密码都是<code>test</code>）</p>
						<p></p>
						<p>如需购买APP/小程序请看：<a href="https://wptao.com/wptao-app.html" target="_blank">https://wptao.com/wptao-app.html</a></p></td>
					</tr>
                    </tbody>
                  </table>
                </div>
                <!-- end of inside -->
              </div>
              <!-- end of postbox -->
            </div>
            <p class="submit">
			  <?php if ($wptao['node']) echo '<input type="hidden" name="wptao[node]" value="'.$wptao['node'].'"/>';?>
			  <input type="hidden" name="wptao[status]" value="1" />
			  <input type="hidden" name="wptao[cv]" value="<?php echo $wptao['cv'];?>" />
			  <input type="hidden" name="wptao_cj_md5" value="<?php echo $wptao['cj']['md5'];?>"/>
			  <input type="submit" name="wptao_options" class="button-primary" value="<?php _e('Save Changes') ?>" />
			</p>
          </form>
        </div>
        <div class="wptao-grid wptao-sidebar">
          <div class="postbox" style="min-width: inherit;">
            <h3 class="hndle">
              <label for="title">联系作者</label>
            </h3>
            <div class="inside">
              <p>QQ群：<a href="http://shang.qq.com/wpa/qunwpa?idkey=5dd1c3ec6a1faf9dd3586b4d76e0bb32073baa09a55d9f76f433db393f6451a7" target="_blank">77434617</a></p>
              <p>QQ：<a href="http://wpa.qq.com/msgrd?v=3&uin=3249892&site=qq&menu=yes" target="_blank">3249892</a></p>
			  <p>微信号：<a href="http://img2.wptao.cn/3/small/62579065gy1fqx11pit2mj20by0bygme.jpg" target="_blank">wptaocom</a></p>
              <p><a href="https://wptao.com/taoke" target="_blank">官方网站</a></p>
            </div>
          </div>
          <div class="postbox" style="min-width: inherit;">
            <h3 class="hndle">
              <label for="title">产品推荐</label>
            </h3>
            <div class="inside">
			<?php $source = urlencode(home_url());?>
			<ol>
			<li><a target="_blank" href="https://wptao.com/product-lists.html?source=<?php echo $source;?>">产品套餐（付费一次拥有以下所有插件，超级划算）</a></li>
			<li><a target="_blank" href="https://wptao.com/weixin-cloned.html?source=<?php echo $source;?>">WordPress微信分身（避免微信封杀网站域名）</a></li>
			<li><a target="_blank" href="https://wptao.com/wp-taomall.html?source=<?php echo $source;?>">WordPress淘宝客主题：wp-taomall (自动获取商品信息和推广链接)</a></li>
			</ol>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php
} 

/**
 * 淘宝授权
 */
function wptao_taobao_oauth($wptao_options) {
	if (!$wptao_options['code']) return '';
	if (substr($wptao_options['code']['authorize_code'], -4) == 'TEST') return '<span style="color:#f50">测试版无法使用！</span>'; // TEST
	if ($wptao_options['union']) {
		if ($wptao_options['pid'] && $wptao_options['union']['appkey'] && $wptao_options['union']['secret']) {
			$keys = array('apikey' => $wptao_options['code']['apikey'], 'secret' => $wptao_options['code']['secret']);
			if ($keys && $keys['apikey'] && $keys['secret']) {
				$op = array('pid' => trim($wptao_options['pid']), 'appkey' => $wptao_options['union']['appkey'], 'secret' => $wptao_options['union']['secret'], 'email' => $wptao_options['email']); 
				// var_dump($op);
				$sign = $keys['apikey'] . '|' . key_authcode(http_build_query($op, '', '&'), 'ENCODE', $keys['secret'], 1800) . '|' . time();
				$oauth_url = 'http://oauth.wptao.cn/oauth/taobao.php?sign=' . urlencode($sign) . '&ref=' . urlencode(home_url()) . '&v=' . WPTAO_V . '&c=p';
				return '【<a target="_blank" href="' . $oauth_url . '">点击授权</a>】';
			} 
		} else {
			return '【<a href="javasricpt:;" onClick="alert(\'请填写App Key、App Secret、PID，保存后再次点击授权，到期前如需提醒请填写邮箱地址哦。\');return false;">点击授权</a>】';
		} 
	} 
} 

/**
 * 文章发布相关模块
 */
add_action('admin_menu', 'wptao_sidebox_info_add');
function wptao_sidebox_info_add() {
	if (function_exists('add_meta_box')) {
		global $wptao_options;
		if ($post_types = $wptao_options['open']) {
			if (!is_array($post_types)) {
				$post_types = array();
				if (function_exists('get_post_types')) { // 自定义文章类型
					$post_types = get_post_types(array('public' => true, '_builtin' => false), 'names', 'and');
				} 
				$post_types['post'] = $post_types['page'] = 1;
			} 
			foreach($post_types as $type => $object) {
				add_meta_box('wp-sidebox-wptao-info', '商品信息', 'wptao_sidebox_info', $type, 'normal', 'high');
			} 
		} 
	} 
}
// WP后台发布页表单基础CSS
if (!function_exists('wptao_admin_table_css')) {
function wptao_admin_table_css() {
	global $wptao_table_css;
	if ($wptao_table_css) return;
	$wptao_table_css = 1;
	?>
<style type="text/css">
.wptao-table .hide{display:none}
.wptao-table .w90{width:90%}
.wptao-table .w30{width:30%}
.wptao-table .w20{width:20%}
.wptao-table input[type=text],.wptao-table select,.wptao-table textarea{margin:1px}
.wptao-table .preview{float:none}
.wptao-table .preview img{max-width:200px;max-height:200px;_width:200px;padding:5px;margin-top:5px;margin-bottom:10px;border:1px solid #ececec;display:table;background-color:#f9f9f9}
#postbox-container-1 .wptao-table .w90,#postbox-container-1 .wptao-table .w30,#postbox-container-1 .wptao-table .w20{width:100%}
#postbox-container-1 .wptao-table .description{display:none}
#postbox-container-1 .wptao-table .description.show{display:inline}
#postbox-container-1 .wptao-table th{padding-top:4px;padding-bottom:0;border-bottom:0}
#postbox-container-1 .form-table td{margin-bottom:0;padding-bottom:6px;padding-top:4px;padding-left:0}
#postbox-container-1 .wptao-table td,#postbox-container-1 .wptao-table th{display:block;width:auto;vertical-align:middle}
#postbox-container-2 .wptao-table th{width:18%}
@media screen and (max-width:782px){
.wptao-table .description{display:none}
.wptao-table .w90,#postbox-container-2 .wptao-table th{width:100%}
}
</style>
<?php }}
function wptao_save_fields() {
	return array('mm_link' => array('title' => '直达链接/推广链接',
			'_desc' => '采集时建议您别采集数据到此，由插件自动转换填写，<span style="color:#f50">旗舰版及以上版本支持函数调用购买按钮，还可以显示内链和处理商品是否过期。</span>',
			'placeholder' => '商品推广链接，淘宝/天猫可留空'
			),
		'link' => array('title' => '商品链接',
			'_desc' => '即商品原始链接，如果原主题没有，请填写<a href="javascript:;" onclick="add_value(\'wptao_cj_link\',this)">wptao_link</a>，<span style="color:#f50">发布后，旗舰版及以上版本会自动组合单品+优惠券二合一链接（下面的优惠券链接要配置哦）。</span>'
			),
		'coupon' => array('title' => '优惠券链接',
			'_desc' => '如果原主题没有，请填写<a href="javascript:;" onclick="add_value(\'wptao_cj_coupon\',this)">wptao_coupon</a>，<span style="color:#f50">发布后，旗舰版及以上版本会自动组合单品+优惠券二合一链接（上面的商品链接要配置哦）。</span>',
			'placeholder' => '输入领取优惠券的网址，发布后会自动组合单品+优惠券二合一链接'
			),
		'sellout' => array('title' => '商品是否售罄', 
			'_desc' => '如果原主题没有，请填写<a href="javascript:;" onclick="add_value(\'wptao_cj_sellout\',this)">wptao_sellout</a>，<span style="color:#f50">旗舰版及以上版本会自动处理商品是否过期</span>'
			),
		'text0' => array('title' => '其他商品信息（可选）',
			'_desc' => '如果你主题没有以下信息，也可以填写，会保存数据，但是可能不显示。'
			),
		'tkl' => array('title' => '淘口令',
			'_desc' => '如果原主题没有，请填写<a href="javascript:;" onclick="add_value(\'wptao_cj_tkl\',this)">wptao_tkl</a> (默认)，当前仅在微信APP内点击淘宝链接时显示',
			'desc' => '当前仅在微信APP内点击淘宝链接时显示，格式为 <code>￥xxxx￥</code>'
			),
		'image' => array('title' => '商品图片',
			'_desc' => '商品图片URL，如果您主题使用的是<span style="color:#f50">特色图片</span>，请填写<a href="javascript:;" onclick="add_value(\'wptao_cj_image\',this)">wptao_img</a>，不要乱填。'
			),
		'red_title' => array('title' => '红色标题', '_desc' => '如果原主题没有，可填写<a href="javascript:;" onclick="add_value(\'wptao_cj_red_title\',this)">red_title</a>，然后勾选【添加到文章标题最后面】或者使用自定义函数。'),
		'zk_price' => array('title' => '券后价',
			'_desc' => '即：商品价格 - 优惠券面值'),
		'price' => array('title' => '商品价格'),
		'old_price' => array('title' => '商品原价'),
		'title' => array('title' => '商品标题',
			'_desc' => '如果对应【文章标题】，请填写<a href="javascript:;" onclick="add_value(\'wptao_cj_title\',this)">tbk_title</a>'
			),
		'desc' => array('title' => '文章内容',
			'_desc' => '如果您想插入商品主图请填写<a href="javascript:;" onclick="add_value(\'wptao_cj_desc\',this)">tbk_pic</a>，插入商品详情请填写<a href="javascript:;" onclick="add_value(\'wptao_cj_desc\',this)">tbk_desc</a> (默认)',
			),
		'coupon_value' => array('title' => '优惠券面值',
			'placeholder' => '优惠券面值，即减多少钱'
			),
		'coupon_end' => array('title' => '优惠券截止日期',
			'placeholder' => '输入优惠券截止日期'
			),
		'id' => array('title' => '商品ID',
			'_desc' => '商品数字ID，也可能没有值'
			),
		'biz30day' => array('title' => '月销量',
			),
		'mall' => array('title' => '商城名称',
			'desc' => '商城的简写，比如: taobao',
			'_desc' => '商城的简写，比如: taobao，也可能没有值',
			),
		'site' => array('title' => '商城名称（中文）',
			'desc' => '商城的中文名称，比如: 淘宝网',
			'_desc' => '商城的中文名称，比如: 淘宝网，也可能没有值',
			),
		);
}
// 文章页面
function wptao_sidebox_info() {
	global $post, $wptao_options;
	$tbk = array();
	if ($wptao_options['box'] && $fields = $wptao_options['caiji']) {
		$options = wptao_save_fields();
		// 不显示一些字段
		unset($options['link'], $options['text0'], $options['desc']);
		if ($fields['red_title'] && $fields['red_title'] == '_secondary_title') unset($fields['red_title']);
		$options = array_intersect_key($options, $fields);
		$items = array();
		foreach ($options as $key => $value) {
			$items[$key] = 'tbk_' . $key;
		}
		if ($fields['title'] && $fields['title'] == 'tbk_title') {
			$items['title'] = 'titlewrap input';
			unset($options['title'], $fields['title']);
		} 
		if ($fields['desc']) {
			if ($fields['desc'] == 'tbk_desc') {
				$items['desc'] = 'wp-content-editor-container textarea';
			} elseif ($fields['desc'] == 'tbk_pic') {
				$items['desc_pic'] = 1;
			}
		}
		unset($items['mm_link']);
		// 特殊节点
		$items['item_click'] = 'tbk_mm_link';
	} else {
		$items = $wptao_options['item'];
		if (!$items) $items = array();
	}
	$items = apply_filters('wptao_items', $items);
	$tbk = get_post_tbk($post->ID);
	wptao_admin_table_css();
?>
<!-- By WordPress淘宝客插件 https://wptao.com/wptao.html -->
<script type="text/javascript">
var wptao_js=<?php echo json_encode(wptao_js_var());?>;
var wptao_data=<?php echo json_encode_zh_cn(array_filter($items));?>;
var wptao_preview=wptao_data.preview;
var wptao_redtitle=<?php echo $fields['red_title'] ? $wptao_options['red_title_format'] : 0;?>;
var wptao_exchange=<?php echo $wptao_options['exchange'] && $wptao_options['currency']['save'] ? $wptao_options['exchange'] : 0;?>;
function sub(a,b){var c,d,e,f;try{c=a.toString().split(".")[1].length}catch(g){c=0}try{d=b.toString().split(".")[1].length}catch(g){d=0}return e=Math.pow(10,Math.max(c,d)),f=c>=d?c:d,((a*e-b*e)/e).toFixed(f)}
function mul(a,b){var c=0,d=a.toString(),e=b.toString();try{c+=d.split(".")[1].length}catch(f){}try{c+=e.split(".")[1].length}catch(f){}return Number(d.replace(".",""))*Number(e.replace(".",""))/Math.pow(10,c)}
jQuery(function($){$("#get_item_info").click(function(){var link=$("#tbk_link").val();var link_old=$("#tbk_link_old").val();if(link!=link_old){$("#tbk input[type='text'],#tbk input[type='hidden'],#tbk textarea").val("");$("#tbk_link,#tbk_link_old").val(link);}
var coupon=$("#tbk_coupon").val();if(!link){alert('商品链接不能留空！');return false;}
$('#wptao_tips,#wptao_commfee,#wptao_preview').html('');jQuery.ajax({type:"GET",url:wptao_js.ajax_url+'?action=wptao_ajax&type=sign&link='+encodeURIComponent(link),success:function(data){if(data){var url=wptao_js.api+'/get_items_detail.php?callback=?';$.getJSON(url,{u:encodeURIComponent(link),ref:encodeURIComponent(wptao_js.blog_url),sign:data,cps:!wptao_data.item_click&&!wptao_data.shop_click?0:1,desc:!wptao_data.desc?0:1,coupon:coupon,c:wptao_js.c,v:wptao_js.v},function(data){if(data.url){$("#tbk_link,#tbk_link_old").val(data.url);$("#tbk_jh").val(data.jh);if(data.tips){$('#wptao_tips').html(data.tips);}
if(data.price){data.zk_price=data.coupon_value?sub(data.price,data.coupon_value):data.price;if(wptao_exchange){data.zk_price=mul(wptao_exchange,data.zk_price);data.price=mul(wptao_exchange,data.price);if(data.old_price)data.old_price=mul(wptao_exchange,data.old_price);}}
for(var i in wptao_data){if(data[i]){$("#"+wptao_data[i]).val(data[i]);}}
if(wptao_redtitle&&data.zk_price){var red_title='';if(data.coupon_value)red_title+=wptao_redtitle==2?'优惠券后':'券后';red_title+=data.zk_price+'元';if(data.postFree)red_title+='包邮';$("#tbk_red_title").val(red_title);}
if(data.tkinfo){$('#wptao_commfee').html(data.tkinfo);}
if(data.coupon){if(data.dx){$('#tbk_dx').attr('checked',true);}else{$("#tbk_dx").attr('checked',false);}}
if(data.tkl){$("#tbk_tkl").val(data.tkl);if(data.tkl_time){$("#tbk_tkl_time").val(data.tkl_time);}}else{$("#tbk_tkl,#tbk_tkl_time").val('');}
var img='';if(data.image){img='<img src="'+data.image+'" />';$('#wptao_preview').html(img);}
if(wptao_preview&&$('#'+wptao_preview).length>0){$('#'+wptao_preview).html(img);}
if(wptao_data.desc_pic&&img){data.desc=img;}
if(data.desc&&(wptao_data.desc_pic||(wptao_data.desc&&wptao_data.desc=='wp-content-editor-container textarea'))){if($('#wp-content-wrap').hasClass('tmce-active')){tinyMCE.activeEditor.execCommand('mceInsertContent',0,data.desc);}else{$("#wp-content-editor-container textarea").val(data.desc);}}<?php do_action('wptao_sidebox_js',$items);?>}
if(data.error){alert(data.error);}})}else{alert('请填写插件授权码！');return false;}}});});$('.form-table').on('click','.upload_button',function(){var send_attachment_bkp=wp.media.editor.send.attachment;var button=$(this);var id=button.attr("upid");wp.media.editor.send.attachment=function(props,attachment){$("#"+id).val(attachment.url);}
wp.media.editor.open(button);return false;});if($('#wptao_preview').length>0){$('#tbk_image').each(function(){$(this).bind('mouseleave change focus blur',function(){var img='';var picurl=$(this).val();if(picurl){img='<img src="'+picurl+'" />';}
$('#wptao_preview').html(img);});});}});
</script>
<div id="wptao_tips"><?php if ($tbk['status'] == '-1') echo '<font color="red">当前商品已经售罄！</font>';?></div>
<table class="form-table wptao-table" id="tbk">
  <tr>
    <th><label for="tbk_link">商品链接*</label>
    </th>
    <td><input type="text" name="tbk_link" id="tbk_link" value="<?php echo $tbk['link'];?>" class="w90" />
	<p>
	<input type="hidden" id="tbk_link_old" value="<?php echo $tbk['link'];?>" />
	<input type="hidden" name="tbk[jh]" id="tbk_jh" value="<?php echo $tbk['jh'];?>" />
	<input type="hidden" name="tbk[sellerId]" id="tbk_sellerId" value="<?php echo $tbk['sellerId'];?>" />
	<input type="button" id="get_item_info" title="获取信息" value="获取信息" />
	<span class="description"><?php wptao_button_text();?></span>
	</p>
	<div id="wptao_commfee"></div>
	</td>
  </tr>
<?php
if ($options) {
	foreach ($options as $key => $value) {
		if ($key == 'coupon_value' || $key == 'coupon_end' || $key == 'old_price' || $key == 'dx' || $key == 'jh' || $key == 'sellout') continue;
		echo '<tr><th>';
		if ($value['type'] == 'checkbox') {
			echo $value['title'] . '</th><td><label><input type="checkbox" name="tbk[' . $key . ']" id="tbk_' . $key . '" value="1"' . checked($tbk[$key], 1, false) . ' />' . ($value['text'] ? $value['text'] : '是') . '</label>';
		} else {
			echo '<label for="tbk_' . $key . '">' . $value['title'] . '</label></th><td>';
			if ($key == 'mm_link' || $key == 'coupon') {
				echo '<textarea name="tbk[' . $key . ']" id="tbk_' . $key . '" ' . ($value['placeholder'] ? 'placeholder="' . $value['placeholder'] . '"' : '') . ' rows="2" class="w90">' . $tbk[$key] . '</textarea>';
			} else {
				echo '<input type="text" name="tbk[' . $key . ']" id="tbk_' . $key . '" class="' . (in_array($key, array('price', 'zk_price', 'id', 'biz30day', 'mall', 'site')) ? 'w30' : 'w90') . '" value="' . $tbk[$key] . '" ' . ($value['placeholder'] ? 'placeholder="' . $value['placeholder'] . '"' : '') . '/>';
			} 
		} 
		if ($key == 'coupon') {
			if ($options['coupon_value']) {
				echo '<input type="text" name="tbk[coupon_value]" id="tbk_coupon_value" value="' . $tbk['coupon_value'] . '" class="w90" placeholder="优惠券面值，即减多少钱" onkeyup="value=value.replace(/[^\d.]/g,\'\')" />';
			} 
			if ($options['coupon_end']) {
				echo '<input type="text" name="tbk[coupon_end]" id="tbk_coupon_end" value="' . $tbk['coupon_end'] . '" class="w90" placeholder="输入优惠券截止日期" /><p class="description">可留空，格式：<a href="javascript:;" onclick="document.getElementById(\'tbk_coupon_end\').value=this.innerHTML">' . date('Y-m-d 23:59:59') . '</a> （提示：淘宝、天猫优惠券会自动转内链。）</p>';
			} else {
				echo '<p class="description">提示：淘宝、天猫优惠券会自动转内链。</p>';
			} 
			echo '<p><label><input type="checkbox" name="tbk[dx]" id="tbk_dx" value="1" ' . checked($tbk['dx'], 1, false) . ' /> 我确定【定向计划】是最高佣金(见获取信息提示)，并且已到官网申请</label></p>';
			if ($options['coupon_value'] || $options['coupon_end']) {
				echo '<p class="description">提示：商品链接和优惠券都填写后，再点击【获取信息】可以得到优惠券面值/截止日期</p>';
			} 
		} elseif ($key == 'price') {
			if ($options['old_price']) {
				echo ' <label for="tbk_old_price">商品原价</label> <input type="text" name="tbk[old_price]" id="tbk_old_price" value="' . $tbk['old_price'] . '" class="w30" />';
			} 
		} elseif ($key == 'image') {
			echo '<input type="button" class="button upload_button" upid="tbk_image" value="上传"><div id="wptao_preview" class="preview"></div>';
		} elseif ($key == 'tkl') {
			echo '<input type="hidden" name="tbk[tkl_time]" id="tbk_tkl_time" value="'.$tbk['tkl_time'].'" />';
		} 
		if ($value['desc']) echo '<p class="description">' . $value['desc'] . '</p>';
		echo '</td></tr>';
	} 
	// do_action('wptao_sidebox_tr', $items);
	if ($options['sellout']) {
?>
  <tr>
   <th>商品是否售罄</th>
   <td><label><input type="checkbox" name="tbk[sellout]" id="tbk_sellout" value="1"<?php checked($tbk['status'] == -1);?>>已经售罄</label></td>
  </tr>
<?php } ?>
  <tr>
    <th colspan="2" style="width:100%;color:red">重要说明：本栏目填写的优先级别大于主题设置，如果遇到同一个的自定义栏目以这边填写的为准（会覆盖其他地方填写的）。
	</th>
  </tr>
<?php }
?>
</table>
<?php
} 
// 发布时保存商品信息
add_action('save_post', 'wptao_save_post_meta', 100, 2);
function wptao_save_post_meta($post_id, $post) {
	if (!empty($_POST['tbk'])) {
		global $wptao_options;
		$fields = $wptao_options['caiji'];
		if ($fields) {
			$_POST['tbk']['link'] = $_POST['tbk_link'];
			if (!$_POST['tbk']['coupon']) {
				unset($fields['coupon_value'], $fields['coupon_end'], $fields['dx'], $fields['jh']);
			}
			if ($fields['red_title'] && $fields['red_title'] == '_secondary_title') unset($fields['red_title']);
			foreach ($fields as $k => $v) {
				if ($_POST['tbk'][$k]) {
					update_post_meta($post_id, $v, $_POST['tbk'][$k]);
				} elseif (get_post_meta($post_id, $v, true)) {
					delete_post_meta($post_id, $v);
				} 
			}
		} 
	} 
}
/**
 * 【淘】商品模块 后台
 */
// 挂载函数到正确的钩子
add_action('init', 'wptao_add_mce_button');
function wptao_add_mce_button() {
	if (wptao_options('mce')) {
		// 检查用户权限
		if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
			return;
		} 
		// 检查是否启用可视化编辑
		if ('true' == get_user_option('rich_editing')) {
			add_filter('mce_external_plugins', 'wptao_mce_js');
			add_filter('mce_buttons', 'wptao_register_mce_button');
			add_filter('mce_css', 'wptao_mce_css');
		} 
	} 
} 
// 新按钮的JS
function wptao_mce_js($plugin_array) {
	$url = set_url_scheme(WPTAO_URL, 'relative');
	$plugin_array['wptao_button'] = $url . '/admin/wptao/js.js?ver=' . WPTAO_V;
	return $plugin_array;
} 
// 可视化编辑器CSS
function wptao_mce_css($mce_css) {
	$mce_css .= ',' . WPTAO_URL . '/admin/css/editor.css?ver=' . WPTAO_V;
	return $mce_css;
} 
// 在编辑器上注册新按钮
function wptao_register_mce_button($buttons) {
	array_push($buttons, 'wptao_button');
	return $buttons;
} 

/**
 * WordPress插件相关
 */
add_action('plugin_action_links_' . plugin_basename(__FILE__), 'wptao_plugin_actions');
function wptao_plugin_actions($links) {
    $new_links = array();
    $new_links[] = '<a href="admin.php?page=wptao">' . __('Settings') . '</a>';
    return array_merge($new_links, $links);
}
// WPMU
add_action('network_admin_plugin_action_links_' . plugin_basename(__FILE__), 'wptao_network_plugin_actions');
function wptao_network_plugin_actions($links) {
    $new_links = array();
    $new_links[] = '<a href="settings.php?page=wptao">' . __('Settings') . '</a>';
    return array_merge($new_links, $links);
}
function wptao_network_pages() {
	add_submenu_page('settings.php', '淘宝客', '淘宝客', 'manage_options', 'wptao', 'wptao_network_admin');
} 
add_action('network_admin_menu', 'wptao_network_pages');
function wptao_network_admin() {
	if (isset($_POST['update_options'])) {
		do_action('wptao_update_network');
		$authorize_code = trim($_POST['authorize_code']);
		if ($authorize_code) {
			if (substr($authorize_code, -4) == 'WPMU') {
				$authorizecode = substr($authorize_code, 0, -4);
				$is_wpmu = 1;
			} else {
				$authorizecode = $authorize_code;
				$is_wpmu = '';
			}
			$apikey = substr($authorizecode, 0, -32);
			$secret = substr($authorizecode, -32);
			$options = array('apikey' => $apikey, 'secret' => $secret, 'wpmu' => $is_wpmu, 'authorize_code' => $authorize_code);
			if (strpos($apikey, '.') >= 1) { // 请勿修改，否则插件会出现未知错误
				$options['bought'] = 1;
			} else {
				$options['bought'] = '';
			}
			update_site_option('wptao_code', $options);
		} else {
			update_site_option('wptao_code', array());
		}
	}
	$options = get_site_option('wptao_code');
	if ($options) {
		if ($options['apikey'] != DOMAIN_CURRENT_SITE) {
			echo '<div class="updated"><p><strong>请填写正确的插件“根域名/WPMU”授权码。</strong></p></div>';
		} 
		if (function_exists('wptao_update_checker')) {
			$validation = validation_error('wptao');
			if ($validation) {
				echo '<div class="error">' . $validation . '</div>';
			} 
		} 
	} 
	?>
<style type="text/css">
.postbox label{float:none}
.postbox .hndle{border-bottom:1px solid #eee}
.nav-tab-wrapper{margin-bottom:15px}
.wptao-grid a{text-decoration:none}
.wptao-main{width:80%;float:left}
.wptao-sidebar{width:19%;float:right}
.wptao-sidebar ol{margin-left:10px}
.wptao-box{margin:10px 0px;padding:10px;border-radius:3px 3px 3px 3px;border-color:#cc99c2;border-style:solid;border-width:1px;clear:both}
.wptao-box.yellow{background-color:#FFFFE0;border-color:#E6DB55}
@media (max-width:782px){
.wptao-grid{display:block;float:none;width:100%}
}
</style>
<div class="wrap">
  <h2>淘宝客插件 <code>网络设置</code></h2>
  <div id="poststuff">
    <div id="post-body">
      <div class="wptao-container">
        <div class="wptao-grid wptao-main">
          <form method="post" action="">
            <?php wp_nonce_field('network-options');?>
            <div id="group-network" class="group" style="display:block;">
              <div class="postbox">
                <h3 class="hndle">
                  <label for="title">整个网络设置</label>
                </h3>
                <div class="inside">
                  <table class="form-table">
                    <tbody>
					<tr>
					  <th scope="row">填写插件“根域名/WPMU”授权码：</span></th>
					  <td><input type="text" name="authorize_code" size="50" value="<?php echo $options['authorize_code'];?>" /> <?php echo $code_yes;?></td>
					</tr>
                    </tbody>
                  </table>
				  <p><a href="https://wptao.com/wptao.html" target="_blank">如何获得授权码?</a> (请选择根域名或者WPMU)</p>
                </div>
                <!-- end of inside -->
              </div>
              <!-- end of postbox -->
            </div>
            <p class="submit">
              <input type="submit" name="update_options" class="button-primary" value="<?php _e('Save Changes') ?>">
            </p>
          </form>
        </div>
        <div class="wptao-grid wptao-sidebar">
          <div class="postbox" style="min-width:inherit;">
            <h3 class="hndle">
              <label for="title">联系作者</label>
            </h3>
            <div class="inside">
              <p>QQ群：<a href="http://shang.qq.com/wpa/qunwpa?idkey=5dd1c3ec6a1faf9dd3586b4d76e0bb32073baa09a55d9f76f433db393f6451a7" target="_blank">77434617</a></p>
              <p>QQ：<a href="http://wpa.qq.com/msgrd?v=3&uin=3249892&site=qq&menu=yes" target="_blank">3249892</a></p>
			  <p>微信号：<a href="http://img2.wptao.cn/3/small/62579065gy1fqx11pit2mj20by0bygme.jpg" target="_blank">wptaocom</a></p>
              <p><a href="https://wptao.com/taoke" target="_blank">官方网站</a></p>
			</div>
          </div>
          <div class="postbox" style="min-width:inherit;">
            <h3 class="hndle">
              <label for="title">产品推荐</label>
            </h3>
            <div class="inside">
			<?php $source = urlencode(home_url());?>
			<ol>
			<li><a target="_blank" href="https://wptao.com/product-lists.html?source=<?php echo $source;?>">产品套餐（付费一次拥有以下所有插件，超级划算）</a></li>
			<li><a target="_blank" href="https://wptao.com/weixin-cloned.html?source=<?php echo $source;?>">WordPress微信分身（避免微信封杀网站域名）</a></li>
			<li><a target="_blank" href="https://wptao.com/wp-taomall.html?source=<?php echo $source;?>">WordPress淘宝客主题：wp-taomall (自动获取商品信息和推广链接)</a></li>
			</ol>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php
}
?>