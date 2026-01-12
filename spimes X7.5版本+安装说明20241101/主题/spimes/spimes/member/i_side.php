<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<div class="member_i_side col-md-3 widget-area <?php if (($this->is('post')) && ($this->options->postimask == '1') ): ?>post_sider<?php endif; ?>" id="secondary">

    <?php if ($this->options->auadside): ?>
     <section class="widget_img _ts"><div class="adTags"><i class="gg-icon"></i> <a target="_blank" href=""> <?php $this->options->auadside(); ?> </a></div> </section>
     <?php endif; ?>

    <section class="widget">
        <div class="box-img bg_color"></div>
        <div class="widget-list meb_autor_top"> 
        <div class="meb_v">  
	
        <img class="widget-about-image" src="<?php echo getuserimg($myuid); ?>" srcset="<?php echo getuserimg($myuid); ?>" class="avatar avatar-140 photo" >
        <?php if ($this->options->viphonor): ?><div class="av_v_honor"><img src="<?php $this->options->viphonor(); ?>" title="注册用户"></div><?php endif; ?>      
        </div>
        <div class="widget-about-intro">
        <div class="name"><?php echo $myscreenName; ?></div>
        <div class="widget-intro"><?php echo reintro($myuid); ?></div>
		
        </div>           
        </div>
        
        <!--扩展资料-->
        <ul class="user__list"> 
        <li><span><i class="ri-account-box-line ri-lg"></i> 会员类型：</span> <span><?php echo yonghuzu($myuid); ?></span></li> 
        <li><span><i class="ri-markup-line ri-lg"></i> 发表文章：</span> <span><?php echo allpostnum($myuid); ?> 篇</span></li>
        <li><span><i class="ri-eye-2-line ri-lg"></i> 访问人气：</span> <span><?php echo authorviews($myuid); ?> 人气</span></li> 
        <li><span><i class="ri-login-circle-line ri-lg"></i> 最近登录：</span> <span><?php echo get_last_login($myuid); ?></span></li>
        </ul>
        <!--扩展资料-->
        
    </section>
    
    <!--<section class="widget">
    <div class="user-auth">
     <i class="icon iconfont icon-qinghuiyuan"></i>  个人认证：创作者
     </div>
    </section>-->

</div>
