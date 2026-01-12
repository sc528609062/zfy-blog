<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 针对Spimes主题的用户会员社区开发插件
* <div class="FansSet"><a href="https://www.veimoz.com/" target="_blank">官网问题反馈</a>&nbsp;</div><style>.FansSet{margin-top: 5px;}.FansSet a{background: #4DABFF;padding: 5px;color: #fff;}</style>
 * @package Deng 插件 for Spimes主题
 * @author 微兔设计
 * @version 1.0
 * @link https://www.veimoz.com/
 */
class Deng_Plugin extends Widget_Abstract_Users implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
	
	  self::install();	
	  
	  /*添加文章修改分类路由*/
      Helper::addRoute("Deng_Special","/Deng/Special","Deng_Action",'Special');
      Helper::addRoute("Deng_Action","/Deng/Action","Deng_Action",'action');
      Helper::addRoute("Deng_chattion","/Deng/chattion","Deng_Ai",'chattion');
      Helper::addRoute("Deng_comtion","/Deng/comtion","Deng_Ai",'comtion');
      Helper::addRoute("Deng_aitag","/Deng/aitag","Deng_Ai",'aitag');
      
		
      Typecho_Plugin::factory('Widget_Register')->register = array('Deng_Plugin', 'zhuce'); 
	  Typecho_Plugin::factory('Widget_Register')->finishRegister = array('Deng_Plugin', 'zhucewan');
      Typecho_Plugin::factory('Widget_Login')->loginSucceed = array('Deng_Plugin', 'Loginwan');
      Typecho_Plugin::factory('Widget_Contents_Post_Edit')->finishPublish = array('Deng_Plugin', 'publish_push');
      Typecho_Plugin::factory('Widget_Contents_Post_Edit')->finishSave = array('Deng_Plugin', 'save_push');
      
      
      
      
      //专题
      Typecho_Plugin::factory('admin/write-post.php')->option = array('Deng_Plugin', 'setFeeContent');
      //发布文章的时候执行
      //Typecho_Plugin::factory('Widget_Contents_Post_Edit')->finishPublish = array('Deng_Plugin', "updateFeeContent");

	  //输入密码or页面跳转限制
      Typecho_Plugin::factory('admin/footer.php')->end = array('Deng_Plugin', 'footerjs');
      //置顶功能
      Typecho_Plugin::factory('Widget_Archive')->indexHandle = array('Deng_Plugin', 'sticky');
 
	  //Typecho_Plugin::factory('Widget_Users_Profile')->filter = array('Deng_Plugin', 'usertip');
      //Typecho_Plugin::factory(' Widget_Users_Edit')->filter = array('Deng_Plugin', 'usertip');
      $index = Helper::addMenu('Deng插件');
      Helper::addPanel($index, 'Deng/html/profile.php', '会员信息', '管理会员信息', 'administrator');
      Helper::addPanel($index, 'Deng/MetasExtend.php', '分类栏目设置', '管理分类的扩展插件', 'administrator');
      Helper::addPanel($index, 'Deng/SpecialExtend.php', '专题内容设置', '管理分类的扩展插件', 'administrator');
      
      
      Helper::addRoute("Deng_aition","/Deng/aition","Deng_Ai",'aition');
      
      
      //找回密码
      Helper::addRoute('Deng_forgot', '/Deng/forgot', 'Deng_Widget', 'doForgot');
      Helper::addRoute('Deng_reset', '/Deng/reset', 'Deng_Widget', 'doReset');
      return _t('请配置此插件的SMTP信息, 以使您的插件生效');
    }
    
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){
        
        Helper::removeRoute('Deng_reset');
        Helper::removeRoute('Deng_forgot');
        $index = Helper::removeMenu('Deng插件');
		Helper::removePanel($index, 'Deng/html/profile.php');
		Helper::removePanel($index, 'Deng/MetasExtend.php');
		Helper::removePanel($index, 'Deng/SpecialExtend.php');
        Helper::removeRoute('Deng_Action');
        Helper::removeRoute('Deng_Special');
        Helper::removeRoute('Deng_chattion');
        Helper::removeRoute('Deng_comtion');
        return _t('插件已被禁用');
        
    }
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {

    $yonghuzu = new Typecho_Widget_Helper_Form_Element_Radio('yonghuzu',array(
      'visitor' => _t('访问者'),
      'subscriber' => _t('关注者'),
      'contributor' => _t('贡献者'),
      'editor' => _t('编辑'),
      'administrator' => _t('管理员')
    ),'subscriber',_t('注册用户默认用户组设置'),_t('<p class="description">
不同的用户组拥有不同的权限，具体的权限分配表请<a href="http://docs.typecho.org/develop/acl" target="_blank" rel="noopener noreferrer">参考这里</a>.<br>---------------<br>1,编辑or管理员可以进入后台编辑管理权限<br>2,贡献者可以进行投稿，发布，无权限进入后台，视为认证用户<br>3,关注者为普通注册用户,无发文章权限</p>'));
    $form->addInput($yonghuzu); 
    
    //拓展设置
    $tuozhan = new Typecho_Widget_Helper_Form_Element_Checkbox('tuozhan', 
    array('register-nb' => _t('勾选该选项后台注册功能将可以直接设置注册密码(默认登录注册页面)'),
),
    array(), _t('拓展设置'), _t('仅限程序后台页面有效'));
    $form->addInput($tuozhan->multiMode());
   
    //密码找回
    
        $host = new Typecho_Widget_Helper_Form_Element_Text('host', NULL, '', _t('服务器(SMTP)'), _t('如: smtp.qq.com'));
        $port = new Typecho_Widget_Helper_Form_Element_Text('port', NULL, '465', _t('端口'), _t('如: 25、465(SSL)、587(SSL)'));

        $username = new Typecho_Widget_Helper_Form_Element_Text('username', NULL, '', _t('帐号'), _t('如: hello@example.com'));
        $password = new Typecho_Widget_Helper_Form_Element_Password('password', NULL, NULL, _t('密码'));

        $secure = new Typecho_Widget_Helper_Form_Element_Select('secure',array(
            'ssl' => _t('SSL'),
            'tls' => _t('TLS'),
            'none' => _t('无')
        ), 'ssl', _t('安全类型'));

        $form->addInput($host);
        $form->addInput($port);
        $form->addInput($username);
        $form->addInput($password);
        $form->addInput($secure);
        
        $Prompt = new Typecho_Widget_Helper_Form_Element_Textarea('Prompt', NULL, _t(''), _t('AI生文指令,Gpt和文心通用'));
        $form->addInput($Prompt);
        
        $API_KEY = new Typecho_Widget_Helper_Form_Element_Text('API_KEY', NULL, _t(''), _t('Chatgpt API_KEY (客服需配置)'));
        $form->addInput($API_KEY);
        $API_URL = new Typecho_Widget_Helper_Form_Element_Text('API_URL', NULL, _t(''), _t('Chatgpt API_URL (客服需配置)'));
        $form->addInput($API_URL);
        

        
        $aiPrompt = new Typecho_Widget_Helper_Form_Element_Textarea('aiPrompt', NULL, _t(''), _t('ChatGptAI客服训练 (给AI客服简单训练调试)'));
        $form->addInput($aiPrompt);
        

      
//    $tcat = new Typecho_Widget_Helper_Form_Element_Text('tcat', NULL, NULL, _t('特例分类'), _t('在这里填入一个分类mid，分类间用英文的半角逗号隔开如【1,2】，这些分类贡献者发布文章必须需要经过审核！'));
//    $form->addInput($tcat);
    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
public static function personalConfig(Typecho_Widget_Helper_Form $form){}
    
	
 public static function install()
    {  

        try {
            return self::addTable();
        } catch (Typecho_Db_Exception $e) {
            if ('42S01' == $e->getCode()) {
                $msg = '数据库已存在!';
                return $msg;
            }
        }
    }	
	
	public static function addTable()
	    {
	        $db = Typecho_Db::get();
			$prefix = $db->getPrefix();
			$db->query('ALTER TABLE `'.$prefix.'contents` ADD `views` INT(10) DEFAULT 0;');
			$db->query('ALTER TABLE `'.$prefix.'users` ADD `uviews` INT(10) DEFAULT 0;');
			$db->query('ALTER TABLE `'.$prefix.'contents` ADD `agree` INT(10) NOT NULL DEFAULT 0;');
			$db->query('ALTER TABLE `'.$prefix.'users` ADD `points` INT(10) DEFAULT NULL;'); 
			$db->query('ALTER TABLE `'.$prefix.'users` ADD `pointy` INT(10) DEFAULT NULL;'); 
			$db->query('ALTER TABLE `'.$prefix.'users` ADD `introduce` varchar(200) DEFAULT NULL;'); 
            $db->query("ALTER TABLE `".$prefix."metas` ADD `seotitle` VARCHAR(255) DEFAULT NULL");
            $db->query("ALTER TABLE `".$prefix."metas` ADD `seokey` VARCHAR(255) DEFAULT NULL");
            $db->query("ALTER TABLE `".$prefix."metas` ADD `seodesc` text");
            $db->query("ALTER TABLE `".$prefix."metas` ADD `catepages` VARCHAR(255) DEFAULT 1");
            $db->query("ALTER TABLE `".$prefix."metas` ADD `cateico` VARCHAR(255) DEFAULT NULL");

            $db->query('ALTER TABLE `'.$db->getPrefix().'contents` ADD `sid` INT(10) DEFAULT NULL;');
			$sql = "CREATE TABLE `{$prefix}special` (
			        `sid` int UNSIGNED NOT NULL AUTO_INCREMENT,
                    `spname` varchar(255) COMMENT '专题名称',
                    `spdep` varchar(255) COMMENT '专题描述',
                    `spimg` varchar(255) COMMENT '专题封面',
                    `spview` INT(10)  COMMENT '专题阅读',
                    PRIMARY KEY (`sid`)
                )DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
            $db->query($sql);
			
			
	    }
	
    /**
     * 插件实现方法
     * 
     * @access public
     * @return void
     */
public static function zhuce($v) {
  /*获取插件设置*/
  $yonghuzu = Typecho_Widget::widget('Widget_Options')->plugin('Deng')->yonghuzu;
  $hasher = new PasswordHash(8, true);
  /*判断注册表单是否有密码*/
  if(isset(Typecho_Widget::widget('Widget_Register')->request->password)){
    /*将密码设定为用户输入的密码*/
    $generatedPassword = Typecho_Widget::widget('Widget_Register')->request->password;
  }else{
    /*用户没输入密码，随机密码*/
    $generatedPassword = Typecho_Common::randString(7);
  }
  /*将密码设置为常量，方便下个函数adu()直接获取*/
  define('passd', $generatedPassword);
  /*将密码加密*/
  $wPassword = $hasher->HashPassword($generatedPassword);
  /*设置用户密码*/
  $v['password']=$wPassword;
  /*将注册用户默认用户组改为插件设置的用户组*/
  $v['group']=$yonghuzu;
  /*返回注册参数*/
  return $v;
}
public static function zhucewan($obj) {
 /*获取密码*/
 $wPassword=passd;
 /*登录账号*/
 $obj->user->login($obj->request->name,$wPassword);
 /*删除cookie*/
 Typecho_Cookie::delete('__typecho_first_run');
 Typecho_Cookie::delete('__typecho_remember_name');
 Typecho_Cookie::delete('__typecho_remember_mail');
 /*发出提示*/
 $obj->widget('Widget_Notice')->set(_t('用户 <strong>%s</strong> 已经成功注册, 密码为 <strong>%s</strong>', $obj->screenName, $wPassword), 'success');
 /*跳转地址(后台)*/
 if (NULL != $obj->request->referer) {
 $obj->response->redirect($obj->request->referer);
 }else if(NULL != $obj->request->tz){
   if (Helper::options()->rewrite==0){$authorurl=Helper::options()->rootUrl.'/index.php/author/';}else{$authorurl=Helper::options()->rootUrl.'/author/';}
  $obj->response->redirect($authorurl.$obj->user->uid);
 }else{
 $obj->response->redirect($obj->options->adminUrl);
 }
}
public static function Loginwan($obj) {
    
 /*跳转地址(后台)*/
 if (NULL != $obj->request->referer) {
 $obj->response->redirect($obj->request->referer);
 }
 else{
 $obj->response->redirect($obj->options->adminUrl);
 }
 
}


public static function footerjs(){
    
echo '<link rel="stylesheet" href="'.Helper::options()->rootUrl.'/usr/themes/spimes/assets/css/setting.fb.css">';     
    
   if (!empty(Typecho_Widget::widget('Widget_Options')->plugin('Deng')->tuozhan) && in_array('register-nb',  Typecho_Widget::widget('Widget_Options')->plugin('Deng')->tuozhan)){
?>
<script>
var Denghtml='<p><label for="password" class="sr-only">密码</label><input type="password"  id="password" name="password" placeholder="输入密码" class="text-l w-100" autocomplete="off" required></p><p><label for="confirm" class="sr-only">确认密码</label><input type="password"  id="confirm" name="confirm" placeholder="再次输入密码" class="text-l w-100" autocomplete="off" required></p>';
$("#mail").parent().after(Denghtml);
</script>
<?php
   }
}
 
 
/**
  * 把设置装入文章编辑页
  *
  * @access public
  * @return void
  */
 public static function setFeeContent($post) {
  //$db = Typecho_Db::get();
  //$row = $db->fetchRow($db->select('table.special')->from('table.contents')->where('cid = ?', $post->cid));
  //$keyword = isset($row['keyword']) ? $row['keyword'] : '';
  //$downloadaddress = isset($row['downloadaddress']) ? $row['downloadaddress'] : '';
   $str='';
   $checked='';
   $sid='';
   $db = Typecho_Db::get();
   

   $postnum=$db->fetchRow($db->select()->from ('table.contents')->where ('cid=?',$post->cid));
   if($postnum){$sid=$postnum['sid'];}
   
   $result=$db->fetchAll($db->select()->from ('table.special'));
   if($result){
        
        foreach($result as $val){
        $checked='';    
        if($sid==$val['sid']){ $checked='checked="true"'; }       
        $str = $str.'<li><input type="checkbox" id="category-1" value="'.$val['sid'].'" name="special" '.$checked.'><label for="category-1">'.$val['spname'].'</label></li>';
        }
        
        $html = '<section id="speci" class="typecho-post-option category-option"><label class="typecho-label">专题内容设置</label><ul>'.$str.'</ul></section>';
         _e($html);
   }
  
 } 

 /**
     * 发布文章时使用接口推送
     * 
     * @access public
     * @return void
     */
public static function publish_push($content, $edit)
    {
  
        $db = Typecho_Db::get();
        
        $special = $edit->request->get('special', NULL);
        
        $siteUrl = Typecho_Widget::widget('Widget_Options')->index;
        $renow = Typecho_Widget::widget('Widget_Contents_Post_Edit')->request->renow;
        $content['cid'] = $edit->cid;
        $content['slug'] = $edit->slug;
        
        //添加到专题更新
		$sql = $db->update('table.contents')->rows(array('sid' => $special))->where('cid = ?', $edit->cid);
		$db->query($sql);
        
        
        //用以判断跳转地址
        $authorId = $edit->authorId;
        $rewrite = Typecho_Widget::widget('Widget_Options')->rewrite;
        if($rewrite==0){ $authorurl = $siteUrl.'index.php/author/'.$authorId; }
        else{  $authorurl = $siteUrl.'/author/'.$authorId; }
        
        $row = $db->fetchRow($db->select('group')->from('table.users')->where('uid = ?', $authorId));
        if($row['group']=='administrator' ||$row['group']=='editor' ){
            $authorurl = $siteUrl.'/admin/manage-posts.php';
        }
        //https://www.acgmkan.com/action/1/author/1
        
        if($renow){
            exit('<script>location.href="'.$siteUrl.'/author/'.$authorId.'";</script>');
        }
        else{
        
        }
        
    } 
 
public static function save_push($content, $edit)
{    
     $siteUrl = Typecho_Widget::widget('Widget_Options')->index;
     $renow = Typecho_Widget::widget('Widget_Contents_Post_Edit')->request->renow;
     $authorId = $edit->authorId;
     $db = Typecho_Db::get();
    
        $rewrite = Typecho_Widget::widget('Widget_Options')->rewrite;
        if($rewrite==0){ $authorurl = $siteUrl.'index.php/author/'.$authorId; }
        else{  $authorurl = $siteUrl.'/author/'.$authorId; }
        
        $row = $db->fetchRow($db->select('group')->from('table.users')->where('uid = ?', $authorId));
        if($row['group']=='administrator' ||$row['group']=='editor' ){
            $authorurl = $siteUrl.'/admin/manage-posts.php';
        }
    
 
    if($renow){
            exit('<script>location.href="'.$siteUrl.'/author/'.$authorId.'";</script>');
        }
        else{ }
}
 
 
/**
     * 选取置顶文章
     * 
     * @access public
     * @param object $archive, $select
     * @return void
*/
    public static function sticky($archive, $select)
    {
        $config  = Typecho_Widget::widget('Widget_Options');
        $sticky_cids = $config->sticky_cids ? explode(',', strtr($config->sticky_cids, ' ', ',')) : '';
        if (!$sticky_cids) return;

        $db = Typecho_Db::get();
        $paded = $archive->request->get('page', 1);
        $sticky_html = $config->sticky_html ? $config->sticky_html : "置顶";
        $sticky_html='<span class="badge arc_v6">'.$sticky_html.'</span>';
        foreach($sticky_cids as $cid) {
          if ($cid && $sticky_post = $db->fetchRow($archive->select()->where('cid = ?', $cid))) {
              if ($paded == 1) {                               // 首頁 page.1 才會有置頂文章
                $sticky_post['sticky'] = $sticky_html;
                $archive->push($sticky_post);                  // 選取置頂的文章先壓入
              }
              $select->where('table.contents.cid != ?', $cid); // 使文章不重覆
          }
        }
    } 
    
    
    //查询调用  前台调用：Deng_Plugin::getIntel();
    public static function getIntel(){
        
        $options = Typecho_Widget::widget('Widget_Options')->plugin('Deng');
        $aihtml='';

        $api_key=$options->API_KEY;
        $base_url = $options->API_URL;
        
        if($api_key&&$base_url){
        
        
        echo '<link rel="stylesheet" href="'.Helper::options()->rootUrl.'/usr/plugins/Deng/call/css/chat_box.css" type="text/css" media="all">';     
        
        $callhtml = $vhtml='<link href="'.Helper::options()->rootUrl.'/usr/plugins/Deng/call/plugins/perfect-scrollbar/css/perfect-scrollbar.min.css" rel="stylesheet"/>
    <script src="'.Helper::options()->rootUrl.'/usr/plugins/Deng/call/plugins/perfect-scrollbar/js/perfect-scrollbar.jquery.min.js" type="text/javascript"></script>
<script src="'.Helper::options()->rootUrl.'/usr/plugins/Deng/call/plugins/backstretch/backstretch.js" type="text/javascript"></script>
<script src="'.Helper::options()->rootUrl.'/usr/plugins/Deng/call/js/app.js"></script>
<!-- PAGE CONTENT BEGINS -->
<div class="container perfect_scrollbar" id="container">
    <div class="pvr_chat_button">
        <span>在线咨询</span>
    </div>
    <div class="pvr_chat_wrapper">
        <div class="pvr_chat_content">
            <!--<div class="close_chat">
                <i class="material-icons">x</i>
            </div>-->
            <div class="chat_header">
                <div class="pvr-user-w with-status status-green">
                    <div class="pvr-user-avatar-w">
                        <div class="user-avatar">
                            <img alt="" src="'.Helper::options()->rootUrl.'/usr/plugins/Deng/call/img/uni_ai.png">
                        </div>
                    </div>
                    <div class="user-name">
                        <h6 class="user-title">
                        在线咨询
                        </h6>
                        <div class="user-role">
                        以确保在用户需要帮助时能够及时提供解答和技术支持
                        </div>
                    </div>
                </div>
            </div>
            <div class="chat-calls theme_1">
                '.$aihtml.'
                <div class="call">
                    <div class="call-content">
                    您好，这里是'.Helper::options()->title.'，请问有什么能帮到您？
                    </div>
                </div>
            </div>
            <div class="chat-controls">
            <input class="call-input" id="call-input" placeholder="请描述您的问题或输入" type="text" autocomplete="off">
            <button class="ai_box_btn form__btn " type="button"><span>Send</span></button>
            </div>
        </div>
    </div>
</div>
<!-- END CONTENT BEGINS -->
';
        
        echo $callhtml;
        
        }
    }
    
    
    
}
