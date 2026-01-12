</main>
</div><!-- .container -->
<footer class="site-footer <?php if ($this->options->mobiset == '1'):?>fsi<?php endif; ?>">
<?php if ( _blog()&&$this->options->footnew && $this->is('index') ): ?>
<?php $this->need('assets/footer - new.php'); ?>
<?php endif; ?>
    <div class="site-info clearfix">
        <div class="container">        
		<!--左边-->
        <div class="footer-left">
            <div class="footer-l-top">
			<?php $this->widget('Widget_Contents_Page_List')->to($pages); ?> 
			<?php while($pages->next()): ?>
			<a href="<?php $pages->permalink(); ?>"><?php $pages->title(); ?></a>
			<?php endwhile; ?>
			</div>
            <!-- 友情链接 -->               
            <div class="footer-l-btm">
            <p><i class="ri-medal-line ri-lg"></i> <?php $this->options->description() ?> 页面执行：<?php echo timer_stop();?></p>
			<p><?php if($this->options->footnav){$this->options->footnav();} ?></p>
			<?php if ($this->is('index')): ?><?php if ($this->options->footernav): ?><p class="links"><?php _e('友情链接：'); ?><?php if($this->options->footernav){$this->options->footernav();} ?></p><?php endif; ?><?php endif; ?>
			</div>
            <?php $this->options->zztj(); ?>
            <!-- 友情链接 end -->
        </div>
		<!--右边-->
        <div class="footer-right ewms">
        <!--s-->
        <div class="like">
        <?php if ($this->options->websum): ?>    
        <strong><?php $this->options->websum(); ?></strong>
        <?php endif; ?>
        <h3><?php $this->options->title(); ?>新媒体等你关注</h3>
        </div>
        <?php if ($this->options->webewm): ?>
        <div class="fright-qrcode">
        <img src="<?php $this->options->webewm(); ?>">    
        </div>
        <?php endif; ?>
        <!--e-->
        </div>
        </div>
    </div>    
</footer>
<?php getCatalog(); ?>
<div class="progress-wrap">
		<svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
			<path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98"/>
		</svg>
</div>
</div>
<script src="<?php echo stcdn($this->options->themeUrl); ?>/src/js/jquery.pjax.js"></script>
<?php if ($this->options->mobiset == '1'):?>
<?php $this->need('assets/nav - mobi - footer.php'); ?>
<?php endif; ?>
<script src="<?php echo stcdn($this->options->themeUrl); ?>/src/js/swiper.min.js"></script>
<?php if ( $this->is('post') || $this->is('page') ) : ?>
<?php if ($this->options->pingimg == 'pingyes'):?>
<link rel="stylesheet" href="<?php echo stcdn($this->options->themeUrl); ?>/src/owo/OwO.min.css" type="text/css" media="all">
<script src="<?php echo stcdn($this->options->themeUrl); ?>/src/owo/OwO.js"></script>
<?php endif; ?>
<?php if ($this->is('post')) : ?>
<script type="text/javascript">
function Share(pType){
var pTitle = "<?php $this->title(); ?>"; //待分享的标题
var pImage = "<?php $this->fields->img(); ?>"; //待分享的图片

var pContent = "<?php echo fottxt($this->excerpt);; ?>";
var pUrl = window.location.href; //当前的url地址
var pObj = jQuery("div[class='yogo_hc']").find("h4");
if(pObj.length){ pTitle = pObj.text();}
var pObj = jQuery("div[class='yogo_hcs']").find("em");
if(pObj.length){ pContent = pObj.text();  }
var pObj = jQuery("div[class='con_cons']").find("img");
if(pObj.length){ pImage = jQuery("div[class='con_cons']").find("img",0).attr("src");}
shareys(pType, pUrl, pTitle,pImage, pContent);
}
</script>
<?php endif; ?>
<?php endif; ?>

<div class="mobile-overlay"></div>
<?php if ($this->options->zhuce && $this->options->denglu && $this->is('page', $this->options->denglu) && $this->is('page', $this->options->zhuce)): ?><?php else : ?>  
<script src="<?php echo stcdn($this->options->themeUrl); ?>/src/js/script.js?ver=<?php echo themeVersion(); ?>"></script>
<script type="text/javascript">hljs.initHighlightingOnLoad();</script> 
<script>$(function() {$(".scrollLoading").scrollLoading();});</script>
<?php endif;?>
<?php if ($this->options->foothtml): ?>
<?php $this->options->foothtml(); ?>
<?php endif; ?>
<?php if ($this->is('post')) : ?>
<script type="text/javascript">
$(".entry-content img").each(function(){
var imgsec=$(this).attr("src");
var index= imgsec.lastIndexOf(".");
//获取后缀
var ext = imgsec.substr(index+1);
if(isAssetTypeAnImage(ext)){
if($(this).hasClass("ctn_img")){ 
$(this).attr({"data-url":imgsec+"<?php $this->options->imageView(); ?>","src":"<?php $this->options->themeUrl('src/images/piex.gif'); ?>"});
$(this).addClass("scrollLoading");
}}});
</script>
<?php if ($this->fields->img&&($this->options->oncover !='off')): ?><nocompress><?php $this->need('assets/poster.php'); ?></nocompress><?php endif; ?>
<?php endif; ?>
<?php if ($this->options->themecompress == '1'):?>
<?php error_reporting(0); $html_source = ob_get_contents(); ob_clean(); print compressHtml($html_source); ob_end_flush(); ?>
<?php endif; ?>
<?php $this->footer(); ?>
<?php if ($this->options->browser == '1'):?>
<?php $this->need('assets/browser.php'); ?>
<?php endif; ?>
<?php if ($this->options->aichat =='1'): ?>
<?php Deng_Plugin::getIntel(); ?>
<?php endif; ?>

</body>
</html>