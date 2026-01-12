<?php

function getcateid($id){  //获取栏目id
   $db = Typecho_Db::get();
   $postnum=$db->fetchRow($db->select()->from ('table.relationships')->where ('cid=?',$id));
   return  $postnum['mid']; 
}

function catename($cateid){  //获取栏目名
   $db = Typecho_Db::get();
   $postnum=$db->fetchRow($db->select()->from ('table.metas')->where ('mid=?',$cateid)->where('type=?', 'category'));
   return  $postnum['name']; 
}

function geipuid($cid){  //文章获取用户id
  Typecho_Widget::widget('Widget_Archive@geipuid'.$cid.'', 'pageSize=1&type=post', 'cid='.$cid)->to($jis);
  $useruid=$jis->author->uid;
  return  $useruid; 
}

function getuname($uid){  //会员id获取用户名
   $db = Typecho_Db::get();
   $postnum=$db->fetchRow($db->select()->from ('table.users')->where ('uid=?',$uid));
   return  $postnum['screenName']; 
}

function getumail($uid){  //会员id获取用户邮箱
   $db = Typecho_Db::get();
   $postnum=$db->fetchRow($db->select()->from ('table.users')->where ('uid=?',$uid));
   return  $postnum['mail']; 
}


/**
* 网站主色调
*/
function vartheme() {
     $vartheme = Helper::options()->vartheme;
     echo $vartheme ? $vartheme : '29d';
}

function GetHttp() {
if (isset($_SERVER['HTTPS']) && ('1' == $_SERVER['HTTPS'] || 'on' == strtolower($_SERVER['HTTPS']))) {
return "https://";
} elseif (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'] )) {
return "https://";
}
return "http://";
}

/**
* 判断主题是否为博客模式
*/
function _blog() {
     $_blog = Helper::options()->_blog;
     if($_blog==0){
         return true; //开启会员
     }
     else{ return false; //关闭会员
     }
}


function _getzt() {
     $_getzt = Helper::options()->_getzt;
     if($_getzt==0){
         return true; //开启
     }
     else{ return false; //关闭
     }
}


//权限判断是否显示
function uav($a){
    if($a!==null){
    $user = Typecho_Widget::widget('Widget_User');
    $uid = $user->hasLogin() ? $user->uid : 0;
    $type=uacc($uid); //判断等级权限
    //判断用户当前登陆的权限是否符合指定的权限，如果符合返回true，如果不符合返回false
    if($type==$a){ return true; }
    else{ return false; }
    }
    else{  //变量a为空，属于没登录，判断是否登录
    $user = Typecho_Widget::widget('Widget_User');
    $uid = $user->hasLogin() ? $user->uid : 0;
    if($uid==0){return false;} //==0，说明未登录，返回假
    else{ return true; }
        
    }
}


// 判断会员的权限
function uacc($id){
    $db   = Typecho_Db::get();
    @$prow = $db->fetchRow($db->select('group')->from('table.users')->where('uid = ?', $id));
    @$group = $prow['group'];
    // 变判断的值为常量
    switch($group){
    case 'subscriber': //关注用户
    return 4;
    break;   // 跳出循环
    case 'administrator':
    return 1;
    break;
    case 'editor':
    return 2;
    break;
    case 'contributor':
    return 3;
    break;
    default:return 0;
    }
}




/**
* 阅读统计
* 调用<?php get_post_view($this); ?>
*/
function Postviews($archive) {
    $db = Typecho_Db::get();
    $cid = $archive->cid;
    if (!array_key_exists('views', $db->fetchRow($db->select()->from('table.contents')))) {
        $db->query('ALTER TABLE `'.$db->getPrefix().'contents` ADD `views` INT(10) DEFAULT 0;');
    }
    $exist = $db->fetchRow($db->select('views')->from('table.contents')->where('cid = ?', $cid))['views'];
    
    
    if ($archive->is('single')) {
        //站内阅读统计
        $cookie = Typecho_Cookie::get('contents_views');
        $cookie = $cookie ? explode(',', $cookie) : array();
        if (!in_array($cid, $cookie)) {
            $db->query($db->update('table.contents')
                ->rows(array('views' => (int)$exist+1))
                ->where('cid = ?', $cid));
            $exist = (int)$exist+1;
            array_push($cookie, $cid);
            $cookie = implode(',', $cookie);
            Typecho_Cookie::set('contents_views', $cookie);
        }
        //站内专题统计
        /**
        $postnum=$db->fetchRow($db->select()->from ('table.contents')->where ('cid=?',$cid));
        if($postnum['sid']){
        $sid=$postnum['sid']; 
        //s
        if (!array_key_exists('spview', $db->fetchRow($db->select()->from('table.special')))) {
        $db->query('ALTER TABLE `'.$db->getPrefix().'special` ADD `spview` INT(10) DEFAULT 0;');
        }
        $exist = $db->fetchRow($db->select('spview')->from('table.special')->where('sid = ?', $sid))['spview'];
        $cookie = Typecho_Cookie::get('special_views');
        $cookie = $cookie ? explode(',', $cookie) : array();
        if (!in_array($sid, $cookie)) {
            $db->query($db->update('table.special')
                ->rows(array('spview' => (int)$exist+1))
                ->where('sid = ?', $sid));
            $exist = (int)$exist+1;
            array_push($cookie, $sid);
            $cookie = implode(',', $cookie);
            Typecho_Cookie::set('special_views', $cookie);
        }
        //end
        
        }
        **/
    }
    
    if( $exist == 0 ){  echo '0';  }
    else{      
      $exist = convert($exist);
      echo $exist;
    }
}

/**
* 专题阅读统计
* 调用<?php get_post_view($this); ?>
*/
function spviews($sid) {
    $db = Typecho_Db::get();
    if (!array_key_exists('spview', $db->fetchRow($db->select()->from('table.special')))) {
    $db->query('ALTER TABLE `'.$db->getPrefix().'special` ADD `spview` INT(10) DEFAULT 0;');
    }
    $exist = $db->fetchRow($db->select('spview')->from('table.special')->where('sid = ?', $sid))['spview'];
    
        $cookie = Typecho_Cookie::get('special_views');
        $cookie = $cookie ? explode(',', $cookie) : array();
        if (!in_array($sid, $cookie)) {
            $db->query($db->update('table.special')
                ->rows(array('spview' => (int)$exist+1))
                ->where('sid = ?', $sid));
            $exist = (int)$exist+1;
            array_push($cookie, $sid);
            $cookie = implode(',', $cookie);
            Typecho_Cookie::set('special_views', $cookie);
        }
        
    if( $exist == 0 ){ return '0';  }
    else{      
      $exist = convert($exist);
      return $exist;
    }
}



/**
* 个人主页统计
* 调用<?php get_post_view($this); ?>
*/
function authorviews($uid) {
    $db = Typecho_Db::get();
    if (!array_key_exists('uviews', $db->fetchRow($db->select()->from('table.users')))) {
        $db->query('ALTER TABLE `'.$db->getPrefix().'users` ADD `uviews` INT(10) DEFAULT 0;');
    }
    
    $exist = $db->fetchRow($db->select('uviews')->from('table.users')->where('uid = ?', $uid))['uviews'];
    
        $cookie = Typecho_Cookie::get('author_uviews');
        $cookie = $cookie ? explode(',', $cookie) : array();
        if (!in_array($uid, $cookie)) {
            $db->query($db->update('table.users')
                ->rows(array('uviews' => (int)$exist+1))
                ->where('uid = ?', $uid));
            $exist = (int)$exist+1;
            array_push($cookie, $uid);
            $cookie = implode(',', $cookie);
            Typecho_Cookie::set('author_uviews', $cookie);
        }
    
    if( $exist == 0 ){ return '0';  }
    else{      
      $exist = convert($exist);
      return $exist;
    }
}


/** 阅读数友好化 */
function convert($num) 
{
    if ($num >= 100000)
    {
        $num = round($num / 10000) .'w';
    } 
    else if ($num >= 10000) 
    {
        $num = round($num / 10000, 1) .'w';
    } 
    else if($num >= 1000) 
    {
        $num = round($num / 1000, 1) . 'k';
    }
    return $num;
}

//博客最后更新时间
function get_last_update(){
    $num   = '1'; //取最近的一笔就好了
    $now = time();
    $db     = Typecho_Db::get();
    $prefix = $db->getPrefix();
    $create = $db->fetchRow($db->select('created')->from('table.contents')->limit($num)->order('created',Typecho_Db::SORT_DESC));
    $update = $db->fetchRow($db->select('modified')->from('table.contents')->limit($num)->order('modified',Typecho_Db::SORT_DESC));
    if($create>=$update){  //发表时间和更新时间取最近的
      echo Typecho_I18n::dateWord($create['created'], $now); //转换为更通俗易懂的格式
    }else{
      echo Typecho_I18n::dateWord($update['modified'], $now);
    }
}


/**
 * 时间友好化
 *
 * @access public
 * @param mixed
 * @return
 */
function formatTime($older_date) {
if($older_date=="no"){return;}
$chunks = array(
array(946080000 , ' 年'),
array(2592000 , ' 月'),
array(604800 , ' 周'),
array(86400 , ' 天'),
array(3600 , ' 小时'),
array(60 , ' 分'),
array(1 , ' 秒'),
);
$newer_date = time();
$since = abs($newer_date - $older_date);

for ($i = 0, $j = count($chunks); $i < $j; $i++){
$seconds = $chunks[$i][0];
$name = $chunks[$i][1];
if (($count = floor($since / $seconds)) != 0) break;
}
$output = $count.$name.'前';
return $output;
}


/**
* 文章访问量等级
*/
function listdeng($archive){
    $db = Typecho_Db::get();
    $cid = $archive->cid;
    $time = $archive->created;
    $Copyr = $archive->fields->Copyrightnew;
    if($Copyr=='0'){ echo '<span class="badge arc_cr">原创</span>';}
    else{
    $nowtime = time();
    $times =$nowtime - $time;
    $times = $times/(60*60*24)/30;
    if (!array_key_exists('views', $db->fetchRow($db->select()->from('table.contents')))) {
        $db->query('ALTER TABLE `'.$db->getPrefix().'contents` ADD `views` INT(10) DEFAULT 0;');
    }
    $exist = $db->fetchRow($db->select('views')->from('table.contents')->where('cid = ?', $cid))['views'];
    if($times<=6){//6个月期限
    /**阅读量s**/
    if($exist<200){
    /** echo '<span class="badge arc_v1"></span>';**/
    }elseif ($exist<500 && $exist>200) {
    //echo '<span class="badge arc_v2">新秀</span>';
    }elseif ($exist<1000 && $exist>=500) {
    echo '<span class="badge arc_v3">推荐</span>';
    }elseif ($exist<5000 && $exist>=1000) {
    echo '<span class="badge arc_v4">热文</span>';
    }elseif ($exist<10000 && $exist>=5000) {
    echo '<span class="badge arc_v5">头条</span>';
    }elseif ($exist<30000 && $exist>=10000) {
    echo '<span class="badge arc_v6">火爆</span>';
    }elseif ($exist>=30000) {
    echo '<span class="badge arc_v7">神贴</span>';
    }
    /**阅读量s**/
    }
    else{}
    
    }
}


/**
* 获取文章图片数量
*/
function imgNums($content){
$output = preg_match_all('#<img(.*?) src="([^"]*/)?(([^"/]*)\.[^"]*)"(.*?)>#', $content,$s);
$cnt = count( $s[1] );
return $cnt;
}


/**
* 判断时间区间
*
* 使用方法  if(timeZone($this->date->timeStamp)) echo 'ok';
*/
function timeZone($from){

$period = time() - 604800; 
if($from > $period){
    return true;
}
else{ return false;}
}

/**
* 判断30天热门
*/
function hotZone($cid,$time){
    $db = Typecho_Db::get();
    $nowtime = time();
    $times =$nowtime - $time;
    $times = $times/(60*60*24)/30;
    if (!array_key_exists('views', $db->fetchRow($db->select()->from('table.contents')))) {
        $db->query('ALTER TABLE `'.$db->getPrefix().'contents` ADD `views` INT(10) DEFAULT 0;');
    }
    $exist = $db->fetchRow($db->select('views')->from('table.contents')->where('cid = ?', $cid))['views'];
    if($times<=1&&$exist>300){//1个月期限
      return true;
    }
    else{ return false; }
}

function myadsee($uid){
   
   $con = myad($uid);
   // 分割输入字符串
   $parts = explode("|", $con);

   // 获取图片URL和链接URL
   $imageUrl = $parts[0];
   $linkUrl = $parts[1];

   // 创建HTML字符串
   if($con){
   $html = "<a href='$linkUrl'><img class='myadimg' src='$imageUrl' alt='Image'></a>";
   return $html;
   }
   else{
   return null;    
   }


   
}


function fottxt($content){
$text = $content;
$text = strip_tags($text); // 移除HTML标签
$text = str_replace(array("\r", "\n"), '', $text); // 移除换行符
$text = addslashes($text); // 添加需要的转义

$maxLength = 80; // 你想要的最大长度
if(mb_strlen($text, 'utf8') > $maxLength) {
    $text = mb_substr($text, 0, $maxLength, 'utf8');
}
   return $text;
}

/**
 * 主题集成一言（Hitokoto）API 经典语句功能
 */
function GetHitokoto(){
    $url = 'https://v1.hitokoto.cn/?encode=json'; // 不限定内容类型
    // $url = https://v1.hitokoto.cn/?encode=json&c=d'; // 限定内容类型
    $ch = curl_init();  
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // 从证书中检查 SSL 加密算法是否存在
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    $response = curl_exec($ch);  
    if($error=curl_error($ch)){  
        return '欢迎来到B2主题的一言功能~'; // 如果 6s 内，一言 API 调用失败则输出这个默认句子~
    }  
    curl_close($ch);
    $array_data = json_decode($response,true);
    $Emu_content = $array_data['hitokoto'].'----《'.$array_data['from'].'》'; // 输出格式：经典语句----《语句出处》
    return $Emu_content;
}

/*专题*/
function Gets_sp(){
    $db = Typecho_Db::get();
    $result = $db->fetchAll($db->select()->from('table.special')->limit(4) );
    if($result){ return true; }
    else{ return false; }
    
}

function Gets_nav(){
    
    $html='';
    $db = Typecho_Db::get();
    $result = $db->fetchAll($db->select()->from('table.special')->limit(4) );
        
        if($result){
        foreach($result as $val){            
            
            $spname = $val['spname'];
            $spdep = $val['spdep'];
            $spdep = mb_strlen($spdep, 'utf-8') > 35 ? mb_substr($spdep, 0, 35, 'utf-8').'....' : $spdep; //格式化内容
            $spimg = $val['spimg'];
            $sid = $val['sid'];
            $surl = Getspecurl($val['sid']);
            $html = $html.'<div class="py-2"><div class="list-item list-overlay-content"><div class="media media-2x1"><a class="media-content" href="'.$surl.'" style="background-image:url('.$spimg.');"><span class="overlay"></span></a></div><div class="list-content"><div class="list-body"><a href="'.$surl.'" class="list-title h-2x">'.$spname.'</a></div><div class="list-footer"><div class="text-muted text-xs">'.$spdep.'</div></div></div></div></div>';
        }}
    
        return $html;
    
}

function Getspecial(){
    $html='';
    $db = Typecho_Db::get();
    $result = $db->fetchAll($db->select()->from('table.special')->limit(2)
    );
        
        if($result){
        foreach($result as $val){            
            
            $spname = $val['spname'];
            $spdep = $val['spdep'];
            $spdep = mb_strlen($spdep, 'utf-8') > 35 ? mb_substr($spdep, 0, 35, 'utf-8').'....' : $spdep; //格式化内容
            $spimg = $val['spimg'];
            $sid = $val['sid'];
            $surl = Getspecurl($val['sid']);
            $html = $html.'<div class="sp_item"><div class="entry-container">
                    <div class="block-image feaimg">
                    <a class="block-fea scrollLoading" title="'.$spname.'" style="background-image:url('.$spimg.')"><i class="mask"></i>
                    </a></div>
                    <header class="entry-header"><span class="entry-title"><a href="'.$surl.'">'.$spname.'</a></span></header>
                    <div class="entry-summary ss"><p>'.$spdep.'</p></div></div><span class="lirekan"><i class="ri-record-circle-line ri-lg"></i> 专题 </span><ul class="lire">'.Getsptext($sid).'</ul></div>';
       
        }}
    
        return $html;
}

function Getsptext($sid){
        $html='';
        $db = Typecho_Db::get();
        $result = $db->fetchAll($db->select()->from('table.contents')
            ->where('sid = ?',$sid)
            ->limit(3)
            //->order('created', Typecho_Db::SORT_DESC) 
        );
        if($result){
            foreach($result as $val){                
                $val = Typecho_Widget::widget('Widget_Abstract_Contents')->push($val);
                $post_title = htmlspecialchars($val['title']);
                $permalink = $val['permalink'];
		        $created = date('m-d', $val['created']);
                $html= $html.'<li><a href="'.$permalink.'"><i class="ri-arrow-right-s-line ri-lg"></i> '.$post_title.'</a><div class="liretime">'.$created.'</div></li>';				
            }
        }
   
    return $html;
}

function Getsplist($sid){
        $html='';
        $imgUrl='';
        $author='';
        $userhtml='';
        $db = Typecho_Db::get();
        $result = $db->fetchAll($db->select()->from('table.contents')
            ->where('sid = ?',$sid)
            ->order('created',Typecho_Db::SORT_DESC)
            ->limit(20)
            //->order('created', Typecho_Db::SORT_DESC) 
        );
        if($result){
            foreach($result as $val){                
                $val = Typecho_Widget::widget('Widget_Abstract_Contents')->push($val);
                $post_title = htmlspecialchars($val['title']);
                $permalink = $val['permalink'];
		        $post_views = convert($val['views']); 
		        $created = date('m-d', $val['created']);
		        $text = filter(strip_tags($val['text']));
		      
                $tdesc = mb_strlen($text, 'utf-8') > 120 ? mb_substr($text, 0, 120, 'utf-8').'....' : $text; //格式化内容
                //$tdesc = preg_replace_callback('/\!\[.*\]\((.*?sinaimg\.cn.*?)\)/',"replaceImage",$tdesc);
                
		    	
                        $imgUrl = getuserimg($val['authorId']);
						$author= getuidname($val['authorId']);
				
		        $img =  $db->fetchAll($db->select()->from('table.fields')->where('name = ? AND cid = ?','img',$val['cid']));
					if(count($img) !=0){
						//var_dump($img);
						$img=$img['0']['str_value'];						
                        if($img){}
						else{
                          $img= Thumbnail($result['text'],0);
						}                        						 
					}									
					// var_dump($img);
					// if($img == ""){
					// 	$img = "wu";
					// }                           
                $str = stcdnimg($img); 
                
                if(_blog()){ $userhtml='<div class="author-infos" data-id="'.$val['authorId'].'"><img srcset="'.$imgUrl.'" class="avatar avatar-140 photo" height="25" width="25">'.$author.'<div class="author-info-card"></div></div><span class="separator">/</span>'; }
                $html= $html.'<article class="post-list contt blockimg " id="post_122">
                <div class="entry-container"><span class="laid_title_l"></span>
                    <div class="block-image feaimg">
                    <a class="block-fea scrollLoading" data-url="'.$str.'" href="'.$permalink.'" title="'.$post_title.'"><i class="mask"></i>
                    <span class="vodlist_top"><em class="voddate voddate_year">专题</em></span></a></div>
                    <header class="entry-header"><span class="entry-title"><a href="'.$permalink.'">'.$post_title.'</a></span></header>
                    <div class="entry-summary ss"><p>'.$tdesc.'</p></div>
                    <div class="entry-meta">'.$userhtml.'<time>'.$created.'</time><span class="separator">/</span>'.$post_views.' 阅读</div></div></article>';				
            }
        }
   
    return $html;
}


function Getspecurl($sid){
   //$db = Typecho_Db::get();
   //$postnum=$db->fetchRow($db->select()->from ('table.special')->where ('sid=?',$sid));
   $spurl=Helper::options()->siteUrl.'zt/'.$sid;
   return  $spurl; 
}

function Getspname($sid){
   $db = Typecho_Db::get();
   $postnum=$db->fetchRow($db->select()->from ('table.special')->where ('sid=?',$sid));
   return  $postnum['spname']; 
}
function Getspimg($sid){
   $db = Typecho_Db::get();
   $postnum=$db->fetchRow($db->select()->from ('table.special')->where ('sid=?',$sid));
   return  $postnum['spimg']; 
}
function Getspspdep($sid){
   $db = Typecho_Db::get();
   $postnum=$db->fetchRow($db->select()->from ('table.special')->where ('sid=?',$sid));
   return  $postnum['spdep']; 
}

function get_aitag($cid) {
    $db=Typecho_Db::get();
    $all = $db->fetchRow($db->select()->from('table.contents')->where ('cid=?',$cid));
    if($all['aitag']){ return $all['aitag']; }
    else{ return '暂无AI摘要'; }
}

function get_sp($sid) {
    $db=Typecho_Db::get();
    $all = $db->fetchAll($db->select()->from('table.contents')->where ('sid=?',$sid));
    $ums =count($all);
    return $ums;
}
function get_spname($cid) {
   $db = Typecho_Db::get();
   $postnum=$db->fetchRow($db->select()->from ('table.contents')->where ('cid=?',$cid));
   if($postnum['sid']){
      $Getspname=Getspname($postnum['sid']);
      echo  '<span class="badge arc_cr zbg" style="float: right;">本文章已被<a href="'.Getspecurl($postnum['sid']).'"><'.$Getspname.'></a>专题收录</span>';
   }
}
function get_spuser($sid) {
$html=""; 
$db = Typecho_Db::get();
$posts = $db->fetchAll(Typecho_Db::get()
->select('COUNT(authorId) AS cnt','authorId')
->from('table.contents')
->where('sid=?',$sid)
->group('authorId')
->order('cnt', Typecho_Db::SORT_DESC)
->limit('30')
);
foreach ($posts as $post) {
       $postnum=$db->fetchRow($db->select()->from ('table.users')->where ('uid=?',$post['authorId']));
       $imgUrl = getuserimg($post['authorId']);
       $html=$html.'<span class="ico"><img src="'.$imgUrl.'"></span>';
 }
   return $html;
}

