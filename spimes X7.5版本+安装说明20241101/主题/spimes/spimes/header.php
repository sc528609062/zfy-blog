<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;?>
<!DOCTYPE HTML>
<html class="no-js">
<head>
<meta charset="<?php $this->options->charset(); ?>">
<meta http-equiv="X-UA-Compatible" content="IE=edge, chrome=1">
<meta name="renderer" content="webkit">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<meta name="applicable-device" content="pc,mobile">  
<?php if($this->options->favicon): ?><link rel="shortcut icon" href="<?php $this->options->favicon(); ?>"><?php endif;?>  
<?php $this->need('assets/seo.php'); ?>

<!-- 使用url函数转换相关路径 -->
<link rel="stylesheet" href="<?php echo stcdn($this->options->themeUrl); ?>/src/css/bootstrap.min.css">
<link rel="stylesheet" href="<?php echo stcdn($this->options->themeUrl); ?>/style.css?ver=<?php echo themeVersion(); ?>">
<link rel="stylesheet" href="<?php echo stcdn($this->options->themeUrl); ?>/src/css/swiper.min.css?ver=<?php echo themeVersion(); ?>">
<link rel="stylesheet" href="<?php echo stcdn($this->options->themeUrl); ?>/src/css/Remix/remixicon.css?ver=<?php echo themeVersion(); ?>">
<!--短代码-->
<link rel="stylesheet" type="text/css" href="<?php echo stcdn($this->options->themeUrl); ?>/src/css/plugins/plugins.css?v2.1.1">
<script type="text/javascript" src="<?php echo stcdn($this->options->themeUrl); ?>/src/js/plugins/joe.short.js"></script>
<!--短代码-->
<script src="<?php echo stcdn($this->options->themeUrl); ?>/src/js/jquery.min.js"></script>
<script src="<?php echo stcdn($this->options->themeUrl); ?>/src/js/ajax.js"></script>
<script type='text/javascript'>window.SPZ = { BASE_site: "<?php $this->options->siteUrl(); ?>" }</script> 

<!--[if lt IE 9]>
<script src="http://cdn.staticfile.org/html5shiv/r29/html5.min.js"></script>
<script src="http://cdn.staticfile.org/respond.js/1.3.0/respond.min.js"></script>
<![endif]-->  

<?php if ($this->is('post')) : ?>
<link rel="canonical" href="<?php $this->permalink() ?>"/>
<?php endif; ?>
<!-- 通过自有函数输出HTML头部信息 --> 
<?php $this->need('assets/og.php'); ?>
<?php if ($this->options->webcss||$this->options->varfont): ?>
<style type="text/css"><?php $this->options->webcss(); ?></style>
<?php endif; ?>
<?php if ($this->options->tophtml): ?>
<?php $this->options->tophtml(); ?>
<?php endif; ?>
</head>
<body class="<?php if ($this->options->night == '2'):?><?php echo($_COOKIE['night'] == '1'?'night':''); ?><?php elseif ($this->options->night == '1'): setcookie("night", "1", time()+3600); ?>sp-dark-mode<?php elseif ($this->options->night == '0'): setcookie("night", "0", time()+3600); ?><?php endif; ?> " style="--theme: #<?php vartheme(); ?>;">  
<!--[if lt IE 8]>
<div class="browsehappy" role="dialog"><?php _e('当前网页 <strong>不支持</strong> 你正在使用的浏览器. 为了正常的访问, 请 <a href="https://browsehappy.com/">升级你的浏览器</a>'); ?>.</div>
<![endif]-->
<div class="site-main spimes-container">
<div class="top-bar">  
<div class="container clearnav secnav">
<!--移动端按钮-->
<nav class="navbar navbar-inverse navbar-static-top">
<div class="container">
<div class="navbar-header">
<div class="m_nav-list" >
<a href="javascript:;" data-toggle="offcanvas" class="lines js-m-navlist">
<span class="line first-line"></span>
<span class="line second-line"></span>
<span class="line third-line"></span>
</a>
</div>
</div>
<div id="navbar" class="collapse navbar-collapse sidebar-offcanvas">
<!--移动导航s-->
<?php if ($this->options->night == '2'):?><input class="wb-switch wb-yes" id="J_themesSwitchBtn" type="checkbox" onclick="javascript:switchNightMode()"><?php endif; ?>  
<div class="mobile-sidebar-column">
<ul class="mobile-sidebar-menu ultop">
 
<li class="menu-item"><a href="<?php $this->options->siteUrl(); ?>"><i class="ri-arrow-right-s-line ri-lg"></i><?php _e('首页'); ?></a></li>
<?php $this->widget('Widget_Metas_Category_List')->to($categorys); ?>
<?php while($categorys->next()): ?>
<?php if ($categorys->levels === 0): ?>
<?php $children = $categorys->getAllChildren($categorys->mid); ?>
<?php if (empty($children)) { ?>
<li class="menu-item">
<a href="<?php $categorys->permalink(); ?>" title="<?php $categorys->name(); ?>"><?php $categorys->name(); ?></a>
</li>
<?php } else { ?> 
<li class="menu-item" >
<a ><i class="ri-arrow-right-s-fill ri-lg"></i><?php $categorys->name(); ?><div class="dropdown-sub-menu"><span class="ri-arrow-down-s-line ri-lg"></span></div></a>
<ul class="sub-menu">
<?php foreach ($children as $mid) { ?>
<?php $child = $categorys->getCategory($mid); ?>
<li class="menu-item">
<a href="<?php echo $child['permalink'] ?>" title="<?php echo $child['name']; ?>"><?php echo $child['name']; ?></a>
</li>
<?php } ?>
</ul>
</li>
<?php } ?>
<?php endif; ?>
<?php endwhile; ?>
<li class="menu-item"> 
<a ><i class="ri-arrow-right-s-fill ri-lg"></i>其他<div class="dropdown-sub-menu"><span class="ri-arrow-down-s-line ri-lg"></span></div></a>
<ul class="sub-menu">
<?php $this->widget('Widget_Contents_Page_List')->to($pages); ?>   
<?php while($pages->next()): ?>
<?php if($pages->status = 'publish'): ?><li class="menu-item"><a href="<?php $pages->permalink(); ?>" title="<?php $pages->title(); ?>"><?php $pages->title(); ?></a></li><?php endif; ?>
<?php endwhile; ?>
</ul>  
</li> 

</ul>
</div>
<!--移动导航e-->
</div><!--/.nav-collapse -->
</div>
</nav> 
<div class="top-bar-left pull-left navlogo">
<a href="<?php $this->options->siteUrl(); ?>" class="logo box">
<?php if($this->options->logoUrl): ?>
<img src="<?php $this->options->logoUrl();?>" class="logo-light" id="logo-light" alt="<?php $this->options->title() ?>" />
<img src="<?php $this->options->logoUrldark();?>" class="logo-dark <?php if($this->options->night == '1'): ?><?php else : ?>d-none<?php endif; ?>" id="logo-dark" alt="<?php $this->options->title() ?>" />
<?php else : ?>
<span id="logo-light" class="logo-light"><?php $this->options->title() ?></span> <span class="logo-dark d-none" id="logo-dark"><?php $this->options->title() ?></span>
<?php endif; ?>
<b class="shan"></b>
</a>
</div>
<?php if ($this->options->liuyan&&getQX($this->user->uid)): ?>
<div class="search-btn nav_liuyan">
<a href="<?php $this->options->siteUrl(); ?><?php $this->options->liuyan(); ?>.html" class="btn btn-blue btn-article"><i class="ri-message-3-line ri-lg"></i> 留言</a>
</div>
<?php endif; ?>
<?php if ($this->options->tougao&&_blog()&&getQX($this->user->uid)): ?>
<div class="search-btn ">
<a href="<?php $this->options->siteUrl(); ?><?php $this->options->tougao(); ?>.html" class="c_red btn btn-blue btn-article"><i class="ri-edit-box-line ri-lg"></i> 投稿</a>
</div>
<?php endif; ?>
<div class="search-warp clearfix">
<form method="get" action="">
<div class="search-area" >
<input class="search-input" placeholder="搜索感兴趣的知识和文章" type="text" name="s" autocomplete="off" id="soblur">
<!--弹出-->
<ul class="dropdown-menu top_so">
    
</ul>
<!--弹出-->
</div>
<button class="showhide-search search-form-input" type="submit"><i class="ri-search-2-line ri-lg"></i></button>
</form>
</div>
<!--手机端s-->
<div class="top-bar-right pull-right text-right mobs">
<div class="top-admin">	
<a href="javascript:;" id="soStats" class="sostats_click"><i id="soico" class="ri-search-2-line ri-lg"></i> <?php _e('搜索'); ?></a>
<?php if($this->user->hasLogin()): ?>
<div id="auStats" class="dropdown-toggle"><img src="<?php echo getuserimg($this->user->uid); ?>" width="25px" height="25px" class="navtar" >
							<div class="austats" id="aus">
							<ul>
							<li><a href="<?php $this->options->siteUrl(); ?><?php if ($this->options->rewrite==0): ?>index.php/<?php endif; ?>author/<?php $this->user->uid(); ?>"><i class="ri-user-settings-line ri-lg"></i>个人主页</a></li>
							<li><a href="<?php $this->options->siteUrl(); ?><?php $this->options->tougao(); ?>.html"><i class="ri-edit-box-line ri-lg"></i>发布文章</a></li>							
							<li><a href="<?php $this->options->logoutUrl(); ?>"><i class="ri-login-circle-line ri-lg"></i>退出登录</a></li>
							</ul>
						    </div>
  </div>
<?php else : ?>
<a href="<?php if ($this->options->denglu): ?><?php $this->options->siteUrl(); ?><?php if ($this->options->rewrite==0): ?>index.php/<?php endif; ?><?php $this->options->denglu(); ?>.html<?php else: ?><?php $this->options->adminUrl('login.php'); ?><?php endif; ?>"><i class="ri-login-circle-line ri-lg"></i> <?php _e('登录'); ?></a>
<?php endif; ?> 
<!--搜索框开始-->
<div class="navbar-search socollapse sostats" id="navbar-search" style="">
			<div class="container">
				<form method="get" role="search" id="searchform" class="searchform shadow" action="">
					<div class="input-group">
						<input type="text" name="s" id="s" placeholder="请输入搜索关键词并按回车键…" class="form-control" required="" >
						<div class="input-group-append">
						<button class="btn btn-nostyle" type="submit"><i class="ri-search-2-line"></i></button>
						</div>
					</div>
<!-- /input-group -->
				</form>
			</div>
</div>
<?php if ($this->options->night == '2'):?><input class="wb-switch wb-no" id="J_themesSwitchBtn" type="checkbox" onclick="javascript:switchNightMode()"><?php endif; ?>   
</div>
</div>
<!--手机端e-->			
</div>  
<!--s-->
<div class="new_header container clearnav">  
<div class="top-bar-left pull-left navs">
<?php if ($this->is('post')): ?> 
<div class="top-bar-title post_no" id="post_top_title"><a href="<?php $this->options->siteUrl(); ?>"><i class="ri-home-fill ri-lg"></i></a> <?php $this->title(); ?></div> 
<?php endif; ?>
<nav class="top-bar-navigation">
<ul class="top-bar-menu">
<li class="menu-item"><a href="<?php $this->options->siteUrl(); ?>"><i class="ri-home-fill ri-lg"></i> <?php _e('首页'); ?></a></li>					
<!--pc导航s-->              
<?php if ($this->options->huaoff == '1'):?>
<li class="drop-down">
    <a href="#"><i class="ri-list-unordered ri-lg"></i> 专题推荐 <i class="ri-arrow-down-s-line ri-lg"></i></a>
    <div class="aui-dow-box aui-dow-box-list">
    <div class="s_tag menu_zt"></div>
    </div>
</li>
<?php endif; ?>
<?php $this->widget('Widget_Metas_Category_List')->to($categorys); ?>
<?php while($categorys->next()): ?>
<?php if ($categorys->levels === 0): ?>
<?php $children = $categorys->getAllChildren($categorys->mid); ?>
<?php if (empty($children)) { ?><li class="nav-s"><a href="<?php $categorys->permalink(); ?>" title="<?php $categorys->name(); ?>"> <?php echo cateico($categorys->mid); ?> <?php $categorys->name(); ?></a></li>
<?php } else { ?>
<li class="drop-down"><a  href="<?php $categorys->permalink(); ?>" ><?php $categorys->name(); ?><i class="ri-arrow-down-s-line ri-lg"></i></a><ul class="aui-nav-dow"><?php foreach ($children as $mid) { ?><?php $child = $categorys->getCategory($mid); ?><li><a href="<?php echo $child['permalink'] ?>" title="<?php echo $child['name']; ?>"><?php echo $child['name']; ?></a></li><?php } ?></ul>
</li>
<?php } ?>
<?php endif; ?>                  
<?php endwhile; ?>
<li class="drop-down"> <i class="msg_remind" style="display: inline;"></i>
<a href="#"><i class="ri-more-fill ri-lg"></i></a>
<ul class="aui-nav-dow">
<?php $this->widget('Widget_Contents_Page_List')->to($pages); ?>   
<?php while($pages->next()): ?>
<?php if($pages->status = 'publish'): ?><li><a href="<?php $pages->permalink(); ?>" title="<?php $pages->title(); ?>"><?php $pages->title(); ?></a></li><?php endif; ?>
<?php endwhile; ?>
</ul>  
</li>
<!--pc导航e-->
</ul>
<div id="sidebar-toggle" class="sidebar-toggle">
<span></span>
</div>
</nav>
</div>
<div class="top-bar-right pull-right text-right">
<div class="top-admin">
<!--站点统计开始-->              

<a href="javascript:;" id="mStats" class="stats_click"><i class="ri-profile-line ri-lg"></i> <?php _e('统计'); ?>
		<div class="stats">
							<ul>
							<?php if ($this->options->sitedate): ?>
							<li><?php _e( '建站日期：' ); ?><?php $this->options->sitedate(); ?></li>
							<?php endif; ?>
							<?php Typecho_Widget::widget('Widget_Stat')->to($stat); ?>
							<li><?php _e( '文章总数：' ); ?><?php $stat->publishedPostsNum() ?><?php _e( ' 篇' ); ?></li>
							<li><?php _e( '评论总数：' ); ?><?php $stat->publishedCommentsNum() ?><?php _e( ' 条' ); ?></li>
							<li><?php _e( '分类总数：' ); ?><?php $stat->categoriesNum() ?><?php _e( ' 个' ); ?></li>
							<li><?php _e( '最后更新：' ); ?><?php get_last_update(); ?></li>
							</ul>
		</div></a>
					<!--站点统计结束-->
					    <?php if(_blog()): ?>
						<div class="login avt_tl">
			            <?php if($this->user->hasLogin()): ?>							
							<div class="avt_cl"><img src="<?php echo getuserimg($this->user->uid); ?>" width="25px" height="25px" class="navtar" ><?php $this->need('member/nav_ainfo.php'); ?></div><?php else: ?>
			                <i class="ri-login-circle-line ri-lg"></i><span> <?php _e('登录'); ?>
			                <!--s-->
			                <?php $this->need('member/notlogin.php'); ?>
			                <!--e-->
			                </span>
			            <?php endif; ?>
			            </div>
			            <?php endif; ?>
						<?php if ($this->options->night == '2'):?><input class="wb-switch" id="J_themesSwitchBtn" type="checkbox" onclick="javascript:switchNightMode()"><?php endif; ?>                
                    <!--夜间白天-->
				</div></div></div>
<!--e-->  
	<div id="percentageCounter"></div>
	</div><!-- .top-bar -->
	<div class="container">
	<main id="main" class="site-main">	