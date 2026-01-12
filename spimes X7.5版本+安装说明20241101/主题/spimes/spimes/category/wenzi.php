<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
<div class="row author-page">
    <div class="col-md-9 archive-content video-index">	
	<header><div class="widget-list-title"><i class="ri-calendar-todo-line ri-lg"></i> <span><?php if($this->_currentPage>1) echo '第 '.$this->_currentPage.' 页 - '; ?><?php $this->archiveTitle(array('category'  =>  _t('%s ')), '', ''); ?></span></div></header>   
	<div class="row" id="content"> 
	
	<?php if ($this->have()): ?>
    <?php while($this->next()): ?>     
	<div class="posts_list p_wen">
	    
	<div class="post-hd"> 
	<div class="datashow-item d-blue"> <div class="datashow--count"><?php $this->commentsNum('0', '1', '%d'); ?></div> <span class="datashow--label">评论</span> </div> 
	</div> 
	<div class="post-hd"> 
	<div class="datashow-item"> <div class="datashow--count"><?php Postviews($this); ?></div> <span class="datashow--label">浏览</span> </div> 
	</div> 
	
	<div class="post-bd">
	    <h2 class="post__title"><a href="<?php $this->permalink(); ?>"><?php $this->title() ?></a></h2> 
	<div class="post__description"> 
	<p><?php $this->excerpt(80, '...');?></p>
	</div> </div> </div>
	<?php endwhile; ?>
	<?php endif; ?>

<script>$(function(){$('.cck').hide(); });</script>
      <?php $this->pageNav('<', '>', 1, '...', array('wrapTag' => 'ol', 'wrapClass' => 'page-navigator', 'itemTag' => 'li', 'textTag' => 'span', 'currentClass' => 'current', 'prevClass' => 'prev', 'nextClass' => 'next',)); ?>
   </div>		
    </div><!-- end #main -->    
    <?php $this->need('sidebar.php'); ?>
</div>
<?php $this->need('footer.php'); ?>
