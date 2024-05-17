<?php

/**
 * @file
 * Contains the RBPS Enabler services
 */

namespace Drupal\testform;

use Drupal\Core\Database\Connection;
use Drupal\node\Entity\Node;

class EnablerService {

  protected $database_connection;

  
  public function __construct(Connection $connection) {
   $this->database_connection = $connection;
  }

  public function isEnabled(Node $node) {

    if ($node->isNew()) {
       return FALSE;
    }
    
    try {
      
       $select = $this->database_connection->select('testform_enabled', 're');
       $select->fields('re', ['nid']);
       $select->condition('nid', $node->id());
       $results = $select->execute();
      
       return !(empty($results->fetchCol()));

      return NULL;       

     }
     catch (\Exception $e) {
       \Drupal::messenger()->addError(
          t('Unable to determine RSVP settigns at this time due to datbase error. Please try again.')
        ); 
        return NULL;
     }
  }
   public function setEnabled(Node $node) { 

      try {
       if (!($this->isEnabled($node)) ) {
        
         $insert = $this->database_connection->insert('testform_enabled');
         $insert->fields(['nid']);
         $insert->values([$node->id()]);
         $insert->execute();
         
       }

      }
      catch (\Exception $e) {
       \Drupal::messenger()->addError(
          t('Unable to save RSVP settigns at this time due to datbase error. Please try again.')
        );
      }

   }

   public function delEnabled(Node $node) {

      try {       
       $delete = $this->database_connection->delete('testform_enabled');
       $delete->condition('nid', $node->id());
       $delete->execute();
      }
      catch (\Exception $e) {
       \Drupal::messenger()->addEror(
          t('Unable to save RSVP settigns at this time due to datbase error. Please try again.')
        );
      }


    }
    

}
