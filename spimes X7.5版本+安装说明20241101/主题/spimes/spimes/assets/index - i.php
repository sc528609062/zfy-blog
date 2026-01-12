<?php if ( $this->is('index')) : ?>
<header class="site-header">
<nav class="main-navigation">
<ul class="menu-nav-inline">
<li class="menu-item active"> <a data-post href="<?php $this->options->siteUrl(); ?>"> 最新文章 </a> </li>
<?php navsecinfo(); ?> 
</ul>
</nav></header><!-- #masthead -->
<?php endif; ?>	
