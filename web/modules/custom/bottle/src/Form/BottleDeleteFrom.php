<?php

namespace Drupal\bottle\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class BottleDeleteFrom extends ContentEntityConfirmFormBase {

    public function getQuestion() {
        return $this->t('Are you sure you want to delete entity %name?', array('%name' => $this->entity->label()));
    }

    public function getCancelUrl() {
        return new url('entity.content_entity_bottle.collection');
    }

    public function getCancelText() {
        return $this->t('Delete');
    }

    /**
     * {@inheritdoc}
     *
     * Delete the entity and log the event. log() replaces the watchdog.
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $entity = $this->getEntity();
        $entity->delete();

        \Drupal::logger('bottle')->notice('@type: deleted %title.', array(
            '@type' => $this->entity->bundle(),
            '%title' => $this->entity->label(),
        ));
        $form_state->setRedirect('entity.content_entity_bottle.collection');
    }

}
