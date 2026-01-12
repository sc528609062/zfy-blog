<?php

/*
首先感谢橙子、魔缇开发者提供的帮助
魔缇开发者提供的过滤函数相关代码
橙子同学提供的过滤数组递归相关代码


filter函数介绍 
1、转义引号、反斜杠
2、过滤除数字、字母、汉字之外的内容
3、过滤除数字、字母之外的内容
4、去除左右空格
*/
function filter($str, $type = 1)
{
    if ($type == 1) {
        return trim(addslashes($str));
    } elseif ($type == 2) {
        preg_match_all('/[\x{4e00}-\x{9fa5}a-zA-Z0-9]/u', trim($str), $result);
        return join('', $result[0]);
    } elseif ($type == 3) {
        return preg_replace('/[^\w]+/', '', trim($str));
    } elseif ($type == 4) {
        return strip_tags(trim($str));
    } else {
        return trim($str);
    }
}


function filters($arr, $type = 1)
{
    return array_map(function ($v) use ($type) {
        if (gettype($v) == 'array') {
            return filters($v, $type);
        } else {
            return filter($v, $type);
        }
    }, $arr);
}


$act=filter($_GET['act'],1);
$edit=filter($_POST['edit'],1);
if (isset($act) && $act== 'reset' ) {

    $file = 'yzm.config.php'; //旧目录
    $newFile = 'bak/config.php'; //新目录
    copy($newFile, $file); //拷贝到新目录
    //unlink($file); //删除旧目录下的文件
    exit;
}
if (isset($act) && $_GET['act'] == 'setting' && isset($edit) && $_POST['edit'] == 1 ) {
    $datas = $_POST;
    $data = $datas['yzm'];

    if (file_put_contents('data.php', "<?php\n \$yzm =  ".var_export($data, true).";\n?>")) {
        echo "{code:1,msg:保存成功}";
    } else {
        echo "<script>alert('修改失败！可能是文件权限问题，请给予yzm.config.php写入权限！');location.href='".$_SERVER["HTTP_REFERER"]."';</script>";
    }
    $yzm = $data;
}

