<?php

namespace Drupal\common\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\common\CommonUtil;

class TagList extends ControllerBase {
	
	
  public function __construct() {
        $this->default_interval = 30;
        $this->default_delimitor = ";";
  }	

  
   public function getListCopTagForModule() {
        $result = self::getCopTag();
        $renderable = [
            '#theme' => 'common-tag-getlistcoptagmodule',                
            '#items' => $result,
        ];
        $content = \Drupal::service('renderer')->renderPlain($renderable);   
        return $content;		
    }
	
	
 public function getList($InModule) {
        $output = array();

        $sql = "SELECT COUNT(1) AS AMOUNT, tag FROM kicp_tags WHERE tag NOT LIKE 'system:%' and is_deleted = 0";
        if ($InModule != 'ALL') {
            $sql .= " AND module LIKE '$InModule%'";
        }
        $sql .= " GROUP BY tag ORDER BY AMOUNT DESC, tag ";
		
		$database = \Drupal::database();
		$result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);
		
        foreach ($result as $record) {
            switch ($record['AMOUNT']) {
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

            $record['size'] = $fontsize;
            $output[] = $record;

        }

        $renderable = [
            '#theme' => 'common-tag-getlist',                
            '#items' => $result,
        ];
        $content = \Drupal::service('renderer')->renderPlain($renderable);   
        return $content;		

    }
	
	
	
	public static function getCopTag() {
	
		  try {
			$database = \Drupal::database();
			$query = $database-> select('kicp_km_cop', 'a'); 
			$query -> leftjoin('kicp_km_cop_group', 'b', 'a.cop_group_id = b.group_id');
			$query-> fields('a', ['cop_name']);
			$query-> condition('a.is_deleted', '0', '=');
			$query-> condition('b.is_deleted', '0', '=');
  		    $query-> orderBy('a.cop_name', 'ASC');
            $result = $query->execute()->fetchCol();
			return $result;
		 }
		 catch (\Exception $e) {
			\Drupal::messenger()->addStatus(
			   t('Unable to load tags at this time due to datbase error. Please try again.').$e
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

            if (!$result)
                return null;
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

    public function getCOPTagList() {

        $output = array();
        $copTag = self::getCopTag();
        if (!$copTag)
            return null;

        foreach($copTag as $cop) {
            $output[] = $cop;
        }

        return $output;

    }

    public function getOtherTagList($start=0, $interval=0) {

        $output = array();
        $interval = ($interval==0)?$this->default_interval:$interval;

        $sql = "SELECT COUNT(1) AS AMOUNT, tag FROM kicp_tags WHERE tag NOT LIKE 'system:%' and is_deleted = 0 GROUP BY tag ORDER BY AMOUNT DESC, tag ";
        
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        $count = 0;
        foreach ($result as $record)  {
            if($count >= $start && $count < ($start+$interval) ) {
                switch ($record['AMOUNT']) {
                    case 1:
                    case 2:
                        $fontsize = '90';
                        break;
                    case 3:
                        $fontsize = '107';
                        break;
                    case 4:
                    case 5:
                        $fontsize = '124';
                        break;
                    case 6:
                    case 7:
                        $fontsize = '141';
                        break;
                    default:
                        $fontsize = '158';
                }

                $record['fontsize']  = $fontsize;
                $output[] = $record;
            }
            $count++;
        }

        $output['interval'] = $interval;
        $output['count'] = $count;

        return $output;

        
    }

    
}