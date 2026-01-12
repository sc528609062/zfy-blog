<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>

<?php 
global $id;
global $zt_on;
$id='';
$zt_on=false;
$str=$_SERVER["REQUEST_URI"];
if(preg_match('/zt/i',$str)){
if(preg_match('/\d+/',$str,$arr)){
$id=$arr[0];
$zt_on=true;
$html='<div class="col-md-9 contpost">		
		        <header>
                    <div class="widget-list-title"><i class="ri-calendar-todo-line ri-lg"></i> <span>xxxxxx</span></div>
                </header>	

				<div class="row" id="content">专题内容</div>			
		 </div>';
$this->need('header.php');
$this->need('assets/special - list.php');

}}
else{
$this->need('header.php');
$this->need('assets/404set.php');
}
?>


<?php $this->need('footer.php'); ?>
