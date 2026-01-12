<?php


function categeid($slug){  //获取栏目id
   $db = Typecho_Db::get();
   $postnum=$db->fetchRow($db->select()->from ('table.metas')->where ('slug=?',$slug)->where('type=?', 'category'));
   return  $postnum['mid']; 
}

function catepages($mid){  //获取栏目名
   $catep=0;
   if(catepagesmid($mid)=='1'){ $catep=1; }
   if(catepagesmid($mid)=='2'){ $catep=2; }
   if(catepagesmid($mid)=='3'){ $catep=3; }
   return $catep;

}

function seotitle($obj){
    $db = Typecho_Db::get();
    $postnum=$db->fetchRow($db->select()->from ('table.metas')->where ('mid=?',$obj));
    return  $postnum['seotitle']; 
    }
function seokey($obj){
    $db = Typecho_Db::get();
    $postnum=$db->fetchRow($db->select()->from ('table.metas')->where ('mid=?',$obj));
    return  $postnum['seokey']; 
    }
function seodesc($obj){
    $db = Typecho_Db::get();
    $postnum=$db->fetchRow($db->select()->from ('table.metas')->where ('mid=?',$obj));
    return  $postnum['seodesc']; 
    }
function catepagesmid($obj){
    $db = Typecho_Db::get();
    $postnum=$db->fetchRow($db->select()->from ('table.metas')->where ('mid=?',$obj));
    return  $postnum['catepages']; 
    }
function cateico($mid){  //获取栏目图片

    $siteUrl = '';    
    $db = Typecho_Db::get();
    $postnum=$db->fetchRow($db->select()->from ('table.metas')->where ('mid=?',$mid));
    if(!$postnum['cateico']){ return $siteUrl; }
    else{ return  '<i class="'.$postnum['cateico'].'"></i>'; }

}    
function cateonseo($slug){  //判断是否存在标题关键字

   $db = Typecho_Db::get();
   $postnum=$db->fetchRow($db->select()->from ('table.metas')->where ('slug=?',$slug)->where('type=?', 'category'));
   $a = $postnum['seotitle']; 
   $b = $postnum['seokey'];
   $c = $postnum['seodesc'];
   if($a||$b||$c){ return true; }
   else{ return false; }
}
function geseo($slug,$s_name){  //获取栏目id

   $db = Typecho_Db::get();
   $postnum=$db->fetchRow($db->select()->from ('table.metas')->where ('slug=?',$slug)->where('type=?', 'category'));
   return  $postnum[$s_name]; 

}