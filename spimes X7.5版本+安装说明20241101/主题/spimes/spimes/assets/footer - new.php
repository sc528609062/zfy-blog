<div class="news-foot">
<?php if ($this->is('author')) : ?><?php else : ?>
<div class="container"><div class="part-news-foot">
  <h2 class="section-title">
  <i class="icon iconfont icon-icon-test25"></i>  <?php echo catename($this->options->footnew); ?>
	<?php if ($this->options->footnewmore): ?>
    <a href="<?php $this->options->footnewmore(); ?>" class="more" >查看更多</a>
	<?php endif; ?>
  </h2>
 <div class="section-content">    
<?php $this->widget('Widget_Archive@indextuis', 'pageSize=5&type=category', 'mid='.$this->options->footnew.'')->to($categoryPosts); ?>
<?php while($categoryPosts->next()): ?>
<div class="item">
            <div class="item-thumb"><div class="feaimg"><a href="<?php $categoryPosts->permalink(); ?>" class="block-fea scrollLoading" data-url="<?php echo stcdnimg($categoryPosts->fields->img); ?>" ></a></div></div>
            <div class="item-main">
            <div class="t_tl"><a href="<?php $categoryPosts->permalink(); ?>"><?php $categoryPosts->title(); ?></a></div>
                
            <div class="t_tls a_cl"> <span><img src="<?php echo getuserimg($categoryPosts->author->uid); ?>" srcset="<?php echo getuserimg($categoryPosts->author->uid); ?>" class="avatar photo" height="22" width="22"> <?php $categoryPosts->author->screenName(); ?></span>
                 <span><?php Postviews($categoryPosts); ?>阅读</span></div>
                </div>
            </div>  
<?php endwhile; ?>	
</div>
</div>
</div>
<?php endif; ?>
</div>

