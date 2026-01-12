<?php if ($this->options->topnews): ?>
<div class="part-mor contt top-news">
<div class="section-cont">
<div class="items">
<?php 
$sequid = $this->options->topnews;
$hang = explode(",", $sequid);
$n=count($hang);
$html="";
for($i=0;$i<$n;$i++){
$this->widget('Widget_Archive@topnews'.$i, 'pageSize=1&type=post', 'cid='.$hang[$i])->to($ji);
if($ji->fields->thumb){$img=$ji->fields->thumb;}
if($i==0){$no=" sx_no";}else{$no="";}
$created = date('m-d', $ji->created);
if ($ji->fields->img){
$str = stcdnimg($ji->fields->img);  
}
else{
$str = stcdnimg(showThumbnail($ji,0));
} 
$html=$html.'<div class="item"><div class="hunter-item"><a href="'.$ji->permalink.'" ><div class="hunter-thumb"><i class="thumb scrollLoading" data-url="'.$str.'"><i class="mask"></i></i></div><h2>'.$ji->title.'</h2><!--<h4><span class="hunter-tag btn btn-default">' . $ji->commentsNum .' 评论</span> <span class="hunter-product">' . $created .' </span></h4>--></a></div></div>';
}
echo $html;
?>
</div>
</div>
</div>
<?php if ($this->options->hdadimg): ?>
<div class="adimgs adTags"><i class="gg-icon"></i> <?php $this->options->hdadimg(); ?></div>
<?php endif; ?>
<?php endif; ?>
<!--专题模式-->
<?php if (Gets_sp()&&_getzt()): ?>
<div class="part-mor contt top-news">
<div class="section-cont">
<div class="sp_flex">
<?php echo Getspecial();?>
</div>
</div>
</div>
<?php endif; ?>