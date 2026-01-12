<?php

/**
* 友情链接
*
* @package custom
*/

if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
<div class="row">
	<div class="col-md-9 contpost" id="content">
	  
		<article class="post">
			<header class="entry-header page-header" >
				<h1 class="entry-title page-title"><?php $this->title(); ?></h1>	
				<div class="border-theme"></div>
			</header>
			<div class="entry-content clearfix">
               <?php if ($this->options->cdnopen == '0'):?>
				<?php $str=get_glo_keywords($this->content); $connt=costcn($this->cid,$this->remember('mail',true),$str,$this->user->hasLogin()); echo $connt; ?>
				<?php else : ?>
				<?php $str = str_replace($this->options->cdnurla,$this->options->cdnurlb,get_glo_keywords($this->content)); $connt=costcn($this->cid,$this->remember('mail',true),$str,$this->user->hasLogin());  echo $connt;?>	
				<?php endif; ?>
			</div>
			</article>	
<!--${bgs[bgid]}-->
<script>
(function(){
    let a =document.getElementById("flinks");
    if(a){
        let ns = a.querySelectorAll("li");
        let nsl = ns.length;
        let str ='<div class="post-lists"><div class="post-lists-body" id ="flinksH">';
        let bgid = 0;
         const bgs =["bg-blue","bg-purple","bg-green","bg-yellow","bg-red","bg-orange"];
        for(let i = 0;i<=nsl-4;i+=4){
           bgid = Math.floor(Math.random() * 6);
            str += (`<div class="post-list-item"><div class="post-list-item-container "><div class="item-label bg-black"><div class="item-title"><a target="_blank" href="${ns[i+1].innerText}">${ns[i].innerText}</a></div><div class="item-meta clearfix"><div class="item-meta-ico bg-ico-book"style="background: url(${ns[i+2].innerText}) no-repeat;background-size: 40px auto;"></div><div class="item-meta-date">${ns[i+3].innerText}</div></div></div></div></div>`);
        }
        str+='</div></div><style></style>';
        let n1 = document.createElement("div");
        n1.innerHTML = str;
        a.parentNode.insertBefore(n1,a);
        a.style="display: none;";
    }else{
        console.log('No such id "flinksH"');
    }
}())
 </script>
<?php $this->need('comments.php'); ?>
</div>
<?php $this->need('sidebar.php'); ?>		
</div>
<?php $this->need('footer.php'); ?>
