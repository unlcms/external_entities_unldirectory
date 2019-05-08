<?php

/**
 * @file
 * Contains \Drupal\external_entities_unldirectory\Plugin\ExternalEntityStorageClient\UnlDirectoryClient.
 */

namespace Drupal\external_entities_unldirectory\Plugin\ExternalEntities\StorageClient;

use Drupal\external_entities\Plugin\ExternalEntities\StorageClient\Rest;

/**
 * UNL Directory implementation of an external entity storage client.
 *
 * @ExternalEntityStorageClient(
 *   id = "unldirectory",
 *   label = @Translation("UNL Directory"),
 *   description = @Translation("Retrieves external entities from directory.unl.edu.")
 * )
 */
class UnlDirectory extends Rest {

  /**
   * {@inheritdoc}
   */
  public function delete(\Drupal\external_entities\ExternalEntityInterface $entity) {
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    $id = str_replace('_', '-', $id);
    $response = $this->httpClient->get(
      $this->configuration['endpoint'],
      [
        'query' => ['uid' => $id, 'format' => 'json'],
        'headers' => $this->getHttpHeaders()
      ]
    );

    $result = $this
      ->getResponseDecoderFactory()
      ->getDecoder($this->configuration['response_format'])
      ->decode($response->getBody());

    $result['uid'] = str_replace('-', '_', $result['uid']);

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
  public function query(array $parameters = [], array $sorts = [], $start = NULL, $length = NULL) {
    if (isset($parameters[0]) && $parameters[0]['field'] == 'title') {
      // New search.
      $q = $parameters[0]['value'];
      $parameters = ['q' => $q, 'format' => 'json'];
    }
    elseif (isset($parameters[0]) && $parameters[0]['field'] == 'id') {
      // Existing populated field.
      $uid = $parameters[0]['value'][0];
      if (strpos($uid, '-') !== false) {
        $uid = explode('-', $parameters[0]['value'][0])[1];
        $uid = str_replace('_', '-', $uid);
      }
      $parameters = ['uid' => $uid, 'format' => 'json'];
    }
    else {
      return [];
    }

    $response = $this->httpClient->get(
      $this->configuration['endpoint'],
      [
        'headers' => $this->getHttpHeaders(),
        'query' => $parameters + $this->configuration['parameters']['list'],
      ]
    );

    $results = $this
      ->getResponseDecoderFactory()
      ->getDecoder($this->configuration['response_format'])
      ->decode($response->getBody());

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
        // Need to substitute hyphen in old student IDs like s-jdoe2.
        if ($pieces[0] == 'uid' || $pieces[0] == 'CN') {
          $pieces[0] = 'uid';
          $pieces[1] = str_replace('-', '_', $pieces[1]);
        }
        $result[$pieces[1]] = [$pieces[0] => $pieces[1]];
      }
    }

    // Only return a few items in order to limit the requests in load().
    return array_slice($results, 0, 8);
  }

}
