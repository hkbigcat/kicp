<?php

namespace Drupal\common\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;

class TagList extends ControllerBase {
	
	
  public function __construct() {
        $this->default_interval = 30;
        $this->default_delimitor = ";";
  }	

  public function showlist() {
    // Modify this part to fit your needs.
    $list = ['item1', 'item2', 'item3'];
    return $list;
  }
  
  
   public function getListCopTagForModule() {
	
    $output = "";

        $result = self::getCopTag();
        //$result = ['item1', 'item2', 'item3'];
        foreach ($result as $record) {
            

            $output .= '<span class="tagBullet"><a style="font-size:107%; font-style: normal; text-decoration:none" href="javascript:void(0)"' .
                ' onClick="addTag(this);">' . $record->cop_name . '</a></span>';
							
				
        }

        return $output;
	
		
		
    }
	
	
 public function getList($InModule) {
        $output = "";


        $sql = "SELECT COUNT(1) AS AMOUNT, tag FROM kicp_tags WHERE tag NOT LIKE 'system:%' and is_deleted = 0";
        if ($InModule != 'ALL') {
            $sql .= " AND module LIKE '$InModule%'";
        }
        $sql .= " GROUP BY tag ORDER BY AMOUNT DESC, tag ";
		
		$database = \Drupal::database();
		$result = $database-> query($sql); 
		
        foreach ($result as $record) {
            switch ($record->AMOUNT) {
                case 1:
                    $fontsize = '90';
                    break;
                case 2:
                    $fontsize = '90';
                    break;
                case 3:
                    $fontsize = '107';
                    break;
                case 4:
                    $fontsize = '124';
                    break;
                case 5:
                    $fontsize = '124';
                    break;
                case 6:
                    $fontsize = '141';
                    break;
                case 7:
                    $fontsize = '141';
                    break;
                default:
                    $fontsize = '158';
            }
            $output .= '<span class="tagBullet"><a style="font-size:' . $fontsize . '%; font-style: normal; text-decoration:none" href="javascript:void(0)"' .
                ' onClick="addTag(this);" title="' .
                $record->AMOUNT . '">' . $record->tag . '</a></span>';
        }

    
		

        return $output;
    }
	
	
	
	public static function getCopTag() {
	
		  try {
			$database = \Drupal::database();
			$selected_query = $database-> select('kicp_km_cop', 'a'); 
			$selected_query -> join('kicp_km_cop_group', 'b', 'a.cop_group_id = b.group_id');
			$selected_query-> fields('a', ['cop_name']);
			$selected_query-> condition('a.is_deleted', '0', '=');
			$selected_query-> condition('b.is_deleted', '0', '=');
  		    $selected_query-> orderBy('a.cop_name', 'ASC');
			$entries =  $selected_query->execute();
			if ($entries)
				return $entries;
		 }
		 catch (\Exception $e) {
			\Drupal::messenger()->addStatus(
			   t('Unable to load tags at this time due to datbase error. Please try again.')
			 );
		   return  NULL;
		 }	
		 
		
    }


    public function getTagsForModule($InModule,$Item=null) {

        
        try {  

            if ($Item==null) {
                $sql = "SELECT * FROM kicp_tags WHERE tag NOT LIKE 'system:%' and is_deleted = 0 AND module = '$InModule' order by fid";
            } else {
                $sql = "SELECT * FROM kicp_tags WHERE tag NOT LIKE 'system:%' and is_deleted = 0 AND module = '$InModule' AND fid='" . intval($Item)."'";
            }
            $database = \Drupal::database();
            $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);

            $taglist = array();
            foreach ($result as $record)  {
                if ($Item==null) {
                    $taglist[$record['fid']][] = $record['tag'];
                } else {
                    $taglist[] = $record['tag'];    
                }
            }
            return $taglist;

            

        }
        catch (\Exception $e) {
   
            \Drupal::messenger()->addStatus(
               t('Unable to load tags for modules at this time due to datbase error. Please try again.')
             );
     
             return NULL;
        }
 
    }

    public function getTagListByRecordId($module, $record_id) {
        $database = \Drupal::database();
        $sql = "SELECT * FROM kicp_tags WHERE tag NOT LIKE 'system:%'  AND module = '" . $module . "' and fid = $record_id and is_deleted = 0";
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        
        $taglist = array();
        foreach ($result as $record)  {
            $taglist[] = $record['tag'];    
        }
        return $taglist;

    }    

    
}