<div class="header__dropdown"><div class="header-box"><div class="refresh-header-top"><div class="header-top"><a class="user-names" href="<?php $this->options->siteUrl(); ?><?php if ($this->options->rewrite==0): ?>index.php/<?php endif; ?>author/<?php $this->user->uid(); ?>">
<img src="<?php echo getuserimg($this->user->uid); ?>" >
</a> 
<div class="user-aut"> <a class="user-names" href="<?php $this->options->siteUrl(); ?><?php if ($this->options->rewrite==0): ?>index.php/<?php endif; ?>author/<?php $this->user->uid(); ?>"><?php $this->user->screenName(); ?></a> <?php echo yonghuzu($this->user->uid); ?></div>
<p><?php echo reintro($this->user->uid); ?> </p>
<a href="<?php $this->options->logoutUrl(); ?>" class="logout" title="退出"><i class="ri-login-circle-line ri-lg"></i>退出</a>
</div></div><div class="header-center"> <div class="md-l"> <span class="md-tit">个人信息</span> <span class="jinbi">作者人气：<?php echo authorviews($this->user->uid); ?></span> <span class="dou">账号年龄：<?php echo reg_login($this->user->uid); ?> 天</span> </div> <div class="md-r"> <span class="md-tit">扩展资料</span><span class="jinbi">发布文章：<?php echo allpostnum($this->user->uid); ?> 篇</span> 
<span class="dou" title="评论次数：<?php echo commentnum($this->user->uid); ?>">评论次数：<?php echo commentnum($this->user->uid); ?> 次</span></div> </div></div> </div>