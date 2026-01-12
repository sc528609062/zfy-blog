<?php if ($this->options->dhtop): ?>
<div class="main-slider row">
<div class="sp-slideshow  <?php if (!$this->options->imghdp): ?>sp-showno<?php endif; ?>">	
<!-- Swiper -->
<div class="sp_index__banner">
<div class="swiper-container">
<div class="swiper-wrapper">
<?php 
$lunbo = $this->options->dhtop;
$hang = explode(",", $lunbo);
$n=count($hang);
$html="";
for($i=0;$i<$n;$i++){
$this->widget('Widget_Archive@lunbo'.$i, 'pageSize=1&type=post', 'cid='.$hang[$i])->to($ji);
if($i==0){$no=" sx_no";}else{$no="";}
$str = stcdnimg($ji->fields->img);
$html=$html.'<div class="swiper-slide"><a class="item block-fea" href="'.$ji->permalink.'"  style="background-image: url('.$str.');" class="small-slider-img"><div class="overlay-1"></div><div class="title"><span class="badge arc_v2">推荐</span>'.$ji->title.'</div></a></div>';
}
echo $html;
?>
</div>
<div class="swiper-pagination"></div>
<div class="swiper-button-next"></div>
<div class="swiper-button-prev"></div>
</div>
</div>
</div>
<!-- sp-slideshow -->
<?php if ($this->options->imghdp): ?>
<div class="small-slider">
<?php 
$lunbo = $this->options->imghdp;
$hang = explode(",", $lunbo);
$n=count($hang);
$html="";
for($i=0;$i<$n;$i++){
$this->widget('Widget_Archive@lunbo1'.$i, 'pageSize=1&type=post', 'cid='.$hang[$i])->to($ji);
if($i==0){$no=" sx_no";}else{$no="";}
$str = stcdnimg($ji->fields->img);
$html=$html.'<div class="small-slider-item"><a href="'.$ji->permalink.'"  style="background-image: url('.$str.');" class="small-slider-img"><div class="overlay-1"></div><div class="title"><span class="badge arc_v2">推荐</span>'.$ji->title.'</div></a></div>';
}
echo $html;
?>
</div>
<?php endif; ?>
</div>
<?php endif; ?>
<?php $this->need('assets/top-news.php'); ?>