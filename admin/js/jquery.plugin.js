/* v2.5.8 https://wptao.com/taoke */
jQuery(function(a){wptao_data||(wptao_data={title:"post_title",url:"tbk_link",item_click:"tbk_mm_link",shop_click:"shop_url",price:"tbk_price",old_price:"tbk_old_price",image:"imageURL",desc:"post_content",shop_name:"shop_name",postfee:"tbk_postfee",tags:"new-tag-post_tag",id:"tbk_id",mall:"tbk_mall",site:"tbk_site",preview:"wptao_preview",end_time:"tbk_end_time",coupon:"tbk_coupon_url",coupon_value:"tbk_coupon_value",coupon_end:"tbk_coupon_end",tkl:"tbk_tkl"});var b=wptao_data.preview;a("#get_item_info").click(function(){var c,d;return wptao_js.login?(c=a("#tbk_link").val())?(d=a("#tbk_coupon_url").val(),a("input[type='text'],input[type='hidden'],textarea").val(""),a("#tbk_link").val(c),a("#wptao_tips,#wptao_commfee").html(""),a.ajax({type:"GET",url:wptao_js.ajax_url+"?action=wptao_ajax&type=sign&link="+encodeURIComponent(c),cache:!1,success:function(e){if(!e)return alert("请填写插件授权码！"),!1;var f=wptao_js.api+"/get_items_detail.php?callback=?";a.getJSON(f,{u:encodeURIComponent(c),ref:encodeURIComponent(wptao_js.blog_url),sign:e,c:wptao_js.c,cps:wptao_data.item_click||wptao_data.shop_click?1:0,desc:wptao_data.desc&&a("#"+wptao_data.desc).length>0?1:0,coupon:d,v:wptao_js.v},function(c){var d,e;if(c.url){c.tips&&a("#wptao_tips").html(c.tips),c.tkinfo&&a("#wptao_commfee").html(c.tkinfo);for(d in wptao_data)c[d]&&a("#"+wptao_data[d]).val(c[d]);b&&a("#"+b).length>0&&(e="",c.image&&(e='<img src="'+c.image+'" />'),a("#"+b).html(e)),c.site&&a("#tax_input option").length>0&&(c.site=c.site.replace("商城","").replace("网",""),a("#tax_input option[value='']").attr("selected",!0),a("#tax_input option").each(function(){return a(this).text().indexOf(c.site)>=0?(a(this).attr("selected",!0),!1):void 0})),c.error&&alert(c.error)}else c.error&&alert(c.error)})}}),void 0):(alert("商品链接不能留空！"),!1):(alert("请登录后再操作！"),!1)}),b&&a("#"+b).length>0&&wptao_data.image&&a("#"+wptao_data.image).each(function(){a(this).bind("mouseleave change focus blur",function(){var c="",d=a(this).val();d&&(c='<img src="'+d+'" />'),a("#"+b).html(c)})})});