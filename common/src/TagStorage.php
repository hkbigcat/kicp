<?php
// using by: 
/**
 * @file
 */

namespace Drupal\common;

class TagStorage  {

    public static function insert($entry) {
		
		$database = \Drupal::database();
				
        $tags = $entry['tags'];
        
        // replace "&amp;" to "&" sign [Start]
        $tags = str_replace('&amp;', '&', $tags);
        // replace "&amp;" to "&" sign [End]
        
        $tag_array = explode(';', $tags);
        $return_value = NULL;



        foreach ($tag_array as $tag) {
            $tag = trim($tag);
            if ($tag != '') {

                $sql = "SELECT count(1) as TOTAL FROM kicp_tags WHERE module = '" . $entry['module'] . "' and fid = " . $entry['module_entry_id'] . " and tag = '$tag' and is_deleted = 0";				
                $result = $database-> query($sql)->fetchObject();
          
                if ($result->TOTAL == 0) {
                    // insert distinct tag
                    $entry1 = array(
                      'module' => $entry['module'],
                      'fid' => $entry['module_entry_id'],
                      'tag' => $tag,
                    );
                    try {
						$return_value = $database-> insert('kicp_tags')
                            ->fields($entry1)
                            ->execute();
                    }
                    catch (\Exception $e) {
                        drupal_set_message(
                            t(
                                'db_insert failed. Message = %message, query= %query', array('%message' => $e->getMessage(), '%query' => $e->query_string)
                            ), 'error'
                        );
                        break;
                    }
                }
				
			
            }
        }


        return $return_value;
    }


    public static function markDelete($module, $entry_id) {
        $return_value = NULL;

        try {

            $database = \Drupal::database();
            $return_value = $database->update('kicp_tags')->fields([
                'is_deleted'=>1 , 
            ])
            ->condition('module',  $module)
            ->condition('fid',  $entry_id)
            ->execute();

        }
        catch (\Exception $e) {
            \Drupal::messenger()->addError(
                t('db_delete failed. Module '.$module.' ID: '.$entry_id )
                );            
        }
        
        return $return_value;
    }
    

}
