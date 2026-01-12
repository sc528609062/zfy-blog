<?php
/**
* 热门文章
*
* @package custom
*/
if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>

<div class="row">	
		<div class="col-md-9 contpost">		
		        <header>
                    <div class="widget-list-title"><i class="ri-calendar-todo-line ri-lg"></i> <span><?php if($this->_currentPage>1) echo '第 '.$this->_currentPage.' 页 - '; ?><?php $this->archiveTitle(array('category'  =>  _t('%s ')), '', ''); ?></span></div>
                </header>		
				<div class="row" id="content">
                
<?php $this->widget('Widget_Post_hot@hot', 'pageSize=6')->to($hot); ?>
<?php while($hot->next()): ?>
<article class="post-list contt blockimg " id="post_<?php $hot->cid(); ?>">
                <div class="entry-container"><span class="laid_title_l"></span>
                    <div class="block-image feaimg">
                    <a class="block-fea scrollLoading" data-url="<?php echo stcdnimg(showThumbnail($hot,0)); ?>" href="<?php $hot->permalink(); ?>" title="<?php $hot->title(); ?>" ><i class="mask"></i>
                    <span class="vodlist_top"><em class="voddate voddate_year"><?php $hot->category(',', false, 'none'); ?></em></span>
                    </a>
                    </div>
                    <header class="entry-header"><span class="entry-title"><a href="<?php $hot->permalink() ?>"><?php listdeng($hot);?><?php if(timeZone($hot->date->timeStamp)) echo '<span class="badge arc_v2">最新</span>'; ?><?php $hot->sticky(); $hot->title(31, '...') ?></a></span></header>
                    <div class="entry-summary ss"><p><?php if ($hot->fields->tdesc): ?><?php $hot->fields->tdesc(); ?><?php else : ?><?php $hot->excerpt(80, '...');?><?php endif; ?></p></div>
                    <div class="entry-meta">
					    <div class="author-infos" data-id="<?php echo geipuid($hot->cid); ?>"><img src="<?php echo getuserimg($hot->author->uid); ?>" srcset="<?php echo getuserimg($hot->author->uid); ?>" class="avatar avatar-140 photo" height="25" width="25" ><?php $hot->author->screenName(); ?>
					   <div class="author-info-card"><!--作者卡片--></div>
					   </div>
					    
					   <span class="separator">/</span><?php $hot->category(',', true, 'none'); ?><span class="separator">/</span><time datetime="<?php $hot->date('Y-m-d'); ?>"><?php echo formatTime($hot->created); ?></time>
                       <span class="separator">/</span>
                       <?php Postviews($hot); ?> 阅读 <p class="meta-zan"><?php list_zannum($hot); ?></p></div> 
                  
                </div><?php if ($this->options->oncosmore == '1'):?><?php cosmore($hot->content); ?><?php endif; ?>
  
</article>
<?php endwhile; ?>
<ol class="page-navigator"><?php $hot->pageNav('<', '>', 1, '...', array('wrapTag' => 'ol', 'wrapClass' => 'page-navigator', 'itemTag' => 'li', 'textTag' => 'span', 'currentClass' => 'current', 'prevClass' => 'prev', 'nextClass' => 'next',)); ?></ol>
                
 
                <script>$(function(){$('.cck').hide(); });</script>
                <script>$(function(){$('.next').hide(); });</script>
				</div>				
		 </div>
		<?php $this->need('sidebar.php'); ?>
	</div>
<?php $this->need('footer.php'); ?>