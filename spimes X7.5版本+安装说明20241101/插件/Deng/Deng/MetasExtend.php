<?php
//include 'common.php';
include 'header.php';
include 'menu.php';



?>
<style>.otr_cate{     color: #999; } .main_cate{ color: red; }</style>
<div class="main">
    <div class="body container">
        <div class="typecho-page-title">
             <h2>分类栏目设置</h2>
             <p style="color:red">可自定义分类栏目封面,SEO关键字,标题,描述以及分类模板节目</p>
        </div>
        <div class="row typecho-page-main manage-metas">
            <div class="col-mb-12 col-tb-9" role="main">
                <form method="post" name="manage_tags" class="operate-form">
                    <div class="typecho-table-wrap">
                        <table class="typecho-list-table">
                            <colgroup>
                            <col width="4%">
                            <col width="8%">
                            <col width="4%">
                            <col width="4%">
                            <col width="4%">
                            <col width="4%">
                            <col width="6%">
                            <col width="4%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>mid</th>
                                    <th>名称</th>
                                    <th>图标</th>
                                    <th>seo标题</th>
                                    <th>seo关键词</th>
                                    <th>seo描述</th>
                                    <th>分类模型</th>
                                    <th>设置</th>
                                </tr>
                            </thead>
                            <tbody>
                                
                                <?php 
                                    $siteUrl = Typecho_Widget::widget('Widget_Options')->index;
                                    $query= $db->select()->from('table.metas')->where('type = ?', 'category');
                                    $result = $db->fetchAll($query);
                                    foreach ($result as $key => $value) {
                                ?>
                                    <tr>
                                        <td id="Xt_mid_<?php echo($key); ?>"><?php echo($value['mid']); ?></td>
                                        
                                        <td id="Xt_name_<?php echo($key); ?>"><a class="<?php if($value['parent']==0){ echo "main_cate"; }else{ echo "otr_cate"; } ?>" href="<?php echo($siteUrl); ?><?php if (Typecho_Widget::widget('Widget_Options')->rewrite==0): ?>index.php/<?php endif; ?>/<?php echo($value['slug']); ?>" target = "_blank"><?php echo($value['name']); ?></a></td>
                                        
                                        <td id="Xt_cateico_<?php echo($key); ?>" data-key="<?php echo($value['cateico']); ?>" ><?php if($value['cateico']){ echo '✔'; }else{  }; ?></td>
                          
                                        
                                        <td id="Xt_seotitle_<?php echo($key); ?>" data-key="<?php echo($value['seotitle']); ?>"><?php if($value['seotitle']){ echo '✔'; }else{  }; ?></td>
                                        
                                        <td id="Xt_seokey_<?php echo($key); ?>" data-key="<?php echo($value['seokey']); ?>"><?php if($value['seokey']){ echo '✔'; }else{  }; ?></td>
                                        
                                        <td id="Xt_seodesc_<?php echo($key); ?>" data-key="<?php echo($value['seodesc']); ?>"><?php if($value['seodesc']){ echo '✔'; }else{  }; ?></td>
                                        <td id="Xt_catepages_<?php echo($key); ?>"><?php 
                                        $i=$value['catepages']; 
                                        if($i==1){ echo '图文模板'; }
                                        if($i==2){ echo '视频模板'; }
                                        if($i==3){ echo '文字模板'; }
                                          ?></td>
                     
                                        <td><input type="button" onclick="SuBmits('<?php echo($key); ?>')" value="编辑"></td>
                                    </tr>
                                        
                                        
                                <?php
                                    }
                                ?>
                                
                                
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
            
            <div class="col-mb-12 col-tb-3" role="form">
                    <ul class="typecho-option" id="typecho-option-item-name-0">
                        <li>
                            <label class="typecho-label" for="name-0-1">Mid *</label>
                            <input id="sortmid" name="sortmid" type="text" class="text" value="" placeholder="分类mid" disabled>
                            <p class="description">这是分类mid，不用编辑.</p>
                        </li>
                    </ul>
                    <ul class="typecho-option" id="typecho-option-item-name-0">
                        <li>
                            <label class="typecho-label" for="name-0-1">名称 *</label>
                            <input id="sortname" name="sortname" type="text" class="text" value="" placeholder="分类名称" disabled>
                            <p class="description">这是分类名称，不用编辑.</p>
                        </li>
                    </ul>
                    
                    <ul class="typecho-option" id="typecho-option-item-name-0">
                        <li>
                            <label class="typecho-label" for="name-0-1">分类模型 *</label>
<select name="catepages" id="catepages">
<option value="1" selected="true">图文模板</option>
<option value="2">视频模板</option>
<option value="3">文字模板</option>
</select>
                            <p class="description">选择适合的分类模板界面.</p>
                        </li>
                    </ul>
                    

                    
                    
                    <ul class="typecho-option" id="typecho-option-item-name-0">
                        <li>
                            <label class="typecho-label" for="name-0-1">图标 *</label>
                            <input id="cateico" name="cateico" type="text" class="text" value="" placeholder="分类封面（推荐填写链接）">
                            <p class="description">请根据http://remixicon.com网站获取对应图标格式</p>
                        </li>
                    </ul>
                                        

                    
                    <ul class="typecho-option" id="typecho-option-item-name-0">
                        <li>
                            <label class="typecho-label" for="name-0-2">SEO标题 * (seo三合一才会生效)</label>
                            <input id="seotitle" name="seotitle" type="text" class="text" value="" placeholder="分类栏目标题">
                            <p class="description">SEO标题，站点优化开发者</p>
                        </li>
                    </ul>
                     <ul class="typecho-option" id="typecho-option-item-name-0">
                        <li>
                            <label class="typecho-label" for="name-0-3">SEO关键字 * (seo三合一才会生效)</label>
                            <input id="seokey" name="seokey" type="text" class="text" value="" placeholder="分类栏目关键字">
                            <p class="description">分类封面，站点优化开发者</p>
                        </li>
                    </ul>
                    <ul class="typecho-option" id="typecho-option-item-slug-1">
                        <li>
                            <label class="typecho-label" for="slug-0-4">SEO描述 * (seo三合一才会生效)</label>
                            <textarea id="seodesc"></textarea>
                            <p class="description">分类描述，站点优化开发者</p>
                        </li>
                    </ul>
                    <?php 
                    $formurl = Helper::security()->getIndex('/Deng/Action');
                    ?>
                    
                    <ul class="typecho-option typecho-option-submit" id="typecho-option-item--4">
                        <li>
                            <button type="button" onclick="upSort()" class="btn primary">更新分类数据</button>
                        </li>
                    </ul>

            </div>
        </div>
    </div>
</div>





<?php
include 'copyright.php';
include 'common-js.php';
?>



<script>
    
       function SuBmits(obj){
           var mid = $("#Xt_mid_"+obj).text();
           var name = $("#Xt_name_"+obj).text();
           //var imglink = $("#Xt_imglink_"+obj).text();

           var cateico = $("#Xt_cateico_"+obj).attr("data-key"); //11
        
           
           
           var seotitle = $("#Xt_seotitle_"+obj).attr("data-key");
           var seokey = $("#Xt_seokey_"+obj).attr("data-key");
           //var seodesc = $("#Xt_seodesc_"+obj).text();
           var seodesc = $("#Xt_seodesc_"+obj).attr("data-key");
           
           var catepages = $("#Xt_catepages_"+obj).text();
           if( catepages=='图文模板' ){ var cate = 1; }
           if( catepages=='视频模板' ){ var cate = 2; }
           if( catepages=='文字模板' ){ var cate = 3; }

           

           $("#sortmid").val(mid);
           $("#sortname").val(name);
          
           $("#seotitle").val(seotitle);
           $("#seokey").val(seokey);
           $("#seodesc").val(seodesc);
           $("#catepages").val(cate);
           $("#cateico").val(cateico);
     

       }
       
       function upSort() {

            var Msortmid  = $("input[name='sortmid']").val();
            var Mseotitle = $("input[name='seotitle']").val();
            var Mseokey = $("input[name='seokey']").val();
            var Mseodesc = $("#seodesc").val();
            var Mcatepages = $("select[name='catepages']").val();
            var Mcateico = $("input[name='cateico']").val();
            
            
            $.post("<?php echo($formurl) ?>",
                {
                    Msortmid: Msortmid,
                    Mseotitle: Mseotitle,
                    Mseokey: Mseokey,
                    Mseodesc: Mseodesc,
                    Mcatepages: Mcatepages,
                    Mcateico: Mcateico
                  
                },
            function(data,status){
                  window.location.reload();
            });
            
            
            
        }
    
</script>

<?php include 'footer.php'; ?>



