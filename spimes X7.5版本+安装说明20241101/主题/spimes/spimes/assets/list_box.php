<?php if (($this->fields->img) && ($this->fields->abcimg == 'mable')): ?>
<article class="post-list contt featured">
<div class="featured-container">
                    <header class="entry-header featitle">
                        <span class="entry-title"><a href="<?php $this->permalink(); ?>"><?php listdeng($this);?><?php if(timeZone($this->date->timeStamp)) echo '<span class="badge arc_v2">最新</span>'; ?><?php $this->sticky(); $this->title() ?></a></span>
                    </header>
                    
                    
					<div class="entry-meta fea-meta">
					<img src="<?php echo getuserimg($this->author->uid); ?>" srcset="<?php echo getuserimg($this->author->uid); ?>" class="avatar avatar-140 photo" height="25" width="25"> <?php $this->author->screenName(); ?>
					<div class="author-info-card"><!--作者卡片--></div>
					
					<span class="separator">/</span>
                        <?php $this->category(',', true, 'none'); ?> <span class="separator">/</span>
                       <time datetime="<?php $this->date('Y-m-d'); ?>"><?php echo formatTime($this->created); ?></time>
                        <span class="separator">/</span>
                        <?php Postviews($this); ?> 阅读<span class="separator">/</span><?php list_zannum($this); ?>
                    </div>
                        
                        
<div class="rowimg">
            <div class="col-4">
                <div class="feaimg">
                 <?php if (showThumbnail($this,0)): ?>  <!--如果有自定义img-->
                 <a class="feaimg-content scrollLoading" title="<?php $this->title() ?>" href="<?php $this->permalink() ?>" data-url="<?php echo stcdnimg(showThumbnail($this,0)); ?>"><i class="mask"></i></a>
				 <?php else: ?>
                 <a class="feaimg-content" title="<?php $this->title() ?>" href="<?php $this->permalink() ?>" style="background-image: url(<?php $this->options->themeUrl(); ?>images/thumbs/other_thumbnail.png);" ></a>
				 <?php endif; ?>
                </div>
</div>
<div class="col-4">
                <div class="feaimg">
				 <?php if (showThumbnail($this,1)): ?>  <!--如果有自定义img-->
                 <a class="feaimg-content scrollLoading" title="<?php $this->title() ?>" href="<?php $this->permalink() ?>" data-url="<?php echo stcdnimg(showThumbnail($this,1)); ?>" ><i class="mask"></i></a>
                 <?php else: ?>
                 <a class="feaimg-content" title="<?php $this->title() ?>" href="<?php $this->permalink() ?>" style="background-image: url(<?php $this->options->themeUrl(); ?>images/thumbs/other_thumbnail.png);" ></a>
				 <?php endif; ?>                    
                </div>
</div>
<div class="col-4">
                <div class="feaimg">
                  <?php if (showThumbnail($this,2)): ?>  <!--如果有自定义img-->
                 <a class="feaimg-content scrollLoading" title="<?php $this->title() ?>" href="<?php $this->permalink() ?>" data-url="<?php echo stcdnimg(showThumbnail($this,2)); ?>" ><i class="mask"></i></a>
				 
                 <?php else: ?>
                 <a class="feaimg-content" title="<?php $this->title() ?>" href="<?php $this->permalink() ?>" style="background-image: url(<?php $this->options->themeUrl(); ?>images/thumbs/other_thumbnail.png);" ></a>
				 <?php endif; ?>
                </div>
            </div>
        </div>
<div class="entry-summary feasum"><p><?php $this->excerpt(80, '...');?></p></div>

</div>
</article>
<?php elseif(($this->fields->img) && ($this->fields->abcimg == 'bable')): ?>
<article class="post-list contt featured" id="post_<?php $this->cid(); ?>">
       <div class="featured-container">
                    <header class="entry-header featitle">
                        <span class="entry-title"><a href="<?php $this->permalink(); ?>"><?php listdeng($this);?><?php if(timeZone($this->date->timeStamp)) echo '<span class="badge arc_v2">最新</span>'; ?><?php $this->sticky(); $this->title() ?></a></span>
                    </header>
					 <div class="entry-meta fea-meta">
					   <div class="author-infos" data-id="<?php echo geipuid($this->cid); ?>"><img src="<?php echo getuserimg($this->author->uid); ?>" srcset="<?php echo getuserimg($this->author->uid); ?>" class="avatar avatar-140 photo" height="25" width="25">
					   <?php $this->author->screenName(); ?>
					   <div class="author-info-card">
					   <!--作者卡片-->
                       <!--作者卡片-->
					   </div>
					    </div>
					    
					    <span class="separator">/</span>				  
                        <?php $this->category(',', true, 'none'); ?>
                        <span class="separator">/</span>
                        <time datetime="<?php $this->date('Y-m-d'); ?>"><?php echo formatTime($this->created); ?></time>
                        <span class="separator">/</span>
                        <?php Postviews($this); ?> 阅读<span class="separator">/</span><?php list_zannum($this); ?></div>
        <div class="feaimg fea-21x9">
            <a id="post_a_<?php $this->cid(); ?>" class="feaimg-content scrollLoading" title="<?php $this->title(); ?>" href="<?php $this->permalink(); ?>" data-url="<?php echo stcdnimg($this->fields->bimg); ?>"><i class="mask"></i></a>
		
        </div> <div class="entry-summary feasum"><p><?php $this->excerpt(80, '...');?></p></div> 
               </div>
         </article>
<?php else: ?>


<?php if ($this->fields->img): ?>
<article class="post-list contt blockimg " id="post_<?php $this->cid(); ?>">
                <div class="entry-container"><span class="laid_title_l"></span>
                    <div class="block-image feaimg">
                    <a id="post_a_<?php $this->cid(); ?>" class="block-fea scrollLoading" data-url="<?php echo stcdnimg($this->fields->img); ?>" href="<?php $this->permalink(); ?>" title="<?php $this->title(); ?>" ><i class="mask"></i>
                    <span class="vodlist_top"><em class="voddate voddate_year"><?php $this->category(',', false, 'none'); ?></em></span>
                    </a>
                    </div>
                    <header class="entry-header"><span class="entry-title"><a href="<?php $this->permalink() ?>"><?php listdeng($this);?><?php if(timeZone($this->date->timeStamp)) echo '<span class="badge arc_v2">最新</span>'; ?><?php $this->sticky(); $this->title() ?></a></span></header>
                    <div class="entry-summary ss"><p><?php $this->excerpt(80, '...');?></p></div>
                    <div class="entry-meta">
					    <!--作者-->
                        <div class="author-infos" data-id="<?php echo geipuid($this->cid); ?>"><img src="<?php echo getuserimg($this->author->uid); ?>" srcset="<?php echo getuserimg($this->author->uid); ?>" class="avatar avatar-140 photo" height="25" width="25" ><?php $this->author->screenName(); ?>
					    <div class="author-info-card"></div></div>
					    <!--作者-->
					    <span class="separator">/</span><?php $this->category(',', true, 'none'); ?><span class="separator">/</span><time datetime="<?php $this->date('Y-m-d'); ?>"><?php echo formatTime($this->created); ?></time>
                        <span class="separator">/</span>
                        <?php Postviews($this); ?> 阅读 <p class="meta-zan _ts"><?php list_zannum($this); ?></p></div> 
                  
                </div>
  
</article>
<?php else: ?>
<article class="post-list contt blockimg">
 
                <?php  $i=$this->options->openimg; if($i!=0){$i=-1;} if (showThumbnail($this,$i)): ?>
                 <div class="entry-container"><span class="laid_title_l"></span>
                    <div class="block-image feaimg">
                    <a class="block-fea scrollLoading" data-url="<?php echo stcdnimg(showThumbnail($this,0)); ?>" href="<?php $this->permalink(); ?>" title="<?php $this->title(); ?>" ><i class="mask"></i>
                    <span class="vodlist_top"><em class="voddate voddate_year"><?php $this->category(',', false, 'none'); ?></em></span>  
                    </a>
                    </div>
                    <header class="entry-header"><span class="entry-title"><a href="<?php $this->permalink() ?>"><?php listdeng($this);?><?php if(timeZone($this->date->timeStamp)) echo '<span class="badge arc_v2">最新</span>'; ?><?php $this->sticky(); $this->title() ?></a></span></header>
                    <div class="entry-summary ss"><p><?php $this->excerpt(80, '...');?></p></div>
                    <div class="entry-meta">
					    <div class="author-infos" data-id="<?php echo geipuid($this->cid); ?>"><img src="<?php echo getuserimg($this->author->uid); ?>" srcset="<?php echo getuserimg($this->author->uid); ?>" class="avatar avatar-140 photo" height="25" width="25"><?php $this->author->screenName(); ?><div class="author-info-card"><!--作者卡片--></div></div><span class="separator">/</span><?php $this->category(',', true, 'none'); ?><span class="separator">/</span><time datetime="<?php $this->date('Y-m-d'); ?>"><?php echo formatTime($this->created); ?></time>
                        <span class="separator">/</span>
                        <?php Postviews($this); ?> 阅读<span class="separator">/</span><?php list_zannum($this); ?></div>
                </div>
                <?php else: ?>  
                <div class="entry-container" style="padding-left: 0px  !important;">                   
                    <header class="entry-header"><span class="entry-title"><a href="<?php $this->permalink() ?>"><?php listdeng($this);?><?php if(timeZone($this->date->timeStamp)) echo '<span class="badge arc_v2">最新</span>'; ?><?php $this->sticky(); $this->title() ?></a></span></header>
                    <div class="entry-summary ss"><p><?php $this->excerpt(80, '...');?></p></div>
                    <div class="entry-meta">
					    <div class="author-infos" data-id="<?php echo geipuid($this->cid); ?>"><img src="<?php echo getuserimg($this->author->uid); ?>" srcset="<?php echo getuserimg($this->author->uid); ?>" class="avatar avatar-140 photo" height="25" width="25"><?php $this->author->screenName(); ?><div class="author-info-card"><!--作者卡片--></div></div><span class="separator">/</span><?php $this->category(',', true, 'none'); ?><span class="separator">/</span><time datetime="<?php $this->date('Y-m-d'); ?>"><?php echo formatTime($this->created); ?></time>
                        <span class="separator">/</span>
                        <?php Postviews($this); ?> 阅读</div>
                </div>
               <?php endif; ?>
</article>
<?php endif; ?>


<?php endif; ?>