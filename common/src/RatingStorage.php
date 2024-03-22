<?php

/**
 * @file
 */

namespace Drupal\common;

class RatingStorage {

    public static function markDelete($module, $rate_id){

        $database = \Drupal::database();
        $transaction =  $database->startTransaction();
        try {

            $query = $database->update('kicp_rate')->fields([
                'is_deleted'=>1 , 
              ])
              ->condition('module', $module)
              ->condition('rate_id', $rate_id);
              $affected_rows = $query->execute();

		} catch (\Exception $e) {
            \Drupal::messenger()->addError(
                t('Unable to delete rating at this time due to datbase error. Please try again.')
              );   
              $transaction->rollBack();
		}
        unset($transaction);   
		return $affected_rows;
	}

}


