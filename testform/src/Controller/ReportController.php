<?php

/**
 * @file
 * Provde Site administrators with a list of all the RSVP List signups
 * so  tehy can know who is attending their events
 */

namespace Drupal\testform\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;


class ReportController extends ControllerBase {

  /**
   * Gets and returns all RSVP for all nodes
   * These are returned as an associative array , with each row
   * containing the username, the node title, and email of RSVP.
   *
   * @return array/null
   */

   protected function load() {
    try {
      $database = \Drupal::database();
      $selected_query = $database-> select('testform', 'r');

      $selected_query -> join('users_field_data', 'u', 'r.uid = u.uid');
      $selected_query -> join('node_field_data', 'n', 'r.nid = n.nid');

      $selected_query -> addField('u', 'name', 'username');
      $selected_query -> addField('n', 'title');
      $selected_query -> addField('r', 'mail');

       $entries =  $selected_query->execute()->fetchAll(\PDO::FETCH_ASSOC);
      //$entries =  $selected_query->execute()->fetchAssoc();

       return $entries;
    }

      catch (\Exception $e) {

       \Drupal::messenger()->addStatus(
          t('Unable to save RSVP settigns at this time due to datbase error. Please try again.')
        );

        return NULL;
      }
  }


  /**
   * Creates the TesForm report page.
   *
   * @return array
   * Render array fro the Test Form list
   */
  public function report() {


    $content = [];
    $content['message'] = [
      '#markup' => t('Below is a  list of all Event RSVPs includin email addrss and he name of the event date they will be attending.'),
    ];


   $headers = [
    t('Username'),
    t('Event'),
    t('Email'),
   ];


   $table_rows = $this->load();


   $content['table'] = [
     '#type' => 'table',
     '#header' => $headers,
     '#rows' => $table_rows,
     '#empty' => t('No entries available.'),
   ];

    $content['#cache']['max-age'] = 0;

    return $content;


  }

}
