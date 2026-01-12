<?php

include 'header.php';
include 'menu.php';

$stat = Typecho_Widget::widget('Widget_Options');

//会员功能开始
//计算分页
$pageSize = 20;
$currentPage = isset($_REQUEST['p']) ? ($_REQUEST['p'] + 0) : 1;

$all = $db->fetchAll($db->select()->from('table.users')
    ->order('created', Typecho_Db::SORT_DESC));

$pageCount = ceil( count($all)/$pageSize );

$current = $db->fetchAll($db->select()->from('table.users')
    ->page($currentPage, $pageSize)
    ->order('created', Typecho_Db::SORT_DESC));

$i=0;
$all = Typecho_Plugin::export();
if (array_key_exists('TePass', $all['activated'])){
  $i=1;  
}

// 判断消费金额 
function payjiage($id){
    
    
    if($i==1){
    $db=Typecho_Db::get();
    $postnum=$db->fetchRow($db->select(array('Sum(feeprice)'=>'alljiage'))->from ('typecho_teepay_fees')->where ('feeuid=?',$id)->where('feestatus=?', '1'));
	$postnum = $postnum['alljiage'];  
	
    }
	if($postnum){
	return $postnum;
	}
	else{
	return 'null';    
	}
	
	
}
//调用用户注册时间
function reg_login($userid){
    $now = time();
    $db     = Typecho_Db::get();
    $prefix = $db->getPrefix();
    $row = $db->fetchRow($db->select('created')->from('table.users')->where('uid = ?', $userid));
    $ti = Typecho_I18n::dateWord($row['created'], $now);
    $d1 = $row['created'];//过去的某天，你来设定
    $d2 = ceil((time()-$d1)/60/60/24);//现在的时间减去过去的时间，ceil()进一函数
    return $d2;
}


?>
<script>
    document.getElementsByTagName("title")[0].innerText = '个人中心';
</script>
<style type="text/css">
.sns_icon {
    padding: 0 4px;
	width:32px;
}
.btn-red {
    border: none;
    background-color: #FF0500;
    cursor: pointer;
    -moz-border-radius: 2px;
    -webkit-border-radius: 2px;
    border-radius: 2px;
    color: #FFF;
}
section {
	word-break: break-all;
}
</style>

<?php
$user=Typecho_Widget::widget('Widget_User');

/*获取用户UID $user->uid */


if($user->uid > 0){
	
?>


<div class="main">
    <div class="body container">
    <div class="typecho-page-title">
    <h2>个人中心</h2>
    </div>
			<div class="row typecho-page-main">
			    
			    
			    
				<div class="col-mb-12 typecho-list">
				 <div class="typecho-list-operate clearfix">
      
                    <form method="POST" action="<?php $options->adminUrl('extending.php?panel=Deng%2Fhtml%2Fprofile.php'); ?>">
                        <div class="search" role="search">


                            <select name="p">
                                <?php for($i=1;$i<=$pageCount;$i++): ?>
                                    <option value="<?php echo $i; ?>"<?php if($i == $currentPage): ?> selected="true"<?php endif; ?>>第<?php echo $i; ?>页</option>
                                <?php endfor; ?>
                            </select>

                            <button type="submit" class="btn btn-s"><?php _e('筛选'); ?></button>
                            <?php if(isset($request->uid)): ?>
                                <input type="hidden" value="<?php echo htmlspecialchars($request->get('uid')); ?>" name="uid" />
                            <?php endif; ?>
                        </div>
                    </form>
                </div><!-- end .typecho-list-operate -->

			    <form method="post" name="manage_posts" class="operate-form">
				<div class="col-mb-12 col-tb-12 typecho-content-panel" role="form">
					 <div class="typecho-table-wrap">
                        <table class="typecho-list-table">
                            <colgroup>
                                <col width="5%"/>
                                <col width="15%"/>
                                <col width="20%"/>
                                
                                <col width="25%"/>
                                <col width="15%"/>
                            </colgroup>
                            <thead>
                            <tr>
                                <th><?php _e('UID'); ?></th>
                                <th><?php _e('昵称'); ?></th>
                                <th><?php _e('邮箱'); ?></th>
                                
                                <th><?php _e('用户组'); ?></th>
                                <th><?php _e('注册时间'); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                                    <?php foreach($current as $line): ?>
                                    <tr>
                                        <td><?php echo $line['uid']; ?></td>
                                        <td><a target="_blank" href="<?php echo $options->adminUrl?>user.php?uid=<?php echo $line['uid']; ?>"><?php echo $line['name']; ?></a></td>
                                        <td><?php echo $line['mail']; ?></td>
                                        
                                        <td><?php echo $line['group']; ?></td>
                                        <td><?php echo reg_login($line['uid']); ?> 天</td>
                                    </tr>
                                   <?php endforeach;?>
                            </tbody>
                        </table>
                    </div>
					</form><!-- end .operate-form -->
					</div>	

					
					<div style="clear: both"></div>				
				</div>		
			 	
		</div>	
    </div>
</div>


<?php
};
include 'copyright.php';
include 'common-js.php';
include 'form-js.php';
Typecho_Plugin::factory('admin/profile.php')->bottom();
include 'footer.php';
?>

<script language="javascript">
function externallinks() {
	if (!document.getElementsByTagName) return;
	var anchors = document.getElementsByTagName("a");
	for (var i=0; i<anchors.length; i++) {
		var anchor = anchors[i];
		if (anchor.getAttribute("href"))
		anchor.target = "_self";
	}
}
window.onload = externallinks;
</script>


