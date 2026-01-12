<?php

/**
 * menber.php
 * Author     : 小灯泡设计
 * Date       : 2020/4/3
 * Version    : 1.0
 * Description: 编辑器功能
 **/


/**
 * 后台编辑器
 */
Typecho_Plugin::factory('admin/write-post.php')->bottom = array('tagshelper', 'tagslist');

class tagshelper {
    public static function tagslist()
    {      
    $tag="";$taglist="";$i=0;//循环一次利用到两个位置
Typecho_Widget::widget('Widget_Metas_Tag_Cloud', 'sort=count&desc=1&limit=200')->to($tags);
while ($tags->next()) {
$tag=$tag."'".$tags->name."',";
$taglist=$taglist."<a id=".$i." onclick=\"$(\'#tags\').tokenInput(\'add\', {id: \'".$tags->name."\', tags: \'".$tags->name."\'});\">".$tags->name."</a>";
$i++;
}
?><style>.Posthelper a{cursor: pointer; padding: 0px 6px; margin: 2px 0;display: inline-block;border-radius: 2px;text-decoration: none;}
.Posthelper a:hover{background: #ccc;color: #fff;}.fullscreen #tab-files{right: 0;}/*解决全屏状态下鼠标放到附件上传按钮上导致的窗口抖动问题*/
</style>
<script>
function chaall () {
   var html='';
   $("#file-list li .insert").each(function(){
   var t = $(this), p = t.parents('li');
   var file=t.text();
   var url= p.data('url');
   var isImage= p.data('image');
   if ($("input[name='markdown']").val()==1) {
   html = isImage ? html+'\n!['+file+'](' + url + ')\n':''+html+'';
   }else{
   html = isImage ? html+'<img src="' + url + '" alt="' + file + '" />\n':''+html+'';
   }
   });
   
   var textarea = $('.cm-activeLine.cm-line');
   $(".cm-activeLine.cm-line").text(html);return false;
  
   //textarea.replaceSelection(html);return false;
}

function chaquan () {
   
   var html='';
   $("#file-list li .insert").each(function(){
   var t = $(this), p = t.parents('li');
   var file=t.text();
   var url= p.data('url');
   var isImage= p.data('image');
   if ($("input[name='markdown']").val()==1) {
   html = isImage ? html+'':html+'\n['+file+'](' + url + ')\n';
   }else{
   html = isImage ? html+'':html+'<a href="' + url + '"/>' + file + '</a>\n';
   }
   });
   var textarea = $('#text');
   $(".cm-activeLine.cm-line").text(html);return false;
   //textarea.replaceSelection(html);return false;
}

function filter_method(text, badword){
    //获取文本输入框中的内容
    var value = text;
    var res = '';
    //遍历敏感词数组
    for(var i=0; i<badword.length; i++){
        var reg = new RegExp(badword[i],"g");
        //判断内容中是否包括敏感词		
        if (value.indexOf(badword[i]) > -1) {
            $('#tags').tokenInput('add', {id: badword[i], tags: badword[i]});
        }
    }
    return;
}
var badwords = [<?php echo $tag; ?>];
function chatag(){
var textarea=$('#text').val();
filter_method(textarea, badwords); 
}
  $(document).ready(function(){
    $('#file-list').after('<div class="Posthelper"><a class="w-100" onclick=\"chaall()\" style="background: #467B96;background-color: #3c6a81;text-align: center; padding: 5px 0; color: #fbfbfb; box-shadow: 0 1px 5px #ddd;">插入所有图片</a><a class="w-100" onclick=\"chaquan()\" style="background: #467B96;background-color: #3c6a81;text-align: center; padding: 5px 0; color: #fbfbfb; box-shadow: 0 1px 5px #ddd;">插入所有非图片附件</a></div>');
    $('#tags').after('<div style="margin-top: 35px;" class="Posthelper"><ul style="list-style: none;border: 1px solid #D9D9D6;padding: 6px 12px; max-height: 240px;overflow: auto;background-color: #FFF;border-radius: 2px;margin-bottom: 0;"><?php echo $taglist; ?></ul><a class="w-100" onclick=\"chatag()\" style="background: #467B96;background-color: #3c6a81;text-align: center; padding: 5px 0; color: #fbfbfb; box-shadow: 0 1px 5px #ddd;">检测内容插入标签</a></div>');
  }); 
</script>
<?php
    }
}



Typecho_Plugin::factory('admin/write-post.php')->richEditor  = array('Editor', 'Edit');
Typecho_Plugin::factory('admin/write-page.php')->richEditor  = array('Editor', 'Edit');


class Editor
{
    public static function Edit()
    {
?>
        <script src="/usr/themes/spimes/src/js/ai.js"></script>
        <link rel="stylesheet" type="text/css" href="/usr/themes/spimes/src/css/plugins/APlayer.min.css">
        <link rel="stylesheet" type="text/css" href="/usr/themes/spimes/src/css/plugins/prism-onedark.min.css">
        <link rel="stylesheet" href="<?php Helper::options()->themeUrl('typecho/write/css/joe.write.min.css') ?>">
        <script>
            window.JoeConfig = {
                uploadAPI: '<?php Helper::security()->index('/action/upload'); ?>',
                emojiAPI: '<?php Helper::options()->themeUrl('typecho/write/json/emoji.json') ?>',
                characterAPI: '<?php Helper::options()->themeUrl('typecho/write/json/character.json') ?>',
                playerAPI: '<?php Helper::options()->CustomPlayer ? Helper::options()->CustomPlayer() : Helper::options()->themeUrl('ext/danmu/player/?url=') ?>',
                autoSave: <?php Helper::options()->autoSave(); ?>,
                themeURL: '<?php Helper::options()->themeUrl(); ?>',
                canPreview: false
            }
        </script>
        <script type="text/javascript" src="/usr/themes/spimes/src/js/plugins/APlayer.min.js?v<?php $ver = themeVersion(); echo ''. $ver .'';?>"></script>
        <script type="text/javascript" src="/usr/themes/spimes/src/js/plugins/prism.min.js?v<?php $ver = themeVersion(); echo ''. $ver .'';?>"></script>
        <script src="<?php Helper::options()->themeUrl('typecho/write/parse/parse.min.js') ?>"></script>
        <script src="<?php Helper::options()->themeUrl('typecho/write/dist/index.bundle.js') ?>"></script>
        <script src="<?php Helper::options()->themeUrl('src/js/plugins/joe.short.js') ?>"></script>
<?php
    }
}


function _parseContent($post, $login)
{
    $content = $post->content;


    if (strpos($content, '{lamp/}') !== false) {
        $content = strtr($content, array(
            "{lamp/}" => '<span class="joe_lamp"></span>',
        ));
    }
    if (strpos($content, '{x}') !== false || strpos($content, '{ }') !== false) {
        $content = strtr($content, array(
            "{x}" => '<input type="checkbox" class="joe_checkbox" checked disabled></input>',
            "{ }" => '<input type="checkbox" class="joe_checkbox" disabled></input>'
        ));
    }
    if (strpos($content, '{music') !== false) {
        $content = preg_replace('/{music-list([^}]*)\/}/SU', '<joe-mlist $1></joe-mlist>', $content);
        $content = preg_replace('/{music([^}]*)\/}/SU', '<joe-music $1></joe-music>', $content);
    }
    if (strpos($content, '{mp3') !== false) {
        $content = preg_replace('/{mp3([^}]*)\/}/SU', '<joe-mp3 $1></joe-mp3>', $content);
    }
    if (strpos($content, '{bilibili') !== false) {
        $content = preg_replace('/{bilibili([^}]*)\/}/SU', '<joe-bilibili $1></joe-bilibili>', $content);
    }
    if (strpos($content, '{dplayer') !== false) {
        $player = Helper::options()->CustomPlayer ? Helper::options()->CustomPlayer : Helper::options()->themeUrl . '/ext/danmu/player/?url=';
        $content = preg_replace('/{dplayer([^}]*)\/}/SU', '<joe-dplayer player="' . $player . '" $1></joe-dplayer>', $content);
    }
    if (strpos($content, '{mtitle') !== false) {
        $content = preg_replace('/{mtitle([^}]*)\/}/SU', '<joe-mtitle $1></joe-mtitle>', $content);
    }
    if (strpos($content, '{abtn') !== false) {
        $content = preg_replace('/{abtn([^}]*)\/}/SU', '<joe-abtn $1></joe-abtn>', $content);
    }
    if (strpos($content, '{cloud') !== false) {
        $content = preg_replace('/{cloud([^}]*)\/}/SU', '<joe-cloud $1></joe-cloud>', $content);
    }
    if (strpos($content, '{anote') !== false) {
        $content = preg_replace('/{anote([^}]*)\/}/SU', '<joe-anote $1></joe-anote>', $content);
    }
    if (strpos($content, '{dotted') !== false) {
        $content = preg_replace('/{dotted([^}]*)\/}/SU', '<joe-dotted $1></joe-dotted>', $content);
    }
    if (strpos($content, '{message') !== false) {
        $content = preg_replace('/{message([^}]*)\/}/SU', '<joe-message $1></joe-message>', $content);
    }

    if (strpos($content, '{hide') !== false) {
        $db = Typecho_Db::get();
        $hasComment = $db->fetchAll($db->select()->from('table.comments')->where('cid = ?', $post->cid)->where('mail = ?', $post->remember('mail', true))->limit(1));
        if ($hasComment || $login) {
            $content = strtr($content, array("{hide}" => "", "{/hide}" => ""));
        } else {
            $content = preg_replace('/{hide[^}]*}([\s\S]*?){\/hide}/', '<joe-hide></joe-hide>', $content);
        }
    }
    if (strpos($content, '{card-default') !== false) {
        $content = preg_replace('/{card-default([^}]*)}([\s\S]*?){\/card-default}/', '<section style="margin-bottom: 15px"><joe-card-default $1><span class="_temp" style="display: none">$2</span></joe-card-default></section>', $content);
    }
    if (strpos($content, '{callout') !== false) {
        $content = preg_replace('/{callout([^}]*)}([\s\S]*?){\/callout}/', '<section style="margin-bottom: 15px"><joe-callout $1><span class="_temp" style="display: none">$2</span></joe-callout></section>', $content);
    }
    if (strpos($content, '{alert') !== false) {
        $content = preg_replace('/{alert([^}]*)}([\s\S]*?){\/alert}/', '<section style="margin-bottom: 15px"><joe-alert $1><span class="_temp" style="display: none">$2</span></joe-alert></section>', $content);
    }
    if (strpos($content, '{card-describe') !== false) {
        $content = preg_replace('/{card-describe([^}]*)}([\s\S]*?){\/card-describe}/', '<section style="margin-bottom: 15px"><joe-card-describe $1><span class="_temp" style="display: none">$2</span></joe-card-describe></section>', $content);
    }
    if (strpos($content, '{tabs') !== false) {
        $content = preg_replace('/{tabs}([\s\S]*?){\/tabs}/', '<section style="margin-bottom: 15px"><joe-tabs><span class="_temp" style="display: none">$1</span></joe-tabs></section>', $content);
    }
    if (strpos($content, '{card-list') !== false) {
        $content = preg_replace('/{card-list}([\s\S]*?){\/card-list}/', '<section style="margin-bottom: 15px"><joe-card-list><span class="_temp" style="display: none">$1</span></joe-card-list></section>', $content);
    }
    if (strpos($content, '{timeline') !== false) {
        $content = preg_replace('/{timeline}([\s\S]*?){\/timeline}/', '<section style="margin-bottom: 15px"><joe-timeline><span class="_temp" style="display: none">$1</span></joe-timeline></section>', $content);
    }
    if (strpos($content, '{collapse') !== false) {
        $content = preg_replace('/{collapse}([\s\S]*?){\/collapse}/', '<section style="margin-bottom: 15px"><joe-collapse><span class="_temp" style="display: none">$1</span></joe-collapse></section>', $content);
    }
    if (strpos($content, '{gird') !== false) {
        $content = preg_replace('/{gird([^}]*)}([\s\S]*?){\/gird}/', '<section style="margin-bottom: 15px"><joe-gird $1><span class="_temp" style="display: none">$2</span></joe-gird></section>', $content);
    }
    echo $content;
}