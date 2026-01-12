<?php 
if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php 
include 'libs/common.php';
$this->need('header.php'); ?>
<?php
$str=$_SERVER["REQUEST_URI"];
if(preg_match('/\d+/',$str,$arr)){
$id=$arr[0];
//0:是自己 1:是别人
if ($this->user->hasLogin()&&$this->user->uid==$id){$GLOBALS['lock']=0;}else{$GLOBALS['lock']=1;}
$info=userok($id);
$myuid=$id;
$myname=$info['name'];
$myscreenName=$info['screenName'];
$mymail=$info['mail'];
$mygroup=$info['group'];
}?>

<div class="row member">
    <?php require_once("member/i_side.php");?>
	<div class="col-md-9 contpost user_center" id="content" >
    <?php if ($this->options->auadtop): ?>
    <div class="adimgs adTags"><?php $this->options->auadtop(); ?></div>
    <?php endif; ?>
	<div class="row auto_xin">
	<div class="col-md-3 col-xs-6 auto_xin_list">
	<div class="card bg-gradient-danger card-img-holder text-white">
	  <div class="card-body">
		<img src="<?php echo stcdn($this->options->themeUrl); ?>/src/images/circle.svg" class="card-img-absolute" alt="circle-image">
		<div class="font-weight-normal">文章总计<i class="ri-profile-line ri-lg"></i></div>
		<span><?php echo allpostnum($myuid); ?> 篇文章</span>     
	  </div>
	</div>
    </div> 
    
    <div class="col-md-3 col-xs-6 auto_xin_list">
	<div class="card bg-gradient-info card-img-holder text-white">
	  <div class="card-body">
		<img src="<?php echo stcdn($this->options->themeUrl); ?>/src/images/circle.svg" class="card-img-absolute" alt="circle-image">
		<div class="font-weight-normal">评论次数<i class="ri-message-3-line ri-lg"></i></div>
		<span><?php echo commentnum($myuid); ?> 次评论</span>     
	  </div>
	</div>
    </div>
    
    <div class="col-md-3 col-xs-6 auto_xin_list">
	<div class="card bg-gradient-success card-img-holder text-white">
	  <div class="card-body">
		<img src="<?php echo stcdn($this->options->themeUrl); ?>/src/images/circle.svg" class="card-img-absolute" alt="circle-image">
		<div class="font-weight-normal">访问总计<i class="ri-eye-2-line ri-lg"></i></div>
		<span><?php echo allviewnum($myuid,true); ?> 阅读</span>     
	  </div>
	</div>
    </div>
    
    <div class="col-md-3 col-xs-6 auto_xin_list">
	<div class="card bg-gradient-warning card-img-holder text-white">
	  <div class="card-body">
		<img src="<?php echo stcdn($this->options->themeUrl); ?>/src/images/circle.svg" class="card-img-absolute" alt="circle-image">
		<div class="font-weight-normal">注册天数<i class="ri-group-2-line ri-lg"></i></div>
		<span><?php echo reg_login($myuid); ?> 天</span>     
	  </div>
	</div>
    </div>
	</div>
    <!--头部s-->
    <div class="meb_top page_auto">
    <header class="site-header">
    <nav class="main-navigation"> 
    <ul class="menu-nav-inline">
    <li class="menu-item active"><p><i class="ri-file-text-line ri-lg"></i> 我的文章</p></li> 
    <li class="menu-item"><p><i class="ri-user-settings-line ri-lg"></i> 个人资料</p></li> 
    <?php if($GLOBALS['lock']==0): ?> <li class="menu-item"><p><i class="ri-settings-2-line ri-lg"></i> 扩展设置</p></li><?php endif; ?>
    <li class="menu-item"><p><i class="ri-message-3-line ri-lg"></i> 我的评论</p></li>
    </ul>
    </nav> </header>
    </div>
    <script type="text/javascript">
		$(".page_auto li").on("click",function(){
			var i = $(this).index();//获取li的下标
			$(".page_auto li").eq(i).addClass("active").siblings().removeClass("active");
			$(".page_auto_con .meb_top").eq(i).addClass("active").siblings().removeClass("active");
		});
	</script>
    <!--头部e-->
    
    <div class="page_auto_con">
    
    <!--box1-->
    <div class="meb_top active">
      <?php if($GLOBALS['lock']==0): ?>  
      <!--待审核-->
      <?php authorwaiting($myuid,$GLOBALS['lock']);?>
      <!--草稿-->
      <?php authordraft($myuid,$GLOBALS['lock']);?>
      <?php endif; ?>
      <!--正式内容-->
      
      <?php if ($this->have()): ?>
      <?php while($this->next()): ?>
      
      <article class="post-list contt blockimg "> <div class="entry-container" <?php if (!$this->fields->img): ?>style="padding-left: 0px  !important;"<?php endif; ?>><span class="laid_title_l"></span> 
      
      <?php if ($this->fields->img): ?>
      <div class="block-image feaimg"> <a class="block-fea scrollLoading" data-url="<?php echo stcdnimg($this->fields->img); ?>" href="<?php $this->permalink(); ?>" title="<?php $this->title(); ?>" style="background-image:url('<?php echo stcdn($this->fields->img); ?>')"></a></div> 
      <?php endif; ?>
      
      <header class="entry-header"><span class="entry-title"><a href="<?php $this->permalink(); ?>"><?php $this->title(); ?></a></span></header> <div class="entry-summary ss"><p><?php $this->excerpt(80, '...');?></p></div></div>
                    <div class="d_meta">
                    <time datetime="<?php echo formatTime($this->created); ?>"><i class="ri-time-line ri-lg"></i> <?php echo formatTime($this->created); ?></time>
                    <span class="separator">/</span>
                    <i class="ri-message-3-line ri-lg"></i> <?php $this->commentsNum('0 评论', '1 条评论', '%d 条评论'); ?> 
                    <span class="separator">/</span>
                    <i class="ri-eye-2-line ri-lg"></i>  <?php Postviews($this); ?> 阅读
                    <span class="separator">/</span>
                    <i class="ri-heart-line ri-lg"></i> <?php zannum($this); ?> 赞
   
                    <?php if ($GLOBALS['lock']==0): ?>
                    <span class="aut_edit"><a href="<?php $security->index('/action/contents-post-edit?do=delete&cid='.$this->cid.''); ?>" onclick="javascript:return p_del()">删除</a></span>
                    <?php if ($this->options->gaoedit):?>
                    <span class="aut_edit"><a href="<?php $this->options->siteUrl(); ?><?php $this->options->gaoedit(); ?>.html?tid=<?php $this->cid(); ?>">编辑</a></span>
                    <?php endif; ?>
                    <?php endif; ?>
                    </p>
                    </article>
      <?php endwhile; ?>
      <?php else: ?>
      
      <div class="text-center"><div class="icon-svg svg-empty"></div><div class="text-muted">看起来这里没有任何东西。</div></div>

      <?php endif; ?>
      <?php $this->pageNav('&laquo; 前一页', '后一页 &raquo;'); ?>
      <!--正式内容end-->
    </div>
    <!--box1-->
   
    <!--个人资料s-->
    <div class="meb_top">
    <div class="col-lg-12 meb_cl"> <div class="form__group"> <label class="form__label">个人主页</label> <input type="text" class="form__input" value="<?php echo $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; ?>" readonly  unselectable="on"> </div> </div>
        
    <?php Typecho_Widget::widget('Widget_Security')->to($security); ?>
    <form action="<?php $security->index('/action/users-profile'); ?>" method="post" class="input-form form" name=forma enctype="application/x-www-form-urlencoded">
    
    <?php if($GLOBALS['lock']==0): ?>  
    
    <div class="col-lg-6 meb_cl"> <div class="form__group"> <label class="form__label">用户ID :</label> <input name="username" type="text" class="form__input" value="<?php echo $myuid; ?>" readonly  unselectable="on"> </div> </div>
    
    <div class="col-lg-6 meb_cl"> <div class="form__group"> <label class="form__label">帐号(登录账号):</label> <input  name="title"  type="text" class="form__input" value="<?php echo $myname; ?>" readonly  unselectable="on"> </div> </div>
    
    <?php endif; ?>
    
    <div class="col-lg-6 meb_cl"> <div class="form__group"> <label class="form__label">昵称:</label> <input name="screenName"  type="text" class="form__input" value="<?php echo $myscreenName; ?>" <?php if($GLOBALS['lock']==1): ?> readonly  unselectable="on"<?php endif; ?>> </div> </div>
    
    <div class="col-lg-6 meb_cl"> <div class="form__group"> <label class="form__label">邮箱:</label> 
    <?php if($GLOBALS['lock']==0): ?>
    <input type="text" name="mail" class="form__input" value="<?php echo $mymail; ?>" > 
    <?php else: ?>
    <input name="mail" type="text" class="form__input" value="<?php
//1.字符串截取法
$new_tel1 = substr($mymail, 0, 3).'****'.substr($mymail, 7);
echo $new_tel1;
?>" <?php if($GLOBALS['lock']==1): ?> readonly  unselectable="on"<?php endif; ?>> <?php endif; ?>
    </div> </div>
    
    <?php if($GLOBALS['lock']==0): ?>
    <div class="col-lg-12 meb_cl"> <input name="do" type="hidden" value="profile"><button class="form__btn" type="submit" name="dosubmit"><span>确定</span></button></div>
    <?php endif; ?>
    </form>
    
    <div class="border-lins"></div>
    
    <?php if($GLOBALS['lock']==0): ?>    
    
    <form action="<?php $security->index('/action/users-profile'); ?>" method="post"  class="input-form form" enctype="application/x-www-form-urlencoded" >
    <div class="col-lg-6 meb_cl"> <div class="form__group"> <label class="form__label">密码:</label> <input type="text" name="password" class="form__input" > </div> </div>
    
    <div class="col-lg-6 meb_cl"> <div class="form__group"> <label class="form__label">重复密码:</label> <input class="form__input" type="text" name="confirm"> </div> </div>
    
    <div class="col-lg-12 meb_cl"><input name="do" type="hidden" value="password"><button class="form__btn" type="submit" name="dosubmit"><span>确定</span></button></div>
    </form>
    <?php endif; ?>   
    
    </div>
    <!--个人资料e-->
    <!--box1.5-->
    <?php if($GLOBALS['lock']==0): ?> 
    <div class="meb_top">
    <!--个人签名s--> 
    
    <!--个人头像-->
    <div class="col-lg-12 meb_cl"> <div class="form__group"> <label class="form__label">个人头像</label>
    <div class="user-img">
        
    <form id="uploadForm">
	<input class="jide" name="imgsrc" type="hidden" value="">
	<img src="<?php echo getuserimg($myuid); ?>" class="my_pic">
	</form>   
        
    </div>
    </div> 
    </div> 
   
    <?php $this->need('libs/userimg.php'); ?>
    <!--个人头像-->
    
    
    <div class="col-lg-12 meb_cl"> <div class="form__group"> <label class="form__label">个人签名</label><input type="text" class="form__input" name="getintros" value="<?php echo reintro($myuid);?>"> </div> </div> 
    
    <!--社交媒体-->
    <!--
    <div class="col-lg-12 meb_cl"> <div class="form__group"> <label class="form__label">小红书</label><input type="text" class="form__input" name="webxhs" value="<?php echo reweburl($myuid,'webxhs');?>"> </div> </div> 
    
    <div class="col-lg-12 meb_cl"> <div class="form__group"> <label class="form__label">抖音</label><input type="text" class="form__input" name="webdy" value="<?php echo reweburl($myuid,'webdy');?>"> </div> </div> 
    -->
    <!--社交媒体-->
    
    
    <div class="col-lg-12 meb_cl"> <div class="form__group"> <label class="form__label">自定义广告(展示在文章底部)</label><input type="text" class="form__input" name="myad" value="<?php echo myad($myuid);?>"> </div> </div> 
    
    <div class="col-lg-12 meb_cl"> <input name="do" type="hidden" value="getintro"><button class="form__btn" type="submit" id="getintro" name="getintro"><span>确定</span></button></div>

    <!--个人签名s--> 
    <div class="border-lins"></div>
    </div>
    <?php endif; ?>
    <!--box1.5-->
	<!--box2-->
<div class="meb_top">
<?php
global $AuthorCommentId;//全局作者id
$AuthorCommentId=$myuid;//获取作者id
?>
<?php $this->widget('Widget_Post_AuthorComment@index','pageSize=15')->to($AuthorComment); ?>
<?php if ($AuthorComment->have()): ?>

<ul class="list_auto_ping">
<?php while($AuthorComment->next()): ?>
<li>
<div class="meta">
<div class="meta-item"><a href="<?php $AuthorComment->permalink() ?>"><?php $AuthorComment->title(); ?></a>
</div>
<span class="views"><?php $AuthorComment->dateWord(); ?></span>
</div>
<?php $cos=parseBiaoQing($AuthorComment->content); echo $cos;?></li>
<?php endwhile; ?>
</ul>
<?php else: ?>
<div class="text-center"><div class="icon-svg svg-empty"></div><div class="text-muted">看起来这里没有任何东西。</div></div>
<?php endif; ?>
</div>
<!--box2-->
</div>    
</div>
</div>
<?php $this->need('footer.php'); ?>
<link rel="stylesheet" href="<?php echo stcdn($this->options->themeUrl); ?>/src/css/popup.css">
<script>
function p_del() {
    var msg = "您真的确定要删除吗？";
    if (confirm(msg)==true){
        return true;
    }else{
        return false;
    }
}

$("#getintro").click(function() {
    
    const value = $('input[name="getintros"]').val();
    const myad = $('input[name="myad"]').val();
    var imgUrl = $("img.my_pic").attr("src");
    const webxhs = $('input[name="webxhs"]').val();
    const webdy = $('input[name="webdy"]').val();
 
 
            $.ajax({			
     
                url: SPZ.BASE_site+"/sp/intro",
				type: "POST",  
				data: {
				    value:value,
				    myad:myad,
				    imgUrl:imgUrl,
				    webxhs:webxhs,
				    webdy:webdy
				},
                async: true,                          
				//dataType: "json",
				success: function(data) {
				  cocoMessage.success('更新成功');
				},
				error: function(err) {
		          cocoMessage.warning('更新失败');
				}
			}); 
    
});

</script>
<script type="text/javascript">
	$(".close,.gb").click(function(){
		$(".pic_box").animate({
			'top':'-1000px'
		},500);
	}),
	$("#uploadForm").click(function(){
		$(".pic_box").animate({
			'top':'80px',
		},300);
	}),
	$(".queren").click(function(){
		var src = $(".jide").val();
		//效果展示,在服务器中把这一段删除,用上面那一段
		if(src != ""){
			$(".my_pic").attr('src',src);
			$(".pic_box").animate({
				'top':'-1000px'
			},500);
		}else{
			return false;
		}
		
	});
	var $box = document.getElementById('pic_box');
	var $li = $box.getElementsByTagName('li');
	var index = 0;
	for(var i=0;i<$li.length;i++){
		$li[i].index=i;
		$li[i].onclick=function(){
			$li[index].style.borderRadius="15%";
			this.style.borderRadius="50%";
			index = this.index;
		}
	}
	$(".pic_box li img").click(function(){
		var src=$(this).attr("src");
		$(".jide").val(src);
	})
</script>
<?php require_once("libs/common-js.php"); ?>
