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
      $parameters = ['q'=>$q, 'format'=>'json'];
    }
    elseif ($parameters['id']) {
      // Existing populated field.
      $uid = explode('-', $parameters['id'])[1];
      $parameters = ['uid'=>$uid, 'format'=>'json'];
    }
    else {
      throw new QueryException("Autocomplete UNL Directory reference failed.");
    }

    $response = $this->httpClient->get(
      $this->configuration['endpoint'],
      [
        'query' => $parameters,
        'headers' => $this->getHttpHeaders()
      ]
    );
    $results = $this->decoder->getDecoder($this->configuration['format'])->decode($response->getBody());

    // Pretend that the specific uid record is actually a search result.
    if (isset($uid)) {
      $results_temp = $results;
      unset($results);
      $results[0] = $results_temp;
    }

    foreach ($results as &$result) {
      // Cleanup the result so that 'uid' is available at $result['uid'].
      foreach (explode(',', $result['dn']) as $piece) {
        $pieces = explode('=', $piece);
        $result[$pieces[0]] = $pieces[1];
      }
      $result = ((object) $result);
    }
    // Only return a few items in order to limit the requests in load().
    return array_slice($results, 0, 6);
  }

}
