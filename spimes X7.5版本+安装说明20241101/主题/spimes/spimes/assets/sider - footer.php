<footer class="sider-footer">
		<div class="pos-r clearfix">
		<div class="share-box ">

        <div class="weixin mouh" id="share-weixin" title="微信分享">
			<i class="ri-wechat-line"></i>
			<div class="wx-t-x pos-a hide box" id="weixin-box">
		        <img class="qrcode fl bor-3" src="<?php $this->options->themeUrl("poster/api.php"); ?>?url=<?php $this->permalink() ?>">
		    </div>
		</div>
        <span class="dot"></span>
        <!--<a href=""  class="qzone">
		<i class="icon iconfont icon-QQkongjian"></i>
		</a>
        <span class="dot"></span>-->
        <a href="javascript:Share('tqq')" class="qq" title="QQ分享">
		<i class="ri-qq-line"></i>
		</a>
        <span class="dot"></span>
        <a href="javascript:Share('sina')" class="weibo" title="微博分享">
		<i class="ri-weibo-line"></i>
		</a>
		
        <span class="dot"></span>
        <a href="javascript:;" class="copy-share-item" title="复制链接">
		<i class="ri-file-copy-line"></i>
		</a> 
		
		<span class="dot"></span>
		<!--点赞s-->
		<?php $agree = $this->hidden?array('agree' => 0, 'recording' => true):agreeNum($this->cid); ?> 
        <span title="点赞" <?php echo $agree['recording']?'class="izan zan_on"':'class="izan"'; ?> id="agree-btn" data-cid="<?php echo $this->cid; ?>" data-url="<?php $this->permalink(); ?>">
		<i class="ri-heart-line"></i>
		<p class="agree-num"><?php echo $agree['agree']; ?></p>
		</span> 
		<!--点赞e-->
	    </div>
		</div>
</footer>  
<?php if(_blog()): ?>
<div class="sider-footer-user post_no">
<div class="author-infos" data-id="<?php echo geipuid($this->cid); ?>"><img src="<?php echo getuserimg($this->author->uid); ?>" srcset="<?php echo getuserimg($this->author->uid); ?>" class="avatar" height="30" width="30"><div class="author-info-card"></div></div>
</div>
<?php endif; ?>