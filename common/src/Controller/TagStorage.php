<?php
// using by: 
/**
 * @file
 */

namespace Drupal\common\Controller;

use Drupal\Core\Database\Database;

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
				$result = $database-> query($sql); 
          
		  /*
                $j = 0;
                foreach ($result as $record) {
                    $j++;
                    break;
                }
            */
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


    /*
    public static function change($entry) {
        $tags = $entry['tags'];
        $tag_array = explode(";", $tags);
        $return_value = NULL;
        $entry1 = array(
          'is_deleted' => $entry['is_deleted'],
        );

        foreach ($tag_array as $tag) {
            try {
                $return_value = db_update('kicp_tags')
                    ->fields($entry1)
                    ->condition('module', $entry['module'])
                    ->condition('fid', $entry['module_entry_id'])
                    ->condition('tag', $tag)
                    ->execute();
            }
            catch (\Exception $e) {
                drupal_set_message(
                    t(
                        'db_change failed. Message = %message, query= %query', array('%message' => $e->getMessage(), '%query' => $e->query_string)
                    ), 'error'
                );
                break;
            }
        }

        return $return_value;
    }

    public static function delete($entry) {
        $tags = $entry['tags'];
        $tag_array = explode(";", $tags);
        $return_value = NULL;

        foreach ($tag_array as $tag) {
            try {
                $return_value = db_delete('kicp_tags')
                    ->condition('module', $entry['module'])
                    ->condition('fid', $entry['module_entry_id'])
                    ->condition('tag', $tag)
                    ->execute();
            }
            catch (\Exception $e) {
                drupal_set_message(
                    t(
                        'db_delete failed. Message = %message, query= %query', array('%message' => $e->getMessage(), '%query' => $e->query_string)
                    ), 'error'
                );
                break;
            }
        }

        return $return_value;
    }
    */

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
            drupal_set_message(
                t(
                    'db_delete failed. Message = %message, query= %query', array('%message' => $e->getMessage(), '%query' => $e->query_string)
                ), 'error'
            );
        }
        
        return $return_value;
    }
    

}
