<?php if ($this->fields->img): ?>
<article class="post-list contt blockimg " id="post_<?php $this->cid(); ?>">
                <div class="entry-container"><span class="laid_title_l"></span>
                    <div class="block-image feaimg">
                    <a class="block-fea scrollLoading" data-url="<?php echo stcdnimg($this->fields->img); ?>" href="<?php $this->permalink(); ?>" title="<?php $this->title(); ?>" ><i class="mask"></i>
                    <span class="vodlist_top"><em class="voddate voddate_year"><?php $this->category(',', false, 'none'); ?></em></span>
                    </a>
					
                    </div>
                    <header class="entry-header"><span class="entry-title"><a href="<?php $this->permalink() ?>"><?php listdeng($this);?><?php if(timeZone($this->date->timeStamp)) echo '<span class="badge arc_v2">最新</span>'; ?><?php $this->sticky(); $this->title() ?></a></span></header>
                    <div class="entry-summary ss"><p><?php $this->excerpt(80, '...');?></p></div>
                    <div class="entry-meta">
					    <?php $this->category(',', true, 'none'); ?><span class="separator">/</span><time datetime="<?php $this->date('Y-m-d'); ?>"><?php echo formatTime($this->created); ?></time>
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
					  <?php $this->category(',', true, 'none'); ?><span class="separator">/</span><time datetime="<?php $this->date('Y-m-d'); ?>"><?php echo formatTime($this->created); ?></time>
                        <span class="separator">/</span>
                        <?php Postviews($this); ?> 阅读<span class="separator">/</span><?php list_zannum($this); ?></div>
                </div>
                <?php else: ?>  
                <div class="entry-container" style="padding-left: 0px  !important;">                   
                    <header class="entry-header"><span class="entry-title"><a href="<?php $this->permalink() ?>"><?php listdeng($this);?><?php if(timeZone($this->date->timeStamp)) echo '<span class="badge arc_v2">最新</span>'; ?><?php $this->sticky(); $this->title() ?></a></span></header>
                    <div class="entry-summary ss"><p><?php $this->excerpt(80, '...');?></p></div>
                    <div class="entry-meta">
					    <?php $this->category(',', true, 'none'); ?><span class="separator">/</span><time datetime="<?php $this->date('Y-m-d'); ?>"><?php echo formatTime($this->created); ?></time>
                        <span class="separator">/</span>
                        <?php Postviews($this); ?> 阅读</div>
                </div>
               <?php endif; ?>
</article>
<?php endif; ?>
