<?php

namespace Drupal\mainpage\Common;



class MainpageDatatable {
    
    public function __construct() {
        $this->module = 'mainpage';
    }
    
    public static function getEditorChoiceRecord() {
        
        $sql ='SELECT id, is_module_record, module, record_id, img_name, description, modify_datetime FROM kicp_editor_choice WHERE is_deleted = 0 ORDER BY modify_datetime DESC';
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
            
    }
    
}