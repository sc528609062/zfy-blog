<?php

/**主题检测**/
function exsql($a){
    $html = '';
    $i = true;
    $db = Typecho_Db::get();
    if (!array_key_exists('views', $db->fetchRow($db->select()->from('table.contents')))) {
        $html=$html.'阅读量views丢失';
        $i = false;
    }
    
    if (!array_key_exists('check', $db->fetchRow($db->select()->from('table.contents')))) {
        $html=$html.'图片检测check丢失';
        $i = false;
    }
    
    if (!array_key_exists('agree', $db->fetchRow($db->select()->from('table.contents')))) {
        $html=$html.'点赞数agree丢失';
        $i = false;
    }
    if (!array_key_exists('aitag', $db->fetchRow($db->select()->from('table.contents')))) {
        $html=$html.'智能总结aitag丢失';
        $i = false;
    }
    if (!array_key_exists('uviews', $db->fetchRow($db->select()->from('table.users')))) {
        $html=$html.'专题阅读数uviews丢失';
        $i = false;
    }
    if (!array_key_exists('uimg', $db->fetchRow($db->select()->from('table.users')))) {
        $html=$html.'用户头像uimg丢失';
        $i = false;
    }
    if (!array_key_exists('points', $db->fetchRow($db->select()->from('table.users')))) {
        $html=$html.'阅读量points丢失';
        $i = false;
    }
    if (!array_key_exists('pointy', $db->fetchRow($db->select()->from('table.users')))) {
        $html=$html.'阅读量pointy丢失';
        $i = false;
    }
    if (!array_key_exists('introduce', $db->fetchRow($db->select()->from('table.users')))) {
        $html=$html.'个人签名introduce丢失';
        $i = false;
    }
    if (!array_key_exists('webxhs', $db->fetchRow($db->select()->from('table.users')))) {
        $html=$html.'小红书webxhs丢失';
        $i = false;
    }
    if (!array_key_exists('webdy', $db->fetchRow($db->select()->from('table.users')))) {
        $html=$html.'抖音webdy丢失';
        $i = false;
    }    
    if (!array_key_exists('seotitle', $db->fetchRow($db->select()->from('table.metas')))) {
        $html=$html.'栏目标题seotitle丢失';
        $i = false;
    }
    if (!array_key_exists('seokey', $db->fetchRow($db->select()->from('table.metas')))) {
        $html=$html.'栏目关键字seokey丢失';
        $i = false;
    }
    if (!array_key_exists('seodesc', $db->fetchRow($db->select()->from('table.metas')))) {
        $html=$html.'栏目描述seodesc丢失';
        $i = false;
    }
    if (!array_key_exists('catepages', $db->fetchRow($db->select()->from('table.metas')))) {
        $html=$html.'栏目模板catepages丢失';
        $i = false;
    }
    if (!array_key_exists('cateico', $db->fetchRow($db->select()->from('table.metas')))) {
        $html=$html.'栏目封面cateico丢失';
        $i = false;
    }

    if('1' == count($db->query("SHOW TABLES LIKE '".$db->getPrefix()."special'")->fetchAll())){} else {
        $html=$html.'专题表special丢失';
        $i = false;
    }
    
    if('1' == count($db->query("SHOW TABLES LIKE '".$db->getPrefix()."message'")->fetchAll())){} else {
        $html=$html.'专题表message丢失';
        $i = false;
    }
    
    if($a==0){ return $i; }//0判断是否错误
    if($a==1){ return $html; }//1获取信息
}
/**主题修复**/
function resql(){
    $i = true;
    $db = Typecho_Db::get();
    $prefix = $db->getPrefix();
    if (!array_key_exists('views', $db->fetchRow($db->select()->from('table.contents')))) {
        $db->query('ALTER TABLE `'.$prefix.'contents` ADD `views` INT(10) DEFAULT 0;');
    }
    if (!array_key_exists('check', $db->fetchRow($db->select()->from('table.contents')))) {
        $db->query('ALTER TABLE `'.$prefix.'contents` ADD `check` INT(10) DEFAULT 0;');
    }
    if (!array_key_exists('agree', $db->fetchRow($db->select()->from('table.contents')))) {
        $db->query('ALTER TABLE `'.$prefix.'contents` ADD `agree` INT(10) NOT NULL DEFAULT 0;');
    }
    if (!array_key_exists('aitag', $db->fetchRow($db->select()->from('table.contents')))) {
        $db->query('ALTER TABLE `'.$prefix.'contents` ADD `aitag` longtext NULL;');
    }  
    
    if (!array_key_exists('uviews', $db->fetchRow($db->select()->from('table.users')))) {
        $db->query('ALTER TABLE `'.$prefix.'users` ADD `uviews` INT(10) DEFAULT 0;');
    }
    if (!array_key_exists('uimg', $db->fetchRow($db->select()->from('table.users')))) {
        $db->query('ALTER TABLE `'.$prefix.'users` ADD `uimg` varchar(200) DEFAULT NULL;');
    }
    if (!array_key_exists('points', $db->fetchRow($db->select()->from('table.users')))) {
        $db->query('ALTER TABLE `'.$prefix.'users` ADD `points` INT(10) DEFAULT NULL;');
    }
    if (!array_key_exists('pointy', $db->fetchRow($db->select()->from('table.users')))) {
        $db->query('ALTER TABLE `'.$prefix.'users` ADD `pointy` INT(10) DEFAULT NULL;'); 
    }
    if (!array_key_exists('introduce', $db->fetchRow($db->select()->from('table.users')))) {
        $db->query('ALTER TABLE `'.$prefix.'users` ADD `introduce` varchar(200) DEFAULT NULL;');
    }
    if (!array_key_exists('webxhs', $db->fetchRow($db->select()->from('table.users')))) {
        $db->query('ALTER TABLE `'.$prefix.'users` ADD `webxhs` varchar(200) DEFAULT NULL;');
    }
    if (!array_key_exists('webdy', $db->fetchRow($db->select()->from('table.users')))) {
        $db->query('ALTER TABLE `'.$prefix.'users` ADD `webdy` varchar(200) DEFAULT NULL;');
    }    
    if (!array_key_exists('seotitle', $db->fetchRow($db->select()->from('table.metas')))) {
        $db->query("ALTER TABLE `".$prefix."metas` ADD `seotitle` VARCHAR(255) DEFAULT NULL");
    }
    if (!array_key_exists('seokey', $db->fetchRow($db->select()->from('table.metas')))) {
        $db->query("ALTER TABLE `".$prefix."metas` ADD `seokey` VARCHAR(255) DEFAULT NULL");
    }
    if (!array_key_exists('seodesc', $db->fetchRow($db->select()->from('table.metas')))) {
        $db->query("ALTER TABLE `".$prefix."metas` ADD `seodesc` text");
    }
    if (!array_key_exists('catepages', $db->fetchRow($db->select()->from('table.metas')))) {
        $db->query("ALTER TABLE `".$prefix."metas` ADD `catepages` VARCHAR(255) DEFAULT 1");
    }
    if (!array_key_exists('cateico', $db->fetchRow($db->select()->from('table.metas')))) {
        $db->query("ALTER TABLE `".$prefix."metas` ADD `cateico` VARCHAR(255) DEFAULT NULL");
    }

    if('1' == count($db->query("SHOW TABLES LIKE '".$db->getPrefix()."special'")->fetchAll())){} else {
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
    //username,tell,content
    if('1' == count($db->query("SHOW TABLES LIKE '".$db->getPrefix()."message'")->fetchAll())){} else {
     $sql = "CREATE TABLE `{$prefix}message` (
                    `mid` int UNSIGNED NOT NULL AUTO_INCREMENT,
                    `uid` INT(11) COMMENT '会员id',
                    `name` varchar(255)  COMMENT '内容',
                    `tell` varchar(255)  COMMENT '电话',
                    `con` varchar(255)  COMMENT '内容',
                    `time` INT(11) COMMENT '开始时间',
                    PRIMARY KEY (`mid`)
                )DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
    $db->query($sql); 
    }
    
    //return $html; 
}






/**
* 是否开启加速更换CDN域名功能
*/
function stcdn($i) {
   $cdnopen = Helper::options()->cdnopen;
   $cdnurla = Helper::options()->cdnurla;
   $cdnurlb = Helper::options()->cdnurlb; 
   if ($cdnopen == '0'){
   $i = $i;
   return $i;
   }else {
   $i = str_replace($cdnurla,$cdnurlb,$i);
   return $i;
   } 
}

/**
* 是否开启加速更换CDN域名功能(正文)
*/
function stcdnimg($i) {
   $cdnopen = Helper::options()->cdnopen;
   $cdnurla = Helper::options()->cdnurla;
   $cdnurlb = Helper::options()->cdnurlb; 
   $imageView = Helper::options()->imageView;
   if ($cdnopen == '0'){
   $i = $i;
   return $i;
   }else {
   $i = str_replace($cdnurla,$cdnurlb,$i);
   return $i.$imageView;  
   } 
}



/**
* 评论者主页链接新窗口打开
* 调用<?php CommentAuthor($comments); ?>
*/
function CommentAuthor($obj, $autoLink = NULL, $noFollow = NULL) {    //后两个参数是原生函数自带的，为了保持原生属性，我并没有删除，原版保留
    $options = Helper::options();
    $autoLink = $autoLink ? $autoLink : $options->commentsShowUrl;    //原生参数，控制输出链接（开关而已）
    $noFollow = $noFollow ? $noFollow : $options->commentsUrlNofollow;    //原生参数，控制输出链接额外属性（也是开关而已...）
    if ($obj->url && $autoLink) {
        echo '<a href="'.$obj->url.'"'.($noFollow ? ' rel="external nofollow"' : NULL).(strstr($obj->url, $options->index) == $obj->url ? NULL : ' target="_blank"').'>'.$obj->author.'</a>';
    } else {
        echo $obj->author;
    }
}

/** 输出文章缩略图 */
function showThumbnail($widget,$imgnum){ //获取两个参数，文章的ID和需要显示的图片数量
    if($imgnum==-1){
    return false;
    }
    else{
    // 当文章无图片时的默认缩略图
    $rand = rand(1,20); 
    $random = $widget->widget('Widget_Options')->themeUrl . '/src/images/thumbs/adimg.png'; // 缩略图路径
    @$attach = $widget->attachments(1)->attachment;
    $pattern = '/\<img.*?src\=\"(.*?)\"[^>]*>/i'; 
    $patternMD = '/\!\[.*?\]\((http(s)?:\/\/.*?(jpg|jpeg|png))/i';
    $patternMDfoot = '/\[.*?\]:\s*(http(s)?:\/\/.*?(jpg|jpeg|png))/i';
    //如果文章内有插图，则调用插图
    if (preg_match_all($pattern, $widget->content, $thumbUrl)) { 
        return $thumbUrl[1][$imgnum];
    }    
    //如果是内联式markdown格式的图片
    else if (preg_match_all($patternMD, $widget->content, $thumbUrl)) {
        return $thumbUrl[1][$imgnum];
    }
    //如果是脚注式markdown格式的图片
    else if (preg_match_all($patternMDfoot, $widget->content, $thumbUrl)) {
        return $thumbUrl[1][$imgnum];
    }
    //没有就调用第一个图片附件
    /***
    else if ($attach && $attach->isImage) {
        return $attach->url; 
    } 
    **/
    //如果真的没有图片，就调用一张随机图片
    return $random;
    }
  
}

/** 处理文章缩略图 */
function Thumbnail($contx,$imgnum){ 
  
    $pattern = '/\<img.*?src\=\"(.*?)\"[^>]*>/i'; 
    $patternMD = '/\!\[.*?\]\((http(s)?:\/\/.*?(jpg|jpeg|png))/i';
    $patternMDfoot = '/\[.*?\]:\s*(http(s)?:\/\/.*?(jpg|jpeg|png))/i';
    //如果文章内有插图，则调用插图
    if (preg_match_all($pattern, $contx, $thumbUrl)) { 
        return $thumbUrl[1][$imgnum];
    }    
    //如果是内联式markdown格式的图片
    else if (preg_match_all($patternMD, $contx, $thumbUrl)) {
        return $thumbUrl[1][$imgnum];
    }
    //如果是脚注式markdown格式的图片
    else if (preg_match_all($patternMDfoot, $contx, $thumbUrl)) {
        return $thumbUrl[1][$imgnum];
    }

    //如果真的没有图片，就调用一张随机图片
    else{
        $adimg = "/usr/themes/spimes/src/images/thumbs/adimg.png"; // 缩略图路径
        return $adimg;
    }

}




/* 解析头像 */
function getuserimg($uid)
{
    if($uid != -1){
    $db     = Typecho_Db::get();
    $row = $db->fetchRow($db->select()->from('table.users')->where('uid = ?', $uid));
    if($row['uimg']){return $row['uimg'];  }
    else{ return '/usr/themes/spimes/src/images/de.png'; }
        
    }
    else{
        return "/usr/themes/spimes/src/images/uni_ai.png";
    }
    
    
}


/* 解析头像 */
function getGravatar($mail)
{
    $a = Typecho_Widget::widget('Widget_Options')->JGravatars;
    $b = 'https://' . $a . '/';
    $c = strtolower($mail); //转为小写
    $d = md5($c);
    $f = str_replace('@qq.com', '', $c);
    if (strstr($c, "qq.com") && is_numeric($f) && strlen($f) < 11 && strlen($f) > 4) {
        $g = '//thirdqq.qlogo.cn/g?b=qq&nk=' . $f . '&s=100';

    } else {
        $g = $b . $d . '?d=mm';
    }
        return $g;
}



//文章目录
function createCatalog($obj) {    //为文章标题添加锚点
    global $catalog;
    global $catalog_count;
    $catalog = array();
    $catalog_count = 0;
    $obj = preg_replace_callback('/<h([2])(.*?)>(.*?)<\/h\1>/i', function($obj) {
        global $catalog;
        global $catalog_count;
        $catalog_count ++;
        $catalog[] = array('text' => trim(strip_tags($obj[3])), 'depth' => $obj[1], 'count' => $catalog_count);
        return '<h'.$obj[1].$obj[2].'><a name="cl-'.$catalog_count.'"></a>'.$obj[3].'</h'.$obj[1].'>';
    }, $obj);
    return $obj;
}

function getCatalog() {    //输出文章目录容器
    global $catalog;
    $index = '';
    if ($catalog) {
        $index = '<ul>'."\n";
        $prev_depth = '';
        $to_depth = 0;
        foreach($catalog as $catalog_item) {
            $catalog_depth = $catalog_item['depth'];
            if ($prev_depth) {
                if ($catalog_depth == $prev_depth) {
                    $index .= '</li>'."\n";
                } elseif ($catalog_depth > $prev_depth) {
                    $to_depth++;
                    $index .= '<ul>'."\n";
                } else {
                    $to_depth2 = ($to_depth > ($prev_depth - $catalog_depth)) ? ($prev_depth - $catalog_depth) : $to_depth;
                    if ($to_depth2) {
                        for ($i=0; $i<$to_depth2; $i++) {
                            $index .= '</li>'."\n".'</ul>'."\n";
                            $to_depth--;
                        }
                    }
                    $index .= '</li>';
                }
            }
            $index .= '<li><a href="#cl-'.$catalog_item['count'].'">'.$catalog_item['text'].'</a>';
            $prev_depth = $catalog_item['depth'];
        }
        for ($i=0; $i<=$to_depth; $i++) {
            $index .= '</li>'."\n".'</ul>'."\n";
        }
    $index = '<div id="mArticle" class="article_click">'."\n".'<span class="ri-menu-add-line" style="cursor:pointer"></span>'."\n".'<div class="fn_article_nav">'."\n".'<div class="toc-arrow"></div>'."\n".'<div class="toc">'."\n".$index.'</div>'."\n".'</div>'."\n".'</div>';
    }
    echo $index;
}


//搜索 - 热门文章
function sosoViewed($limit = 4, $before = '<br/> - ( views: ', $after = ' times ) ')
    {
        
        $db = Typecho_Db::get();
        $options = Typecho_Widget::widget('Widget_Options');
        $limit = is_numeric($limit) ? $limit : 4;
        $posts = $db->fetchAll($db->select()->from('table.contents')
                 ->where('type = ? AND status = ? AND password IS NULL', 'post', 'publish')
                 ->order('views', Typecho_Db::SORT_DESC)
                 ->limit($limit)
                 );
        $i = 1;       
        $z = 1;      
        if ($posts) {
            foreach ($posts as $post) {
                $result = Typecho_Widget::widget('Widget_Abstract_Contents')->push($post);
                $post_views = convert($result['views']);  
                $post_title = htmlspecialchars($result['title']);
                $permalink = $result['permalink'];
				$created = date('m-d', $result['created']); 
				$post_title = mb_strlen($post_title, 'utf-8') > 30 ? mb_substr($post_title, 0, 30, 'utf-8').'....' : $post_title; //格式化内容
				
				if($z<=9){ $z = '0'.$z; }
				
                $sohtml = $sohtml.'<li><a href="' .$permalink. '"><span class="rank ran'.$i.'">'.$z.'</span> ' .$post_title. ' <i class="view">'.$post_views.' 阅读</i></a></li>';  
                    $i++; $z++;
			 }
        } else {
          $sohtml = "<li>N/A</li>\n";
        }
        
        return $sohtml;
}




//热门访问量文章
function theMostViewed($limit = 4, $before = '<br/> - ( views: ', $after = ' times ) ')
    {
        $db = Typecho_Db::get();
        $options = Typecho_Widget::widget('Widget_Options');
        $limit = is_numeric($limit) ? $limit : 4;
        $posts = $db->fetchAll($db->select()->from('table.contents')
                 ->where('type = ? AND status = ? AND password IS NULL', 'post', 'publish')
                 ->order('views', Typecho_Db::SORT_DESC)
                 ->limit($limit)
                 );
        if ($posts) {
            foreach ($posts as $post) {
                $result = Typecho_Widget::widget('Widget_Abstract_Contents')->push($post);
                $post_views = convert($result['views']);  
                $post_title = htmlspecialchars($result['title']);
                $permalink = $result['permalink'];
				$created = date('m-d', $result['created']);            
			    $img =  $db->fetchAll($db->select()->from('table.fields')->where('name = ? AND cid = ?','img',$result['cid']));
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
                $html = $html."<div class='py-2'><div class='list-item list-overlay-content'><div class='media media-2x1'><a class='media-content' href='$permalink'  style='background-image:url($str);'><span class='overlay'></span></a></div><div class='list-content'><div class='list-body'><a href='$permalink' class='list-title h-2x'>$post_title</a></div><div class='list-footer'><div class='text-muted text-xs'>$post_views 阅读 ，<time class='d-inline-block'>$created</time></div></div></div></div></div>";
			 }
        } else {
          $html =  "<li>N/A</li>\n";
        }
        
        return $html;
}




//热门评论文章
function getHotPosts($limit = 10){
    $db = Typecho_Db::get();
    $html='';
    $result = $db->fetchAll($db->select()->from('table.contents')
        ->where('status = ?','publish')
        ->where('type = ?', 'post')
        ->where('created <= unix_timestamp(now())', 'post') //添加这一句避免未达到时间的文章提前曝光
        ->limit($limit)
        ->order('commentsNum', Typecho_Db::SORT_DESC)
    );
    if($result){
        $i=1;
        foreach($result as $val){ 
            $img_url="";
            $wu_url="/usr/themes/spimes/src/images/dian2.png";
 
            
            $mails =  $db->fetchAll($db->select()->from('table.comments')->where('cid = ?',$val['cid'])->limit(6)->order('created', Typecho_Db::SORT_DESC) );

$usedImgUrls = array(); // 添加一个数组来存储已经使用过的imgUrl

if($mails && $i<=2){ // 输出第三个后，就不需要显示评论头像了
    foreach($mails as $avimg){    
        $imgUrl = getuserimg($avimg['authorId']);

        if (!in_array($imgUrl, $usedImgUrls)) { // 检查imgUrl是否已经在数组中
            $img_url=$img_url.'<img src="'.$imgUrl.'" class="avatar avatar-140 photo sider_pimg height="25" title="'.$avimg['author'].'">';                 
            $usedImgUrls[] = $imgUrl; // 添加imgUrl到数组中
        }
    }
    $img_url=$img_url.'<img src="'.$wu_url.'" class="avatar avatar-140 photo sider_pimg" height="25">';
}
            $val = Typecho_Widget::widget('Widget_Abstract_Contents')->push($val);
            $post_title = htmlspecialchars($val['title']);
            $permalink = $val['permalink'];
            $html = $html.'<li class="c_li"><div class="widget-posts-text"><a class="widget-posts-title" href="' .$permalink. '" title="' .$post_title. '"><span class="hotge hotge'.$i.'">'.$i.'</span>' .$post_title. '</a><div class="widget-posts-meta"><i>' . $val['commentsNum'] . ' 评论</i></div></div></li>'.$img_url.'';  
            $i += 1;
        }}
    return $html;
}

/**
* 显示下一篇
*
* @access public
* @param string $default 如果没有下一篇,显示的默认文字
* @return void
*/
function theNext($widget, $default = NULL)
{
$db = Typecho_Db::get();
$sql = $db->select()->from('table.contents')
->where('table.contents.created > ?', $widget->created)
->where('table.contents.status = ?', 'publish')
->where('table.contents.type = ?', $widget->type)
->where('table.contents.password IS NULL')
->order('table.contents.created', Typecho_Db::SORT_ASC)
->limit(1);
$content = $db->fetchRow($sql);
if ($content) {
$img =  $db->fetchAll($db->select()->from('table.fields')->where('name = ? AND cid = ?','img',$content['cid']));
					
						$imgs=$img['0']['str_value'];						
                        if(!$imgs){ $imgs=""; }                        						 
												
$strimg = stcdnimg($imgs); 
$content = $widget->filter($content);
echo '<div class="entry-page-next j-lazy" style="background-image: url('.$strimg.')"><a href="' . $content['permalink'] . '"><span>' . $content['title'] . '</span> </a><div class="entry-page-info"> <span class="pull-right">下一篇  »</span> <span class="pull-left">' . date('m-d', $content['created']) . '</span></div></div>';
} else { echo $default; }}

/**
* 显示上一篇
*
* @access public
* @param string $default 如果没有下一篇,显示的默认文字
* @return void
*/
function thePrev($widget, $default = NULL)
{
$imgs="";    
$db = Typecho_Db::get();
$sql = $db->select()->from('table.contents')
->where('table.contents.created < ?', $widget->created)
->where('table.contents.status = ?', 'publish')
->where('table.contents.type = ?', $widget->type)
->where('table.contents.password IS NULL')
->order('table.contents.created', Typecho_Db::SORT_DESC)
->limit(1);
$content = $db->fetchRow($sql);
if ($content) {
 $img =  $db->fetchAll($db->select()->from('table.fields')->where('name = ? AND cid = ?','img',$content['cid']));
			$imgs=$img['0']['str_value'];			
                   								
$strimg = stcdnimg($imgs);     
$content = $widget->filter($content);
echo '<div class="entry-page-prev j-lazy" style="background-image: url(';
print_r($strimg);
echo ')"><a href="' . $content['permalink'] . '"> <span>' . $content['title'] . '</span> </a><div class="entry-page-info"> <span class="pull-left">« 上一篇</span> <span class="pull-right">' . date('m-d', $content['created']) .'</span></div></div>';
}   
else {
echo $default;
}}



function autlist($i){
    $db=Typecho_Db::get();
    $mail=$db->fetchAll($db->select(array('COUNT(cid)'=>'rbq'))->from('table.comments')->where('mail = ?', $i)/**->where('authorId = ?','0')**/);
    foreach ($mail as $sl){
    $rbq=$sl['rbq'];}
    if($rbq<1){
    echo '<span class="utitle_v">'.$rbq.'</span>';
    }elseif ($rbq<10 && $rbq>0) {
    echo '<span class="utitle_lv1">'.$rbq.'</span>';
    }elseif ($rbq<20 && $rbq>=10) {
    echo '<span class="utitle_lv2">'.$rbq.'</span>';
    }elseif ($rbq<40 && $rbq>=20) {
    echo '<span class="utitle_lv3">'.$rbq.'</span>';
    }elseif ($rbq<80 && $rbq>=40) {
    echo '<span class="utitle_lv4">'.$rbq.'</span>';
    }elseif ($rbq<100 && $rbq>=80) {
    echo '<span class="utitle_lv5">'.$rbq.'</span>';
    }elseif ($rbq>=100) {
    echo '<span class="utitle_lv6">'.$rbq.'</span>';
    }
}
/**
* 首页随机文章
*/
function getRandomindex($random=5){
    $db = Typecho_Db::get();
    $adapterName = $db->getAdapterName();//兼容非MySQL数据库
    if($adapterName == 'pgsql' || $adapterName == 'Pdo_Pgsql' || $adapterName == 'Pdo_SQLite' || $adapterName == 'SQLite'){
        $order_by = 'RANDOM()';
    }else{
        $order_by = 'RAND()';
    }
    $sql = $db->select()->from('table.contents')
        ->where('status = ?','publish')
        ->where('table.contents.created <= ?', time())
        ->where('type = ?', 'post')
        ->limit($random)
        ->order($order_by);

$result = $db->fetchAll($sql);
if($result){
    foreach($result as $val){
        $obj = Typecho_Widget::widget('Widget_Abstract_Contents');
        $val = $obj->push($val);
        $post_title = htmlspecialchars($val['title']);
        $permalink = $val['permalink'];
		$created = date('m-d', $val['created']);
		$img =  $db->fetchAll($db->select()->from('table.fields')->where('name = ? AND cid = ?','img',$val['cid']));
					if(count($img) !=0){
						//var_dump($img);
						$img=$img['0']['str_value'];						
                        if($img){}
						else{
                         $img=showThumbnail($obj,0);
						}                        						 
					}

            $strimg = stcdnimg($img);   
            $limg="/usr/themes/spimes/src/images/pixel.gif";
            if ($strimg){$strimg=$strimg;}else{$strimg = "/usr/themes/spimes/src/images/thumbs/adimg.png";}
 


      echo '<li class="aui-slide-item-item"><a class="sle_i" href="'.$permalink.'" ><img class="scrollLoading" data-url="'.$strimg.'" src="'.$limg.'" alt=""><h2>'.$post_title.'</h2></a></li>';


    }
}
}

/**
* 随机文章
*/
function getRandomPosts($random=5){
    $db = Typecho_Db::get();
    $adapterName = $db->getAdapterName();//兼容非MySQL数据库
    if($adapterName == 'pgsql' || $adapterName == 'Pdo_Pgsql' || $adapterName == 'Pdo_SQLite' || $adapterName == 'SQLite'){
        $order_by = 'RANDOM()';
    }else{
        $order_by = 'RAND()';
    }
    $sql = $db->select()->from('table.contents')
        ->where('status = ?','publish')
        ->where('table.contents.created <= ?', time())
        ->where('type = ?', 'post')
        ->limit($random)
        ->order($order_by);

$result = $db->fetchAll($sql);
if($result){
    foreach($result as $val){
        $obj = Typecho_Widget::widget('Widget_Abstract_Contents');
        $val = $obj->push($val);
        $post_title = htmlspecialchars($val['title']);
        $permalink = $val['permalink'];
		$created = date('m-d', $val['created']);
		$img =  $db->fetchAll($db->select()->from('table.fields')->where('name = ? AND cid = ?','img',$val['cid']));
					if(count($img) !=0){
						//var_dump($img);
						$img=$img['0']['str_value'];						
                        if($img){}
						else{
                         $img=showThumbnail($obj,0);
						}                        						 
					}

            $strimg = stcdnimg($img);   
            if ($strimg){$strimg=$strimg;}else{$strimg = "/usr/themes/spimes/src/images/thumbs/adimg.png";}
        echo '<div class="item"><div class="hunter-item"><a href="'.$permalink.'"><div class="hunter-thumb"><i class="thumb " style="background-image:url(';
print_r($strimg);
echo ')"><i class="mask"></i></i></div><h2>'.$post_title.'</h2><!--<h4><span class="hunter-tag btn btn-default">' . $val['commentsNum'] .' 评论</span> <span class="hunter-product">'.$created.'</span></h4>--></a></div></div>';
    }
}
}

/** 会员列表中输出作者最近文章列表带图 */
function nerPosts($authorid){
    $html='';
    if($authorid){ 
        $limit = 3;
        $db = Typecho_Db::get();
        $result = $db->fetchAll($db->select()->from('table.contents')
            ->where('authorId = ?',$authorid)
            ->where('status = ?','publish')
            ->where('type = ?', 'post')
            ->limit($limit)
            //->order('created', Typecho_Db::SORT_DESC)  
            
        );
        if($result){
            foreach($result as $val){                
                $val = Typecho_Widget::widget('Widget_Abstract_Contents')->push($val);
                $post_title = htmlspecialchars($val['title']);
                $permalink = $val['permalink'];
				$commentsNum = $val['commentsNum'];
				$imgs =  $db->fetchAll($db->select()->from('table.fields')->where('name = ? AND cid = ?','img',$val['cid']));
					if(count($imgs) !=0){$img=$imgs['0']['str_value'];}
				if(!$img){ $img='/usr/themes/spimes/src/images/thumbs/adimg.png';	} 	
                $strimg = stcdnimg($img);   
                $html= $html.'<li class="work-show-item"><div class="feaimg"><a class="block-fea" href="'.$permalink.'" title="'.$post_title.'" style="background-image: url('.$strimg.');"></a></div></li>';				
            }
        }
        else{ $html=''; }
    }else{
        
    }
    return $html;
}



class Widget_Post_hot extends Widget_Abstract_Contents
{
    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);
        $this->parameter->setDefault(array('pageSize' => $this->options->commentsListSize, 'parentId' => 0, 'ignoreAuthor' => false));
    }
    public function execute()
    {
        $select  = $this->select()->from('table.contents')
->where("table.contents.password IS NULL OR table.contents.password = ''")
->where('table.contents.status = ?','publish')
->where('table.contents.created <= ?', time())
->where('table.contents.type = ?', 'post')
->limit($this->parameter->pageSize)
->order('table.contents.views', Typecho_Db::SORT_DESC);
 $this->db->fetchAll($select, array($this, 'push'));
    }
}


// 生成地图
function getxml(){

        $doc = new \DOMDocument('1.0','utf-8');//引入类并且规定版本编码
        $urlset = $doc->createElement("urlset");//创建节点 
        
        $db = Typecho_Db::get();
        $result = $db->fetchAll($db->select()->from('table.contents')
        ->where('status = ?','publish')
        ->where('type = ?', 'post')
        ->where('created <= unix_timestamp(now())', 'post') //添加这一句避免未达到时间的文章提前曝光
        ->limit(100)
        ->order('created', Typecho_Db::SORT_DESC)
        );
        if($result){
        foreach($result as $val){            
            $val = Typecho_Widget::widget('Widget_Abstract_Contents')->push($val);
            $permalink = $val['permalink'];
            $created = date('Y-m-d', $val['created']);   
                
        /*循环输出节点*/        
        $url = $doc->createElement("url");//创建节点  
		$loc = $doc->createElement("loc");//创建节点
		$lastmod = $doc->createElement("lastmod");//创建节点
		$urlset->appendChild($url);//
        $url->appendChild($loc);//讲loc放到url下
		$url->appendChild($lastmod );
        $content = $doc -> createTextNode($permalink);//设置标签内容
        $contime = $doc -> createTextNode($created);//设置标签内容
        $loc  -> appendChild($content);//将标签内容赋给标签
		$lastmod  -> appendChild($contime);//将标签内容赋给标签    
        
        }}

        $doc->appendChild($urlset);//创建顶级节点
        $doc->save("./../sitemap.xml");//保存代码
        echo "<script>alert('地图生成')</script>";
}



// 添加OwO表情
function parsePaopaoBiaoqingCallback($match)
    {
        return '<img class="biaoqing" src="/usr/themes/spimes/src/owo/img/paopao/'. str_replace('%', '', urlencode($match[1])) . '_2x.png">';
    }
function parseAruBiaoqingCallback($match)
    {
        return '<img class="biaoqing" src="/usr/themes/spimes/src/owo/img/aru/'. str_replace('%', '', urlencode($match[1])) . '_2x.png">';
    }
function parseQuyinBiaoqingCallback($match)
    {
        return '<img class="biaoqing" src="/usr/themes/spimes/src/owo/img/quyin/'. str_replace('%', '', urlencode($match[1])) . '_2x.png">';
    }
function parseadaiBiaoqingCallback($match)
    {
        return '<img class="biaoqing" src="/usr/themes/spimes/src/owo/img/adai/'. str_replace('%', '', urlencode($match[1])) . '.gif">';
    }

function parseBiaoQing($content)
    {
        $content = preg_replace_callback('/\:\:\(\s*(呵呵|哈哈|吐舌|惊恐|酷|发飙|嗯哼|流汗|大哭|尴尬|黑脸|超赞|金钱|疑问|尬脸|吐|哦豁|委屈|眯眼笑|哟嚯|超开心|滑稽|眨眼|羡慕|入眠|惊哭|气呼呼|震惊|喷水|爱心|心碎|玫瑰|礼物|咖啡|OK|小无奈|偷笑|坏笑|卧槽|要死|亚麻跌|笑哭了|犀利|理一下|坐端正|完美|吃瓜|摊手|困狗|靠墙看|发现|饮酒|忍笑|警告|炸黑|滑稽汗|打脸|胖次|低看)\s*\)/is',
'parsePaopaoBiaoqingCallback', $content);

		$content = preg_replace_callback('/\:\*\(\s*(愤怒|装酷|委屈|鄙视|尴尬|魔鬼|惊恐|亲亲|喜欢|猪头|抠鼻|伤心|吃惊|微笑|邪笑|失落|冒汗|闭嘴)\s*\)/is',
'parseadaiBiaoqingCallback', $content);

        $content = preg_replace_callback('/\:\@\(\s*(发火|羡慕|吐血倒地|吐血|抱抱|鼓掌|呆滞|流泪|嫌疑|灵魂出窍|囧|惊慌|流口水|送花花|不高兴|阴险|捅死你|暗中观察|哲学思考|无奈|嘟嘴|大佬|闭嘴|害羞|脸红|黑线|举白旗|赞|吐舌头)\s*\)/is',
'parseAruBiaoqingCallback', $content);

		$content = preg_replace_callback('/\:\&\(\s*(蛆音滑稽|蛆音震惊|蛆音生气|蛆音吓哭|蛆音血躺|蛆音疑惑|蛆音捡肥皂|蛆音捂脸|蛆音吐血石化|蛆音哼气|蛆音大笑|蛆音偷看|蛆音卖萌|蛆音好的|蛆音惊吓|蛆音摇头|蛆音睡觉|蛆音无语|蛆音吃瓜|蛆音自恋)\s*\)/is',
'parseQuyinBiaoqingCallback', $content);
        return $content;
}




/**输出点赞数*/
function zannum($archive){
    $db = Typecho_Db::get();
    $cid = $archive->cid;
    $exist = 0;
    $prefix = $db->getPrefix();
    //  判断点赞数量字段是否存在
    if (!array_key_exists('agree', $db->fetchRow($db->select()->from('table.contents')))) {
        //  在文章表中创建一个字段用来存储点赞数量
        $db->query('ALTER TABLE `' . $prefix . 'contents` ADD `agree` INT(10) NOT NULL DEFAULT 0;');
    }
    if (array_key_exists('agree', $db->fetchRow($db->select()->from('table.contents')))) {   
    $exist = $db->fetchRow($db->select('agree')->from('table.contents')->where('cid = ?', $cid))['agree'];
    }
    echo $exist == 0 ? '0 ' : $exist.'';
}

/**列表输出点赞数*/
function list_zannum($archive){
    $db = Typecho_Db::get();
    $cid = $archive->cid;
    $exist = 0;
    $prefix = $db->getPrefix();
    //  判断点赞数量字段是否存在
    if (!array_key_exists('agree', $db->fetchRow($db->select()->from('table.contents')))) {
        //  在文章表中创建一个字段用来存储点赞数量
        $db->query('ALTER TABLE `' . $prefix . 'contents` ADD `agree` INT(10) NOT NULL DEFAULT 0;');
    }
    if (array_key_exists('agree', $db->fetchRow($db->select()->from('table.contents')))) {   
    $exist = $db->fetchRow($db->select('agree')->from('table.contents')->where('cid = ?', $cid))['agree'];
    }
    if($exist==null){ $exist == 0; }
    echo $exist == 0 ? '' : '<i class="ri-heart-line ri-lg"></i> '.$exist.' 赞';
}

/**点赞**/

function agreeNum($cid) {
    $db = Typecho_Db::get();
    $prefix = $db->getPrefix();
    //  判断点赞数量字段是否存在
    if (!array_key_exists('agree', $db->fetchRow($db->select()->from('table.contents')))) {
        //  在文章表中创建一个字段用来存储点赞数量
        $db->query('ALTER TABLE `' . $prefix . 'contents` ADD `agree` INT(10) NOT NULL DEFAULT 0;');
    }

    //  查询出点赞数量
    $agree = $db->fetchRow($db->select('table.contents.agree')->from('table.contents')->where('cid = ?', $cid));
    //  获取记录点赞的 Cookie
    $AgreeRecording = Typecho_Cookie::get('typechoAgreeRecording');
    //  判断记录点赞的 Cookie 是否存在
    if (empty($AgreeRecording)) {
        //  如果不存在就写入 Cookie
        Typecho_Cookie::set('typechoAgreeRecording', json_encode(array(0)));
    }

    //  返回
    return array(
        //  点赞数量
        'agree' => $agree['agree'],
        //  文章是否点赞过
        'recording' => in_array($cid, json_decode(Typecho_Cookie::get('typechoAgreeRecording')))?true:false
    );
}

function agree($cid) {
    $db = Typecho_Db::get();
    //  根据文章的 `cid` 查询出点赞数量
    $agree = $db->fetchRow($db->select('table.contents.agree')->from('table.contents')->where('cid = ?', $cid));
    //  获取点赞记录的 Cookie
    $agreeRecording = Typecho_Cookie::get('typechoAgreeRecording');
    //  判断 Cookie 是否存在
    if (empty($agreeRecording)) {
        //  如果 cookie 不存在就创建 cookie
        Typecho_Cookie::set('typechoAgreeRecording', json_encode(array($cid)));
    }else {
        //  把 Cookie 的 JSON 字符串转换为 PHP 对象
        $agreeRecording = json_decode($agreeRecording);
        //  判断文章是否点赞过
        if (in_array($cid, $agreeRecording)) {
            //  如果当前文章的 cid 在 cookie 中就返回文章的赞数，不再往下执行
            echo "<script> cocoMessage.warning('已点赞过了!');</script>";
            return $agree['agree'];
        }
        //  添加点赞文章的 cid
        array_push($agreeRecording, $cid);
        echo "<script> cocoMessage.success('点赞+1');</script>";
        Typecho_Cookie::set('typechoAgreeRecording', json_encode($agreeRecording));
    }
    //  更新点赞字段，让点赞字段 +1
    $db->query($db->update('table.contents')->rows(array('agree' => (int)$agree['agree'] + 1))->where('cid = ?', $cid));
    //  查询出点赞数量
    $agree = $db->fetchRow($db->select('table.contents.agree')->from('table.contents')->where('cid = ?', $cid));
    //  返回点赞数量
    return $agree['agree'];
}



/** HTML压缩功能 */
function compressHtml($html_source) {
    $chunks = preg_split('/(<!--<nocompress>-->.*?<!--<\/nocompress>-->|<nocompress>.*?<\/nocompress>|<pre.*?\/pre>|<textarea.*?\/textarea>|<script.*?\/script>)/msi', $html_source, -1, PREG_SPLIT_DELIM_CAPTURE);
    $compress = '';
    foreach ($chunks as $c) {
        if (strtolower(substr($c, 0, 19)) == '<!--<nocompress>-->') {
            $c = substr($c, 19, strlen($c) - 19 - 20);
            $compress .= $c;
            continue;
        } else if (strtolower(substr($c, 0, 12)) == '<nocompress>') {
            $c = substr($c, 12, strlen($c) - 12 - 13);
            $compress .= $c;
            continue;
        } else if (strtolower(substr($c, 0, 4)) == '<pre' || strtolower(substr($c, 0, 9)) == '<textarea') {
            $compress .= $c;
            continue;
        } else if (strtolower(substr($c, 0, 7)) == '<script' && strpos($c, '//') != false && (strpos($c, "\r") !== false || strpos($c, "\n") !== false)) {
            $tmps = preg_split('/(\r|\n)/ms', $c, -1, PREG_SPLIT_NO_EMPTY);
            $c = '';
            foreach ($tmps as $tmp) {
                if (strpos($tmp, '//') !== false) {
                    if (substr(trim($tmp), 0, 2) == '//') {
                        continue;
                    }
                    $chars = preg_split('//', $tmp, -1, PREG_SPLIT_NO_EMPTY);
                    $is_quot = $is_apos = false;
                    foreach ($chars as $key => $char) {
                        if ($char == '"' && $chars[$key - 1] != '\\' && !$is_apos) {
                            $is_quot = !$is_quot;
                        } else if ($char == '\'' && $chars[$key - 1] != '\\' && !$is_quot) {
                            $is_apos = !$is_apos;
                        } else if ($char == '/' && $chars[$key + 1] == '/' && !$is_quot && !$is_apos) {
                            $tmp = substr($tmp, 0, $key);
                            break;
                        }
                    }
                }
                $c .= $tmp;
            }
        }
        $c = preg_replace('/[\\n\\r\\t]+/', ' ', $c);
        $c = preg_replace('/\\s{2,}/', ' ', $c);
        $c = preg_replace('/>\\s</', '> <', $c);
        $c = preg_replace('/\\/\\*.*?\\*\\//i', '', $c);
        $c = preg_replace('/<!--[^!]*-->/', '', $c);
        $compress .= $c;
    }
    return $compress;
}

/**
 * 文章内容替换为内链
 * @param $content
 * @return mixed
 */
function get_glo_keywords($content)
{   
	$settings = Helper::options()->Keywordspress;	
	$keywords_list = array();
	if (strpos($settings,'|')) {
			//解析关键词数组
			$kwsets = array_filter(preg_split("/(\r|\n|\r\n)/",$settings));
			foreach ($kwsets as $kwset) {
			$keywords_list[] = explode('|',$kwset);
			}
		}
	ksort($keywords_list);  //对关键词排序，短词排在前面
	
    if($keywords_list){
        $readnum = 0;
		$i = 0;
		$j = 1;
        foreach ($keywords_list as $key => $val) {
			
            $title = $val[$i];
            $len = strlen($title);
            $str = '<a href="'.$val[$j].'" target="_blank">'.$title.'</a>';
            $str_index = mb_strpos($content, $title);            
			$content = preg_replace('/(?!<[^>]*)'.$title.'(?![^<]*>)/',$str,$content,1); 
			
            if(is_numeric($str_index)){
                $readnum += 1;
            }
            if($readnum == 8) {
			return $content; //匹配到8个关键词就退出
			$i += 2;
            $j += 2;
            }
		}
    }
    return $content;
}



/**处理边栏快讯**/
function get_kuaixun()
{
    $html='';
    $newico='';
    $hotico='';
    $db = Typecho_Db::get();
    $result = $db->fetchAll($db->select()->from('table.contents')
        ->where('status = ?','publish')
        ->where('type = ?', 'post')
        ->where('created <= unix_timestamp(now())', 'post')
        ->limit(5)
        ->order('RAND()')
    );
        
        if($result){
        foreach($result as $val){   
            $newico='';
            $hotico='';
            $val = Typecho_Widget::widget('Widget_Abstract_Contents')->push($val);
            $permalink = $val['permalink'];
            $created = date('Y-m-d', $val['created']);
            if(timeZone($val['created'])){ $newico='<i class="news-icon new"></i>'; }
            if(hotZone($val['cid'],$val['created'])){ $hotico='<i class="news-icon hot"></i>'; }
            $post_title = htmlspecialchars($val['title']);
            $html = $html.'<li class="kx-item"> 
            <a class="kx-title" href="'.$permalink.'">'.$newico.''.$hotico.''.$post_title.'</a><div class="kx-meta clearfix"> <span class="kx-time">'.$created.'</span></div></li>';
        }}
    
        return '<div class="column-newsflash-vertiacl-line"></div>'.$html;
    
}



/**
 * 幻灯片下方栏目pjax处理数组
 */
function navsecinfo()
{   
	$settings = Helper::options()->navsecs;	
	$navtops_list = array();
	if (strpos($settings,'|')) {
			//解析关键词数组
			$kwsets = array_filter(preg_split("/(\r|\n|\r\n)/",$settings));
			foreach ($kwsets as $kwset) {
			$navtops_list[] = explode('|',$kwset);
			}
		}
	ksort($navtops_list);  //对关键词排序，短词排在前面	
    if($navtops_list){
        $readnum = 0;
		$i = 0;
		$j = 1;		
        foreach ($navtops_list as $key => $val) {			
            $title = $val[$i]; 
            $str = '<li class="menu-item"><a data-post href="'.$val[$j].'"> '.$title.'</a></li> ';	            
                $readnum += 1;
				echo $str;
                //$content = substr_replace($content,$str,$str_index,$len);
                //$content = $this->str_replace_limit($title,$str,$content,$this->limit);
            if($readnum == 4) {
			//匹配到8个关键词就退出
			$i += 2;
            $j += 2;           
            }
		}
    }
}

// 获取浏览器信息
function getBrowser($agent)
{
    if (preg_match('/MSIE\s([^\s|;]+)/i', $agent, $regs)) {
        $outputer = '<i class="ua-icon icon-ie"></i>&nbsp;&nbsp;Internet Explore';
    } else if (preg_match('/FireFox\/([^\s]+)/i', $agent, $regs)) {
      $str1 = explode('Firefox/', $regs[0]);
$FireFox_vern = explode('.', $str1[1]);
        $outputer = '<i class="ua-icon icon-firefox"></i>&nbsp;&nbsp;FireFox';
    } else if (preg_match('/Maxthon([\d]*)\/([^\s]+)/i', $agent, $regs)) {
      $str1 = explode('Maxthon/', $agent);
$Maxthon_vern = explode('.', $str1[1]);
        $outputer = '<i class="ua-icon icon-edge"></i>&nbsp;&nbsp;MicroSoft Edge';
    } else if (preg_match('#360([a-zA-Z0-9.]+)#i', $agent, $regs)) {
$outputer = '<i class="ua-icon icon-360"></i>&nbsp;&nbsp;360极速浏览器';
    } else if (preg_match('/Edge([\d]*)\/([^\s]+)/i', $agent, $regs)) {
        $str1 = explode('Edge/', $regs[0]);
$Edge_vern = explode('.', $str1[1]);
        $outputer = '<i class="ua-icon icon-edge"></i>&nbsp;&nbsp;MicroSoft Edge';
    } else if (preg_match('/UC/i', $agent)) {
              $str1 = explode('rowser/',  $agent);
$UCBrowser_vern = explode('.', $str1[1]);
        $outputer = '<i class="ua-icon icon-uc"></i>&nbsp;&nbsp;UC浏览器';
    }  else if (preg_match('/QQ/i', $agent, $regs)||preg_match('/QQBrowser\/([^\s]+)/i', $agent, $regs)) {
                  $str1 = explode('rowser/',  $agent);
$QQ_vern = explode('.', $str1[1]);
        $outputer = '<i class= "ua-icon icon-qqq"></i>&nbsp;&nbsp;QQ浏览器';
    } else if (preg_match('/UBrowser/i', $agent, $regs)) {
              $str1 = explode('rowser/',  $agent);
$UCBrowser_vern = explode('.', $str1[1]);
        $outputer = '<i class="ua-icon icon-uc"></i>&nbsp;&nbsp;UC浏览器';
    }  else if (preg_match('/Opera[\s|\/]([^\s]+)/i', $agent, $regs)) {
        $outputer = '<i class= "ua-icon icon-opera"></i>&nbsp;&nbsp;Opera';
    } else if (preg_match('/Chrome([\d]*)\/([^\s]+)/i', $agent, $regs)) {
$str1 = explode('Chrome/', $agent);
$chrome_vern = explode('.', $str1[1]);
        $outputer = '<i class="ua-icon icon-chrome"></i>&nbsp;&nbsp;Chrome';
    } else if (preg_match('/safari\/([^\s]+)/i', $agent, $regs)) {
         $str1 = explode('Version/',  $agent);
$safari_vern = explode('.', $str1[1]);
        $outputer = '<i class="ua-icon icon-safari"></i>&nbsp;&nbsp;Safari';
    } else{
        $outputer = '<i class="ua-icon icon-chrome"></i>&nbsp;&nbsp;Google Chrome';
    }
    echo $outputer;
}
// 获取操作系统信息
function getOs($agent)
{
    $os = false;
 
    if (preg_match('/win/i', $agent)) {
        if (preg_match('/nt 6.0/i', $agent)) {
            $os = '&nbsp;&nbsp;<i class= "ua-icon icon-win1"></i>&nbsp;&nbsp;Win Vista&nbsp;/&nbsp;';
        } else if (preg_match('/nt 6.1/i', $agent)) {
            $os = '&nbsp;&nbsp;<i class= "ua-icon icon-win1"></i>&nbsp;&nbsp;Win 7&nbsp;/&nbsp;';
        } else if (preg_match('/nt 6.2/i', $agent)) {
            $os = '&nbsp;&nbsp;<i class="ua-icon icon-win2"></i>&nbsp;&nbsp;Win 8&nbsp;/&nbsp;';
        } else if(preg_match('/nt 6.3/i', $agent)) {
            $os = '&nbsp;&nbsp;<i class= "ua-icon icon-win2"></i>&nbsp;&nbsp;Win 8.1&nbsp;/&nbsp;';
        } else if(preg_match('/nt 5.1/i', $agent)) {
            $os = '&nbsp;&nbsp;<i class="ua-icon icon-win1"></i>&nbsp;&nbsp;Win XP&nbsp;/&nbsp;';
        } else if (preg_match('/nt 10.0/i', $agent)) {
            $os = '&nbsp;&nbsp;<i class="ua-icon icon-win2"></i>&nbsp;&nbsp;Win 10&nbsp;/&nbsp;';
        } else{
            $os = '&nbsp;&nbsp;<i class="ua-icon icon-win2"></i>&nbsp;&nbsp;Win X64&nbsp;/&nbsp;';
        }
    } else if (preg_match('/android/i', $agent)) {
    if (preg_match('/android 9/i', $agent)) {
            $os = '&nbsp;&nbsp;<i class="ua-icon icon-android"></i>&nbsp;&nbsp;Android&nbsp;/&nbsp;';
        }
    else if (preg_match('/android 8/i', $agent)) {
            $os = '&nbsp;&nbsp;<i class="ua-icon icon-android"></i>&nbsp;&nbsp;Android&nbsp;/&nbsp;';
        }
    else{
            $os = '&nbsp;&nbsp;<i class="ua-icon icon-android"></i>&nbsp;&nbsp;Android&nbsp;/&nbsp;';
    }
    }
    else if (preg_match('/ubuntu/i', $agent)) {
        $os = '&nbsp;&nbsp;<i class="ua-icon icon-ubuntu"></i>&nbsp;&nbsp;Ubuntu&nbsp;/&nbsp;';
    } else if (preg_match('/linux/i', $agent)) {
        $os = '&nbsp;&nbsp;<i class= "ua-icon icon-linux"></i>&nbsp;&nbsp;Linux&nbsp;/&nbsp;';
    } else if (preg_match('/iPhone/i', $agent)) {
        $os = '&nbsp;&nbsp;<i class="ua-icon icon-apple"></i>&nbsp;&nbsp;iPhone&nbsp;/&nbsp;';
    } else if (preg_match('/mac/i', $agent)) {
        $os = '&nbsp;&nbsp;<i class="ua-icon icon-mac"></i>&nbsp;&nbsp;MacOS&nbsp;/&nbsp;';
    }else if (preg_match('/fusion/i', $agent)) {
        $os = '&nbsp;&nbsp;<i class="ua-icon icon-android"></i>&nbsp;&nbsp;Android&nbsp;/&nbsp;';
    } else {
        $os = '&nbsp;&nbsp;<i class="ua-icon icon-linux"></i>&nbsp;&nbsp;Linux&nbsp;/&nbsp;';
    }
    echo $os;
}

/**
 * 手机底部列表菜单
 */
function navmobis()
{   
	$settings = Helper::options()->navmobi;	
	$navtops_list = array();
	if (strpos($settings,'|')) {
			//解析关键词数组
			$kwsets = array_filter(preg_split("/(\r|\n|\r\n)/",$settings));
			foreach ($kwsets as $kwset) {
			$navtops_list[] = explode('|',$kwset);
			}
		}
	ksort($navtops_list);  //对关键词排序，短词排在前面	
    if($navtops_list){
        $readnum = 0;
		$i = 0;
		$j = 1;		
        foreach ($navtops_list as $key => $val) { 
         $str = '<div class="navigation-tab-item"><a data-pjax href="'.$val[$j].'"><span class="navigation-tab__icon">'.$val[$i].'</span></a></div>';            
                $readnum += 1;
				echo $str;
                //$content = substr_replace($content,$str,$str_index,$len);
                //$content = $this->str_replace_limit($title,$str,$content,$this->limit);          
            if($readnum == 4) {
			//匹配到8个关键词就退出
			$i += 2;
            $j += 2;           
            }
		}
    }
}

//获取评论的锚点链接
function get_comment_at($coid)
{
    $db   = Typecho_Db::get();
    $prow = $db->fetchRow($db->select('parent,status')->from('table.comments')
        ->where('coid = ?', $coid));//当前评论
    $mail = "";
    $parent = @$prow['parent'];
    if ($parent != "0") {//子评论
        $arow = $db->fetchRow($db->select('author,status,mail')->from('table.comments')
            ->where('coid = ?', $parent));//查询该条评论的父评论的信息
        @$author = @$arow['author'];//作者名称
        $mail = @$arow['mail'];
        if(@$author && $arow['status'] == "approved"){//父评论作者存在且父评论已经审核通过
            if (@$prow['status'] == "waiting"){
                echo '<p class="commentReview">（评论审核中）)</p>';
            }
            echo '<a href="#comment-' . $parent . '">@' . $author . '</a>';
        }else{//父评论作者不存在或者父评论没有审核通过
            if (@$prow['status'] == "waiting"){
                echo '<p class="commentReview">（评论审核中）)</p>';
            }else{
                echo '';
            }
        }

    } else {//母评论，无需输出锚点链接
        if (@$prow['status'] == "waiting"){
            echo '<p class="commentReview">（评论审核中）)</p>';
        }else{
            echo '';
        }
    }
}

/** 阅读时间 */
function art_time ($cid){
    $db=Typecho_Db::get ();
    $rs=$db->fetchRow ($db->select ('table.contents.text')->from ('table.contents')->where ('table.contents.cid=?',$cid)->order ('table.contents.cid',Typecho_Db::SORT_ASC)->limit (1));
    $text = preg_replace("/[^\x{4e00}-\x{9fa5}]/u", "", $rs['text']);
    $text_word = mb_strlen($text,'utf-8');
    echo ceil($text_word / 200);
}






/**将正文转成摘要的代码*/
function cutArticle($data,$cut=0,$str="....")  
{     
    $data=strip_tags($data);//去除html标记  
    $pattern = "/&[a-zA-Z]+;/";//去除特殊符号  
    $data=preg_replace($pattern,'',$data);  
    if(!is_numeric($cut))  
        return $data;  
    if($cut>0)  
        $data=mb_strimwidth($data,0,$cut,$str); 
    return $data;  
} 

/** 后台编辑器文章输出 */
function costcn($cid,$mid,$str,$status){  
//回复可见
if ( strpos( $str, '[hide')!== false) {//提高效率，避免每篇文章都要解析 
$db = Typecho_Db::get();
$sql = $db->select()->from('table.comments')
    ->where('cid = ?',$cid)
    ->where('mail = ?', $mid)
    ->where('status = ?', 'approved')
//只有通过审核的评论才能看回复可见内容
    ->limit(1);
$result = $db->fetchAll($sql);
if($status || $result) {
    $str = preg_replace("/\[hide\](.*?)\[\/hide\]/sm",'<div class="reply2view">$1</div>',$str);
}
else{
    $str = preg_replace("/\[hide\](.*?)\[\/hide\]/sm",'<div class="reply2view"><i class="ri-rotate-lock-fill ri-lg"></i> 此处内容需要评论回复后</div>',$str);
} }  

//提示框短代码
if ( strpos( $str, '[scode')!== false) {//提高效率，避免每篇文章都要解析
  //[scode class="red"]这里编辑标签内容//[/scode]   
   $str = preg_replace("/\[scode\](.*?)\[\/scode\]/sm",'<div class="tip ">$1</div>',$str);
}  
  
//按钮短代码
if ( strpos( $str, '[button')!== false) {//提高效率，避免每篇文章都要解析
  //[scode class="red"]这里编辑标签内容//[/scode]   
   $str = preg_replace("/\[button\ a=(.*?)\](.*?)\[\/button\]/sm",'<div class="btn_nt"><a class="btns" target="_blank" href="//$1"><i class="icon iconfont icon-icon-test14"></i> $2</a></div>',$str);
}  


  
//调用其他文章短代码
if ( strpos( $str, '[post')!== false) {//提高效率，避免每篇文章都要解析  
   preg_match_all("/\[post\](.*?)\[\/post\]/sm",$str,$strcid);
for($i=0;$i<count($strcid[1]);$i++){
    $scid =  $strcid[1][$i];     
    $db = Typecho_Db::get();
    $result = $db->fetchAll($db->select()->from('table.contents')->where('cid = ?', $scid));
    if($result){
        foreach($result as $val){            
            $val = Typecho_Widget::widget('Widget_Abstract_Contents')->push($val);
            $post_title = htmlspecialchars($val['title']);
            $permalink = $val['permalink'];
            $post_text = preg_replace('/($s*$)|(^s*^)/m', '',strip_tags($val['text'])); //获取内容
            $cont_text = cutArticle($post_text,150);
            $post_views = convert($val['views']);  
            $created = date('m-d', $val['created']);
            $pnum = $val['commentsNum'];
            $img =  $db->fetchAll($db->select()->from('table.fields')->where('name = ? AND cid = ?','img',$scid));
					if(count($img) !=0){
						//var_dump($img);
						$img=$img['0']['str_value'];						
                        if($img){}
						else{ $img=showThumbnail($val,0); }                        						 
					}
            $strimg = stcdnimg($img);   
            $html='<div class="post-list contt blockimg"><div class="entry-container"><div class="block-image feaimg"><a class="block-fea" href="' .$permalink. '" title="' .$post_title. '" style="background-image: url(' .$strimg. ');"></a></div><header class="entry-header"><span class="entry-title"><a href="' .$permalink. '">' .$post_title. '</a></span></header><div class="entry-summary"><p>' . $cont_text . '</p></div></div></div>';  
        } }    
   $str = preg_replace("/\[post\](".$scid.")\[\/post\]/sm",$html,$str);  
  }  
}    
   return $cosen=parseBiaoQing($str);
}

/**短代码过滤**/
function filter($text) {

    $text = preg_replace('/\!\[.*\]\((.*?)\)/', '[图片]',$text); //获取内容
	$text = preg_replace('/\!\[.*\]\[(.*?)\]/', '[图片]',$text); //获取内容
	$text= preg_replace("/\[scode\](.*?)\[\/scode\]/sm",'',$text);
    $text= preg_replace("/\[button\](.*?)\[\/button\]/sm",'',$text);
    $text= preg_replace("/\[hide\](.*?)\[\/hide\]/sm",'<div class="reply2view"><i class="ri-rotate-lock-fill ri-lg"></i> 此处内容需要评论回复后</div>',$text);
    $text = mb_strlen($text, 'utf-8') > 80 ? mb_substr($text, 0, 80, 'utf-8').'....' : $text; //格式化内容
    return $text;
}


/**
 * 加载时间
 * @return bool
 */
function timer_start() {
    global $timestart;
    $mtime     = explode( ' ', microtime() );
    $timestart = $mtime[1] + $mtime[0];
    return true;
}
timer_start();
function timer_stop( $display = 0, $precision = 3 ) {
    global $timestart, $timeend;
    $mtime  = explode( ' ', microtime() );
    $timeend  = $mtime[1] + $mtime[0];
    $timetotal = number_format( $timeend - $timestart, $precision );
    $r   = $timetotal < 1 ? $timetotal * 1000 . " ms" : $timetotal . " s";
    if ( $display ) {
    echo $r;
    }
    return $r;
}



/**边栏ajax数据调用*/
function time_ajax($a)  
{   
    if($a=="time"){ return ajax_time(); } 
   
} 

function ajax_time(){
	    $html='';
	    $limit='';
	    $newico='';
	    $hotico='';
	    $db = Typecho_Db::get();
        $options = Typecho_Widget::widget('Widget_Options');
        $limit = is_numeric($limit) ? $limit : 5;
        $posts = $db->fetchAll($db->select()->from('table.contents')
                 ->where('type = ? AND status = ? AND password IS NULL', 'post', 'publish')
                 ->order('RAND()')
                 ->limit($limit)
                 );
        if ($posts) {
            foreach ($posts as $post) {
                $newico='';
	            $hotico='';
                $result = Typecho_Widget::widget('Widget_Abstract_Contents')->push($post);
                $post_title = htmlspecialchars($result['title']);
                $permalink = $result['permalink'];
				$created = date('m-d', $result['created']);  
		        if(timeZone($result['created'])){ $newico='<i class="news-icon new"></i>'; }
		        if(hotZone($result['cid'],$result['created'])){ $hotico='<i class="news-icon hot"></i>'; }
		    $html = $html.'<li class="kx-item"> 
            <a class="kx-title" href="'.$permalink.'">'.$newico.''.$hotico.''.$post_title.'</a><div class="kx-meta clearfix"> <span class="kx-time">'.$created.'</span></div></li>';
            
			 }
        } else {
         
        }

        return '<div class="column-newsflash-vertiacl-line"></div>'.$html;
}
