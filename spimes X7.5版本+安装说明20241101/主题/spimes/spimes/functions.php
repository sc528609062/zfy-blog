<?php

if (!defined('__TYPECHO_ROOT_DIR__')) exit;
require_once("core/theme.php");
require_once("core/oin.php");
require_once("libs/member.php");
require_once("libs/writepost.php");
require_once("core/get_int.php");
require_once("core/setcate.php");
require_once("libs/Contents.php");

define("THEME_URL", str_replace('//usr', '/usr', str_replace(Helper::options()->siteUrl, Helper::options()->rootUrl . '/', Helper::options()->themeUrl)));
$str1 = explode('/themes/', (THEME_URL . '/'));
$str2 = explode('/', $str1[1]);
define("THEME_NAME", $str2[0]);



function themeConfig($form)
{
    $Version =themeVersion();
    $index = Helper::options()->siteUrl;
    

    
?>  
    <link rel="stylesheet" href="<?php echo THEME_URL ?>/assets/css/setting.spimes.css">
    <script src="<?php echo THEME_URL ?>/src/js/jquery.min.js"></script>
    <script src="<?php echo THEME_URL ?>/assets/css/setjs.js"></script>
    <script src="<?php echo THEME_URL ?>/assets/css/jscolor.js"></script>
    <script type='text/javascript'>window.SPZ = { BASE_site: "<?php Helper::options()->siteUrl(); ?>",BASE_ex: "<?php Helper::options()->index("sp/ex") ?>",BASE_Alltu: "<?php Helper::options()->index("sp/alltu") ?>" }
    
</script>
    <div class="j-setting-contain">
        <link href="<?php echo THEME_URL ?>/core/j.setting.min.css" rel="stylesheet" type="text/css" />
        <div>
            <div class="j-aside">
                <div class="logo" id="t_logo" data-key="<?php $onblog = Helper::options()->_blog; echo $onblog; ?>">设置面板</div>
                <ul class="j-setting-tab">
                    <li data-current="j-setting-notice"><i class="ri-volume-up-line ri-lg"></i> 最新公告 Noti</li>
                    <li data-current="j-setting-global" id="global"><i class="ri-settings-2-line ri-lg"></i> 常规设置 Set</li>
                    <li data-current="j-setting-index"><i class="ri-home-7-line ri-lg"></i> 首页设置 Ind</li>
                    <li data-current="j-setting-user" id="user"><i class="ri-user-settings-line ri-lg"></i> 会员中心 User</li>
                    <li data-current="j-setting-ads"><i class="ri-image-line ri-lg"></i> 广告设置 Ad</li>
                    <li data-current="j-setting-color" id="color"><i class="ri-star-half-line ri-lg"></i> 风格样式 Style</li>
                    <li data-current="j-setting-aside"><i class="ri-article-line ri-lg"></i> 边栏页脚 Side</li>
                    <li data-current="j-setting-mox"><i class="ri-android-line ri-lg"></i> 移动设置 Mox</li>
                    <li data-current="j-setting-seo" id="seo"><i class="ri-service-line ri-lg"></i> SEO设置 Seo</li>
                    <li data-current="j-setting-speed"><i class="ri-dashboard-2-line ri-lg"></i> 优化加速 Spe</li>
                </ul>
                <?php require_once("core/backup.php"); ?>
            </div>
        </div>
    <span id="j-version" style="display: none;"><?php echo $Version ?></span>

    <div class="j-setting-notice">
        
    <!--主题检测-->
    <div class="sp_ex"></div>
        
    <!--公告-->  
    <div class="miracles-pannel pannel_clo">
    <span class="spimes_logo" id="spimes_ex" ></span>
    
	<h1>Spimes x<?php echo $Version ?> 设置面板</h1>
	<p>Spimes主题为博客、自媒体、资讯类的网站设计开发，自适应兼容手机、平板设备的团队，工作室门户主题，精心打磨的一处处细节。只为让您的站点拥有速度与优雅兼具的极致体验。</p>
    <hr><p>提交sitemap可以向搜索提交网站的sitemap文件，帮助spider更好的抓取您的网站。</p>
    <p>sitemap.xml地图：<a href='<?php echo $index ?>sitemap.xml' target='_blank'><?php echo $index ?>sitemap.xml</a> <a href='https://ziyuan.baidu.com/linksubmit'>(地图提交)</a><p>
    <hr>
    <div class="tips">
    <div id="setid">主题环境检测：</div>
    <p> ● 伪静态：<?php if (Helper::options()->rewrite): ?>已开启 （建议简短链接）<?php else: ?>未开启（建议开启，并设置简短链接）<?php endif; ?></p>
    <p> ● 插件Deng：<?php $all = Typecho_Plugin::export();?><?php if (array_key_exists('Deng', $all['activated'])) : ?>已开启 （请设置好插件的相关功能）<?php else: ?>未开启（主题正常启用需要激活插件Deng）<?php endif; ?></p>
    <p> ● 主题配色外观：<?php if (Helper::options()->vartheme==null||Helper::options()->vartheme=='FFFFFF'): ?>颜色没有配置正确哦，或者当前的（<?php Helper::options()->vartheme();?>）颜色并不适合你<?php else: ?>已经配对 <?php endif; ?></p>
    
    <p> ● 域名检查：<?php $nowhtml=GetHttp().$_SERVER['HTTP_HOST'].'/'; if ($nowhtml!=$index): ?>当前的域名( <?php echo GetHttp(); ?><?php echo $_SERVER['HTTP_HOST']; ?> )和程序默认域名( <?php echo $index; ?> )不一致，为了防止SSL证书验证错误，请保持一致。<?php else: ?>当前域名路径一致，配置正确<?php endif; ?></p>
    <hr>
    <p>Spimes主题使用教程：<a href='https://www.veimoz.com/306' target='_blank'>自媒体博客Spimes主题用法和注意事项</a><p>

    </div>
    
	</div>
	
	<?php
	/**主题检测**/
	if(isset($_POST['themetype'])){ 
    if($_POST["themetype"]=="一键修复"){
    resql();
    ;?>
    <script language="JavaScript">cocoMessage.success('修复成功………');</script><?php    
    }}

	
	/**留言面板**/	    
    if('1' == count($db->query("SHOW TABLES LIKE '".$db->getPrefix()."message'")->fetchAll())){
        
         $db = Typecho_Db::get();
    //$result = $db->fetchAll($db->select()->from('table.eechat')->where('uid1 = ?', $id)->where('uid2 = ?', $myid));
    $result = $db->fetchAll($db->select()->from('table.message'));
    echo '<div class="miracles-pannel"><ul>';
    if($result){
    foreach($result as $val){
    $time = date("Y-m-d H:i",$val['time']);   
    echo "<li>".$time.' 姓名：'.$val['name'].' 电话：'.$val['tell'].' 内容：'.$val['con']."</li>";    
    }  
    }else{
        echo null;
    }

    echo ' </ul><input type="submit" name="liutype" class="miracles-backup-button" value="清空留言" />	   
	  </div>';   
        
        
    } else {
        
    }
    
    

  

 
	
	?>
	<script>
	$('input[name="liutype"]').click(function() {
    // 在这里编写您想要执行的代码
            $.ajax({				
            //url: globals.ajax_url,
            url: SPZ.BASE_site+"/sp/delmsg",
            type: "GET",
            async: true,                          
            dataType: "json",
            success: function(data) {
                 cocoMessage.success('清空成功………');
                 location.reload();
            },
            error: function(err) {
                console.log(err)
            }
            });
            
    });   
	</script>
	<!--公告-->  
    </div>
        <script src="<?php echo THEME_URL ?>/core/j.setting.min.js"></script>
        
    <?php
    //网站模式

    $_blog = new Typecho_Widget_Helper_Form_Element_Select('_blog',array('-1'=>'不开启','0'=>'开启'),'-1','开启会员模式','开启会员模式，不开启为极简模式,注意备份数据，选择的时候可能会清除部分会员设置的数据');
    $form->addInput($_blog);
    
    $aichat = new Typecho_Widget_Helper_Form_Element_Select('aichat',array('0'=>'不开启','1'=>'开启'),'0','开启AI客服','开启AI客服，需要配置Chatgpt配置');
    $form->addInput($aichat);

    $logoUrl = new Typecho_Widget_Helper_Form_Element_Text('logoUrl', NULL, NULL, _t('站点LOGO地址<b style="color: red;">✱</b>'), _t('在这里填入一个图片URL地址, 以在网站标题前加上一个logo,留空则会显示网站文字标题, 尺寸高度建议50-70px之间，宽度等比例自适应'));
    $form->addInput($logoUrl);

	$logoUrldark = new Typecho_Widget_Helper_Form_Element_Text('logoUrldark', NULL, NULL, _t('站点夜间模式LOGO地址<b style="color: red;">✱</b>'), _t('夜间模式的logo,白天模式下不会显示,尺寸高度建议50-70px之间，宽度等比例自适应'));
    $form->addInput($logoUrldark);

	$favicon = new Typecho_Widget_Helper_Form_Element_Text('favicon', NULL, NULL, _t('favicon地址'), _t('一般为http://www.yourblog.com/image.png,支持 https:// 或 //,留空则不设置favicon'));
    $form->addInput($favicon);
    
	$useimg = new Typecho_Widget_Helper_Form_Element_Text('useimg', NULL, NULL, _t('默认头像'), _t('默认头像地址'));
    $form->addInput($useimg);
    

	$mail = new Typecho_Widget_Helper_Form_Element_Text('mail', NULL, NULL, _t('邮箱地址'), _t('官方邮箱'));
    $form->addInput($mail);
    $mail->setAttribute('class', 'j-setting-content j-setting-global');
    
	$tell = new Typecho_Widget_Helper_Form_Element_Text('tell', NULL, NULL, _t('电话联系'), _t('官方电话'));
    $form->addInput($tell);  
    $tell->setAttribute('class', 'j-setting-content j-setting-global');

    $liuyan = new Typecho_Widget_Helper_Form_Element_Text('liuyan', NULL, NULL, _t('留言板页面缩略名'), _t('填入则显示留言板的入口，不填则不显示'));
    $form->addInput($liuyan);
    
    $tougao = new Typecho_Widget_Helper_Form_Element_Text('tougao', NULL, NULL, _t('投稿页面缩略名'), _t('填入则显示投稿的入口，不填则不显示'));
    $form->addInput($tougao);
    
    $gaoedit = new Typecho_Widget_Helper_Form_Element_Text('gaoedit', NULL, NULL, _t('文章编辑页面缩略名'), _t('不填则不显示'));
    $form->addInput($gaoedit);


    $openimg = new Typecho_Widget_Helper_Form_Element_Select('openimg',array('-1'=>'不开启','0'=>'开启'),'-1','开启后首页列表获取文章缩略图','默认：不开启，无图的时候显示缩略图功能');
    $form->addInput($openimg);

    /**
    $JGravatars = new Typecho_Widget_Helper_Form_Element_Select(
        'JGravatars',
        array(
            'gravatar.helingqi.com/wavatar' => '禾令奇（默认）',
            'www.gravatar.com/avatar' => 'gravatar的www源',
            'cn.gravatar.com/avatar' => 'gravatar的cn源',
            'secure.gravatar.com/avatar' => 'gravatar的secure源',
            'sdn.geekzu.org/avatar' => '极客族',
            'cdn.v2ex.com/gravatar' => 'v2ex源',
            'dn-qiniu-avatar.qbox.me/avatar' => '七牛源[不建议]',
            'gravatar.loli.net/avatar' => 'loli.net源',
        ),
        'gravatar.helingqi.com/wavatar',
        '选择头像源',
        '介绍：不同的源响应速度不同，头像也不同'
    );
    $form->addInput($JGravatars->multiMode());
    **/

    $imghdp = new Typecho_Widget_Helper_Form_Element_Text('imghdp', NULL, NULL, _t('幻灯片侧栏文章ID(2)<b style="color: red;">✱</b>'), _t('在这里填入幻灯片旁边文章ID,输入2个文章ID,<b style="color: red;">限定为2个以内</b>'));
    $form->addInput($imghdp);


	$dhtop = new Typecho_Widget_Helper_Form_Element_Text('dhtop', NULL, NULL, _t('幻灯片文章ID<b style="color: red;">✱</b>'), _t('在这里填入幻灯片轮显文章ID'));
    $form->addInput($dhtop);
  
    $nolist = new Typecho_Widget_Helper_Form_Element_Text('nolist', NULL, NULL, _t('首页不显示某分类'), _t('仅用在首页，首页不显示某分类(仅限一个)，填入<b style="color: red;">缩略名</b>'));
    $form->addInput($nolist);
    
    $_getzt = new Typecho_Widget_Helper_Form_Element_Select('_getzt',array('0'=>'显示','-1'=>'不显示'),'0','首页显示专题','首页幻灯片底部是否显示专题内容');
    $form->addInput($_getzt);
    
    $topnews = new Typecho_Widget_Helper_Form_Element_Text('topnews', NULL, NULL, _t('头部推荐文章ID<b style="color: red;">✱</b>'), _t('首页头部显示的推荐文章ID,幻灯片下方位置<b style="color: red;">限定为4个以内</b>'));
    $form->addInput($topnews);

	$sequid = new Typecho_Widget_Helper_Form_Element_Text('sequid', NULL, NULL, _t('列表推荐文章ID'), _t('首页列表显示的推荐文章ID,默认出现在第12个的位置<b style="color: red;">限定为4个以内</b>'));
    $form->addInput($sequid); 

	$sticky_cids = new Typecho_Widget_Helper_Form_Element_Text('sticky_cids', NULL, NULL, _t('置顶文章ID'), _t('在这里填入置顶文章ID,输入1~2个文章ID,设置太多,会影响美观，推荐1,2个最为合适'));
    $form->addInput($sticky_cids);
    
    $sticky_html = new Typecho_Widget_Helper_Form_Element_Text('sticky_html', NULL, NULL, _t('自定义置顶标题'), _t('自定义置顶标题的文字，不填则显示默认的“置顶”'));
    $form->addInput($sticky_html);



	$footnew = new Typecho_Widget_Helper_Form_Element_Text('footnew', NULL, NULL, _t('底部推荐栏目ID'), _t('输入栏目ID，底部会显示推荐的栏目最新文章,不填则不显示'));
    $form->addInput($footnew);

	$footnewmore = new Typecho_Widget_Helper_Form_Element_Text('footnewmore', NULL, NULL, _t('底部栏目更多推荐链接'), _t('一般为http://www.yourblog.com/,不填则不显示'));
    $form->addInput($footnewmore);
 

	$sidetag = new Typecho_Widget_Helper_Form_Element_Text('sidetag', NULL, NULL, _t('边栏TAG标签更多链接'), _t('一般为http://www.yourblog.com/,不填则不显示'));
    $form->addInput($sidetag);
  
    $liuynes = new Typecho_Widget_Helper_Form_Element_Text('liuynes', NULL, NULL, _t('边栏留言下方我要留言链接'), _t('一般为http://www.yourblog.com/，不填则不显示'));
    $form->addInput($liuynes);

    $huaoff = new Typecho_Widget_Helper_Form_Element_Select('huaoff',array('0'=>'不显示','1'=>'显示'),'0','导航#专题显示','关闭则不显示');
    $form->addInput($huaoff);

	$navsecs = new Typecho_Widget_Helper_Form_Element_Textarea('navsecs', NULL, NULL, _t('首页列表菜单'), _t('幻灯片下方位置,列表菜单设置,自动pjax加载,一般填入4个较为合适,只适合放页面，<b style="color: red;">不适合放分类，分类不能分页</b>，不宜太多,每行1组以"<b style="color: red;">标题|链接</b>"形式)'));
    $form->addInput($navsecs);
    
    $denglu = new Typecho_Widget_Helper_Form_Element_Text('denglu', NULL, NULL, _t('登录页面缩略名<b style="color: red;">✱</b>'), _t('必填，新建页面，配置好缩略名<b style="color: red;">（新建页面的时候填写的缩略名）</b>后填入到这里'));
    $form->addInput($denglu);
    
    $zhuce = new Typecho_Widget_Helper_Form_Element_Text('zhuce', NULL, NULL, _t('注册页面缩略名<b style="color: red;">✱</b>'), _t('必填，新建页面，配置好缩略名<b style="color: red;">（新建页面的时候填写的缩略名）</b>后填入到这里'));
    $form->addInput($zhuce);
    
    $tgcat = new Typecho_Widget_Helper_Form_Element_Text('tgcat', NULL, NULL, _t('投稿指定栏目ID<b style="color: red;">✱</b>'), _t('投稿指定的栏目id,必填'));
    $form->addInput($tgcat);

    $pingopen = new Typecho_Widget_Helper_Form_Element_Select('pingopen',array('0'=>'不开启','1'=>'开启'),'0','开启登录才能评论','默认：不开启');
    $form->addInput($pingopen);

   // $autjifen = new Typecho_Widget_Helper_Form_Element_Text('autjifen', NULL, NULL, _t('积分公式'), _t('(阅读数*0.001)+评论数+(点赞数*5)'));
   // $form->addInput($autjifen);

    $adimg = new Typecho_Widget_Helper_Form_Element_Text('adimg', NULL, NULL, _t('边栏广告图片+链接'), _t('边栏第一个位置,在这里填入你广告图片链接代码：&lt;a rel="nofollow" target="_blank" href=""&gt; &lt;img src="图片链接"&gt;  &lt;/a&gt;'));
    $form->addInput($adimg);
  
    $adimgs = new Typecho_Widget_Helper_Form_Element_Text('adimgs', NULL, NULL, _t('边栏2广告图片+链接'), _t('边栏第二个位置,在这里填入你广告图片链接代码：&lt;a rel="nofollow" target="_blank" href=""&gt; &lt;img src="图片链接"&gt;  &lt;/a&gt;'));
    $form->addInput($adimgs);
    
    $ztadimg = new Typecho_Widget_Helper_Form_Element_Text('ztadimg', NULL, NULL, _t('专题页面广告'), _t('在这里填入你广告图片链接代码：&lt;a rel="nofollow" target="_blank" href=""&gt; &lt;img src="图片链接"&gt;  &lt;/a&gt;'));
    $form->addInput($ztadimg);

	$hdadimg = new Typecho_Widget_Helper_Form_Element_Text('hdadimg', NULL, NULL, _t('幻灯片下方广告'), _t('在这里填入你广告图片链接代码：&lt;a rel="nofollow" target="_blank" href=""&gt; &lt;img src="图片链接"&gt;  &lt;/a&gt;'));
    $form->addInput($hdadimg);
  
    $listadimg = new Typecho_Widget_Helper_Form_Element_Text('listadimg', NULL, NULL, _t('列表广告'), _t('首页列表广告，出现到第12个位置，在这里填入你广告图片链接代码：&lt;a rel="nofollow" target="_blank" href=""&gt; &lt;img src="图片链接"&gt;  &lt;/a&gt;'));
    $form->addInput($listadimg);

	$txtadimg = new Typecho_Widget_Helper_Form_Element_Text('txtadimg', NULL, NULL, _t('文章上方广告'), _t('在这里填入你广告图片链接代码：&lt;a rel="nofollow" target="_blank" href=""&gt; &lt;img src="图片链接"&gt;  &lt;/a&gt;'));
    $form->addInput($txtadimg);

	$txtaddown = new Typecho_Widget_Helper_Form_Element_Text('txtaddown', NULL, NULL, _t('文章下方底部广告'), _t('在这里填入你广告图片链接代码：&lt;a rel="nofollow" target="_blank" href=""&gt; &lt;img src="图片链接"&gt;  &lt;/a&gt;'));
    $form->addInput($txtaddown);
    
    $auadtop = new Typecho_Widget_Helper_Form_Element_Text('auadtop', NULL, NULL, _t('会员页头部广告'), _t(''));
    $form->addInput($auadtop);
    
    $auadside = new Typecho_Widget_Helper_Form_Element_Text('auadside', NULL, NULL, _t('会员页边栏广告'), _t(''));
    $form->addInput($auadside);
	
	$vartheme = new Typecho_Widget_Helper_Form_Element_Text('vartheme', NULL, '#29D', _t('网站主色调'), _t('默认色调: 29D'));
	
    $vartheme->input->setAttribute('class', 'jscolor');
    $form->addInput($vartheme);

    //开启白天黑夜模式
	$night = new Typecho_Widget_Helper_Form_Element_Select('night',array('0'=>'白天模式','1'=>'黑夜模式','2'=>'自动模式'),'2','自动模式:21点为界限，之前为白天模式，之后为黑夜模式');
    $form->addInput($night);
    
    $browser = new Typecho_Widget_Helper_Form_Element_Select('browser',array('0'=>'不开启','1'=>'开启'),'0','微信/QQ提示外部浏览器打开','通过js判断微信、QQ等内置浏览器并在外部浏览器打开');
	$form->addInput($browser);


    //开启表情评论
	$pingimg = new Typecho_Widget_Helper_Form_Element_Select('pingimg',array('pingyes'=>'开启','pingno'=>'关闭'),'pingyes','开启全站表情评论功能');
    $form->addInput($pingimg);
    
  
    //开启评论UserAgent (UA)模式
	$piua = new Typecho_Widget_Helper_Form_Element_Select('piua',array('0'=>'不开启UA','1'=>'开启UA'),'0','是否开启UserAgent(UA)博客评论显示');
    $form->addInput($piua);
    
    //开启阅读文章边栏透明功能
	$postimask = new Typecho_Widget_Helper_Form_Element_Select('postimask',array('0'=>'不开启','1'=>'开启'),'0','是否开启开启阅读文章边栏透明功能');
    $form->addInput($postimask);

	//分页样式显示
	$navpages = new Typecho_Widget_Helper_Form_Element_Select('navpages',array('navshu'=>'分页显示','navmor'=>'更多加载'),'navshu','分页样式显示');
    $form->addInput($navpages);
    
    //关闭封面
	$oncover = new Typecho_Widget_Helper_Form_Element_Select('oncover',array('on'=>'显示','off'=>'更多加载'),'on','是否文章生成封面');
    $form->addInput($oncover);
    
	$webcss = new Typecho_Widget_Helper_Form_Element_Textarea('webcss', NULL, NULL, _t('自定义CSS'));
    $form->addInput($webcss);

	$tophtml = new Typecho_Widget_Helper_Form_Element_Textarea('tophtml', NULL, NULL, _t('页头代码'), _t('添加在页面</head>前,比如：站长平台HTML验证代码,谷歌分析代码'));
    $form->addInput($tophtml);

	$foothtml = new Typecho_Widget_Helper_Form_Element_Textarea('foothtml', NULL, NULL, _t('页脚代码'), _t('添加在页面</body>前,比如：客服工具等js代码，注意 统计代码不在这里填！！'));
    $form->addInput($foothtml);

	$sidebarBlock = new Typecho_Widget_Helper_Form_Element_Checkbox('sidebarBlock', 
    array(
	'ShowAboutMe' => _t('关于我 - (仅在文章页显示)'),
	'ShowAd' => _t('广告'),
	'Showkx' => _t('快讯 - (仅在首页显示)'),
	'ShowSidebarPosts' => _t('热门文章'),
    'ShowAds' => _t('广告'),  
    'Showtag' => _t('热门标签'), 
	'ShowlastComments' => _t('热评文章 - (全站关闭评论则不显示)'),   
    'ShowRecentComments' => _t('最新回复')),
    array('ShowAboutMe','ShowAd','Showkx','ShowSidebarPosts','ShowAds','Showtag','ShowlastComments','ShowRecentComments'), _t('侧边栏显示（个别不显示的地方，需要开启缓存）'));    
    $form->addInput($sidebarBlock->multiMode());

    $footernav = new Typecho_Widget_Helper_Form_Element_Textarea('footernav', NULL, NULL, _t('底部链接（友情链接）'), _t('一行一个链接,格式为：&lt;a rel="nofollow" target="_blank" href="https://www.veimoz.com/"&gt;小灯泡&lt;/a&gt;'));
    $form->addInput($footernav);
  
	$footnav = new Typecho_Widget_Helper_Form_Element_Textarea('footnav', NULL, NULL, _t('页脚版权设置'), _t('页脚版权/备案信息，比如：版权所有 本站内容未经书面许可,禁止一切形式的转载。粤ICP备123456号-1. All rights reserved.'));
    $form->addInput($footnav);

    $zztj = new Typecho_Widget_Helper_Form_Element_Text('zztj', NULL, NULL, _t('网站统计代码'), _t('在这里填入你的网站统计代码,这个可以到百度统计或者cnzz上申请。'));
    $form->addInput($zztj);
    
    $webewm = new Typecho_Widget_Helper_Form_Element_Text('webewm', NULL, NULL, _t('网站底部二维码'), _t('在这里填入你网站底部二维码图片链接'));
    $form->addInput($webewm);
    
    $websum = new Typecho_Widget_Helper_Form_Element_Text('websum', NULL, NULL, _t('网站底部统计数值'), _t('在这里填入你网站底部统计数值'));
    $form->addInput($websum);


	$mobiset = new Typecho_Widget_Helper_Form_Element_Select('mobiset',array('0'=>'不开启','1'=>'开启'),'0','移动底部菜单设置','移动端页脚底部菜单');
    $form->addInput($mobiset);
  
    $navmobi = new Typecho_Widget_Helper_Form_Element_Textarea('navmobi', NULL, NULL, _t('手机端底部菜单<b style="color: red;">✱</b>'), _t('手机端底部高级菜单格式,每行1组以"<b style="color: red;">icon图标|链接</b>"(案例：&lt;i class="ri-home-line" &gt;&lt;/i&gt;|https://www.veimoz.com)形式),顺序不能弄错，icon图标参考：<a href="http://remixicon.com"><b>http://remixicon.com</b></a>'));
    $form->addInput($navmobi); 
  
    $seotitle = new Typecho_Widget_Helper_Form_Element_Text('seotitle', NULL, NULL, _t('首页标题<b style="color: red;">✱</b>'), _t('首页SEO标题，不设置默认显示[设置]里面的站点标题和描述，<b style="color: red;">关键字和描述，请到程序设置</b>'));
    $form->addInput($seotitle);
  
    $closelun = new Typecho_Widget_Helper_Form_Element_Select('closelun',array('0'=>'不开启','1'=>'开启'),'0','开启评论','选择是否开启全网评论，');
    $form->addInput($closelun);	

	$themecompress = new Typecho_Widget_Helper_Form_Element_Select('themecompress',array('0'=>'不开启','1'=>'开启'),'0','HTML压缩功能','是否开启HTML压缩功能,缩减页面代码');
    $form->addInput($themecompress);
  
    $tsmore = new Typecho_Widget_Helper_Form_Element_Select('tsmore',array('0'=>'不开启','1'=>'开启'),'0','文章-阅读全文','手机移动端文章过长截断显示阅读全文');
    $form->addInput($tsmore);

	$Keywordspress = new Typecho_Widget_Helper_Form_Element_Textarea('Keywordspress', NULL, NULL, _t('关键字内链<b style="color: red;">✱</b>'), _t('文章详情页自动关键词链接,每行1组以"<b style="color: red;">关键词|(半角竖线)链接</b>"形式)'));
    $form->addInput($Keywordspress);

	$cdnopen = new Typecho_Widget_Helper_Form_Element_Select('cdnopen',array('0'=>'不开启','1'=>'开启'),'0','开启镜像存储','可不开启，关闭状态下，镜像存储，镜像源，子域名cdn则无效');
    $form->addInput($cdnopen);
	
	$cdnurla = new Typecho_Widget_Helper_Form_Element_Text('cdnurla', NULL, NULL, _t('镜像存储-镜像源'), _t('利用镜像存储做cdn缓存图片文件,格式：www.yourblog.com/ ,记得带上/,不带http或者https,和七牛之类云存储所填的保持一致'));
    $form->addInput($cdnurla);

	$cdnurlb = new Typecho_Widget_Helper_Form_Element_Text('cdnurlb', NULL, NULL, _t('镜像存储-子域名'), _t('利用镜像存储做cdn缓存图片文件,和第三方存储所填的域名保持一致即可,格式：xxx.yourblog.com/ '));
    $form->addInput($cdnurlb);
  
    $imageView = new Typecho_Widget_Helper_Form_Element_Text('imageView', NULL, NULL, _t('文章内第三方存储图片后缀'), _t('第三方存储的处理接口样式，填入则开启,注意开头是否需要以？开头 '));
    $form->addInput($imageView);

 
    $_blog->setAttribute('class', 'j-setting-content j-setting-global');
    $aichat->setAttribute('class', 'j-setting-content j-setting-global');
    
    $_getzt->setAttribute('class', 'j-setting-content j-setting-index');
     
    $logoUrl->setAttribute('class', 'j-setting-content j-setting-global');
    $logoUrldark->setAttribute('class', 'j-setting-content j-setting-global');
    $favicon->setAttribute('class', 'j-setting-content j-setting-global');
    $useimg->setAttribute('class', 'j-setting-content j-setting-global');
    $liuyan->setAttribute('class', 'j-setting-content j-setting-global');
    $tougao->setAttribute('class', 'j-setting-content j-setting-global');
    $gaoedit->setAttribute('class', 'j-setting-content j-setting-global');
    
    $openimg->setAttribute('class', 'j-setting-content j-setting-global');
    /**$JGravatars->setAttribute('class', 'j-setting-content j-setting-global');**/
    $imghdp->setAttribute('class', 'j-setting-content j-setting-index');
    $dhtop->setAttribute('class', 'j-setting-content j-setting-index');
    $nolist->setAttribute('class', 'j-setting-content j-setting-index');
    $topnews->setAttribute('class', 'j-setting-content j-setting-index');
    $sequid->setAttribute('class', 'j-setting-content j-setting-index');
    
    $sticky_cids->setAttribute('class', 'j-setting-content j-setting-index');
    $sticky_html->setAttribute('class', 'j-setting-content j-setting-index');
    $footnew->setAttribute('class', 'j-setting-content j-setting-index');
    $footnewmore->setAttribute('class', 'j-setting-content j-setting-index');
    $sidetag->setAttribute('class', 'j-setting-content j-setting-index');
    $liuynes->setAttribute('class', 'j-setting-content j-setting-index');
    $huaoff->setAttribute('class', 'j-setting-content j-setting-index');
    $navsecs->setAttribute('class', 'j-setting-content j-setting-index');
    $denglu->setAttribute('class', 'j-setting-content j-setting-user');
    $zhuce->setAttribute('class', 'j-setting-content j-setting-user');
    $tgcat->setAttribute('class', 'j-setting-content j-setting-user');
    $pingopen->setAttribute('class', 'j-setting-content j-setting-user');
    $adimg->setAttribute('class', 'j-setting-content j-setting-ads');
    $adimgs->setAttribute('class', 'j-setting-content j-setting-ads');
    $hdadimg->setAttribute('class', 'j-setting-content j-setting-ads');
    $ztadimg->setAttribute('class', 'j-setting-content j-setting-ads');
    $listadimg->setAttribute('class', 'j-setting-content j-setting-ads');
    $txtadimg->setAttribute('class', 'j-setting-content j-setting-ads');
    $txtaddown->setAttribute('class', 'j-setting-content j-setting-ads');
    $auadtop->setAttribute('class', 'j-setting-content j-setting-ads');
    $auadside->setAttribute('class', 'j-setting-content j-setting-ads');
    $vartheme->setAttribute('class', 'j-setting-content j-setting-color');
    $night->setAttribute('class', 'j-setting-content j-setting-color');
   
    $browser->setAttribute('class', 'j-setting-content j-setting-color');  
    $pingimg->setAttribute('class', 'j-setting-content j-setting-color');
    $piua->setAttribute('class', 'j-setting-content j-setting-color');
    $postimask->setAttribute('class', 'j-setting-content j-setting-color');
    $navpages->setAttribute('class', 'j-setting-content j-setting-color');
    $oncover->setAttribute('class', 'j-setting-content j-setting-color');
    $webcss->setAttribute('class', 'j-setting-content j-setting-color');
    $tophtml->setAttribute('class', 'j-setting-content j-setting-color');
    $foothtml->setAttribute('class', 'j-setting-content j-setting-color');
    $sidebarBlock->setAttribute('class', 'j-setting-content j-setting-aside');
    $footernav->setAttribute('class', 'j-setting-content j-setting-aside');
    $footnav->setAttribute('class', 'j-setting-content j-setting-aside');
    $zztj->setAttribute('class', 'j-setting-content j-setting-aside');
    $webewm->setAttribute('class', 'j-setting-content j-setting-aside');
    $websum->setAttribute('class', 'j-setting-content j-setting-aside');
    $mobiset->setAttribute('class', 'j-setting-content j-setting-mox');
    $navmobi->setAttribute('class', 'j-setting-content j-setting-mox');
    $seotitle->setAttribute('class', 'j-setting-content j-setting-seo');
    $closelun->setAttribute('class', 'j-setting-content j-setting-seo');
    $themecompress->setAttribute('class', 'j-setting-content j-setting-seo');
    $tsmore->setAttribute('class', 'j-setting-content j-setting-seo');
    $Keywordspress->setAttribute('class', 'j-setting-content j-setting-seo');
    $cdnopen->setAttribute('class', 'j-setting-content j-setting-speed');
    $cdnurla->setAttribute('class', 'j-setting-content j-setting-speed');
    $cdnurlb->setAttribute('class', 'j-setting-content j-setting-speed');
    $imageView->setAttribute('class', 'j-setting-content j-setting-speed');

}


//后台编辑器添加功能
function themeFields($layout) {
  
    $img = new Typecho_Widget_Helper_Form_Element_Text('img', NULL, NULL, _t('封面图'), _t('在这里填入一个图片 URL 地址, 以添加显示本文的缩略图，留空则显示默认缩略图'));
    $img->input->setAttribute('class', 'w-100 setfb set_img');
    $layout->addItem($img);

	$bimg = new Typecho_Widget_Helper_Form_Element_Text('bimg', NULL, NULL, _t('封面大图'), _t('在这里填入一个图片 URL 地址, 以添加显示本文的大封面缩略图，留空则显示默认小缩略图'));
    $bimg->input->setAttribute('class', 'w-100 setfb');
    $layout->addItem($bimg);  

	  //单图/大图/三图显示
    $abcimg = new Typecho_Widget_Helper_Form_Element_Select('abcimg',
        array('able' => _t('单图'),
              'bable' => _t('大图'),
		      'mable' => _t('三图'),
        ),
        'able', _t('单图/大图/三图显示'), _t('默认单图，注意三图确保发布的文章必须有三张以上的图片附件'));
    $abcimg->input->setAttribute('class', 'setfb');    
    $layout->addItem($abcimg);
  
    $tktit = new Typecho_Widget_Helper_Form_Element_Text('tktit', NULL, NULL, _t('SEO标题'), _t('在这里填入文章的标题，不填则为默认系统'));
    $tktit->input->setAttribute('class', 'w-100 setfb');
    $layout->addItem($tktit);
  
    $tkeyc = new Typecho_Widget_Helper_Form_Element_Text('tkeyc', NULL, NULL, _t('SEO关键字'), _t('在这里填入文章的关键字，不填则为默认系统（与SE0描述同时填入则会出现文章SEO优化描述）'));
    $tkeyc->input->setAttribute('class', 'w-100 setfb');
    $layout->addItem($tkeyc);
  
    $tdesc = new Typecho_Widget_Helper_Form_Element_Text('tdesc', NULL, NULL, _t('SEO描述'), _t('在这里填入文章的描述内容（同时也是自定义描述内容）不填则为默认系统'));
    $tdesc->input->setAttribute('class', 'w-100 setfb');
    $layout->addItem($tdesc);

    $videourl = new Typecho_Widget_Helper_Form_Element_Text('videourl', NULL, NULL, _t('视频链接'), _t('在这里填入一个视频 URL 地址, 以添加显示视频，留空则没有'));
    $videourl->input->setAttribute('class', 'w-100 setfb');
    $layout->addItem($videourl); 
    
	$catalog = new Typecho_Widget_Helper_Form_Element_Radio('catalog', 
    array(true => _t('启用'),
    false => _t('关闭')),
    false, _t('文章目录'), _t('默认关闭，启用则会在文章内显示“文章目录”（若文章内无任何 h2 标题，则不显示目录）'));
    $layout->addItem($catalog);
   
    $pinglun = new Typecho_Widget_Helper_Form_Element_Radio('pinglun', 
    array('1' => _t('启用'),
    '0' => _t('关闭')),
    '1', _t('文章评论'), _t('默认开启，如后台设置了评论关闭，则不会显示评论，注意：主题后台配置关闭的话，则这里会失效'));
    $layout->addItem($pinglun);
  
    $Copyrightnew = new Typecho_Widget_Helper_Form_Element_Select('Copyrightnew', 
    array('0' => _t('原创版权'),
    '1' => _t('投稿版权'),
    '2' => _t('转载文章')),
    '1', _t('投稿版权'), _t('版权类型默认：投稿版权，文章版权类型，可以在主题设置里面新增和编辑版权类型，'));
    $Copyrightnew->input->setAttribute('class', 'setfb');    
    $layout->addItem($Copyrightnew); 
  
    $Copyurl = new Typecho_Widget_Helper_Form_Element_Text('Copyurl', NULL, NULL, _t('转载文章来源'), _t('在这里填入一个文章 URL 地址，留空则没有'));
    $Copyurl->input->setAttribute('class', 'w-100 setfb');
    $layout->addItem($Copyurl);  
}

$Version =themeVersion();

/**
 * 获取主题版本号
 */
function themeVersion() {
    $info = Typecho_Plugin::parseInfo(__DIR__ . '/index.php');
    return $info['version'];
}

Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('myyodux','one');
class myyodux { public static function one($con,$obj,$text) { $text= empty($text)?$con:$text; 
if(!$obj->is('single'))
{ 
$text= preg_replace("/\[scode\](.*?)\[\/scode\]/sm",'',$text);
$text= preg_replace("/\[button\](.*?)\[\/button\]/sm",'',$text);
$text= preg_replace("/\[hide\](.*?)\[\/hide\]/sm",'<div class="reply2view"><i class="ri-rotate-lock-fill ri-lg"></i> 此处内容需要评论回复后</div>',$text);
}
return $text;
}}

/**
 * 文章解析
 */
Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('contents','parseContent'); 
Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('contents','excerptEx');



function themeInit($archive) {

if ($archive->request->getPathInfo() == "/sp/kx") {
    _getkx($archive);
} 
if ($archive->request->getPathInfo() == "/sp/gus") {
    _getgus($archive);
}     
if ($archive->request->getPathInfo() == "/sp/kxs") {
    _getkxs($archive);
}
if ($archive->request->getPathInfo() == "/sp/kan") {
    _getkan($archive);
} 
if ($archive->request->getPathInfo() == "/zt") {
    _getzts($archive);
} 
if ($archive->request->getPathInfo() == "/sp/ex") {
    _getex($archive);
} 
if ($archive->request->getPathInfo() == "/sp/alltu") {
    _getalltu($archive);
}
if ($archive->request->getPathInfo() == "/sp/soblur") {
    _getsoblur($archive);
}
if ($archive->request->getPathInfo() == "/sp/intro") {
    _getintro($archive);
}
if ($archive->request->getPathInfo() == "/sp/sequ") {
    _getsequ($archive);
}
if ($archive->request->getPathInfo() == "/sp/hotwen") {
    _gethotwen($archive);
}
if ($archive->request->getPathInfo() == "/sp/abautor") {
    _getabautor($archive);
}
if ($archive->request->getPathInfo() == "/sp/hotping") {
    _gethotping($archive);
}
if ($archive->request->getPathInfo() == "/sp/msg") {
    _getmsg($archive);
}
if ($archive->request->getPathInfo() == "/sp/delmsg") {
    _getdelmsg($archive);
}


    
/* 强制用户关闭反垃圾保护 */
Helper::options()->commentsAntiSpam = false;
/* 强制用户关闭检查来源URL */
Helper::options()->commentsCheckReferer = false;
/* 强制用户强制要求填写邮箱 */
 Helper::options()->commentsRequireMail = true;
/* 强制用户强制要求无需填写url */
Helper::options()->commentsRequireURL = false;
Helper::options()->commentsMaxNestingLevels = '5'; //最大嵌套层数
Helper::options()->commentsOrder = 'DESC'; //将最新的评论展示在前
Helper::options()->commentsHTMLTagAllowed = '<a href=""> <img src=""> <img src="" class=""> <code> <del>';

if ($archive->is('single')) {
        if ($archive->fields->catalog) {
            $archive->content = createCatalog($archive->content);
        }
        
        $archive->content = get_glo_keywords($archive->content);
        $archive->content = stcdn($archive->content);
        
}


}




?>


