<?php if (($this->options->closelun == '1') && ($this->fields->pinglun !='0')):?>
<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>

<?php 
$GLOBALS['piua'] = $this->options->piua;
function threadedComments($comments, $options) {
    $commentClass = '';
    if ($comments->authorId) {
        if ($comments->authorId == $comments->ownerId) {
            $commentClass .= ' comment-by-author';
        } else {
            $commentClass .= ' comment-by-user';
        }
    } 
    $commentLevelClass = $comments->levels > 0 ? ' comment-child' : ' comment-parent';
?>
 
<li id="li-<?php $comments->theId(); ?>" class="comment<?php 
if ($comments->levels > 0) {
    echo ' comment-child';
    $comments->levelsAlt(' comment-level-odd', ' comment-level-even');
} else {
    echo ' comment-parent';
}
$comments->alt(' comment-odd', ' comment-even');
echo $commentClass;
?>">
    <div id="<?php $comments->theId(); ?>" class="comment-body">
        <div class="comment-author">
            	
            <?php if ($comments->authorId != '0'):  ?>
			<a href="//<?php echo $_SERVER['HTTP_HOST']; ?>/index.php/author/<?php $comments->authorId(); ?>"><img src="<?php echo getuserimg($comments->authorId); ?>" width="40px" height="40px" class="avatar"><?php CommentAuthor($comments); ?></a><img class="v_ci" src="/usr/themes/spimes/src/images/authen.svg" title="认证用户">
			<?php else: ?>
			<img src="<?php echo getuserimg($comments->authorId); ?>" width="40px" height="40px" class="avatar"><?php CommentAuthor($comments); ?>
			<?php endif; ?>
           
            <span class="says"><?php _e(':'); ?></span>
        </div>
        <div class="comment-meta">
            <a href="<?php $comments->permalink(); ?>"><?php $comments->date('Y-m-d'); ?></a> <?php if ($GLOBALS['piua'] == '1'):?><span class="comment-ua">    <?php getOs($comments->agent); ?>    <?php getBrowser($comments->agent); ?></span><?php endif; ?>
        </div>
        <p><?php $parentMail = get_comment_at($comments->coid)?><?php echo $parentMail;?></p>
       <?php $cos=parseBiaoQing($comments->content); echo $cos; ?>
        <div class="reply">
            <span class="comment-reply-link"><?php $comments->reply(); ?></span>
        </div>
    </div>
<?php if ($comments->children) { ?>
    <div class="comment-children">
        <?php $comments->threadedComments($options); ?>
    </div>
<?php } ?>
</li>
<?php } ?>

<div id="comments">
   
    <?php $this->comments()->to($comments); ?>
   
    <div id="<?php $this->respondId(); ?>" class="respond">
        
        <div class="cancel-comment-reply">
        <?php $comments->cancelReply(); ?>
        </div>

        <h3 id="response" class="comment-reply-title section-title"><span><i class="ri-message-3-line ri-lg"></i> <?php _e('发表评论'); ?></span></h3>
        <form id="new_comment_form" method="post" action="<?php $this->commentUrl() ?>" _lpchecked="1">
        <!--遮罩-->
        <?php if (_blog()&&$this->options->pingopen == '1'&&(!$this->user->hasLogin()) ):?>
        <div class="comment-overlay"> <div class="comment-overlay-login"><p>您必须<a href="<?php if ($this->options->denglu): ?><?php $this->options->siteUrl(); ?><?php if ($this->options->rewrite==0): ?>index.php/<?php endif; ?><?php $this->options->denglu(); ?>.html<?php else: ?><?php $this->options->adminUrl('login.php'); ?><?php endif; ?>">登录</a>才能评论哦~</p></div> </div>
        <?php endif; ?>
        <!--遮罩-->
		<div class="comment_triggered" style="display: block;">
            <div class="input_body inp">
			 <?php if($this->user->hasLogin()): ?>
					<div class="hasLogin">
						<img src="<?php echo getuserimg($this->author->uid); ?>" width="22px" height="22px" class="avatar hasLogin-author" ><?php $this->user->screenName(); ?>. <a href="<?php $this->options->logoutUrl(); ?>" title="Logout">退出 &raquo;</a>
					</div>	
						<?php else: ?>
               <?php if($this->remember('author',true) != "" && $this->remember('mail',true) != "") : ?>
               <div class="hasLogin comm_on" id="comm_on">
						<?php $this->remember('author'); ?>. <a href="javascript:;" onclick="bian()" title="Logout" >编辑资料? &raquo;</a>
<script>function bian()
{ var oBox = document.getElementById("comm_off"); var oBox1 = document.getElementById("comm_on"); oBox.style.display= "block"; oBox1.style.display= "none";}</script>
			   </div>	
			   
               <ul class="ident" id="comm_off">
                    <li>
                        <input type="text" name="author" placeholder="昵称*" value="<?php $this->remember('author'); ?>" <?php if (_blog()&&$this->options->pingopen == '1'&&(!$this->user->hasLogin()) ):?>disabled="disabled"<?php endif ; ?> >
                    </li>
                    <li>
                        <input type="mail" name="mail" placeholder="邮件*" value="<?php $this->remember('mail'); ?>" <?php if (_blog()&&$this->options->pingopen == '1'&&(!$this->user->hasLogin()) ):?>disabled="disabled"<?php endif ; ?> >
                    </li>                   
                </ul>
               <?php else : ?>
                <ul class="ident">
                    <li>
                        <input type="text" name="author" placeholder="昵称*" <?php if (_blog()&&$this->options->pingopen == '1'&&(!$this->user->hasLogin()) ):?>disabled="disabled"<?php endif ; ?> >
                    </li>
                    <li>
                        <input type="mail" name="mail" placeholder="邮件*" <?php if (_blog()&&$this->options->pingopen == '1'&&(!$this->user->hasLogin()) ):?>disabled="disabled"<?php endif ; ?> >
                    </li>                   
                </ul>
             <?php endif; ?><?php endif ; ?>  
			                                       
            </div>
        </div>    
		<div class="new_comment"><textarea name="text" rows="3" class="textarea_box OwO-textarea" style="height: auto;" placeholder="你的每一个好想法，都让人惊喜" required <?php if (_blog()&&$this->options->pingopen == '1'&&(!$this->user->hasLogin()) ):?>disabled="disabled"<?php endif ; ?> ></textarea></div> 
		<div class="input_body bottom"> <?php if ($this->options->pingimg == 'pingyes'):?><span class="OwO"></span><?php endif; ?><input type="submit" value="提交评论" class="comment_submit_button c_button">  </div> 
		</form>			 
    </div>
    
    
    <?php if ($comments->have()): ?>
	<div class="comments-info-title">
	    
	<div class="comment-info"> <span><b class="comment-auth-mod comment-auth">V</b><i>注册会员</i></span> <span><b class="comment-auth-mod comment-mod">L</b><i>评论等级</i></span> </div>
	<div class="comment-tips" ><span><b class="comment-auth-mod comment-rec">R</b><?php $this->commentsNum(_t('暂无回复'), _t('1 条回复'), _t('%d 条回复')); ?></span></div>
	
	</div>
	
    <?php $comments->listComments(); ?>
    <?php $comments->pageNav('←','→','2','...',array('nextClass' => 'nexts') ); ?> 
    <!--结束语-->
    
    <div class="comment-nomore">
  没有更多评论了
</div>
    

    <!--结束语-->
    
    <?php else: ?>
    <div class="allcomment-empy text-center">
    <div class="icon-svg svg-nocomment svg-sm"></div>
    <div class="">发表评论</div>
    </div>
    <?php endif; ?>
    
    <!--底部页脚-->
    <div class="comment-footer">
  <div class="comment-footer-main">
    <div class="item">
      <span class="hide_sm">让大家也知道你的独特见解</span>
      <a href="#comments" class="btn btn-orange-border">立即评论</a> 
      <?php if (uav(1)): ?><div class="btn btn-orange-border rec-btn" data-cid="<?php echo $this->cid; ?>">智能回复</div> <?php endif; ?>
    </div>
    <div class="item">
      以上留言仅代表用户个人观点，不代表官方立场
    </div>
  </div>
  <i class="ji2-icon" data-bubble="yes"></i>
</div>
    <!--底部页脚-->
    
</div>

<?php endif; ?>