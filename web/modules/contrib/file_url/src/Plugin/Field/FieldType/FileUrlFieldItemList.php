<?php

namespace Drupal\file_url\Plugin\Field\FieldType;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file_url\FileUrlHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Represents a configurable entity file URL field.
 */
class FileUrlFieldItemList extends EntityReferenceFieldItemList {

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = [];

    $cardinality = $this->getFieldDefinition()->getFieldStorageDefinition()->getCardinality();
    if ($cardinality != FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      $constraints[] = $this->getTypedDataManager()
        ->getValidationConstraintManager()
        ->create('Count', [
          'max' => $cardinality,
          'maxMessage' => t('%name: this field cannot hold more than @count values.', ['%name' => $this->getFieldDefinition()->getLabel(), '@count' => $cardinality]),
        ]);
    }

    // @todo Add a constraint to file URI references.

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    /** @var \Drupal\file_url\FileUrlHandler $file_handler */
    $file_handler = \Drupal::service('file_url.handler');
    $entity = $this->getEntity();

    if (!$update) {
      // Add a new usage for newly uploaded files.
      foreach ($this->referencedEntities() as $file) {
        if (!$file_handler->isRemote($file)) {
          \Drupal::service('file.usage')->add($file, 'file', $entity->getEntityTypeId(), $entity->id());
        }
      }
    }
    else {
      // Get current target file entities and file IDs.
      $files = $this->referencedEntities();
      $ids = [];

      /** @var \Drupal\file\FileInterface $file */
      foreach ($files as $file) {
        $ids[] = $file->id();
      }

      // On new revisions, all files are considered to be a new usage and no
      // deletion of previous file usages are necessary.
      if (!empty($entity->original) && $entity->getRevisionId() != $entity->original->getRevisionId()) {
        foreach ($files as $file) {
          if (!$file_handler->isRemote($file)) {
            \Drupal::service('file.usage')->add($file, 'file', $entity->getEntityTypeId(), $entity->id());
          }
        }
        return;
      }

      // Get the file IDs attached to the field before this update.
      $field_name = $this->getFieldDefinition()->getName();
      $original_ids = [];
      $langcode = $this->getLangcode();
      $original = $entity->original;
      if ($original->hasTranslation($langcode)) {
        $original_items = $original->getTranslation($langcode)->{$field_name};
        foreach ($original_items as $item) {
          $file = $file_handler::urlToFile($item->target_id);
          if (!$file_handler->isRemote($file)) {
            $original_ids[] = $file->id();
          }
        }
      }

      // Decrement file usage by 1 for files that were removed from the field.
      $removed_ids = array_filter(array_diff($original_ids, $ids));
      $removed_files = \Drupal::entityManager()->getStorage('file')->loadMultiple($removed_ids);
      foreach ($removed_files as $file) {
        \Drupal::service('file.usage')->delete($file, 'file', $entity->getEntityTypeId(), $entity->id());
      }

      // Add new usage entries for newly added files.
      foreach ($files as $file) {
        if (!in_array($file->id(), $original_ids) && !$file_handler->isRemote($file)) {
          \Drupal::service('file.usage')->add($file, 'file', $entity->getEntityTypeId(), $entity->id());
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function referencedEntities() {
    /** @var \Drupal\file_url\FileUrlHandler $file_handler */
    $file_handler = \Drupal::service('file_url.handler');
    if (empty($this->list)) {
      return [];
    }

    // Collect the IDs of existing entities to load, and directly grab the
    // "autocreate" entities that are already populated in $item->entity.
    $target_entities = [];
    foreach ($this->list as $delta => $item) {
      if ($item->target_id !== NULL) {
        $file = $file_handler::urlToFile($item->target_id);
        $target_entities[$delta] = $file;
      }
      elseif ($item->hasNewEntity()) {
        $target_entities[$delta] = $item->entity;
      }
    }
    // Ensure the returned array is ordered by deltas.
    ksort($target_entities);

    return $target_entities;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    parent::delete();
    $entity = $this->getEntity();

    // If a translation is deleted only decrement the file usage by one. If the
    // default translation is deleted remove all file usages within this entity.
    $count = $entity->isDefaultTranslation() ? 0 : 1;
    foreach ($this->referencedEntities() as $file) {
      if (!FileUrlHandler::isRemote($file)) {
        \Drupal::service('file.usage')->delete($file, 'file', $entity->getEntityTypeId(), $entity->id(), $count);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteRevision() {
    parent::deleteRevision();
    $entity = $this->getEntity();

    // Decrement the file usage by 1.
    foreach ($this->referencedEntities() as $file) {
      if (!FileUrlHandler::isRemote($file)) {
        \Drupal::service('file.usage')->delete($file, 'file', $entity->getEntityTypeId(), $entity->id());
      }
    }
  }

}
