<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
<div class="row">	
		<div class="col-md-9 contpost">		
		        <header>
                    <div class="widget-list-title"><i class="ri-calendar-todo-line ri-lg"></i> <span><?php if($this->_currentPage>1) echo '第 '.$this->_currentPage.' 页 - '; ?><?php $this->archiveTitle(array('category'  =>  _t('%s ')), '', ''); ?></span></div>
                </header>		
				<div class="row" id="content">
				<?php $this->need('index - list.php'); ?>
                  <script>$(function(){$('.cck').hide();});</script>
                  <script>$(function(){$('.next').hide(); });</script>
                  <?php $this->need('assets/nav-pages.php'); ?>
				</div>			
		 </div>
		<?php $this->need('sidebar.php'); ?>
	</div>
<?php $this->need('footer.php'); ?>