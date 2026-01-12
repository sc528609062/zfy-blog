<?php

function _getzts($archive){
   global $i;
   $i=123;
}


function _getex($archive){
    $archive->response->setStatus(200); 
    if(!exsql(0)){ //错误的时候输出
    $html = exsql(1);
    $html = $html.'，检测结束，配置错误';
    $info = true;
    }
    else{  //正确的时候输出
    $html = '检测结束，配置成功';   
    $info = false;
    }
    
    $archive->response->throwJson(array(
    "vhtml" => $html,
    "vinfo" => $info
    ));
    
}    



function _getkx($archive){
$archive->response->setStatus(200); 
$html = get_kuaixun();
$archive->response->throwJson(array(
   "vuser" => $html,
));    
}

function _getmsg($archive){
    $archive->response->setStatus(200); 
    $username=$_POST["username"];
    $tell=$_POST["tell"];
    $content=$_POST["content"];
    $time = (int) time();
    $userid = Typecho_Widget::widget('Widget_User')->uid;
    $db= Typecho_Db::get();
    $updates = $db->insert('table.message')->rows(array('uid'=>$userid,'name'=>$username,'tell'=>$tell,'con'=>$content,'time'=>$time));
    //执行后，返回收影响的行数。
    $result = $db->query($updates);

    $archive->response->throwJson(array(
    "vuser" => 1,
    ));    
}

function _getdelmsg($archive){
    $archive->response->setStatus(200); 
    
    $db= Typecho_Db::get();
    $updates = $db->delete('table.message');
    $db->query($updates);
    
    
    $archive->response->throwJson(array(
    "vuser" => 1,
));
}

function _getkan($archive){
$archive->response->setStatus(200); 
$cid=$_POST["id"];
Typecho_Widget::widget('Widget_Archive@indexxiu', 'pageSize=1&type=post', 'cid='.$cid)->to($ji);    
$vurl=$ji->fields->videourl;
$aaa =  array('name'=>$vurl,'cid'=>$cid);
$archive->response->throwJson(array(
   'name'=>$vurl,
   'cid'=>$cid
));    
}


function _getintro($archive){
$archive->response->setStatus(200); 
$intros=$_POST["value"];
$myad=$_POST["myad"];
$imgUrl=$_POST["imgUrl"];
$webxhs=$_POST["webxhs"];
$webdy=$_POST["webdy"];

$user = Typecho_Widget::widget('Widget_User');
if($intros){
getintro($user->uid,$intros);
}
if($imgUrl){
getuimg($user->uid,$imgUrl);
}
if($myad){
getmyad($user->uid,$myad);
}
if($webxhs){
getwebxhs($user->uid,$webxhs);
}
if($webdy){
getwebdy($user->uid,$webdy);
}
$sta=1;
$archive->response->throwJson(array(
   'sta'=>$sta
));    
}


function _getsoblur($archive){
    $archive->response->setStatus(200); 
    
    $sohtml = sosoViewed(10);
    
    $zthtml = Gets_nav();
    
    $archive->response->throwJson(array(
   'sohtml'=>$sohtml,
   'zthtml'=>$zthtml
    )); 
}

function _gethotwen($archive){
    $archive->response->setStatus(200); 
    
    $hotinfo = theMostViewed();
    
     $archive->response->throwJson(array(
   'hotinfo'=>$hotinfo
    ));
}
function _gethotping($archive){
    $archive->response->setStatus(200); 
    
    $hotping = getHotPosts('5');
    
    $archive->response->throwJson(array(
    'hotping'=>$hotping
    ));
}



function _getabautor($archive){
    $archive->response->setStatus(200); 
    $uid=$_POST["uid"];
    

    $imgUrl = getuserimg($uid);
    
    $getuidname =getuidname($uid);
    
    $aurinfo = ' <h4 class="widget-title"><strong class="ts"><i class="txt">作者信息</i></strong></h4>
        <div class="widget-list"> 
        <div class="av_v">  
		<a class="av_v_img author-infos author-left" data-id="'.$uid.'" href="'.Helper::options()->siteUrl.'author/'.$uid.'"><img class="widget-about-image" src="'.$imgUrl.'" srcset="'.$imgUrl.'" class="avatar avatar-140 photo" height="70" width="70">
        </a>
        </div>
        <div class="widget-about-intro">
        <div class="name">'.$getuidname.'</div>
        <div class="widget-intro">'.reintro($uid).'</div>
		<div class="widget-article-newest"><span>TA的最新作品</span></div>
		<ul class="posts-widget">'.authorPosts($uid).'</ul>
        </div>           
        </div>
        
<div class="viewAll">
<a href="'.Helper::options()->siteUrl.'author/'.$uid.'"  class="btn btn-default"> 更多 <i class="ri-more-fill ri-lg"></i> </a>
</div>';
    
    
   $archive->response->throwJson(array(
   'info'=>$aurinfo
    ));
}

function _getsequ($archive){
    $archive->response->setStatus(200); 
    
    
    $sequid = Helper::options()->sequid;
    $hang = explode(",", $sequid);
    $n=count($hang);
    $html="";
    for($i=0;$i<$n;$i++){
    Typecho_Widget::widget('Widget_Archive@sequid'.$i, 'pageSize=1&type=post', 'cid='.$hang[$i])->to($ji);
    
    if($ji->fields->thumb){$img=$ji->fields->thumb;}
    if($i==0){$no=" sx_no";}else{$no="";}
    $created = date('m-d', $ji->created);
    if ($ji->fields->img){
    $str = stcdnimg($ji->fields->img);  
    }
    else{
    $str = stcdnimg(showThumbnail($ji,0));
    } 
    $html=$html.'<div class="item"><div class="hunter-item"><a href="'.$ji->permalink.'"><div class="hunter-thumb"><i class="thumb" style="background-image:url('.$str.')" ><i class="mask"></i></i></div><h2>'.$ji->title.'</h2></a></div></div>';
    }
    
    
    $archive->response->throwJson(array(
    'html'=>$html
    )); 
}


function _getgus($archive){
$archive->response->setStatus(200); 
$siteUrl = Helper::options()->siteUrl;
$genum=$_POST["genum"];    
$username=getuname($genum);
       $useruid=$genum;
       $usermail=getuserimg($useruid);
       
       $usergroup='<img class="v_ci" src="/usr/themes/spimes/src/images/authen.svg" title="认证用户">';
       $userurl=$siteUrl.'author/'.$useruid;
       $userimg = nerPosts($useruid);
       $intro = reintro($useruid);
$archive->response->throwJson(array(
   'userimg'=>$userimg,
   'username'=>$username,
   'usermail'=>$usermail,
   'usergroup'=>$usergroup,
   'userurl'=>$userurl,
   'userintro'=>$intro
)); 
}


function _getkxs($archive){
$archive->response->setStatus(200); 
if (isset($_POST['dataloor'])) {
    if ($_POST['dataloor'] == 'time') {
      $html =  time_ajax($_POST['dataloor']);
    }
}
$archive->response->throwJson(array(
   "vhtml" => $html,
));   
}

function _getalltu($archive){
  $archive->response->setStatus(200); 
  $html=$_POST["dataloor"];
        $i=1;
  
        $db = Typecho_Db::get();
        $result = $db->fetchAll($db->select()->from('table.contents')->where('type = ?', 'post')->where('check <> ?', 1)->limit(100));
        if($result){
           foreach($result as $val){
           
           Typecho_Widget::widget('Widget_Archive@index'.$val['cid'], 'pageSize=1&type=post', 'cid='.$val['cid'])->to($ji); 
           
           if(!$ji->fields->img){
            
           $pattern = '/\<img.*?src\=\"(.*?)\"[^>]*>/i'; 
           $patternMD = '/\!\[.*?\]\((http(s)?:\/\/.*?(jpg|jpeg|png))/i';
           $patternMDfoot = '/\[.*?\]:\s*(http(s)?:\/\/.*?(jpg|jpeg|png))/i';
           $post_img='';
           //如果文章内有插图，则调用插图
           if (preg_match_all($pattern, $ji->content, $thumbUrl)) { 
               $post_img=$thumbUrl[1][0];
           }    
           //如果是内联式markdown格式的图片
           else if (preg_match_all($patternMD, $ji->content, $thumbUrl)) {
               $post_img=$thumbUrl[1][0];
           }
           //如果是脚注式markdown格式的图片
           else if (preg_match_all($patternMDfoot, $ji->content, $thumbUrl)) {
               $post_img=$thumbUrl[1][0];
           }
           

           if($post_img){ 
            // 将自定义字段写入到fields表中
           $db->query($db->insert('table.fields')->rows(array('cid' => $val['cid'],'name'=>'img','str_value'=> $post_img)));
           $db->query($db->update('table.contents')->rows(array('check' =>1))->where('cid = ?',$val['cid'])); 
           }
           
           $i++; 
                
           }
           else{
           $db->query($db->update('table.contents')->rows(array('check' =>1))->where('cid = ?',$val['cid']));  
           }
        
        }}
  
  $archive->response->throwJson(array(
   "vhtml" => $i,
  ));    
}