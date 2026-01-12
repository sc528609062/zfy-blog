<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
class Contents{
    public static $frag = false;
    public static function parseContent($data, $widget, $last)
    {
        $text = empty($last) ? $data : $last;
        if ($widget instanceof Widget_Archive) {
            $text = self::parseBiaoQing($text);
        }
        
        return $text;
    }

    public static function excerptEx($data, $widget, $last)
    {
        $text = empty($last) ? $data : $last;
        if ($widget instanceof Widget_Archive) {
            @$text = preg_replace("/{(.*?)}/", "${1}", $text);
        }
        
        return $text;
    }


    /* 短代码解析 */

    /**
     * 解析表情
     * 
     * @return string
     */
    public static function parseBiaoQing($content)
    {   $emo = false;
        global $emo;
        if(!$emo){
            $emo = json_decode(file_get_contents(dirname(dirname(__FILE__)).'/src/owo/OwO.json'), true);
        }
        /* $options = Helper::options();
        $url = $options->siteUrl; */
        foreach ($emo as $v){
            if($v['type'] == 'image'){
                foreach ($v['container'] as $vv){
                    $content = str_replace($vv['data'], '<img class="biaoqing no-fabcybox" width="35px" height="35px" src='.$vv['icon'] .' alt="'.$vv['text'] .'">', $content);
                }
            }
        }

        $reg='/\!\[(.*?)\]\((.*?)\)/';
        $rp='';
        $content=preg_replace($reg,$rp,$content);

        $options = Typecho_Widget::widget('Widget_Options');
        if(!empty($options->src_add) && !empty($options->cdn_add)){
            $content = str_ireplace($options->src_add,$options->cdn_add,$content);
        }
        $content = preg_replace("/<a href=\"([^\"]*)\">/i", "<a href=\"\\1\" target=\"_blank\">", $content);

        return $content;
        
    }



}
