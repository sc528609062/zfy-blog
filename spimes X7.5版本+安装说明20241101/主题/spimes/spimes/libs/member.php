<?php

/**
 * menber.php
 * Author     : 小灯泡设计
 * Date       : 2020/4/3
 * Version    : 1.0
 * Description: 会员功能
 **/

// 会员页判断是否会员id
function userok($id){
$db = Typecho_Db::get();
$userinfo=$db->fetchRow($db->select()->from ('table.users')->where ('table.users.uid=?',$id));
return $userinfo;
}

// 通过文章id获取文章作者id
function getpcid($cid){
$db = Typecho_Db::get();
$userinfo=$db->fetchRow($db->select()->from ('table.contents')->where ('table.contents.cid=?',$cid));
return $userinfo['authorId'];
}

//获取用户邮箱
function getmail($userid){
   
    $db     = Typecho_Db::get();
    $row = $db->fetchRow($db->select('mail')->from('table.users')->where('uid = ?', $userid));

    return $row['mail'];
}

//通过id查询输出昵称
function getuidname($uid){

    $db = Typecho_Db::get();
    $prefix = $db->getPrefix();
    $row = $db->fetchRow($db->select('screenName')->from('table.users')->where('uid = ?', $uid));
    return $row['screenName'];
    
}


//判断用户权限
function getQX($uid)
{
    $db   = Typecho_Db::get();
    @$prow = $db->fetchRow($db->select('group')->from('table.users')->where('uid = ?', $uid));
    @$group = $prow['group'];

    // 变判断的值为常量
    switch($group){
    case 'subscriber': //关注用户
    return false;
    break;   // 跳出循环
    case 'administrator':
    return true;
    break;
    case 'editor':
    return true;
    break;
    case 'contributor':
    return true;
    break;
    default:return false;
    }

}  

//判断用户组
function yonghuzu($uid)
{
    $db   = Typecho_Db::get();
    @$prow = $db->fetchRow($db->select('group')->from('table.users')->where('uid = ?', $uid));
    @$group = $prow['group'];

    // 变判断的值为常量
    switch($group){
    case 'subscriber': //关注用户
    //return '<img class="v_cv" src="/usr/themes/spimes/images/vipico/1.png" title="认证用户">';
    return '';
    break;   // 跳出循环
    case 'administrator':
    return '<span>注册会员</span>';
    break;
    case 'editor':
    return '<span>注册会员</span>';
    break;
    case 'contributor':
    return '<span>注册会员</span>';
    break;
    }

}  


//调用用户注册时间
function reg_login($userid){
    $now = time();
    $db     = Typecho_Db::get();
    $prefix = $db->getPrefix();
    $row = $db->fetchRow($db->select('created')->from('table.users')->where('uid = ?', $userid));
    $ti = Typecho_I18n::dateWord($row['created'], $now);
    $d1 = $row['created'];//过去的某天，你来设定
    $d2 = ceil((time()-$d1)/60/60/24);//现在的时间减去过去的时间，ceil()进一函数
    return $d2;
}


/**输出作者文章总数，可以指定*/
function allpostnum($id){
    $db = Typecho_Db::get();
    $postnum=$db->fetchRow($db->select(array('COUNT(authorId)'=>'allpostnum'))->from ('table.contents')->where ('table.contents.authorId=?',$id)->where('table.contents.type=?', 'post'));
    $postnum = $postnum['allpostnum'];
	if($postnum=='0') {	return 0; }
	else{  return $postnum;	}
}

//调用最近登录时间
function get_last_login($userid){
    $now = time();
    $db     = Typecho_Db::get();
    $prefix = $db->getPrefix();
    $row = $db->fetchRow($db->select('activated')->from('table.users')->where('uid = ?', $userid));
    return Typecho_I18n::dateWord($row['activated'], $now);
}

/**输出作者人气*/
function allviewnum($id,$sta){
    $db = Typecho_Db::get();
    $postnum=$db->fetchRow($db->select(array('Sum(views)'=>'allviewnum'))->from ('table.contents')->where ('table.contents.authorId=?',$id)->where('table.contents.type=?', 'post'));
    $postnum = $postnum['allviewnum'];
    if($sta){
    $exist = convert($postnum);
    }else{
    $exist =$postnum;
    }
	return $exist == 0 ? '0 ' : $exist;
}

/**输出作者获得点赞数*/
function allagreenum($id){
    $db = Typecho_Db::get();
    $postnum=$db->fetchRow($db->select(array('Sum(agree)'=>'allagreenum'))->from ('table.contents')->where ('table.contents.authorId=?',$id)->where('table.contents.type=?', 'post'));
    $postnum = $postnum['allagreenum'];
    $exist = convert($postnum);
	return $exist;
}


/**输出作者评论总数，可以指定*/
function commentnum($id){
    $db = Typecho_Db::get();
    $commentnum=$db->fetchRow($db->select(array('COUNT(authorId)'=>'commentnum'))->from ('table.comments')->where ('table.comments.authorId=?',$id)->where('table.comments.type=?', 'comment'));
    $commentnum = $commentnum['commentnum'];    
	return $commentnum;
}

/** 输出该作者最近文章列表 */
function authorPosts($authorid){
    if($authorid){ 
        $limit = 5;
        $db = Typecho_Db::get();
        $result = $db->fetchAll($db->select()->from('table.contents')
            ->where('authorId = ?',$authorid)
            ->where('status = ?','publish')
            ->where('type = ?', 'post')
            ->limit($limit)
            ->order('cid', Typecho_Db::SORT_DESC)        
        );
        if($result){
            foreach($result as $val){                
                $val = Typecho_Widget::widget('Widget_Abstract_Contents')->push($val);
                $post_title = htmlspecialchars($val['title']);
                $permalink = $val['permalink'];
				$commentsNum = $val['commentsNum'];
				$post_views = convert($val['views']);  
                $html = $html.'<li><div class="widget-posts-text"><a class="widget-posts-title" href="'.$permalink.'" title="'.$post_title.'"><i class="ri-arrow-right-s-line ri-lg"></i> '.$post_title.'</a><div class="widget-posts-meta"><i>' . $post_views .' 阅读</i></div></div></li>';
            }
        }
        
    }else{
        $html = '请设置要调用的作者ID';
    }
    return $html;
}


/**
* 显示用户等级，按邮箱
*/
function autvip($i){
    $db=Typecho_Db::get();
    $mail=$db->fetchAll($db->select(array('COUNT(cid)'=>'rbq'))->from('table.comments')->where('mail = ?', $i)/**->where('authorId = ?','0')**/);
    foreach ($mail as $sl){
    $rbq=$sl['rbq'];}
    if($rbq<1){
    echo '<span class="autlv aut-0">Lv.0</span>';
    }elseif ($rbq<10 && $rbq>0) {
    echo '<span class="autlv aut-1">Lv.1</span>';
    }elseif ($rbq<20 && $rbq>=10) {
    echo '<span class="autlv aut-2">Lv.2</span>';
    }elseif ($rbq<40 && $rbq>=20) {
    echo '<span class="autlv aut-3">Lv.3</span>';
    }elseif ($rbq<80 && $rbq>=40) {
    echo '<span class="autlv aut-4">Lv.4</span>';
    }elseif ($rbq<100 && $rbq>=80) {
    echo '<span class="autlv aut-5">Lv.5</span>';
    }elseif ($rbq>=100) {
    echo '<span class="autlv aut-6">Lv.6</span>';
    }
}


/** 输出该作者审核文章列表 */
function authorwaiting($authorid,$lock){
    $now = time();
    if($authorid){ 
        $limit = 10;
        $db = Typecho_Db::get();
        $result = $db->fetchAll($db->select()->from('table.contents')
            ->where('authorId = ?',$authorid)
            ->where('status = ?','waiting')
            ->where('type = ?', 'post')
            ->limit($limit)
            ->order('created', Typecho_Db::SORT_DESC)        
        );
        if($result){
            foreach($result as $val){                
                $val = Typecho_Widget::widget('Widget_Abstract_Contents')->push($val);
                $post_title = htmlspecialchars($val['title']);
                
                $permalink = $val['permalink'];
				$commentsNum = $val['commentsNum'];
				$post_text = preg_replace('/($s*$)|(^s*^)/m', '',strip_tags($val['text'])); //获取内容
				$cont_text = cutArticle($post_text,150);
			    $post_views = convert($val['views']);
			    $post_agree = $val['agree'];
				$cont_time = Typecho_I18n::dateWord($val['created'], $now);
				$siteUrl = Helper::options()->siteUrl;
						$img =  $db->fetchAll($db->select()->from('table.fields')->where('name = ? AND cid = ?','img',$val['cid']));
					if(count($img) !=0){
						//var_dump($img);
						$img=$img['0']['str_value'];						
                        if($img){}
						else{
                          $img= Thumbnail($val['text'],0);
						}                        						 
					}	 
		
                
                echo '<article class="post-list contt blockimg "> <div class="entry-container"><span class="laid_title_l"></span> <div class="block-image feaimg"> <div class="block-fea scrollLoading" data-url="'.$img.'" title="'.$post_title.'" style="background-image:url('.$img.')"></div></div> <header class="entry-header"><span class="entry-title">[待审核] '.$post_title.'</span></header> <div class="entry-summary ss"><p>'.$cont_text.'</p></div></div>
                    <div class="d_meta"><time datetime="'.$cont_time.'"><i class="ri-time-line ri-lg"></i> '.$cont_time.'</time>
                    </p>
                    </article>';
            }
        }
        
        else{ echo ''; }
        
    }else{
        echo '请设置要调用的作者ID';
    }
}



/** 输出该作者最近草稿文章列表 */
function authordraft($authorid,$lock){
    $now = time();
    if($authorid){ 
        $limit = 10;
        $db = Typecho_Db::get();
        $result = $db->fetchAll($db->select()->from('table.contents')
            ->where('authorId = ?',$authorid)
            //->where('status = ?','publish')
            ->where('type = ?', 'post_draft')
            ->limit($limit)
            ->order('created', Typecho_Db::SORT_DESC)        
        );
        if($result){
            foreach($result as $val){                
                $val = Typecho_Widget::widget('Widget_Abstract_Contents')->push($val);
                $post_title = htmlspecialchars($val['title']);
                $permalink = $val['permalink'];
				$commentsNum = $val['commentsNum'];
				$post_text = preg_replace('/($s*$)|(^s*^)/m', '',strip_tags($val['text'])); //获取内容
				$cont_text = cutArticle($post_text,150);
			    $post_views = convert($val['views']);
			    $post_agree = $val['agree'];
				$cont_time = Typecho_I18n::dateWord($val['created'], $now);
				
				$siteUrl = Helper::options()->siteUrl;
				$gaoedit = Helper::options()->gaoedit;
				if($lock == 0){ $edit = '<span class="aut_edit"><a href="'.$siteUrl.''.$gaoedit.'.html?tid='.$val['cid'].'">编辑</a></span>';}
				else{ $edit ='';}
				
				//删除
		        Typecho_Widget::widget('Widget_Security')->to($security);

				
				$img =  $db->fetchAll($db->select()->from('table.fields')->where('name = ? AND cid = ?','img',$val['cid']));
					if(count($img) !=0){
						//var_dump($img);
						$img=$img['0']['str_value'];						
                        if($img){}
						else{
                          $img= Thumbnail($val['text'],0);
						}                        						 
					}	 
                
                echo '<article class="post-list contt blockimg "> <div class="entry-container"><span class="laid_title_l"></span> <div class="block-image feaimg"> <div class="block-fea scrollLoading" data-url="'.$img.'" title="'.$post_title.'" style="background-image:url('.$img.')"></div></div> <header class="entry-header"><span class="entry-title">[草稿] '.$post_title.'</span></header> <div class="entry-summary ss"><p>'.$cont_text.'</p></div></div>
                    <div class="d_meta">
                    <time datetime="'.$cont_time.'"><i class="ri-time-line ri-lg"></i> '.$cont_time.'</time>
                    ';
                     ?>
                     <?php if ($lock==0): ?>
                     <span class="aut_edit">
                     <a href="<?php $security->index('/action/contents-post-edit?do=delete&cid='.$val['cid'].''); ?>" onclick="javascript:return p_del()">删除</a></span>
                     <?php endif; ?>
                     <?php 
                  echo  $edit.'</p></article>';
            }
        }
        
        else{ echo ''; }
        
    }else{
        echo '请设置要调用的作者ID';
    }
}



/*输出作者发表的评论*/
class Widget_Post_AuthorComment extends Widget_Abstract_Comments
{
    public function execute()
    {
        global $AuthorCommentId;//全局作者id
        $select  = $this->select()->limit($this->parameter->pageSize)
        ->where('table.comments.status = ?', 'approved')
        ->where('table.comments.authorId = ?', $AuthorCommentId)//获取作者id
        ->where('table.comments.type = ?', 'comment')
        ->order('table.comments.coid', Typecho_Db::SORT_DESC);//根据coid排序
        $this->db->fetchAll($select, array($this, 'push'));
    }
}


/**
* 修改个性签名
*/
function getintro($uid,$intros) {
    $db = Typecho_Db::get();
    
    if (!array_key_exists('introduce', $db->fetchRow($db->select()->from('table.users')))) {
        $db->query('ALTER TABLE `'.$db->getPrefix().'users` ADD `introduce` varchar(200) DEFAULT NULL;');
    }
    
    $exist = $db->fetchRow($db->select('introduce')->from('table.users')->where('uid = ?', $uid))['introduce'];
    
    $db->query($db->update('table.users')
                ->rows(array('introduce' => $intros))
                ->where('uid = ?', $uid));
}

/**
* 修改个人头像
*/
function getuimg($uid,$uimg) {
    $db = Typecho_Db::get();
    
    if (!array_key_exists('uimg', $db->fetchRow($db->select()->from('table.users')))) {
        $db->query('ALTER TABLE `'.$db->getPrefix().'users` ADD `uimg` varchar(200) DEFAULT NULL;');
    }
    
    $exist = $db->fetchRow($db->select('uimg')->from('table.users')->where('uid = ?', $uid))['uimg'];
    
    $db->query($db->update('table.users')
                ->rows(array('uimg' => $uimg))
                ->where('uid = ?', $uid));
}
/**
* 修改个人广告
*/
function getmyad($uid,$myad) {
    $db = Typecho_Db::get();
    
    if (!array_key_exists('myad', $db->fetchRow($db->select()->from('table.users')))) {
        $db->query('ALTER TABLE `'.$db->getPrefix().'users` ADD `myad` varchar(200) DEFAULT NULL;');
    }
    
    $exist = $db->fetchRow($db->select('myad')->from('table.users')->where('uid = ?', $uid))['myad'];
    
    $db->query($db->update('table.users')
                ->rows(array('myad' => $myad))
                ->where('uid = ?', $uid));
}

/**
* 修改个人广告
*/
function getwebxhs($uid,$webxhs) {
    $db = Typecho_Db::get();
    
    if (!array_key_exists('webxhs', $db->fetchRow($db->select()->from('table.users')))) {
        $db->query('ALTER TABLE `'.$db->getPrefix().'users` ADD `webxhs` varchar(200) DEFAULT NULL;');
    }
    
    $exist = $db->fetchRow($db->select('webxhs')->from('table.users')->where('uid = ?', $uid))['webxhs'];
    
    $db->query($db->update('table.users')
                ->rows(array('webxhs' => $webxhs))
                ->where('uid = ?', $uid));
}

/**
* 修改个人广告
*/
function getwebdy($uid,$webdy) {
    $db = Typecho_Db::get();
    
    if (!array_key_exists('webdy', $db->fetchRow($db->select()->from('table.users')))) {
        $db->query('ALTER TABLE `'.$db->getPrefix().'users` ADD `webdy` varchar(200) DEFAULT NULL;');
    }
    
    $exist = $db->fetchRow($db->select('webdy')->from('table.users')->where('uid = ?', $uid))['webdy'];
    
    $db->query($db->update('table.users')
                ->rows(array('webdy' => $webdy))
                ->where('uid = ?', $uid));
}



function myad($uid) {
    $db = Typecho_Db::get();
    
    if (!array_key_exists('myad', $db->fetchRow($db->select()->from('table.users')))) {
        $db->query('ALTER TABLE `'.$db->getPrefix().'users` ADD `myad` varchar(200) DEFAULT NULL;');
    }
    $exist = $db->fetchRow($db->select('myad')->from('table.users')->where('uid = ?', $uid))['myad'];
    if($exist==''){$exist = null;}
    return $exist;
}
function reintro($uid) {
    $db = Typecho_Db::get();
    
    if (!array_key_exists('introduce', $db->fetchRow($db->select()->from('table.users')))) {
        $db->query('ALTER TABLE `'.$db->getPrefix().'users` ADD `introduce` varchar(200) DEFAULT NULL;');
    }
    $exist = $db->fetchRow($db->select('introduce')->from('table.users')->where('uid = ?', $uid))['introduce'];
    if($exist==''){$exist = '作者有点忙，还没写简介';}
    return $exist;
}

function reweburl($uid,$name) {
    $db = Typecho_Db::get();

    $exist = $db->fetchRow($db->select($name)->from('table.users')->where('uid = ?', $uid))[$name];
    
    return $exist;
}
  