
<?php if ($this->is('post')||$this->is('page')) : ?>
<?php if ($this->fields->tktit) : ?>  
<title><?php $this->fields->tktit(); ?> - <?php $this->options->title(); ?></title>
<?php else : ?>
<title><?php $this->archiveTitle(array(), '', ' - '); ?><?php $this->options->title(); ?></title>
<?php endif; ?>
<?php if ($this->fields->tktit&&$this->fields->tkeyc&&$this->fields->tdesc) : ?> 
<?php $tdesc = $this->fields->tdesc; $tkeyc = $this->fields->tkeyc; $this->header("generator=&template=&description=$tdesc&keywords=$tkeyc&pingback=&xmlrpc=&wlw=&atom=&rss1=&rss2="); ?>
<?php else : ?>
<?php $this->header(); ?>
<?php endif; ?>
<?php endif; ?>

<?php if ($this->is('index')) : ?>
<?php if ($this->options->seotitle) : ?>
<title><?php $this->options->seotitle(); ?></title>
<?php else : ?>
<title><?php $this->archiveTitle(array(), '', ' - '); ?><?php $this->options->title(); ?></title>
<?php endif; ?>
<?php $this->header(); ?>
<?php endif; ?>

<?php if ($this->is('category')) : ?>
<?php if (cateonseo($this->getArchiveSlug())) : ?>
<title><?php echo geseo($this->getArchiveSlug(),'seotitle'); ?></title>
<?php else : ?>
<title><?php $this->archiveTitle(array('category'  =>  _t('分类%s下的文章')), '', ' - '); ?><?php $this->options->title(); ?></title>
<?php endif; ?>
<?php if (cateonseo($this->getArchiveSlug())) : ?>
<?php $tdesc = geseo($this->getArchiveSlug(),'seodesc'); $tkeyc = geseo($this->getArchiveSlug(),'seokey'); $this->header("generator=&template=&description=$tdesc&keywords=$tkeyc&pingback=&xmlrpc=&wlw=&atom=&rss1=&rss2="); ?>
<?php else : ?>
<?php $this->header(); ?>
<?php endif; ?>
<?php endif; ?>

<?php if ($this->is('tag')) : ?>
<title><?php $this->archiveTitle(array('tag' =>  _t('标签%s下的文章')), '', ' - '); ?><?php $this->options->title(); ?></title>
<?php $this->header(); ?>
<?php endif; ?>

<?php if ($this->is('search')) : ?>
<title><?php $this->archiveTitle(array('search' =>  _t('包含关键字%s的文章')), '', ' - '); ?><?php $this->options->title(); ?></title>
<?php $this->header(); ?>
<?php endif; ?>

<?php if ($this->is('author')) : ?>
<title><?php $this->archiveTitle(array('author' =>  _t('%s的主页')), '', ' - '); ?><?php $this->options->title(); ?></title>
<?php $this->header(); ?>
<?php endif; ?>

<?php global $zt_on; global $id; if ($zt_on) : ?>
<title>[专题]<?php echo Getspname($id); ?> - <?php $this->options->title(); ?></title>
<?php $this->header(); ?>
<?php endif; ?>