<?php $this->related(4)->to($relatedPosts); ?>						
<div class="part-mor">
  <h3 class="section-title">
  <?php if ($relatedPosts->have()): ?>
    <span><i class="ri-terminal-window-line ri-lg"></i> 相关推荐 </span>    
  </h3>
  <div class="section-cont">
    <div class="items">	  
      <?php while ($relatedPosts->next()): ?>
      <?php if ($relatedPosts->fields->img): ?>
      <div class="item"><div class="hunter-item"><a href="<?php $relatedPosts->permalink(); ?>" ><div class="hunter-thumb"><i class="thumb " style="background-image:url(<?php echo stcdnimg($relatedPosts->fields->img); ?>)"><i class="mask"></i></i></div><h2><?php $relatedPosts->title(); ?></h2><!--<h4><span class="hunter-tag btn btn-default"><?php $relatedPosts->category(',', false, 'none'); ?></span> <span class="hunter-product"><?php Postviews($relatedPosts); ?>阅读</span></h4>--></a></div></div>
	  <?php else: ?>
      <div class="item"><div class="hunter-item"><a href="<?php $relatedPosts->permalink(); ?>" ><div class="hunter-thumb"><i class="thumb " style="background-image:url(<?php echo stcdnimg(showThumbnail($relatedPosts,0)); ?>)"></i></div><h2><?php $relatedPosts->title(); ?></h2><!--<h4><span class="hunter-tag btn btn-default"><?php $relatedPosts->category(',', false, 'none'); ?></span> <span class="hunter-product"><?php Postviews($relatedPosts); ?>阅读</span></h4>--></a></div></div>
      <?php endif; ?>	      
      <?php endwhile; ?>
      <?php else: ?>
 <span><i class="ri-shuffle-line ri-lg"></i> 随机推荐 </span>    
 </h3>
  <div class="section-cont">
    <div class="items">	  
	  <?php getRandomPosts(4);?>  
	  <?php endif; ?>		  
	  </div>
  </div>
</div>