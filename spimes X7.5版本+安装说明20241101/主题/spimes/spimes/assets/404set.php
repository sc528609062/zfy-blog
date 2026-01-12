
	<div class="row">
		<div class="col-md-12" id="content">
			<div class="error-page">
				<h1 class="not-found-title"><?php _e( '404' ); ?></h1>
				<a class="not-found-back" href="<?php $this->options->siteUrl(); ?>"><?php _e( '返回主页' ); ?></a>
			    <p><?php _e('你想查看的页面已被转移或删除了, 要不搜索看看: '); ?></p>
	        <form id="search" class="search-form" method="post" action="<?php $this->options->siteUrl(); ?>" role="search">
	            <span class="search-box clearfix">
	                <input type="text" id="input" class="input" name="s" required="true" placeholder="" maxlength="30" autocomplete="off">
	                <button type="submit" class="search-submit"><i class="icon iconfont icon-sousuo"></i></button>
	            </span>
	        </form>
			</div>
            <ul id="tag_cloud" class="spimes-tags">
               <?php $this->widget('Widget_Metas_Tag_Cloud', array('sort' => 'count', 'ignoreZeroCount' => true, 'desc' => true, 'limit' => 65))->to($taglist); ?><?php while($taglist->next()): ?>
                <li><a href="<?php $taglist->permalink(); ?>" title="<?php $taglist->name(); ?>">#<?php $taglist->name(); ?></a></li>
                <?php endwhile; ?>
            </ul>
		</div>
	</div>