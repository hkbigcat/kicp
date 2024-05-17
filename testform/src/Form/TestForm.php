<?php

namespace Drupal\testform\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * HelloForm controller.
 */
class TestForm extends FormBase {

/**
 * {@inheritdoc}
 */
  public function getFormId() {
    return 'test_hello_form';
  }

/**
 * {@inheritdoc}
 */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $node = \Drupal::routeMatch()-> getParameter('node');

    if (!(is_null($node)) ) {
      $nid = $node->id();
    }
    else {
         $nid = 0;
    }

    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('Please enter the title and accept the terms of use of the site.'),
    ];

    $form['email'] = [
      '#type' => 'textfield',
      '#title' => t('Email Address'),
      '#size' => 25,
      '#description' => t('Enter the email.'),
      '#required' => TRUE,
    ];

/*
    $form['accept'] = [
      '#type' => 'checkbox',
      '#title' => $this
        ->t('I accept the terms of use of the site'),
      '#description' => $this->t('Please read and accept the terms of use'),
    ];
*/
     $form['nid'] = [
      '#type' => 'hidden',
      '#value' => $nid,
    ];


    // Group submit handlers in an actions element with a key of "actions" so
    // that it gets styled correctly, and so that other modules may add actions
    // to the form. This is not required, but is convention.
    $form['actions'] = [
      '#type' => 'actions',
    ];

    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Enter email'),
    ];

    return $form;

  }

/**
 * {@inheritdoc}
 */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $email = $form_state->getValue('email');
  //  $accept = $form_state->getValue('accept');

/*
    if (strlen($email) < 10) {
      // Set an error for the form element with a key of "title".
     $form_state->setErrorByName('email', t('The email must be at least 10 characters long.'));
    }
*/

   if (!(\Drupal::service('email.validator')->isValid($email)) ) {
      $form_state->setErrorByName('email', t('%mail is not valid email.', ['%mail' => $email] ));
   }


/*
    if (empty($accept)) {
      // Set an error for the form element with a key of "accept".
      $form_state->setErrorByName('accept', $this->t('You must accept the terms of use to continue'));
    }
*/

  }

/**
 * {@inheritdoc}
 */
   public function submitForm(array &$form, FormStateInterface $form_state) {

    // Display the results
    // Call the Static Service Container wrapper
    // We should inject the messenger service, but its beyond the scope
    // of this example.
    //$messenger = \Drupal::messenger();
//    $messenger->addMessage('Email: ' . $form_state->getValue('email'));
//    $messenger->addMessage('Accept: ' . $form_state->getValue('accept'));

//      $submitted_email = $form_state->getValue('email');
//      $this->messenger()->addMessage(t("the form is working. You enter @entry.",
//       ['@entry' => $submitted_email] ));

    try {
       $uid = \Drupal::currentUser()->id();
       //$uid = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
//       $full_user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());

       //Obtain the value as entered into the Form
       $nid =  $form_state->getValue('nid');
       $email =  $form_state->getValue('email');
       $current_time =  \Drupal::time()->getRequestTime();

       //End Phase 1

       $query = \Drupal::database()->insert('testform');

       $query->fields([
         'uid',
         'nid',
         'mail',
         'created',
       ]);

      $query->values([
        $uid,
        $nid,
        $email,
        $current_time,
      ]);

     $query->execute();

     //End Phase 2

/*
     \Drupal::messenger()->addMessage(
       t('Thank you for your RSVP. You are on the list for teh event');
     );
*/
      $messenger = \Drupal::messenger(); 
      $messenger->addMessage( t('Thank you for your RSVP. You are on the list for teh event'));


    }

    // End Phase 3
    catch (\Exception $e ) {
        \Drupal::messenger()->addError(
          t('Unable to save RSVP settigns at this time due to datbase error. Please try again.')
        ); 

    }

    // Redirect to home.
    //$form_state->setRedirect('<front>');
  }

}
