<?php

/**
 * @file
 * Create Block
 */

namespace Drupal\testform\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provide the Form main Block.
 *
 * @Block(
 *   id = "testform_block",
 *   admin_label = @Translation("The Test Form Block"),
 * )
 */
class TestFormBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {

/*
   return [
    '#type' => 'markup',
    '#markup' => $this->t('My test form Block'),
   ];
*/

   return \Drupal::formBuilder()->getForm('Drupal\testform\Form\TestForm');
  }

   /**
   * {@inheritdoc}
   */
   public function BlockAccess(AccountInterface $account) {
      $node =\Drupal::routeMatch()->getParameter('node');

     if (!(is_null($node)) ) {
            $enabler = \Drupal::service('testform.enabler');
            if ($enabler->isEnabled($node)) {
              return AccessResult::allowedIfHasPermission($account, 'view testform');
            }
     }

     return AccessResult::forbidden();
 
   }


}
