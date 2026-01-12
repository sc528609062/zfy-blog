<?php
/**
 * Spimes版本,一款简约新闻自媒体类的 typecho 主题，设计上简约、干净、精致、响应式，后台设置更是强大而且实用的新闻自媒体类主题
 *
 * @package Spimes 主题
 * @author 小灯泡设计
 * @version 7.5
 * @link https://www.veimoz.com/
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;
 $this->need('header.php');

?>
<div class="row">
<div class="col-md-9 contpost">
<?php if($this->is('index') && $this->_currentPage == 1): ?>		
<?php $this->need('assets/index - hd.php'); ?>
<?php $this->need('assets/index - i.php'); ?>
<?php endif; ?>
<div class="row" id="content">
<?php $this->need('index - one.php'); ?>
<script>$(function(){$('.cck').show();});</script>
<script>$(function(){$('.next').show();});</script>
</div>
<?php if ($this->is('index')): ?><?php $this->need('assets/nav-pages.php'); ?><?php endif; ?>
</div>
<?php $this->need('sidebar.php'); ?>
</div>
<?php $this->need('footer.php'); ?>