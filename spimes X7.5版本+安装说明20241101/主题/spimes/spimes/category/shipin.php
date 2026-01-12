<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
<div class="row author-page">
    <div class="col-md-9 archive-content video-index">	
	<header><div class="widget-list-title"><i class="ri-calendar-todo-line ri-lg"></i> <span><?php if($this->_currentPage>1) echo '第 '.$this->_currentPage.' 页 - '; ?><?php $this->archiveTitle(array('category'  =>  _t('%s ')), '', ''); ?></span></div></header>   
	<div class="row" id="content">
    <div class="video-content">
    <?php if ($this->have()): ?>
    <?php while($this->next()): ?>
	<div id="video-content">
	<article class="post-list video-list feaimg">
	<?php if ($this->fields->img): ?>    
    <a class="video-img scrollLoading" href="<?php $this->permalink(); ?>" data-url="<?php echo stcdnimg($this->fields->img); ?>">
    <?php else: ?> 
    <a class="video-img scrollLoading" href="<?php $this->permalink(); ?>" data-url="<?php echo stcdnimg(showThumbnail($this,0)); ?>">
    <?php endif; ?>
	<div class="video-icon"><i class="ri-play-circle-line"></i></div>
	<i class="mask"></i>
    <header class="video-header">    
    <span class="video-title"><?php listdeng($this);?><?php if(timeZone($this->date->timeStamp)) echo '<span class="badge arc_v2">最新</span>'; ?><?php $this->title() ?></span>
    </header></a>
	</article>
	<div class="video-user">
	<div class="author-infos" data-id="<?php echo geipuid($this->cid); ?>"><?php $email=$this->author->mail; $imgUrl = getGravatar($email);echo '<img src="'.$imgUrl.'" srcset="'.$imgUrl.'" class="avatar photo" height="25" width="25">'; ?><?php $this->author->screenName(); ?>
	<?php if(_blog()): ?>
	<div class="author-info-card"></div>
	<?php endif; ?>
	</div>
    <div class="entry-meta video-meta"><?php if(_blog()): ?><?php $this->commentsNum('0 评论', '1 条评论', '%d 条评论'); ?><span class="separator">/</span><?php endif; ?><time datetime="<?php $this->date('c'); ?>"><?php $this->date('m-d'); ?></time><span class="separator">/</span><?php Postviews($this); ?> 阅读</div>
	</div>
	</div>
	<?php endwhile; ?>
	<?php endif; ?>
    </div>
    <?php $this->pageNav('<', '>', 1, '...', array('wrapTag' => 'ol', 'wrapClass' => 'page-navigator', 'itemTag' => 'li', 'textTag' => 'span', 'currentClass' => 'current', 'prevClass' => 'prev', 'nextClass' => 'nexts',)); ?>
    </div>		
    </div><!-- end #main -->    
    <?php $this->need('sidebar.php'); ?>
</div>
<?php $this->need('footer.php'); ?>
