<?php
if (!defined('ABSPATH')) {
	include "../../../../../wp-config.php";
}
if (!current_user_can('edit_posts')) {
	wp_die(__('You do not have sufficient permissions to access this page.'));
} 
global $wptao_options;
$wptao_position = isset($_COOKIE['wptao_position']) ? $_COOKIE['wptao_position'] : '';
$wptao_theme = isset($_COOKIE['wptao_theme']) ? $_COOKIE['wptao_theme'] : '';
$js_var = array('mce_desc' => stripslashes($wptao_options['mce_desc']), 'mce_desc_p' => (int)$wptao_options['mce_desc_p']);
?>
<!DOCTYPE HTML>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>获取淘宝客信息</title>
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
<link rel='stylesheet' id='wptao-style-css' href='<?php echo WPTAO_URL;?>/admin/wptao/css.css?ver=<?php echo WPTAO_V;?>' type='text/css' media='all' />
<body>
<!-- By WordPress淘宝客插件 https://wptao.com/taoke -->
<form onsubmit="InsertValue();return false;" id="form-table">
<div id="wptao_tips"></div>
<table class="form-table">
  <tr>
    <th style="width: 20%;"> <label for="tbk_link">商品链接 (*)</label>
    </th>
    <td><input type="text" name="tbk[link]" id="tbk_link" size="30" style="width: 80%;" />
	<p class="description">
	<input type="hidden" name="tbk[site]" id="tbk_site" />
	<input type="hidden" name="tbk[end_time]" id="tbk_end_time" />
	<input type="button" id="get_item_info" title="获取信息" value="获取信息" /> <?php wptao_button_text();?>
	</p>
	</td>
  </tr>
  <tr>
    <td colspan="2" id="wptao_commfee" style="padding:0"></td>
  </tr>
  <?php if ($wptao_options['mce_mm']) { ?>
  <tr>
    <th style="width: 20%;"> <label for="tbk_mm_link">商品推广链接</label>
    </th>
    <td><div id="get_alimama" style="max-width:510px;overflow:hidden;"></div><input type="text" name="tbk[mm_link]" id="tbk_mm_link" size="30" style="width: 80%;" placeholder="商品推广链接" /> <a href="javascript:;" onclick="document.getElementById('tbk_mm_link').value='';return false">[清空]</a></td>
  </tr>
  <?php } ?>
  <tr>
    <th style="width: 20%;"> <label for="post_title">商品标题</label>
    </th>
    <td><input type="text" name="post_title" id="post_title" size="30" style="width: 80%;" /></td>
  </tr>
  <tr>
    <th style="width: 20%;"> <label for="tbk_price">商品价格</label>
    </th>
    <td><input type="text" name="tbk[price]" id="tbk_price" size="30" style="width:34%;" placeholder="商品价格" /> <label for="tbk_old_price">原价 </label><input type="text" name="tbk[old_price]" id="tbk_old_price" size="30" style="width:34%;" placeholder="商品原价" /></td>
  </tr>
  <tr>
    <th style="width: 20%;"> <label for="tbk_coupon">优惠券链接</label>
    </th>
    <td><input type="text" name="tbk[coupon]" id="tbk_coupon_url" size="30" style="width:80%;" placeholder="优惠券链接" /><br /><input type="text" name="tbk[coupon_value]" id="tbk_coupon_value" size="30" style="width:34%;" placeholder="优惠券面值" /> <label for="tbk_old_price">口令 </label><input type="text" name="tbk[tkl]" id="tbk_tkl" size="30" style="width:34%;" placeholder="淘口令" /></td>
  </tr>
  <tr>
    <th style="width: 20%;"> <label for="imageURL">商品图片</label>
    </th>
    <td><input type="text" name="tbk[image]" id="imageURL" size="30" style="width: 80%;" /> <a title="清空后，前台不使用模版，仅显示链接" href="javascript:;" onclick="document.getElementById('imageURL').value='';return false">[清空]</a></td>
  </tr>
  <?php if ($wptao_options['mce_desc']) { ?>
  <tr>
    <th style="width: 20%;"> <label for="imageURL">自定义内容</label>
    </th>
    <td><input type="text" id="tbk_desc" size="30" style="width:80%;" /><label></td>
  </tr>
  <?php } ?>
  <tr>
    <th style="width: 20%;"> <label for="post_content">推荐理由</label>
    </th>
    <td><textarea id="tbk_content" rows="5" name="tbk_content" style="width: 80%;"></textarea></td>
  </tr>
</table>
<div class="submitbox">
	<div id="wp-link-theme">
		<span style="padding-left:20px">模版：<label><input type="radio" name="tbk_theme" value=""<?php checked(!$wptao_theme);?>>默认(全局)</label>
		<?php
		for ($t = 1; $t <= 5; $t++) {
			echo '<label><input type="radio" name="tbk_theme" value="' . $t . '"' . checked($t == $wptao_theme, true, false) . '>模版' . $t . '</label> ';
		} ?>
		</span>
	</div>
	<div id="wp-link-position">
		<span style="padding-left:20px">本窗口插入的会自动转内链 <a href="javascript:;" onclick="alert('提示：淘宝、天猫、聚划算也可以不填写【商品推广链接】，用户点击时会自动生成并且自动转内链(走普通佣金)。只要填写了【商品推广链接】都会自动转内链。')">[？]</a> （位置：<label><input type="radio" name="tbk_position" value="L"<?php checked($wptao_position == 'L');?>>居左</label> <label><input type="radio" name="tbk_position" value="C"<?php checked(!$wptao_position || $wptao_position == 'C');?>>居中</label> <label><input type="radio" name="tbk_position" value="R"<?php checked($wptao_position == 'R');?>>居右</label>）</span>
	</div>
	<div id="wp-link-update">
		<input type="submit" value="插入至文章" class="button button-primary" id="wp-link-submit" name="wp-link-submit">
	</div>
</div>
</form>
<script type="text/javascript">
var wptao_data,wptao_js = <?php echo json_encode_zh_cn(wptao_js_var()+$js_var);?>;
function getId(a){return document.getElementById(a)}
function wp_set_aCookie(a,b,c,d,e,f){if("number"==typeof c){var g=c,h=c=new Date;h.setTime(+h+864e5*g),c=h.toGMTString()}document.cookie=a+"="+encodeURIComponent(b)+(c?"; expires="+c:"")+(d?"; path="+d:"; path=/")+(e?"; domain="+e:"")+(f?"; secure":"")}
function InsertValue(){<?php if (wptao_is_tested()) echo "alert('\u60a8\u7684\u514d\u8d39\u8bd5\u7528\u5df2\u7ecf\u5230\u671f\uff0c\u8bf7\u8d2d\u4e70\u540e\u7ee7\u7eed\u4f7f\u7528\uff0c\u8c22\u8c22\uff01');window.open('https://wptao.com/wptao.html?from=wptao');return false;";?>var b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,a=getId("tbk_link").value;return a?(b=getId("post_title").value,c=getId("imageURL").value,b||c?(d=getId("tbk_content").value,e=getId("tbk_site").value,f=getId("tbk_price").value,g=getId("tbk_old_price").value,h=getId("tbk_end_time").value,i=getId("tbk_coupon_url").value,j=getId("tbk_coupon_value").value,k=getId("tbk_mm_link"),l=getId("tbk_desc"),m=getId("tbk_tkl").value,n=$("#wp-link-theme input[type='radio']:checked").val(),o=$("#wp-link-position input[type='radio']:checked").val(),o||(o="C"),wp_set_aCookie("wptao_theme",n,365),wp_set_aCookie("wptao_position",o,365),html='[wptao _title="'+b+'" price="'+f+'" url="'+a+'"',k&&(p=k.value,p&&p!=a&&(html+=' _url="'+p+'"')),g&&f&&parseInt(g)>parseInt(f)&&(html+=' _price="'+g+'"'),i&&(html+=' coupon="'+i+'"',j&&(html+=' coupon_value="'+j+'"')),html+=' site="'+e+'"',h&&(html+=' end_time="'+h+'"'),m&&(html+=' tkl="'+m+'"'),c&&(html+=' <img class="wptao-img" src="'+c+'">'),"C"!=o&&(html+=' position="'+o+'"'),""!=n&&(html+=' theme="'+n+'"'),l&&(q=l.value,q&&(q=wptao_js.mce_desc.replace(new RegExp("#desc#","g"),q),0===wptao_js.mce_desc_p?d=q+d:d+=q)),html+="]"+d+"[/wptao]",window.parent.tinyMCE.activeEditor.execCommand("mceInsertContent",0,html),window.parent.tinyMCE.activeEditor.windowManager.close(),void 0):(alert("商品标题和商品图片必须要有一个！"),!1)):(alert("请输入商品链接"),!1)}
</script>
<script type='text/javascript' src='<?php echo WPTAO_URL;?>/admin/js/jquery.min.js?ver=1.2.6'></script>
<script type='text/javascript' src='<?php echo WPTAO_URL;?>/admin/js/jquery.plugin.js?ver=<?php echo WPTAO_V;?>'></script>
</body>
</html>