<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php 

//  判断是否是点赞的 POST 请求
if (isset($_POST['agree'])) {
    //  判断 POST 请求中的 cid 是否是本篇文章的 cid
    if ($_POST['agree'] == $this->cid) {
        //  调用点赞函数，传入文章的 cid，然后通过 exit 输出点赞数量
        exit(agree($this->cid));
    }
    //  如果点赞的文章 cid 不是本篇文章的 cid 就输出 error 不再往下执行
    exit('error');
}

$this->need('header.php'); ?> 
	<div class="row">
	<div class="col-md-9 contpost" id="content">  
    <?php if ($this->fields->Copyrightnew =='0'):?><div class="originalImg"></div><?php endif; ?> 

      
      <?php if ($this->fields->videourl): ?>
      <?php $this->need('ext/danmu/post - dmplay.php'); ?>
      <?php endif; ?>          
		<article class="post <?php if ($this->fields->abcimg =='bable' && $this->fields->videourl == null ):?>bs_img<?php endif; ?>">
			<header class="entry-header page-header" >
			    <div class="">            
				<?php listdeng($this);?><span class="badge badge-hot"><?php $this->category(',', true, 'none'); ?></span>
				<?php get_spname($this->cid);?>
                </div>
				<h1 class="entry-title page-title"><?php $this->title(); ?></h1>
				<?php if(_blog()): ?>
				<div class="contimg">
				<div class="author-infos" data-id="<?php echo geipuid($this->cid); ?>"><img src="<?php echo getuserimg($this->author->uid); ?>" srcset="<?php echo getuserimg($this->author->uid); ?>" class="avatar" height="35" width="35">
				<div class="author-info-card"></div>
				</div>
				</div>
				<?php endif; ?>
				<div class="entry-meta contpost-meta">	
				    <?php if(_blog()): ?>
				    <a href="<?php $this->options->siteUrl(); ?><?php if ($this->options->rewrite==0): ?>index.php/<?php endif; ?>author/<?php $this->author->uid(); ?>">
				    <?php $this->author->screenName(); ?>
					</a><span class="separator">/</span>
					<?php endif; ?>
					<?php if($this->category == "toss"): ?>
					<?php _e( '最后更新：' ); ?><?php echo date('m-d' , $this->modified); ?>
					<?php else : ?>
					<time datetime="<?php $this->date('c'); ?>"><?php $this->date('m-d'); ?></time>
					<?php endif; ?>
					<span class="separator">/</span>
					<a href="<?php $this->permalink() ?>#comments"><?php $this->commentsNum('0 评论', '1 条评论', '%d 条评论'); ?></a>
					<span class="separator">/</span>
					<?php Postviews($this); ?> 阅读
					<span class="separator">/</span><?php zannum($this); ?> 赞
					<?php if($this->user->hasLogin()):?>
					<?php if($this->user->uid == $this->author->uid):?>
					<?php if ($this->options->gaoedit&&_blog()): ?>
					<i class="edit">
					<a href="<?php $this->options->siteUrl(); ?><?php $this->options->gaoedit(); ?>.html?tid=<?php $this->cid(); ?>" class="category-link"  target="_blank"><?php _e( '[编辑]' ); ?></a></i>
					<?php endif; ?>
					<?php endif;?>
					<?php endif;?>
					<?php if(_blog()): ?>
					<div class="post-intro"><?php echo reintro($this->author->uid); ?></div>
					<?php endif;?>
				</div>
				<div class="postArticle-meta">
				<i class="ri-time-line ri-lg"></i> 本文阅读 <?php echo art_time($this->cid); ?> 分钟
				<?php $this->need('assets/link-s.php'); ?>
				</div>
				<div class="border-theme"></div>
			</header>
			<!--文章上方广告-->
            <?php if ($this->options->txtadimg): ?><div class="shadimg con_ad-top"><div class="adTags"><i class="gg-icon"></i><?php $this->options->txtadimg(); ?></div></div><?php endif; ?>
            <!--文章上方广告-->
            <div <?php if ($this->options->tsmore == '1'):?>class="show_text"<?php endif; ?>>               
			<div class="entry-content clearfix">
			    
			    <!--ai-->
			    <?php if(get_aitag($this->cid)||uav(1)):?>
			    <div class="post_ai_box"><img src="<?php echo stcdn($this->options->themeUrl); ?>/src/images/aitag.png" class="post_ai_ico"><span class="post_ai_desc">
			        <?php echo get_aitag($this->cid);?>
			        </span>
			    	<div class="post_ai_meta"><span>摘要由智能技术生成</span></div>
			    	<?php if (uav(1)): ?>
			    	<div class="btn btn-orange-border aitag-btn" data-cid="<?php echo $this->cid;?>">智能总结</div>
			    	<?php endif; ?>
			    </div>
			    <?php endif; ?>
			    <!--ai-->
			    
			    
			    <?php _parseContent($this, $this->user->hasLogin()) ?>
              
                <?php if ($this->fields->Copyrightnew =='0'):?>
                <div class="Copyrightnew">原创文章，作者：<?php $this->author->screenName(); ?>，如若转载，请注明出处：<?php $this->permalink() ?></div>
                <?php elseif($this->fields->Copyrightnew =='2') : ?>
                <div class="Copyrightnew">本文经授权后发布，本文观点不代表立场<?php if ($this->fields->Copyurl):?>，文章出自：<?php $this->fields->Copyurl(); ?><?php endif; ?></div>
                <?php else : ?>
                <div class="Copyrightnew">本文来自投稿，不代表本站立场，如若转载，请注明出处：<input id="informationurl" value="<?php $this->permalink() ?>"></div>               
                <?php endif; ?> 
                
                <?php echo myadsee($this->author->uid); ?>
                
              </div>  
              <?php if ($this->options->tsmore == '1'):?>
              <div class="showall" >-- 展开阅读全文 --</div>
              <?php endif; ?>	
              </div>
            <?php $this->need('assets/sider - footer.php'); ?> 
			<footer class="entry-footer" id="footfix">
			    <div class="entry-bar-inner">
				<div class="post-tags">
				<?php if(  count($this->tags) == 0 ): ?>   
				<?php $this->category('', true, 'none'); ?>
                <?php else: ?>
				<?php $this->tags('', true, ''); ?>
				<?php endif; ?>
				</div>
				<div class="readlist">
			
				<?php if ($this->fields->img&&($this->options->oncover !='off')): ?><div class="read_outer"><a class="comiis_poster_a" href="javascript:;" title="生成封面"><i class="ri-image-line ri-lg"></i> <?php _e( '封面' ); ?></a></div><?php endif; ?>
				</div>
				</div>
			</footer>
			</article>
<!--下一篇上一篇--> 
<div class="entry-page">
<?php thePrev($this); ?>
<?php theNext($this); ?> 
</div>
<!--下一篇上一篇-->
	<?php if ($this->options->txtaddown): ?><div class="shadbottom"><div class="adTags"><i class="gg-icon"></i><?php $this->options->txtaddown(); ?></div></div><?php endif; ?>  
	<!--相关文章s-->  
    <?php $this->need('assets/post - more.php'); ?> 
	<!--相关文章e-->
	<?php $this->need('comments.php'); ?>
	</div>
	<?php $this->need('sidebar.php'); ?>		
	</div>
<?php $this->need('footer.php'); ?>