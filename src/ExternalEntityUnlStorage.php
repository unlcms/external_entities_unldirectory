<?php

namespace Drupal\external_entities_unldirectory;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\external_entities\ResponseDecoderFactoryInterface;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Entity\ContentEntityStorageBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\external_entities\ExternalEntityStorage;

/**
 * Defines the controller class for nodes.
 *
 * This extends the base storage class, adding required special handling for
 * node entities.
 */
class ExternalEntityUnlStorage extends ExternalEntityStorage {

  /**
   * {@inheritdoc}
   */
  protected function doLoadMultiple(array $ids = NULL) {
    $entities = array();

    foreach ($ids as $id) {
      if (strpos($id, '-')) {
        list($bundle, $external_id) = explode('-', $id);
        if ($external_id) {
          $entities[$id] = $this->create([$this->entityType->getKey('bundle') => $bundle])->mapObject($this->getStorageClient($bundle)->load($external_id))->enforceIsNew(FALSE);
        }
      }
    }
    return $entities;
  }
}
