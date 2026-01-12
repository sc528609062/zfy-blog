<?php

class Deng_Action extends Typecho_Widget implements Widget_Interface_Do
{

    // public function __construct($request, $response, $params = null)
    // {
    //     parent::__construct($request, $response, $params);
    //     if (!Typecho_Widget::widget('Widget_User')->pass('administrator', true)) {
    //         throw new Typecho_Exception(_t('Forbidden'), 403);
    //     }
    // }
    
    
        public function Action(){
            
            /*判断是否管理员操作*/
            if (!Typecho_Widget::widget('Widget_User')->pass('administrator', true)) {
            throw new Typecho_Exception(_t('非管理员禁止操作'), 403);
            }
            
            /*进行更新*/
            try {
                $Msortmid     = $_POST['Msortmid'];
                $Mseotitle     = $_POST['Mseotitle'];
                $Mseokey     = $_POST['Mseokey'];
                $Mseodesc     = $_POST['Mseodesc'];
                $Mcatepages     = $_POST['Mcatepages'];
                $Mcateico     = $_POST['Mcateico'];
                
                if(empty($Msortmid)){
                    exit('请勿篡改数据');
                }
                
                if(empty($Mseotitle)){
                    $Mseotitle = NULL;
                }
                if(empty($Mseokey)){
                    $Mseokey = NULL;
                }
                if(empty($Mseodesc)){
                    $Mseodesc = NULL;
                }
                if(empty($Mcatepages)){
                    $Mcatepages = NULL;
                }
                
                if(empty($Mcateico)){
                    $Mcateico = NULL;
                }
                
                
                //开始更新
                $db= Typecho_Db::get();
                $updates = $db->update('table.metas')->rows(array('seotitle'=>$Mseotitle,'seokey'=>$Mseokey,'seodesc'=>$Mseodesc,'catepages'=>$Mcatepages,'cateico'=>$Mcateico))->where('table.metas.mid = ?',$Msortmid);
                //执行后，返回收影响的行数。
                $db->query($updates);
                Typecho_Widget::widget('Widget_Notice')->set(_t("更新成功"), 'success');
                exit('更新成功');
                
            } catch (Exception $e ) {
                echo "<b>失败，请检查网络等问题~</b><br>";
            }
            
        }
        
        
        public function Special(){
            
            /*判断是否管理员操作*/
            if (!Typecho_Widget::widget('Widget_User')->pass('administrator', true)) {
            throw new Typecho_Exception(_t('非管理员禁止操作'), 403);
            }
            
            /*进行更新*/
            try {
                $Msortsid  = $_POST['Msortsid'];
                $Mcateico  = $_POST['Mcateico'];
                $Msortname = $_POST['Msortname'];
                $Msortdepict = $_POST['Msortdepict'];
                
                $MupSort = $_POST['MupSort'];
                
                //if(empty($Msortsid)){  exit('请勿篡改数据');  }
                
                if(empty($Mcateico)){
                    $Mcateico = NULL;
                }
                
                if($MupSort==1){
                //开始更新
                $db= Typecho_Db::get();
                $updates = $db->update('table.special')->rows(array('spimg'=>$Mcateico,'spdep'=>$Msortdepict,'spname'=>$Msortname))->where('table.special.sid = ?',$Msortsid);
                //执行后，返回收影响的行数。
                $db->query($updates);
                Typecho_Widget::widget('Widget_Notice')->set(_t("更新成功"), 'success');
                exit('更新成功');
                }
                if($MupSort==0){
                //开始删除    
                $db= Typecho_Db::get();
                $updates = $db->delete('table.special')->where('table.special.sid = ?',$Msortsid);
                //执行后，返回收影响的行数。
                $db->query($updates);
                Typecho_Widget::widget('Widget_Notice')->set(_t("删除成功"), 'success');
                exit('删除成功'); 
                }
                if($MupSort==2){
                //开始添加    
                
                $db= Typecho_Db::get();
                $updates = $db->insert('table.special')->rows(array('spimg'=>$Mcateico,'spdep'=>$Msortdepict,'spname'=>$Msortname));

                
                //执行后，返回收影响的行数。
                $db->query($updates);
                Typecho_Widget::widget('Widget_Notice')->set(_t("添加成功"), 'success');
                exit('添加成功');    
                    
                }
                
            } catch (Exception $e ) {
                echo "<b>失败，请检查网络等问题~</b><br>";
            }
            
        }
        

        

}