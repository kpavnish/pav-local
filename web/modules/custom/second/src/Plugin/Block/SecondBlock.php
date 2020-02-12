<?php
namespace Drupal\second\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormInterface;

/**
 * Provides a 'Second' block.
 *
 * @Block(
 *   id = "second_block",
 *   admin_label = @Translation("Second Block"),
 *   category = @Translation("Custom Block block example")
 * )
 */
class SecondBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $form = \Drupal::formBuilder()->getForm('Drupal\study\Form\CustomForm');

    return $form;
   }
}

