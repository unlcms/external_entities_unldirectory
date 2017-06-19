<?php

/**
 * @file
 * Contains \Drupal\external_entities_unldirectory\Plugin\ExternalEntityStorageClient\UnlDirectoryClient.
 */

namespace Drupal\external_entities_unldirectory\Plugin\ExternalEntityStorageClient;

use Drupal\external_entities\ExternalEntityStorageClientBase;

/**
 * UNL Directory implementation of an external entity storage client.
 *
 * @ExternalEntityStorageClient(
 *   id = "unldirectory_client",
 *   name = "UNL Directory"
 * )
 */
class UnlDirectoryClient extends ExternalEntityStorageClientBase {

  /**
   * {@inheritdoc}
   */
  public function delete(\Drupal\external_entities\ExternalEntityInterface $entity) {
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    $response = $this->httpClient->get(
      $this->configuration['endpoint'],
      [
        'query' => ['uid'=>$id, 'format'=>'json'],
        'headers' => $this->getHttpHeaders()
      ]
    );
    $result = (object) $this->decoder->getDecoder($this->configuration['format'])->decode($response->getBody());
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function save(\Drupal\external_entities\ExternalEntityInterface $entity) {
  }

  /**
   * {@inheritdoc}
   */
  public function query(array $parameters) {
    if (isset($parameters['title'])) {
      // New search.
      $q = $parameters['title'];
    }
    elseif ($parameters['id']) {
      // Existing populated field.
      $q = explode('-', $parameters['id'])[1];
    }
    else {
      throw new QueryException("Pulling UNL Directory data failed.");
    }

    $response = $this->httpClient->get(
      $this->configuration['endpoint'],
      [
        'query' => ['q'=>$q, 'format'=>'json'],
        'headers' => $this->getHttpHeaders()
      ]
    );
    $results = $this->decoder->getDecoder($this->configuration['format'])->decode($response->getBody());
    foreach ($results as &$result) {
      // Cleanup the result so that 'uid' is available at $result['uid'].
      foreach (explode(',', $result['dn']) as $piece) {
        $pieces = explode('=', $piece);
        $result[$pieces[0]] = $pieces[1];
      }
      $result = ((object) $result);
    }
    // Only return a few items in order to limit the requests in load().
    return array_slice($results, 0, 5);
  }

}
