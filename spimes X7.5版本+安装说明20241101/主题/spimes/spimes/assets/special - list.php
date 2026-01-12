<?php global $id; $ztadimg='';
if ($this->options->ztadimg){ $ztadimg='<div class="adimgs adTags" style="margin-top: 25px;"><i class="gg-icon"></i> <img src="'.$this->options->ztadimg.'"></div>'; }
echo '<div class="row"><div class="col-md-9 contpost">
<!--头部-->
<div >
<div class="sp_flex">
<div class="sp_item">
    <div class="entry-container sp_list_index">
    <div class="block-image feaimg">
    <a class="block-fea scrollLoading" title="'.Getspname($id).'" style="background-image:url('.Getspimg($id).')"><i class="mask"></i></a></div>
    <header class="entry-header"><span class="entry-title">'.Getspname($id).'<p class="badge arc_cr zbg" style="float: right;">专题收录</p></span></header>
    <div class="entry-summary ss"><p>'.Getspspdep($id).' </p></div>
    <div class="entry-meta">共 '.get_sp($id).' 篇文章<span class="separator">/</span>'.spviews($id).' 阅读</div>
</div>
</div>
</div>
</div>
<!--头部-->
<!--作者s-->
<!--
<div class="say-more"><h4 class="sub-title">
<div class="icons">
'.get_spuser($id).'
</div>
</h4></div>-->
<!--作者e-->
<!--描述-->
'.$ztadimg.'
<header class="site-header">
<nav class="main-navigation">
<ul class="menu-nav-inline">
<li class="menu-item active"> 专题内容 </li>
</ul>
</nav>
</header>
<!--描述-->
<div class="row" id="content">'.Getsplist($id).'</div>
</div>';
?>
<?php $this->need('sidebar.php'); ?> 
</div>