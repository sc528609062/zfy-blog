<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 找回密码类
 *
 * @package Deng
 * @copyright Copyright (c) 2016 ShingChi (https://github.com/shingchi)
 * @license GNU General Public License 2.0
 */

class Deng_Ai extends Typecho_Widget
{
    //生成文章
    public function aition()
    {
        $request = Typecho_Request::getInstance();
        $keyword = $request->get('keyword');
     
        
        $options = Typecho_Widget::widget('Widget_Options')->plugin('Deng');
        $Prompt=$options->Prompt;

        $script = "根据".$keyword.",".$Prompt;
        

        $con = self::chatgpt($script);
        
        
        $response = array(
        'keyword' => $keyword,
        'content' => $con
         );
    
         header('Content-Type: application/json');
         echo json_encode($response);
    }
    
    //ai回复评论
    public function comtion()
    {
        $request = Typecho_Request::getInstance();
        $cid = $request->get('cid');
        $textContent = $request->get('textContent');
        $currentUrl = $request->get('currentUrl');
        
        
        $Prompt='请你30字评论一下这段文章:';

        $script = $Prompt.$textContent;
        $con = self::chatgpt($script);
         
        //回复入库
        $db = Typecho_Db::get();
        
        // 将文章添加到table.contents表中
       
        $postId = $db->query($db->insert('table.comments')->rows(array(
        'cid' => $cid,
        'created' => time(),
        'author' => 'AI点评',
        'authorId' => 0,
        'ownerId' => 1,
        'text' => '<a href="'.$currentUrl.'">#'.$currentUrl.'</a> '.$con,
        'type' => 'comment',
        'status' => 'approved',
        'parent' => 0
        //'category' => '1'
        )));
        
        //回复入库
         
         $response = array(
        'content' => $con
        );
    
         header('Content-Type: application/json');
         echo json_encode($response);
    }
    
    //aitag标签
    public function aitag()
    {
        $request = Typecho_Request::getInstance();
        $cid = $request->get('cid');
        $db= Typecho_Db::get();
        $exist = $db->fetchRow($db->select()->from('table.contents')->where('cid = ?', $cid));
        $cont = $exist['text'];
        $Prompt = '请你根据《'.$cont.'》的内容，总结一段250字的描述大纲，一段话进行总结，不能掺杂其它内容。';
        $con = self::chatgpt($Prompt);
        
        $db->query($db->update('table.contents')->rows(array('aitag' =>$con))->where('cid = ?',$cid)); 

        $response = array(
        'content' => $con
        );
    
         header('Content-Type: application/json');
         echo json_encode($response);
    }
    
    //对话生成
    public function chattion() {
    $request = Typecho_Request::getInstance();
    
    $ai_replies = json_decode($request->get('aicon'), true); //ai回复内容数组
    $user_replies = json_decode($request->get('usercon'), true); //用户回复内容数组
    $new_user_question=$_POST["newcon"];//用户最新提问

    
    if (!is_array($ai_replies)) {
    $ai_replies = array();
    }

    if (!is_array($user_replies)) {
    $user_replies = array();
    }
    
    $options = Typecho_Widget::widget('Widget_Options')->plugin('Deng');
    $aise=$options->aiPrompt;

    //正式内容s---------------------------------------------
    
        // 定义一个变量来表示是否是第一次提问
        $first_time = empty($user_replies);

        $merged_array = array_merge($ai_replies, $user_replies);
        // 创建对话历史记录数组
        $conversation = array();

        // 开头加入一组对话
        $conversation[] = array(
            "role" => "user",
            "content" => $aise
        );
        $conversation[] = array(
            "role" => "assistant",
            "content" => "好的，明白了"
        );
        
        // 如果是第一次提问，将用户的提问直接作为对话的起点
        if ($first_time) {
            $conversation[] = array(
                "role" => "user",
                "content" => $new_user_question
            );
        } else {
            // 如果不是第一次提问，将之前的用户回答和AI回答添加到对话历史记录数组
            $count = min(count($user_replies), count($ai_replies));
            for ($i = 0; $i < $count; $i++) {
                $conversation[] = array(
                    "role" => "user",
                    "content" => $user_replies[$i]
                );
                $conversation[] = array(
                    "role" => "assistant",
                    "content" => $ai_replies[$i]
                );
            }

            // 将最新的用户提问添加到对话历史记录的末尾
            $conversation[] = array(
                "role" => "user",
                "content" => $new_user_question
            );
        }

        $vtit = self::chatai($conversation); // AI回复的内容
        
        

    //正式内容e---------------------------------------------

    $vtit = Markdown::convert($vtit);
    
    if(empty($vtit)){  $vtit='很抱歉，我无法直接回答您的问题。请您提供更具体的信息，以便我更好地回答您的问题。';  }//防止意外
    //正式内容---------------------------------------------    

    header('Content-Type: application/json');
    echo json_encode($vtit);
        
    }
    
    function chatai($conversation) {

    $options = Typecho_Widget::widget('Widget_Options')->plugin('Deng');
    $api_key=$options->API_KEY;
    $base_url = $options->API_URL;
    
    $ch = curl_init($base_url);

    // 设置 cURL 选项
    $data = array(
        "model" => "gpt-3.5-turbo",
        "messages" => $conversation,
        "temperature" => 0.8  // 设置 temperature 参数为 0.8
    );
    $headers = array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key,
    );

    // 配置 cURL 请求
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // 发送请求并处理响应
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close($ch);

    $response = json_decode($result, true);

    // 获取响应内容部分
    $content = $response['choices'][0]['message']['content'];

    return $content;
}
    
    
    public static function chatgpt($con){
    ///////////////////////////////////////////////////请求CHATGPT///////////////////////////////////////////
    // 将您的 API 密钥和要发送的文本分配给变量
    $options = Typecho_Widget::widget('Widget_Options')->plugin('Deng');
    $API_KEY=$options->API_KEY;
    $API_URL=$options->API_URL;
    // 设置 cURL 选项
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array(
    "model" => "gpt-3.5-turbo",
    "stream" => false,
    "messages" => array(
        array(
            "role" => "user",
            "content" => $con,
        ),
    ),
    )));
    $headers = array();
    $headers[] = 'Authorization: Bearer ' . $API_KEY;
    $headers[] = 'Content-Type: application/json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // 发送请求并处理响应
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close($ch);


    $response = json_decode($result, true);

    // 输出响应 内容部分
    $content = $response['choices'][0]['message']['content'];
    return $content;

    }

}
