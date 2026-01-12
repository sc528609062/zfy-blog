<script>
 function is_neizhi() {
    var ua = navigator.userAgent.toLowerCase();
    if (ua.match(/MicroMessenger/i) == "micromessenger") {
        return "weixin";
    } else if (ua.match(/QQ/i) == "qq") {
        return "QQ";
    } else if (ua.match(/Alipay/i) == "alipay") {
        return "alipay";
    }
    return false;
}
var isNeizhi = is_neizhi();  //调用上面js判断
var winHeight = typeof window.innerHeight != 'undefined' ? window.innerHeight : document.documentElement.clientHeight;  //网页可视区高度
var weixinTip = $('<div class="wxtip" id="JweixinTip" ><span class="wxtip-icon"></span><p class="wxtip-txt" id="wxtip-txt">请点击右上角<br>选择在浏览器中打开</p></div>');
if(isNeizhi){
  $("body").append(weixinTip);
}
</script>
