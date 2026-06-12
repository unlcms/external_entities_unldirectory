<?php

namespace Drupal\external_entities_unldirectory\Entity;

use Drupal\external_entities\ExternalEntityStorage;

class UnlDirectoryExternalEntityStorage extends ExternalEntityStorage {

  /**
   * {@inheritdoc}
   */
  protected function setPersistentCache($entities) {
    if (!$this->entityType->isPersistentlyCacheable()) {
      return;
    }

    foreach ($entities as $id => $entity) {
      // If fetching from directory.unl.edu failed, skip caching.
      $data = $entity->toRawData();
      if (empty($data['uid'])) {
        unset($entities[$id]);
      }
    }

    if (!empty($entities)) {
      // If there are any entries left, go to the parent and cache as normal.
      parent::setPersistentCache($entities);
    }
  }

}
