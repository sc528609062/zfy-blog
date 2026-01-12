<div class="header__dropdown"> 
			
<div class="login-div notlogin">
<div class="info">
<div class="info-thumb"> <i class="thumb" style="background-image:url(<?php echo stcdn($this->options->themeUrl); ?>/images/wu-user.png);"></i> </div>
<h2 class="user-name">您还未登录</h2>
<h4 class="user-info">登录体验更多功能</h4>
<a href="<?php if ($this->options->denglu): ?><?php $this->options->siteUrl(); ?><?php if ($this->options->rewrite==0): ?>index.php/<?php endif; ?><?php $this->options->denglu(); ?>.html<?php else: ?><?php $this->options->adminUrl('login.php'); ?><?php endif; ?>" class="modal-open btn btn-orange info-btn"><i class="ri-login-circle-line ri-lg"></i> 立即登录 </a>

</div>

</div>

<style>
.login-div {
    background: #fff;
    border-radius: 3px;
    overflow: hidden;
}
.login-div .info {
   
    position: relative;
    padding: 10px 80px;
}
.login-div .info .info-thumb {
    width: 50px;
    position: absolute;
    left: 10px;
    top: 8px;
    border: 4px solid #fff;
    box-shadow: 0 0 30px 0 #eee;
    border-radius: 100%;
}
.login-div .info .info-thumb .thumb {
    padding-top: 100%;
    border-radius: 100%;
    transition: none;
}
.login-div .info h2 {
    font-size: 14px;
    color: #333;
    margin-bottom: 6px;
    line-height: 1.5;
    height: 1.5em;
    overflow: hidden;
}
.login-div .info h4 {
    font-size: 12px;
    color: #888;
    font-weight: normal;
    line-height: 1.5;
    height: 1.5em;
    overflow: hidden;
    margin-bottom: 0px;
}
.login-div .info .info-btn {
        position: absolute;
    right: 20px;
    top: 15px;
    padding: 2px 10px;
    z-index: 10;
    font-size: 12px;
}
.login-div .info .info-btn:hover{ color:#fff; }
</style>
			
</div>