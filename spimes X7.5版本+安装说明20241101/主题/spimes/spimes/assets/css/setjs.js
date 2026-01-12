
$(function () {
var global=0; 
var user=0;
var color=0;
var seo=0;

//常规设置
var tougao = document.getElementsByName('tougao')[0].value;
var gaoedit = document.getElementsByName('gaoedit')[0].value;
//会员中心
var denglu = document.getElementsByName('denglu')[0].value;
var zhuce = document.getElementsByName('zhuce')[0].value;
var tgcat = document.getElementsByName('tgcat')[0].value;
//风格样式
var vartheme = document.getElementsByName('vartheme')[0].value;
//SEO设置
var seotitle = document.getElementsByName('seotitle')[0].value;

if(tougao.length <= 0){  global=global+1; }
if(gaoedit.length <= 0){  global=global+1; }

if(denglu.length <= 0){  user=user+1; }
if(zhuce.length <= 0){  user=user+1; }
if(tgcat.length <= 0){  user=user+1; }

if(vartheme.length <= 0){  color=color+1; }

if(seotitle.length <= 0){  seo=seo+1; }

var onblog = $("#t_logo").attr("data-key"); //11

if(onblog!=-1){
if(global!=0){  $("#global").append("<em>"+global+"</em>");  }   
if(user!=0){  $("#user").append("<em>"+user+"</em>");  }  
if(color!=0){  $("#color").append("<em>"+color+"</em>");  }  
if(seo!=0){  $("#seo").append("<em>"+seo+"</em>");  }  
var sum =Number(global)+Number(user)+Number(color)+Number(seo);
if(sum>0){  $("#setid").after("<p> ● 相关页面设置：检查发现主题还有 "+sum+" 处地方页面没有设置，详细请看左边栏数字提示</p>");  }  
}
//当不开启会员的时候，屏蔽主题某些会员设置
if(onblog==-1){
    
    $(":text[name='gaoedit']").attr("disabled","disabled");
    $(":text[name='tougao']").attr("disabled","disabled");
    $(":text[name='denglu']").attr("disabled","disabled");
    $(":text[name='zhuce']").attr("disabled","disabled");
    $(":text[name='tgcat']").attr("disabled","disabled");
    $(":text[name='auadside']").attr("disabled","disabled");
    $("select[name='spoints']").attr("disabled","disabled").css("background-color","#EEEEEE");
    $("select[name='spoints']").attr("disabled","disabled").css("background-color","#EEEEEE");
}


})



$(function(){   
$('#logoUrl-0-1').attr('placeholder','https://xxx.com/xxx.png')
$('#logoUrldark-0-2').attr('placeholder','https://xxx.com/xxx.png') 
$('#favicon-0-3').attr('placeholder','https://xxx.com/xxx.ico')
$('#liuyan-0-4').attr('placeholder','liuyan')
$('#sitelink-0-5').attr('placeholder','sitelink')
$('#imghdp-0-6').attr('placeholder','1,2')
$('#dhtop-0-7').attr('placeholder','1,2')
$('#nolist-0-8').attr('placeholder','选填')
$('#topnews-0-9').attr('placeholder','1,2,3,4') 
$('#sequid-0-10').attr('placeholder','1,2,3,4')   
$('#slidenum-0-11').attr('placeholder','默认为30天最热（选填）')
$('#zhiduid-0-12').attr('placeholder','1')  
$('#labanew-0-13').attr('placeholder','1,2')
$('#footnew-0-14').attr('placeholder','1')
$('#footnewmore-0-15').attr('placeholder','https://xxx.com/xxx')
$('#sidetag-0-16').attr('placeholder','https://xxx.com/xxx')
$('#liuynes-0-17').attr('placeholder','https://xxx.com/xxx')
$('#sitedate-0-18').attr('placeholder','2017-05-20')
$('#navtops-0-21').attr('placeholder','标题|https://xxx.com/xxx|文字描述|icon-icon-test')
$('#navsecs-0-22').attr('placeholder','标题|https://xxx.com') 
$('#adimg-0-23').attr('placeholder','<a href="https://xxx.com/xxx" ><img src="https://xxxx.com/xxxx.png"></a>')
$('#hdadimg-0-24').attr('placeholder','<a href="https://xxx.com/xxx" ><img src="https://xxxx.com/xxxx.png"></a>')  
$('#listadimg-0-25').attr('placeholder','<a href="https://xxx.com/xxx" ><img src="https://xxxx.com/xxxx.png"></a>')  
$('#txtadimg-0-26').attr('placeholder','<a href="https://xxx.com/xxx" ><img src="https://xxxx.com/xxxx.png"></a>')  
$('#txtaddown-0-27').attr('placeholder','<a href="https://xxx.com/xxx" ><img src="https://xxxx.com/xxxx.png"></a>')   
$('#footernavs-0-38').attr('placeholder','<a href="https://xxx.com/xxx">标题</a>')
$('#footernav-0-39').attr('placeholder','<a href="https://xxx.com/xxx">标题</a>')    
$('#footnav-0-40').attr('placeholder','版权所有 本站内容未经书面许可,禁止一切形式的转载')  
$('#footlogo-0-41').attr('placeholder','https://xxx.com/xxx.png')  
$('#navmobi-0-44').attr('placeholder','icon-huati|https://xxx.com')  
$('#Keywordspress-0-49').attr('placeholder','关键字|https:/xxx.com')  
$('#cdnurla-0-51').attr('placeholder','www.xxx.com/usr/uploads/') 
$('#cdnurlb-0-52').attr('placeholder','cdn.xxx.com/') 
$('#imageView-0-53').attr('placeholder','选填')    
$('#pdmapi-0-54').attr('placeholder','https://danmu.izhuolin.cn/3.0/')  
$('#plogo-0-55').attr('placeholder','https://xxx.com/xxx.png')
$('#navtops-0-23').attr('placeholder','自定义标题|https://www.xxx.com|自定义描述|icon-dianying')
$('#navsecs-0-24').attr('placeholder','标题|链接')
$('#footernavs-0-43').attr('placeholder','<a target="_blank" href="//www.dpaoz.com">小灯泡</a>')
$('#footernav-0-44').attr('placeholder','<a target="_blank" href="//www.dpaoz.com">小灯泡</a>')
$('#footnav-0-45').attr('placeholder','本站内容未经书面许可,禁止一切形式的转载。粤ICP备123456号-1')
$('#navmobi-0-49').attr('placeholder','icon-shouye|https://www.xxx.com')
$('#cdnurla-0-57').attr('placeholder','www.xxx.com/usr/uploads/')
$('#cdnurlb-0-58').attr('placeholder','x.xxx.com/')
$('#Keywordspress-0-55').attr('placeholder','关键词|www.xxx.com')
$('#navmobi-0-50').attr('placeholder','icon-shouye|https://www.xxx.com')
})

// Run when the DOM ready
jQuery( function ( $ ) {

	var clickEvent = 'ontouchstart' in window ? 'touchstart' : 'click';

	/**
	 * Scroll to top
	 */
	function scrollToTop() {
		var $window = $( window ),
			$button = $( '#scroll-to-top' );
		$window.scroll( function () {
			$button[$window.scrollTop() > 100 ? 'removeClass' : 'addClass']( 'hidden' );
		} );
		$button.on( clickEvent, function ( e ) {
			e.preventDefault();
			$( 'body, html' ).animate( {
				scrollTop: 0
			}, 500 );
		} );
	}

	scrollToTop();
} );

$(function() {
	$(".Close").click(function() {
		$(".pannel_box").stop().slideToggle();
        $(".Close").toggleClass("close_top",100);
	})

//  主题检测
$('#spimes_ex').on('click', function () {
   cocoMessage.info('主题检测中……');    
   $(".sp_ex").html("");
   $.ajax({
    //  请求方式 post
    type: 'post',
    //  url 获取点赞按钮的自定义 url 属性
    url: SPZ.BASE_ex,
    //  发送的数据 cid，直接获取点赞按钮的 cid 属性
    data: 'dataloor=1',
    async: true,
    dataType: "json",
    //  请求成功的函数
    success: function (data) {
       if(data.vinfo){
        $(".sp_ex").html('<div class="miracles-pannel zt-pannel"><span>'+data.vhtml+'</span><form class="protected" action="?Miraclesliu" method="post"><input type="submit" name="themetype" class="miracles-backup-button backup" value="一键修复"></form></div>');
       }
       else{
          $(".sp_ex").html('<div class="miracles-pannel zt-pannel"><span>'+data.vhtml+'</span></div>'); 
       }
    },
    error: function () {
    },
  });
});



});

$('.current').after('<li><a href="/usr/themes/spimes/ext/danmu/admin"><i class="ri-play-circle-line ri-lg"></i> 播放器设置</a></li>');

/*!
coco-message 是一个简单实用的javascript信息提示插件， 兼容主流浏览器，兼容至ie9 (ie没有svg动画)

兼容性 >=ie9 
*/

"use strict";function _typeof(o){return _typeof="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(o){return typeof o}:function(o){return o&&"function"==typeof Symbol&&o.constructor===Symbol&&o!==Symbol.prototype?"symbol":typeof o},_typeof(o)}!function(o,t){"object"===("undefined"==typeof exports?"undefined":_typeof(exports))&&"undefined"!=typeof module?module.exports=t():"function"==typeof define&&define.amd?define(t):(o=o||self,o.cocoMessage=t())}(void 0,function(){function o(o,t){var e=document.createElement("div");for(var n in o){var s=o[n];"className"==n?(n="class",e.setAttribute(n,s)):"_"==n[0]&&e.addEventListener(n.slice(1),s)}if("string"==typeof t)e.innerHTML=t;else if("object"==_typeof(t)&&t.tagName)e.appendChild(t);else if(t)for(var i=0;i<t.length;i++){var c=t[i];e.appendChild(c)}return e}function t(o,t){["a","webkitA"].forEach(function(e){var n=e+"nimationEnd";o.addEventListener(n,function(){t()})})}function e(o,t){for(var e in t)o.style[e]=t[e];""===o.getAttribute("style")&&o.removeAttribute("style")}function n(o,t){var e=o.className||"";if(!s(e,t)){var n=e.split(/\s+/);n.push(t),o.className=n.join(" ")}}function s(o,t){return o.indexOf(t)>-1}function i(o,t){var e=o.className||"";if(s(e,t)){var n=e.split(/\s+/),i=n.indexOf(t);n.splice(i,1),o.className=n.join(" ")}""===o.className&&o.removeAttribute("class")}function c(o,t){var e={};for(var n in h)e[n]=h[n];for(var s=0;s<o.length;s++){var i=o[s];void 0!==i&&("string"==typeof i||"object"==_typeof(i)?e.msg=i:"boolean"==typeof i?e.showClose=i:"function"==typeof i?e.onClose=i:"number"==typeof i&&(e.duration=i))}return e.type=t,r(e)}function r(n){var s=n.type,c=n.duration,r=n.msg,d=n.showClose,g=n.onClose,m=0===c,h=l();"loading"==s&&(r=""===r?"正在加载，请稍后":r,m=d,c=0);var u=o({className:"coco-msg-wrapper"},[o({className:"coco-msg coco-msg-fade-in ".concat(s)},[o({className:"coco-msg-icon"},h[s]),o({className:"coco-msg-content"},r),o({className:"coco-msg-wait ".concat(m?"coco-msg-pointer":""),_click:function(){m&&f(u,g)}},a(m))])]),v=u.querySelector(".coco-msg__circle");if(v&&"loading"!==s&&(e(v,{animation:"coco-msg__circle ".concat(c,"ms linear")}),"onanimationend"in window?t(v,function(){f(u,g)}):setTimeout(function(){f(u,g)},c)),"loading"==s&&0!==c&&setTimeout(function(){f(u,g)},c),p.children.length||document.body.appendChild(p),p.appendChild(u),e(u,{height:u.offsetHeight+"px"}),setTimeout(function(){i(u.children[0],"coco-msg-fade-in")},300),"loading"==s)return function(){f(u,g)}}function a(o){return o?'\n    <svg class="coco-msg-close" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="5514"><path d="M810 274l-238 238 238 238-60 60-238-238-238 238-60-60 238-238-238-238 60-60 238 238 238-238z" p-id="5515"></path></svg>\n    ':'<svg class="coco-msg-progress" viewBox="0 0 33.83098862 33.83098862" xmlns="http://www.w3.org/2000/svg">\n    <circle class="coco-msg__background" cx="16.9" cy="16.9" r="15.9"></circle>\n    <circle class="coco-msg__circle" stroke-dasharray="100,100" cx="16.9" cy="16.9" r="15.9"></circle>\n    </svg>\n    '}function f(o,t){o&&(e(o,{padding:0,height:0}),n(o.children[0],"coco-msg-fade-out"),t&&t(),setTimeout(function(){if(o){for(var t=!1,e=0;e<p.children.length;e++)p.children[e]===o&&(t=!0);t&&d(o),o=null,p.children.length||t&&d(p)}},300))}function l(){return{info:'\n    <svg t="1609810636603" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="3250"><path d="M469.333333 341.333333h85.333334v469.333334H469.333333z" fill="#ffffff" p-id="3251"></path><path d="M469.333333 213.333333h85.333334v85.333334H469.333333z" fill="#ffffff" p-id="3252"></path><path d="M384 341.333333h170.666667v85.333334H384z" fill="#ffffff" p-id="3253"></path><path d="M384 725.333333h256v85.333334H384z" fill="#ffffff" p-id="3254"></path></svg>\n    ',success:'\n    <svg t="1609781242911" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="1807"><path d="M455.42 731.04c-8.85 0-17.75-3.05-24.99-9.27L235.14 553.91c-16.06-13.81-17.89-38.03-4.09-54.09 13.81-16.06 38.03-17.89 54.09-4.09l195.29 167.86c16.06 13.81 17.89 38.03 4.09 54.09-7.58 8.83-18.31 13.36-29.1 13.36z" p-id="1808" fill="#ffffff"></path><path d="M469.89 731.04c-8.51 0-17.07-2.82-24.18-8.6-16.43-13.37-18.92-37.53-5.55-53.96L734.1 307.11c13.37-16.44 37.53-18.92 53.96-5.55 16.43 13.37 18.92 37.53 5.55 53.96L499.67 716.89c-7.58 9.31-18.64 14.15-29.78 14.15z" p-id="1809" fill="#ffffff"></path></svg>\n    ',warning:'\n    <svg t="1609776406944" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="18912"><path d="M468.114286 621.714286c7.314286 21.942857 21.942857 36.571429 43.885714 36.571428s36.571429-14.628571 43.885714-36.571428L585.142857 219.428571c0-43.885714-36.571429-73.142857-73.142857-73.142857-43.885714 0-73.142857 36.571429-73.142857 80.457143l29.257143 394.971429zM512 731.428571c-43.885714 0-73.142857 29.257143-73.142857 73.142858s29.257143 73.142857 73.142857 73.142857 73.142857-29.257143 73.142857-73.142857-29.257143-73.142857-73.142857-73.142858z" p-id="18913" fill="#ffffff"></path></svg>\n    ',error:'\n    <svg t="1609810716933" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="5514"><path d="M810 274l-238 238 238 238-60 60-238-238-238 238-60-60 238-238-238-238 60-60 238 238 238-238z" p-id="5515" fill="#ffffff"></path></svg>\n    ',loading:'\n    <div class="coco-msg_loading">\n    <svg class="coco-msg-circular" viewBox="25 25 50 50">\n      <circle class="coco-msg-path" cx="50" cy="50" r="20" fill="none" stroke-width="4" stroke-miterlimit="10"/>\n    </svg>\n    </div>\n    '}}function d(o){o&&o.parentNode.removeChild(o)}function g(){for(var o=0;o<p.children.length;o++){var t=p.children[o];f(t)}}function m(){var o=document;if(o&&o.head){var t=o.head,e=o.createElement("style"),n="\n[class|=coco],[class|=coco]::after,[class|=coco]::before{box-sizing:border-box;outline:0}.coco-msg-progress{width:14px;height:14px}.coco-msg__circle{stroke-width:2;stroke-linecap:square;fill:none;transform:rotate(-90deg);transform-origin:center}.coco-msg-stage:hover .coco-msg__circle{-webkit-animation-play-state:paused!important;animation-play-state:paused!important}.coco-msg__background{stroke-width:2;fill:none}.coco-msg-stage{position:fixed;top:20px;left:50%;width:auto;transform:translate(-50%,0);z-index:3000}.coco-msg-wrapper{position:relative;left:50%;transform:translate(-50%,0);transition:height .25s ease-out,padding .25s ease-out;transition:height .35s ease-out,padding .35s ease-out;padding:8px 0;will-change:transform,opacity}.coco-msg-content,.coco-msg-icon,.coco-msg-wait{display:inline-block}.coco-msg-icon{position:relative;width:13px;height:13px;border-radius:100%;display:flex;justify-content:center;align-items:center;opacity:.8}.coco-msg-icon svg{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:11px;height:11px;box-sizing:content-box}.coco-msg-wait{width:20px;height:20px;position:relative}.coco-msg-wait svg{position:absolute;top:50%;right:-4px;transform:translate(0,-50%);fill:#b3b9b9}.coco-msg-close{width:16px;height:16px}.coco-msg-content{margin:0 10px;min-width:220px;text-align:left;font-size:14px;font-weight:400}.coco-msg.error .coco-msg-icon,.coco-msg.info .coco-msg-icon,.coco-msg.success .coco-msg-icon,.coco-msg.warning .coco-msg-icon{background-color:currentColor}.coco-msg{padding:13px 25px;border-radius:2px;position:relative;left:50%;transform:translate(-50%,0);display:flex;align-items:center}.coco-msg.info,.coco-msg.loading{color:#635f6b;background-color:#f3f3f4;box-shadow:0 0 1px 0 rgba(239,238,240,.3)}.coco-msg.success{color:#68c43b;background-color:#f0faeb;box-shadow:0 0 1px 0 rgba(145,194,126,.3)}.coco-msg.warning{color:#be820a;background-color:#faf4e1;box-shadow:0 0 1px 0 rgba(212,198,149,.3)}.coco-msg.error{color:#f74e60;background-color:#fee2e5;box-shadow:0 0 1px 0 rgba(218,163,163,.3)}.coco-msg.loading .coco-msg-icon{background-color:transparent}@keyframes coco-msg__circle{0%{stroke:#b3b9b9;stroke:currentColor}to{stroke:#b3b9b9;stroke:currentColor;stroke-dasharray:0 100}}.coco-msg_loading{flex-shrink:0;width:20px;height:20px;position:relative}.coco-msg-circular{-webkit-animation:coco-msg-rotate 2s linear infinite both;animation:coco-msg-rotate 2s linear infinite both;transform-origin:center center;height:18px!important;width:18px!important}.coco-msg-path{stroke-dasharray:1,200;stroke-dashoffset:0;stroke:currentColor;-webkit-animation:coco-msg-dash 1.5s ease-in-out infinite;animation:coco-msg-dash 1.5s ease-in-out infinite;stroke-linecap:round}@-webkit-keyframes coco-msg-rotate{100%{transform:translate(-50%,-50%) rotate(360deg)}}@keyframes coco-msg-rotate{100%{transform:translate(-50%,-50%) rotate(360deg)}}@-webkit-keyframes coco-msg-dash{0%{stroke-dasharray:1,200;stroke-dashoffset:0}50%{stroke-dasharray:89,200;stroke-dashoffset:-35px}100%{stroke-dasharray:89,200;stroke-dashoffset:-124px}}@keyframes coco-msg-dash{0%{stroke-dasharray:1,200;stroke-dashoffset:0}50%{stroke-dasharray:89,200;stroke-dashoffset:-35px}100%{stroke-dasharray:89,200;stroke-dashoffset:-124px}}.coco-msg-pointer{cursor:pointer}.coco-msg-fade-in{-webkit-animation:coco-msg-fade .22s ease-out both;animation:coco-msg-fade .22s ease-out both}.coco-msg-fade-out{animation:coco-msg-fade .22s linear reverse both}@-webkit-keyframes coco-msg-fade{0%{opacity:0;transform:translate(-50%,0)}to{opacity:1;transform:translate(-50%,0)}}@keyframes coco-msg-fade{0%{opacity:0;transform:translate(-50%,-80%)}to{opacity:1;transform:translate(-50%,0)}}\n      ";e.innerHTML=n,t.children.length?t.insertBefore(e,t.children[0]):t.appendChild(e)}}if("undefined"!=typeof window){var p=o({className:"coco-msg-stage"}),h={msg:"",duration:2e3,showClose:!1},u={info:function(){c(arguments,"info")},success:function(){c(arguments,"success")},warning:function(){c(arguments,"warning")},error:function(){c(arguments,"error")},loading:function(){return c(arguments,"loading")},destroyAll:function(){g()},config:function(o){for(var t in o)Object.hasOwnProperty.call(o,t)&&void 0!==o[t]&&(h[t]=o[t])}};return window.addEventListener("DOMContentLoaded",function(){m()}),u}});

