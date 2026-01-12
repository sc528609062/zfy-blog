<?php
/**
* 会员列表
*
* @package custom
*/
if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
<div class="row author-page">
    <div class="col-md-9 archive-content video-index"> 	
	<header>
    <div class="widget-list-title"><i class="ri-account-box-line ri-lg"></i> <span><?php if($this->_currentPage>1) echo '第 '.$this->_currentPage.' 页 - '; ?><?php $this->archiveTitle(array('category'  =>  _t('%s ')), '', ''); ?></span></div>
    </header>  
	<div class="row" id="content">
       
    <?php Typecho_Widget::widget('Widget_Users_Admin', 'pageSize=10')->to($users); ?>
    <div class="ner-content"> 
    <?php while($users->next()): ?>
    <article class="nercontt"> 
    <div class="ner-container">    
    <a class="avatar" href="<?php $users->permalink(); ?>"><img src="<?php echo getuserimg($users->uid); ?>" srcset="<?php echo getuserimg($users->uid); ?>" height="60" width="60"></a>
    </div>
	<div class="ner-info">
	<p class="ner-info-title">
    <a href="<?php $users->permalink(); ?>" title="<?php $users->screenName(); ?>" class="title-content" ><?php $users->screenName(); ?></a>
    </p>
	<div class="info-num">
            <div class="work">
                <span>文章：<?php echo allpostnum($users->uid);?> 篇</span>                
                <i></i>
            </div>
            <div class="work">
                <span>评论：<?php echo commentnum($users->uid);?> 次</span>                
            </div>
      </div>
	  <div class="signature">
            <p><?php echo reintro($users->uid); ?></p>
        </div>
	</div>
   <div class="work-show">
   <ul class="work-con-box">	
	<?php echo nerPosts($users->uid);?>
   </ul>
   </div>    
   </article>
<?php endwhile; ?>
      </div>
      <ol class="page-navigator"><?php $users->pageNav('<', '>', 1, '...', array('wrapTag' => 'ol', 'wrapClass' => 'page-navigator', 'itemTag' => 'li', 'textTag' => 'span', 'currentClass' => 'current', 'prevClass' => 'prev', 'nextClass' => 'next',)); ?></ol>

<script>$(function(){$('.cck').hide(); });</script>
<script>$(function(){$('.next').hide(); });</script>

</div>
</div><!-- end #main -->    
<?php $this->need('sidebar.php'); ?>
</div>
<?php $this->need('footer.php'); ?>
