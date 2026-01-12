<?php
/**
* 留言建议
*
* @package custom
*/

if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>

<?php $this->need('header.php'); ?>
<style>body{ background-color:#fff }</style>
<!--留言-->
<div class="mtop ly_content ">
        <div class="left">
            <h2><?php $this->options->title() ?><br>我们帮助您解决各种需求</h2>
            <h4 class="pc">填写您的需求，咨询顾问将在尽快与您对接沟通</h4> 
            <div class="gwboxbg pc"> 
                <div class="heabox">
                    <img draggable="false" src="<?php $this->options->useimg(); ?>" class="head" alt="">
                    <div>
                        <h3>资深顾问</h3>
                        <p>专业1V1专属咨询服务，高效梳理您的需求</p>
                    </div>
                </div>
                <div class="callbox">
                    <div>
                        <p>添加顾问微信</p>
                        <img draggable="false" src="<?php $this->options->webewm(); ?>" class="qr" alt="">
                    </div>
                    <div>
                        <p>立即联系顾问</p>
                        <div>
                            <div class="libx phone"> <img draggable="false" src="<?php echo stcdn($this->options->themeUrl); ?>/src/images/phone.png" class="ico" alt=""><a href="tel:<?php $this->options->tell(); ?>"><?php $this->options->tell(); ?></a></div>
                            <div class="libx email"> <img draggable="false" src="<?php echo stcdn($this->options->themeUrl); ?>/src/images/email.png" class="ico" alt=""><a href="mailto:<?php $this->options->mail(); ?>"><?php $this->options->mail(); ?></a></div>
                        </div>
                    </div>
                </div>
            </div> 
        </div>
        <hr class="pc">
        <div class="right">
            <div>  
                    <div>
                        <p class="tit">需求类型 <i>*</i></p>
                        <div class="radiobox requirement_type" id="requirement_body">
                                

                                <p data-val="A1">A1</p> 
                                    
                                <p data-val="A2">A2</p> 
                                    
                                <p data-val="A3">A3</p> 
                                    
                                <p data-val="A4">A4</p> 
                                    
                                <p data-val="A5">A5</p> 
                                    
                                <p data-val="A6">A6</p>
                                <p data-val="A7">A7</p>
                                <p data-val="A8">A8</p>
                               </div>
                       
                    </div>
                    <div>
                        <p class="tit">预算范围 <i>*</i></p>
                        <div class="radiobox budget">
                            <p data-val="B0">B0</p> 
                            <p data-val="B1">B1</p> 
                            <p data-val="B2">B2</p> 
                            <p data-val="B3">B3</p> 
                            <p data-val="B4">B4</p> 
                            <p data-val="B5">B5</p> 
                            <p data-val="B6">B6</p> 
                        </div>
                       
                    </div> 

                <p class="tit">需求描述 <i>*</i></p>
                <div class="input textarea" style="border: 1px solid rgb(164, 169, 177);">
                    <textarea row="40" name="description" id="description" onfocus="this.placeholder=''" onkeyup="this.value=needcheck(this.value)" onblur="this.placeholder='简要描述您的需求'" placeholder="简要描述您的需求"></textarea>
                    <p class="tips" style="display: none;">请输入您的需求</p>
                </div>
                <div class="flex">
                    <div>
                        <p class="tit">称呼 <i>*</i></p>
                        <div class="input " style="border: 1px solid rgb(164, 169, 177);">
                            <input type="text" autocomplete="off" id="call" name="call" onfocus="this.placeholder=''" onblur="this.placeholder='怎么称呼您'" placeholder="怎么称呼您">
                            <p class="tips" style="display: none;">请输入您的称呼</p>
                        </div>
                    </div>
                    <div> 
                        <p class="tit">手机号 <i>*</i></p>
                        <div class="input " style="border: 1px solid rgb(164, 169, 177);">
                            <input type="tel" autocomplete="on" maxlength="11" id="phone" name="phone" onfocus="this.placeholder=''" onblur="this.placeholder='请输入您的手机号'" placeholder="请输入您的手机号">
                            <p class="tips" style="display: none;">请输入您的手机号</p>
                        </div>
                    </div>
                </div>
                
                <!--
                <div class="flex">
                    <div>
                        <p class="tit">邮箱联系 </p>
                        <div class="input ">
                            <input type="text" autocomplete="off" name="mail"
                               onfocus="this.placeholder=''"
                                onblur="this.placeholder='请输入您的公司名称（非必填）'" placeholder="请输入您的公司名称（非必填）"> 
                        </div>
                    </div> 
                    <div>
                        <p class="tit">公司名称 </p>
                        <div class="input ">
                            <input type="text" autocomplete="off" id="company" name="company"
                               onfocus="this.placeholder=''"
                                onblur="this.placeholder='请输入您的公司名称（非必填）'" placeholder="请输入您的公司名称（非必填）"> 
                        </div>
                    </div> 
                </div>
                -->
                <input type="hidden" name="avl" value="">
                <input type="hidden" name="bvl" value="">
                <div class="flexs"> 
                    <div class="btn submitrequire" >立即提交</div> 
                </div>
            </div>

        </div>
    </div>
    
    
<script>
$(document).ready(function(){
    $(".requirement_type p").click(function(){
        $(".requirement_type p").removeClass("on");
        $(this).addClass("on");
        var dataVal = $(this).attr("data-val");
        $("input[name='avl']").val(dataVal);
    });
    $(".budget p").click(function(){
        $(".budget p").removeClass("on");
        $(this).addClass("on");
        var dataVal = $(this).attr("data-val");
        $("input[name='bvl']").val(dataVal);
    });
    $(".submitrequire").click(function(){
        var avl = $("input[name='avl']").val();
        var bvl = $("input[name='bvl']").val();
        var description = $("textarea[name='description']").val();
        var call = $("input[name='call']").val();
        var phone = $("input[name='phone']").val();
        
        if (description == "" || call == "" || phone == "") {
            alert('请填写所有必填项！');
        }
        else{
        console.log("avl: " + avl);
        console.log("bvl: " + bvl);
        console.log("description: " + description);
        console.log("call: " + call);
        console.log("phone: " + phone);
        
            //ajax
            
            $.ajax({			
     
                url: SPZ.BASE_site+"/sp/msg",
				type: "POST",  
				data: {
				    username:call,
				    tell:phone,
				    content:avl+bvl+description
				},
                async: true,                          
				dataType: "json",
				success: function(data) {
				  cocoMessage.success('提交成功');
				  $(".abautor").append(data.info);
				  $('input[name="call"]').val("");
                  $('input[name="phone"]').val("");
                  $('textarea[name="description"]').val("");
				  
				},
				error: function(err) {
		          console.log(err);
				}
	        }); 
            
            //ajax
        
        }
        
    });
});
</script>

<!--留言-->

<?php $this->need('footer.php'); ?>