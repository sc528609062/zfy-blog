<?php 
/**
* 文章编辑页面
*
* @package custom
*/
if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php 

include 'libs/common.php';

$this->need('header.php'); ?>

<?php $tid=$_GET['tid']; //文章id

$myid = $this->user->uid; //登陆用户id
$postid = getpcid($tid);

$siteUrl = $this->options->siteUrl;
$postcateid=getcateid($tid);

if(!$this->user->hasLogin()){
   exit('<script>alert("非法操作");location.href="'.$siteUrl.'";</script>');
}
elseif($tid == ''|| $tid==null){//编辑状态
   exit('<script>alert("非法操作");location.href="'.$siteUrl.'";</script>');
}
elseif( $myid!=$postid ){
   exit('<script>alert("非法操作'.$myid.'+'.$postid.'");location.href="'.$siteUrl.'";</script>'); 
}
else{
   $this->widget('Widget_Contents_Post_Edit@indexxiu', 'pageSize=1&type=post', 'cid='.$tid)->to($post);
}

//$db = Typecho_Db::get();
//$rs=$db->fetchRow ($db->select ('table.contents.text')->from ('table.contents')->where ('table.contents.cid=?',$tid)->order ('table.contents.cid',Typecho_Db::SORT_ASC)->limit (1));


?>

<div class="row member">
    
    

    <!--边栏-->
    <div class="col-md-2 widget-area <?php if (($this->is('post')) && ($this->options->postimask == '1') ): ?>post_sider<?php endif; ?>" id="secondary">
    <section class="widget ">
    <ul class="user-tips">
    
    <li><b>尊重原创</b><p>请不要发布任何盗版下载链接，包括软件、音乐、电影等等。我们尊重原创。</p></li>
    
    <li><b>友好互助</b><p>您的文章将会有成千上万人阅读，保持对陌生人的友善，用知识去帮助别人也是一种快乐。</p></li>
    
    <li><b>处罚</b><p>禁止发布垃圾广告</p><p>发现垃圾广告，本站会立刻封停您的账户</p></li>
    
    
    </ul>
    </section>
    </div>
    <!--边栏-->
    
    
	<div class="col-md-10 contpost user_center" id="content" >
	    

    <!--投稿s-->
    
    <link rel="stylesheet" href="<?php echo stcdn($this->options->themeUrl); ?>/tougao/css/jquery-markdown.css">
        <link rel="stylesheet" href="https://cdn.staticfile.org/font-awesome/4.7.0/css/font-awesome.css">
        <script type="text/javascript" src="https://upcdn.b0.upaiyun.com/libs/jquery/jquery-2.0.2.min.js"></script>
        <script type="text/javascript" src="<?php echo stcdn($this->options->themeUrl); ?>/tougao/js/jquery-markdown.js"></script>
		<script src="<?php echo stcdn($this->options->themeUrl); ?>/tougao/plugin/HyperDown.js/Parser.js"></script>

    
    
      <form action="<?php $security->index('/action/contents-post-edit'); ?>" method="post" name="write_post">  
                           
      <div class="tougao__form">
      <p class="pos_z">
      <input id="title" name="title" value="<?php $post->title(); ?>" placeholder="标题不少于5个字">
      <i class="ri-file-edit-line ri-lg"></i>
      </p>
      
      <div class="tips">给大家提供图文创作与分享趣事感受将于文字分享他人喜悦</div>
      </div>
      
      
      <input type="hidden" id="po_img" name="fields[img]" value="<?php echo htmlspecialchars($post->fields->img); ?>" placeholder="封面地址">

      
      <div class="tougao__form">
      <p class="pos_z">
      <input  name="fields[videourl]" value="<?php echo htmlspecialchars($post->fields->videourl); ?>" placeholder="视频地址">
      <i class="ri-movie-line ri-lg"></i>
      </p>
      <div class="tips">视频帮助读者快速了解内容，请勿上传色情，反动等违法视频哦</div>
      </div> 
      
      <!--多图-->
      <div class="tougao__form">
      专栏封面：
      <div class="radio_box" id="radio01">
<input type="hidden" value="" class="radioVal" name="fields[abcimg]" />
<ul>
<li id="able" class="selected">单图</li>
<li id="mable">三图</li>
</ul>
      </div> </div> 


      <!--多图-->
      <!--上传附件-->
      <?php @$currGroup = get_object_vars($this->user) ['row']['group'];if ($currGroup == "administrator"||$currGroup == "editor"||$currGroup == "contributor"): ?> 
      
      
<?php
if (isset($post) || isset($page)) {
    $cid = isset($post) ? $post->cid : $page->cid;
    
    if ($cid) {
        Typecho_Widget::widget('Widget_Contents_Attachment_Related', 'parentId=' . $cid)->to($attachment);//相关的
    } else {
        //Typecho_Widget::widget('Widget_Contents_Attachment_Unattached')->to($attachment);//单独的
        Typecho_Widget::widget('Widget_Contents_Attachment_Related', 'parentId=' . $cid)->to($attachment);
    }
}
?>

    <div id="upload-panel" class="p">
    
    <div class="upload-area" draggable="true"><?php _e('拖放文件到这里<br>或者 %s选择文件上传%s', '<a href="###" class="upload-file">', '</a>'); ?></div>
    <ul id="file-list" class="Up_img">
    <?php while ($attachment->next()): ?>
        <li data-cid="<?php $attachment->cid(); ?>" data-title="<?php $attachment->title(); ?>" data-url="<?php echo $attachment->attachment->url; ?>" data-image="<?php echo $attachment->attachment->isImage ? 1 : 0; ?>"><input type="hidden" name="attachment[]" value="<?php $attachment->cid(); ?>" />
            <a class="insert" title="<?php _e('点击插入文件'); ?>" href="###">
            <img title="<?php $attachment->title(); ?>" src="<?php echo $attachment->attachment->url; ?>">
            <?php if ($attachment->attachment->url==$post->fields->img): ?>
            <div class="picsum-icon p_color">封面</div>
            <?php endif; ?>   
            </a>
            <div class="info">
                <?php echo number_format(ceil($attachment->attachment->size / 1024)); ?> Kb
                <!--<a class="file" target="_blank" href="<?php $options->adminUrl('media.php?cid=' . $attachment->cid); ?>" title="<?php _e('编辑'); ?>"><i class="icon iconfont icon-icon-test10"></i></a>-->
                <a href="###" class="delete" title="<?php _e('删除'); ?>"><i class="ri-close-line ri-lg"></i></a>
                <a href="###" class="fimg" title="<?php _e('封面'); ?>"><i class="ri-image-fill ri-lg"></i></a>
            </div>
        </li>
    <?php endwhile; ?>
    </ul>
    </div>

           

      <?php endif; ?>
      <!--上传附件-->
      
      <div class="item">
					
	    <!--编辑器-->
	    <div id="md-container">
            <div id="md-header">
                <button type="button" title="加粗" id="md-bold"><i class="fa fa-bold"></i></button>
                <button type="button" title="斜体" id="md-italic"><i class="fa fa-italic"></i></button>
                <button type="button" title="1号标题" id="md-h1"><i class="fa fa-header">1</i></button>
                <button type="button" title="2号标题" id="md-h2"><i class="fa fa-header">2</i></button>
                <button type="button" title="3号标题" id="md-h3"><i class="fa fa-header">3</i></button>
                <button type="button" title="4号标题" id="md-h4"><i class="fa fa-header">4</i></button>
                <button type="button" title="链接" id="md-link"><i class="fa fa-link"></i></button>
                <button type="button" title="图片" id="md-image"><i class="fa fa-file-photo-o"></i></button>
                <button type="button" title="引用" id="md-quote"><i class="fa fa-quote-left"></i></button>
                <button type="button" title="代码" id="md-code"><i class="fa fa-code"></i></button>
                <button type="button" title="分割线" id="md-hr"><i class="fa fa-minus"></i></button>
                <button type="button" title="数字列表" id="md-numList"><i class="fa fa-list-ol"></i></button>
                <button type="button" title="点列表" id="md-bulletList"><i class="fa fa-list-ul"></i></button>
                <button type="button" title="预览效果" id="md-build"><i id="md-build-i" class="fa fa-eye"></i></button>
            </div>
            <textarea id="md-editor" name="text"><?php echo htmlspecialchars($post->text); ?></textarea>
            <div id="md-build-div" class="entry-content"></div>
        </div>
	    <!--编辑器-->
                         
	</div>
						
						
						<div class="item">
							
							<div style="display:none;" id="tag_input">
								<input name="tags" class="text" maxlength="2" type="text" value="<?php $post->tags(',', false); ?>" style="width:60px;">
								
								<span style="margin-left: 10px; font-size: 12px;">标签为两个汉字（如电影、工作等，清晰、合理的标签可以让话题更有价值。）</span>
							</div>
						</div>
                        <?php if($tid!=null ): ?>	
                        <input type="hidden" name="cid" value="<?php echo $tid; ?>" />
                        <?php endif; ?>
						<input type="hidden" name="do" value="publish" /><!--公开，可以无视-->
						<input type="hidden" id="allowComment" name="allowComment" value="1" checked="true" /><!--允许评论-->
				        <input type="hidden" name="markdown" value="1">	
				        <input type="hidden" value="<?php echo $postcateid; ?>" name="category[]">
				        <input type="hidden" name="referer" value="<?php $this->options->siteUrl(); ?>">
		<!--提交-->
		<div class="tougao_btn">
		<?php @$currGroup = get_object_vars($this->user) ['row']['group'];if ($currGroup == "administrator"||$currGroup == "editor"||$currGroup == "contributor"): ?>    
        <button id="myBtn" type="submit" class="con_btn"><i class="ri-file-edit-line ri-lg"></i> 发布</button>
        <button type="submit" name="do" value="save" id="btn-save" class="btn con_btn" ><i class="ri-save-line ri-lg"></i> 保存</button>
        <?php else: ?> 
        <button id="myBtn" type="submit" disabled="disabled" class="con_btn"><i class="ri-file-edit-line ri-lg"></i> 发布</button> 
        <button type="submit" name="do" value="save" id="btn-save" class="btn con_btn" disabled="disabled"><i class="ri-save-line ri-lg"></i> 保存</button>
        <?php endif; ?> 
        </div>
        <!--提交-->
         <input type="hidden" name="renow" value="tougao">                  
					</form> 
    <!--投稿e-->
 
	  	  <div class="meb_top active"> 
<p>投稿专栏是一个面向全体用户的图文阅读专栏。为了给大家提供图文创作与分享的空间，感受将文字分享于他人的喜悦。

为了给Up主更多样化的内容呈现形式，让用户体验写作与阅读的快感。</p>

<p>欢迎各位的加入～</p>
</div>
	    
	    
	
    </div>
	
</div>
<?php $this->need('footer.php'); ?>
<link rel="stylesheet" href="<?php echo stcdn($this->options->themeUrl); ?>/src/css/popup.css">
<?php
require_once("libs/common-js.php");
?>

<?php
include 'libs/file-upload-js.php';
?>

<!--以下是图片复制粘贴上传处理-->
<?php
 Typecho_Widget::widget('Widget_Options')->to($options);
?>
<script>
// 粘贴文件上传
$(document).ready(function () {
    // 上传URL
    var uploadUrl = '<?php Helper::security()->index('/action/upload'); ?>';
    // 处理有特定的 CID 的情况
    var cid = $('input[name="cid"]').val();
    if (cid) {
        uploadUrl += '&cid=' + cid;
    }

    // 上传文件函数
    function uploadFile(file) {
        // 生成一段随机的字符串作为 key
        var index = Math.random().toString(10).substr(2, 5) + '-' + Math.random().toString(36).substr(2);
        // 默认文件后缀是 png，在Chrome浏览器中剪贴板粘贴的图片都是png格式，其他浏览器暂未测试
        var fileName = index + '.png';

        // 上传时候提示的文字
        var uploadingText = '[图片上传中...(' + index + ')]';

        // 先把这段文字插入
        var textarea = $('#md-editor'), sel = textarea.getSelection(),
        offset = (sel ? sel.start : 0) + uploadingText.length;
        textarea.replaceSelection(uploadingText);
        // 设置光标位置
        textarea.setSelection(offset, offset);

        // 设置附件栏信息
        // 先切到附件栏
        $('#tab-files-btn').click();

        // 更新附件的上传提示
        var fileInfo = {
            id: index,
            name: fileName
        }
        fileUploadStart(fileInfo);

        // 是时候展示真正的上传了
        var formData = new FormData();
        formData.append('name', fileName);
        formData.append('file', file, fileName);

        $.ajax({
            method: 'post',
            url: uploadUrl,
            data: formData,
            contentType: false,
            processData: false,
            success: function (data) {
                var url = data[0], title = data[1].title;
                textarea.val(textarea.val().replace(uploadingText, '![' + title + '](' + url + ')'));
                // 触发输入框更新事件，把状态压人栈中，解决预览不更新的问题
                textarea.trigger('paste');
                // 附件上传的UI更新
                fileUploadComplete(index, url, data[1]);
            },
            error: function (error) {
                textarea.val(textarea.val().replace(uploadingText, '[图片上传错误...]\n'));
                // 触发输入框更新事件，把状态压人栈中，解决预览不更新的问题
                textarea.trigger('paste');
                // 附件上传的 UI 更新
                fileUploadError(fileInfo);
            }
        });
    }

    // 监听输入框粘贴事件
    document.getElementById('md-editor').addEventListener('paste', function (e) {
      var clipboardData = e.clipboardData;
      var items = clipboardData.items;
      for (var i = 0; i < items.length; i++) {
        if (items[i].kind === 'file' && items[i].type.match(/^image/)) {
          // 取消默认的粘贴操作
          e.preventDefault();
          // 上传文件
          uploadFile(items[i].getAsFile());
          break;
        }
      }
    });



    //
    // 以下代码均来自 /admin/file-upload-js.php，
    //

    // 更新附件数量显示
    function updateAttacmentNumber () {
        var btn = $('#tab-files-btn'),
            balloon = $('.balloon', btn),
            count = $('#file-list li .insert').length;

        if (count > 0) {
            if (!balloon.length) {
                btn.html($.trim(btn.html()) + ' ');
                balloon = $('<span class="balloon"></span>').appendTo(btn);
            }

            balloon.html(count);
        } else if (0 == count && balloon.length > 0) {
            balloon.remove();
        }
    }

    // 开始上传文件的提示
    function fileUploadStart (file) {
        $('<li id="' + file.id + '" class="loading">'
            + file.name + '</li>').appendTo('#file-list');
    }

    // 上传完毕的操作
    var completeFile = null;
     function fileUploadComplete (id, url, data) {
        var li = $('#' + id).removeClass('loading').data('cid', data.cid)
            .data('url', data.url)
            .data('image', data.isImage)
            .html('<input type="hidden" name="attachment[]" value="' + data.cid + '" />'
                + '<a class="insert" href="###" title="<?php _e('点击插入文件'); ?>"><img title="'+data.title+'" src="'+data.url + '"></a><div class="info">' + data.bytes
                //+ ' <a class="file" target="_blank" href="<?php $options->adminUrl('media.php'); ?>?cid=' 
                //+ data.cid + '" title="<?php _e('编辑'); ?>"><i class="icon iconfont icon-icon-test10"></i></a>'
                + ' <a class="delete" href="###" title="<?php _e('删除'); ?>"><i class="icon iconfont icon-shanchu"></i></a><a href="###" class="fimg" title="<?php _e('封面'); ?>"><i class="icon iconfont icon-tupian"></i></a></div>')
            .effect('highlight', 1000);
            
            

        attachInsertEvent(li);
        attachDeleteEvent(li);
        attachfimgEvent(li);
        updateAttacmentNumber();

        if (!completeFile) {
            completeFile = data;
        }
    }


    function attachInsertEvent (el) {
        $('.insert', el).click(function () {
            
            var t = $(this), p = t.parents('li');
            
            var settings = $.extend({
        default_image_url: p.data('url'),
        default_alt_text: prompt('添加图片描述',p.data('title'))
    }, el);
    $('#md-editor').textReplace({
        selected: function(image_alt_text) {
            if(settings.default_alt_text !== null &&  settings.default_alt_text !== false){
              return '![' + settings.default_alt_text + '](' + settings.default_image_url + ')';
            }else{
              return false;
            }
        },
        no_selection: function() {
            if(settings.default_alt_text !== null &&  settings.default_alt_text !== false){
                return '![' + settings.default_alt_text + '](' + settings.default_image_url + ')';
            }else{
                return false;
            }
        }
    });
            
            
        });
    }


    // 增加删除事件
    function attachDeleteEvent (el) {
        var file = $('a.insert', el).text();
        $('.delete', el).click(function () {
            if (confirm('<?php _e('确认要删除文件 %s 吗?'); ?>'.replace('%s', file))) {
                var cid = $(this).parents('li').data('cid');
                $.post('<?php Helper::security()->index('/action/contents-attachment-edit'); ?>',
                    {'do' : 'delete', 'cid' : cid},
                    function () {
                        $(el).fadeOut(function () {
                            $(this).remove();
                            updateAttacmentNumber();
                        });
                    });
            }

            return false;
        });
    }
    
     function attachfimgEvent (el) {
       
        var title = $('a.insert img', el).attr('src');//获取title内容
        $('.fimg', el).click(function () {
            if (confirm('<?php _e('设置为文章封面图?'); ?>')) {
                $('#po_img').val(title);
                $("a.insert .picsum-icon").remove();
                $('a.insert img', el).after("<div class='picsum-icon p_color'>封面</div>");
            }

            return false;
        });
    }
    
     $('#file-list li').each(function () {
        attachInsertEvent(this);
        attachDeleteEvent(this);
        attachfimgEvent(this);
    });

    // 错误处理，相比原来的函数，做了一些微小的改造
    function fileUploadError (file) {
        var word;

        word = '<?php _e('上传出现错误'); ?>';

        var fileError = '<?php _e('%s 上传失败'); ?>'.replace('%s', file.name),
            li, exist = $('#' + file.id);

        if (exist.length > 0) {
            li = exist.removeClass('loading').html(fileError);
        } else {
            li = $('<li>' + fileError + '<br />' + word + '</li>').appendTo('#file-list');
        }

        li.effect('highlight', {color : '#FBC2C4'}, 2000, function () {
            $(this).remove();
        });
    }
})
</script>