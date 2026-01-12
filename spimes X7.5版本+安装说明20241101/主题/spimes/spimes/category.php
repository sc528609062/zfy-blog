<?php if (catepages(categeid($this->getArchiveSlug()))==1): ?>
<?php $this->need('category/tuwen.php'); ?>
<?php endif; ?>
<?php if (catepages(categeid($this->getArchiveSlug()))==2): ?>
<?php $this->need('category/shipin.php'); ?>
<?php endif; ?>
<?php if (catepages(categeid($this->getArchiveSlug()))==3): ?>
<?php $this->need('category/wenzi.php'); ?>
<?php endif; ?>
