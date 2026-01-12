<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('header.php');
?>
<link rel="stylesheet" href="<?php $this->options->themeUrl('css/links.css'); ?>">
<div class="nav-content">
    <div class="row">
        <!--s导航-->
        <div class="nav-sidebar col-md-2">
            <nav class="nav"> 
              <span class="nav-title">站点导航</span>
              <ul class="nav-list">
                <li class="nav-list-item"><a href="#div-1" class="nav-item active"><i class="icon iconfont icon-shoucang1"></i> 一周热门</a></li>  
                <li class="nav-list-item"><a href="#div-2" class="nav-item"><i class="icon iconfont icon-shoucang1"></i> 30天热门</a></li>  
                <li class="nav-list-item"><a href="#div-3" class="nav-item"><i class="icon iconfont icon-wode1"></i>  用户访客</a></li>
                <li class="nav-list-item"><a href="#div-4" class="nav-item"><i class="icon iconfont icon-shuju1"></i> 标签导航</a></li>                
              </ul> 
              
              <span class="nav-title">关于我们</span>
              <ul class="nav-list">
                <?php $this->widget('Widget_Contents_Page_List')->to($pages); ?>
		        <?php while($pages->next()): ?>
                <li class="nav-list-item"><a href="<?php $pages->permalink(); ?>" class="nav-item"><i class="icon iconfont icon-shouye1"></i> <?php $pages->title(); ?></a></li>
                <?php endwhile; ?>
                <!--<li class="nav-list-item"><a href="" class="nav-item"><i class="icon iconfont icon-shouye1"></i>  联系我们</a></li>
                <li class="nav-list-item"><a href="" class="nav-item"><i class="icon iconfont icon-shouye1"></i>  用户协议</a></li>
                <li class="nav-list-item"><a href="" class="nav-item"><i class="icon iconfont icon-shouye1"></i> 免责声明</a></li> -->           
              </ul> 
              
             
            </nav> 
          
            <div class="news-home">
              <a class="kr-link"  href="<?php $this->options->siteUrl(); ?>"><span class="icon iconfont icon-shouye1"></span>
                <span class="back-text kr-tags">返回首页</span>
              </a>
            </div>
          
        </div>
        <!--e导航-->
        <div class="nav-main col-md-10">
          
           <div class="navlists">
          
             
            <!--一周热门-->
            <div id="div-1" class="mod-column column-nav resource" >
                <div class="mod-column-head">
                    <div class="mod-column-title">悦看视频</div>                 
                </div>
                <div class="big-mod-column-body">
                    <ul class="mod-list modnews row">            
                      
                      
<?php if ($this->have()): ?>
<?php while($this->next()): ?>
<li class="mod-list-item col-md-3 col-xs-6 col-sm-3"><div class="feaimg"><a href="<?php $this->permalink(); ?>"  class="block-fea scrollLoading" data-url="<?php echo stcdnimg($this->fields->img); ?>"><span class="vodlist_top"><span class="voddate voddate_tag"><?php $this->tags('', false, ''); ?></span></span></a><div class="kan-icon"><?php if ($this->fields->videourl): ?><i class="icon iconfont icon-icon-test15"></i><?php else : ?><?php echo ''.imgNums($this->content).'' ; ?><?php endif; ?></div></div><div class="modnews-content"><div class="modnews-title"><?php $this->title() ?></div><div class="a_cl"> <?php $email=$this->author->mail; $imgUrl = getGravatar($email);echo '<img src="'.$imgUrl.'" srcset="'.$imgUrl.'" class="avatar photo" height="22" width="22">'; ?> <?php $this->author->screenName(); ?> <span class="a_cl_r"><?php Postviews($this); ?> 阅读 </span></div></div></li>
<?php endwhile; ?>
<?php endif; ?>                     
                       
                    </ul>                  
                </div>
            </div>
            <!--一周热门-->
             


 
       </div>
     
        <!--导航结束-->
        </div>
    </div>
</div>

<?php $this->need('footer.php'); ?>