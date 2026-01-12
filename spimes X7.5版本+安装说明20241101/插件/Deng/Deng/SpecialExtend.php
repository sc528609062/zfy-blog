<?php
//include 'common.php';
include 'header.php';
include 'menu.php';



?>
<style>.otr_cate{     color: #999; } .main_cate{ color: red; }</style>
<div class="main">
    <div class="body container">
        <div class="typecho-page-title">
             <h2>专题内容设置<a href="#" onClick="js_method()">新增专题</a></h2>
             <p style="color:red">可自定义分类栏目封面,SEO关键字,标题,描述以及分类模板节目</p>
        </div>
        <div class="row typecho-page-main manage-metas">
            <div class="col-mb-12 col-tb-9" role="main">
                <form method="post" name="manage_tags" class="operate-form">
                    <div class="typecho-table-wrap">
                        <table class="typecho-list-table">
                            <colgroup>
                            <col width="10%">
                            <col width="25%">
                            <col width="25%">
                            <col width="25%">
                            <col width="15%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>sid</th>
                                    <th>专题名称</th>
                                    <th>专题描述</th>
                                    <th>专题封面</th>
                                </tr>
                            </thead>
                            <tbody>
                                
                                <?php 
                                    $siteUrl = Typecho_Widget::widget('Widget_Options')->index;
                                    $query= $db->select()->from('table.special');
                                    $result = $db->fetchAll($query);
                                    foreach ($result as $key => $value) {
                                ?>
                                    <tr>
                                        <td id="Xt_sid_<?php echo($key); ?>"><?php echo($value['sid']); ?></td>
                                        <td id="Xt_name_<?php echo($key); ?>"><?php echo($value['spname']); ?></td>
                                        <td id="Xt_spdep_<?php echo($key); ?>"><?php echo($value['spdep']); ?></td>
                                        <td id="Xt_img_<?php echo($key); ?>"><?php echo($value['spimg']); ?></td>
                     
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
                    <ul class="typecho-option" id="typecho-option-item-sortsid">
                        <li>
                            <label class="typecho-label" for="name-0-1">Sid *</label>
                            <input id="sortsid" name="sortsid" type="text" class="text" value="" placeholder="分类mid" >
                            <p class="description">这是分类mid，不用编辑.</p>
                        </li>
                    </ul>
                    <ul class="typecho-option" id="typecho-option-item-name-0">
                        <li>
                            <label class="typecho-label" for="name-0-1">名称 *</label>
                            <input id="sortname" name="sortname" type="text" class="text" value="" placeholder="分类名称">
                            <p class="description">这是专题名称，不用编辑.</p>
                        </li>
                    </ul>
                    
                    <ul class="typecho-option" id="typecho-option-item-name-0">
                        <li>
                            <label class="typecho-label" for="name-0-1">描述 *</label>
                            <textarea id="sortdepict"></textarea>
                            <p class="description">这是专题描述，不用编辑.</p>
                        </li>
                    </ul>
                    
                    <ul class="typecho-option" id="typecho-option-item-name-0">
                        <li>
                            <label class="typecho-label" for="name-0-1">封面 *</label>
                            <input id="cateico" name="cateico" type="text" class="text" value="" placeholder="分类封面（推荐填写链接）">
                            <p class="description">专题图标，请填写http://的链接</p>
                        </li>
                    </ul>
                    
                    <?php 
                    $formurl = Helper::security()->getIndex('/Deng/Special');
                    ?>
                    
                    <ul class="typecho-option typecho-option-submit" id="typecho-option-item--4">
                        <li>
                            <button type="button" onclick="adSort()" id="adSort" class="btn primary">添加专题</button>
                            <button type="button" onclick="upSort()" id="upSort" class="btn primary">更新专题</button>
                            <button type="button" onclick="deSort()" id="deSort" class="btn primary">删除专题</button>
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

       $(document).ready(function () { 
           $("#upSort").hide();
           $("#deSort").hide();
           $("#typecho-option-item-sortsid").hide(); 
       });    
   
       function js_method(){
           window.location.reload();
       }
    
       function SuBmits(obj){
           var mid = $("#Xt_sid_"+obj).text();
           var name = $("#Xt_name_"+obj).text();
           var spdep = $("#Xt_spdep_"+obj).text();
           //var imglink = $("#Xt_imglink_"+obj).text();

           var cateico = $("#Xt_img_"+obj).text(); //11

           $("#sortsid").val(mid);
           $("#sortname").val(name);
           $("#sortdepict").val(spdep);
           $("#cateico").val(cateico);
          
           $("#adSort").hide();
           $("#upSort").show();
           $("#deSort").show();
           $("#typecho-option-item-sortsid").show(); 
           $(":text[name='sortsid']").attr("disabled","disabled");

       }
       
       function adSort() {

            var Msortsid  = null;
            var Msortname = $("input[name='sortname']").val();
            var Mcateico = $("input[name='cateico']").val();
            var Msortdepict = $("#sortdepict").val();
            var MupSort = 2;
            
            $.post("<?php echo($formurl) ?>",
                {
                    MupSort: MupSort,
                    Msortsid: Msortsid,
                    Msortname: Msortname,
                    Msortdepict: Msortdepict,
                    Mcateico: Mcateico
                  
                },
            function(data,status){
                  window.location.reload();
            });
            
        }
       
       function upSort() {

            var Msortsid  = $("input[name='sortsid']").val();
            var Msortname = $("input[name='sortname']").val();
            var Mcateico = $("input[name='cateico']").val();
            var Msortdepict = $("#sortdepict").val();
            var MupSort = 1;
            
            $.post("<?php echo($formurl) ?>",
                {
                    MupSort: MupSort,
                    Msortsid: Msortsid,
                    Msortname: Msortname,
                    Msortdepict: Msortdepict,
                    Mcateico: Mcateico
                  
                },
            function(data,status){
                  window.location.reload();
            });
            
        }
        
        function deSort() {

            var Msortsid  = $("input[name='sortsid']").val();
            var Msortname = $("input[name='sortname']").val();
            var Mcateico = $("input[name='cateico']").val();
            var Msortdepict = $("#sortdepict").val();
            var MupSort = 0;
            
            $.post("<?php echo($formurl) ?>",
                { 
                    MupSort: MupSort,
                    Msortsid: Msortsid,
                    Msortname: Msortname,
                    Msortdepict: Msortdepict,
                    Mcateico: Mcateico
                  
                },
            function(data,status){
                  window.location.reload();
            });
            
        }
    
</script>

<?php include 'footer.php'; ?>



