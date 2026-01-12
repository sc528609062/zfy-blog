<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<div class="col-md-3 widget-area <?php if (($this->is('post')) && ($this->options->postimask == '1') ): ?>post_sider<?php endif; ?>" id="secondary">
   
<?php if (_blog()&&!empty($this->options->sidebarBlock) && in_array('ShowAboutMe', $this->options->sidebarBlock)): ?>
<?php if ($this->is('post')): ?>
<section class="widget abautor g_m_i" data-uid="<?php $this->author->uid();?>"></section>
<?php endif; ?>
<?php endif; ?>

<?php if (!empty($this->options->sidebarBlock) && in_array('ShowAd', $this->options->sidebarBlock) && ($this->options->adimg)): ?>
<section class="widget_img"><div class="adTags"><i class="gg-icon"></i>
<?php $this->options->adimg(); ?></div>
</section>
<?php endif; ?> 

<!--快讯-->
<?php if (_blog() && !empty($this->options->sidebarBlock) && in_array('Showkx', $this->options->sidebarBlock)): ?>
<section class="widget g_m_i widget_kuaixun">
    
<div class="news-header"><img src="<?php echo stcdn($this->options->themeUrl); ?>/src/images/newicon.png" alt="" class="w-[39px] h-[18px] absolute top-[18px] left-4" ></div> 

<button data-cid="time" type="button" id="btn_time" class="right" aria-label="换一换热榜" >
<img class="boxtime rotate" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADEAAAAwCAMAAACPHmKLAAAAM1BMVEVHcEzxQEHwQEHwQUHvQEDwQUHwQELvQEDwQUHvQEDwQEHwQEHvQEDwQELwQEHwQELwQULQdpGMAAAAEHRSTlMAn2DvEL+AIN86kFAhcM+vUcBIKwAAAVJJREFUSMfVVduChCAIVVTwsmn//7W76kybilbzNrwFnLgfhfgi0UQPnMFHtf+JMj7oa3+Xivch0q39yeyDpFV+284KTgt4BzBbICLn8FDwYcgWq8JTtYRVaTmIrjbfdYd8hTBNKxkopjOu/MoMepwGFz8FsvVFsACQMplo64Q6o8y6PiVoZzmG8H06coGQbBFNjNasJ5MF6REBgnP9KgemsrV4tuErMat1e2do46mXueHh4s7yRvx/csPoB599dDMNuoGgzxHiBsI1WQnbfE0Rp8pdtHDR3HwN8fHI5BNAKbwbWSaPAIDoJUx4STNE8hJgD1cOuzg7njcLuMW5ATeM1FVR2dxGk+RQRyUT4kpbsQ/yDGcfMNzBol0Y7efE+2qwxZNRo5oDzq8BZvKgsB0KvVo3RrbVqyZHf3NxOR1GJXfjdQ7e1HlGD/r+ehNp8U3yC/YPH/jMrgU4AAAAAElFTkSuQmCC">
<span>换一换</span></button>
<ul class="widget-kx-list">
<div class='postpj'><div class='loading-5'><i></i><i></i></div></div>
<?php if (!$this->options->aboutme):?><div class="settips">请配置好页面缩略名选项</div><?php endif; ?> 
</ul>
</section>   
<?php endif; ?>

<!--快讯-->

<?php if (!empty($this->options->sidebarBlock) && in_array('ShowSidebarPosts', $this->options->sidebarBlock)): ?>
<section class="widget g_m_i">
<h4 class="widget-title"><strong class="ts"><i class="txt"><?php _e('热门文章'); ?></i></strong></h4>



<div class="list-rounded my-n2 hot-wen">
</div>
</section>
<?php endif; ?> 
  
<?php if (!empty($this->options->sidebarBlock) && in_array('ShowAds', $this->options->sidebarBlock) && ($this->options->adimgs)): ?>
<section class="widget_img"><div class="adTags"><i class="gg-icon"></i>
<?php $this->options->adimgs(); ?></div>
</section>
<?php endif; ?>  

  
<?php if (!empty($this->options->sidebarBlock) && in_array('Showtag', $this->options->sidebarBlock)): ?>
<section class="widget widget-hunter-topics g_m_i">
	<h4 class="widget-title"><strong class="ts"><i class="txt"><?php _e('标签TAG'); ?></i></strong></h4>
	<div class="items">	  
<?php $this->widget('Widget_Metas_Tag_Cloud', array('sort' => 'count', 'ignoreZeroCount' => true, 'desc' => true, 'limit' => 12))->to($tags); ?>
<?php if($tags->have()): ?>
<?php while ($tags->next()): ?>
<div class="item "><div class="wall-item"><a href="<?php $tags->permalink(); ?>" rel="tag"><h2> <i class="clr_orange">#</i> <?php $tags->name(); ?> </h2></a></div></div>
<?php endwhile; ?>
<?php else: ?>
<?php _e('没有任何标签'); ?>
<?php endif; ?>
</div>
<?php if ($this->options->sidetag): ?>
<div class="viewAll">
<a href="<?php $this->options->sidetag(); ?>"  class="btn btn-default"> 更多标签 <i class="ri-more-fill ri-lg"></i> </a>
 </div>
<?php endif; ?>
</section>
<?php endif; ?>
<?php if ($this->options->closelun == '1') :?>  
<?php if (!empty($this->options->sidebarBlock) && in_array('ShowlastComments', $this->options->sidebarBlock)): ?>
<section class="widget g_m_i">
<h4 class="widget-title"><strong class="ts"><i class="txt"><?php _e('热评文章'); ?></i></strong></h4>

<ul class="posts-widget hot-ping">

</ul>
</section>
<?php endif; ?>
 <?php endif; ?>    
   <?php if ($this->is('page')): ?><?php else: ?>
	<div class="widget-fixed">
    <?php if ($this->options->closelun == '1'):?>
    <?php if (!empty($this->options->sidebarBlock) && in_array('ShowRecentComments', $this->options->sidebarBlock)): ?>    
    <section class="widget wRecent g_m_i">
		<h4 class="widget-title"><strong class="ts"><i class="txt"><?php _e('最近回复'); ?></i></strong></h4>
        <?php $this->widget('Widget_Comments_Recent','ignoreAuthor=false&pageSize=4')->to($comments); ?>
        <?php while($comments->next()): ?>
				<div class="item"><div class="meta"><div class="meta-item"> <span class="comment-avatar"><i class="thumb " style="background-image:url(<?php echo getuserimg($comments->authorId); ?>)"></i></span> <?php $comments->author(); ?></div>				
				<span class="views"><?php $comments->dateWord(); ?></span>
				</div>
				<div class="comment-content"><a href="<?php $comments->permalink(); ?>" ><?php $cos=parseBiaoQing($comments->content); echo $cos;?></a></div>
				<h2> <i class="ri-message-3-line ri-lg"></i><a href="<?php $comments->permalink(); ?>" >  <?php $comments->title(); ?> </a> </h2>
				</div>
        <?php endwhile; ?> 
      <?php if ($this->options->liuynes): ?>
<div class="viewAll"><a href="<?php $this->options->liuynes(); ?>"  class="btn btn-default"> 我要留言 <i class="ri-more-fill ri-lg"></i> </a></div>
<?php endif; ?></section><?php endif; ?><?php endif; ?></div><?php endif; ?></div>
