<?php

/**
 * @file
 * Contains \Drupal\external_entities_unldirectory\Plugin\ExternalEntityStorageClient\UnlDirectoryClient.
 */

namespace Drupal\external_entities_unldirectory\Plugin\ExternalEntities\StorageClient;

use Drupal\external_entities\Plugin\ExternalEntities\StorageClient\Rest;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

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

  protected $blank_result = [
    'dn' => '',
    'cn' => [],
    'eduPersonAffiliation' => [],
    'eduPersonNickname' => NULL,
    'eduPersonPrimaryAffiliation' => [],
    'eduPersonPrincipalName' => [],
    'givenName' => [],
    'displayName' => [],
    'sn' => [],
    'uid' => '',
    'unlSISClassLevel' => NULL,
    'unlSISCollege' => NULL,
    'unlSISMajor' => NULL,
    'unlSISMinor' => NULL,
    'imageURL' => '',
    'mail' => [],
    'telephoneNumber' => [],
    'postalAddress' => [],
    'unlDirectoryAddress' => [],
    'title' => [],
    'unlHROrgUnitNumber' => [],
    'unlHRPrimaryDepartment' => [],
  ];

  /**
   * {@inheritdoc}
   */
  public function delete(\Drupal\external_entities\ExternalEntityInterface $entity) {
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    try {
      $response = $this->httpClient->get(
        $this->configuration['endpoint'],
        [
          'query' => ['uid' => $id, 'format' => 'json'],
          'headers' => $this->getHttpHeaders(),
          'timeout' => 10,
          'connect_timeout' => 5,
        ]
      );

      $result = $this
        ->getResponseDecoderFactory()
        ->getDecoder($this->configuration['response_format'])
        ->decode($response->getBody());

      return $result;
    }
    catch (ConnectException $e) {
      // Network-level failure (offline, DNS, firewall, etc.)
      \Drupal::logger('external_entities_unldirectory')
        ->warning('External endpoint offline: @url - @message', [
          '@url' => $this->configuration['endpoint'],
          '@message' => $e->getMessage(),
        ]);
      return $this->blank_result;
    }
    catch (RequestException $e) {
      // Other HTTP-related errors (5xx, redirects, etc.)
      \Drupal::logger('external_entities_unldirectory')
        ->warning('Request failed for endpoint: @url - @message', [
          '@url' => $this->configuration['endpoint'],
          '@message' => $e->getMessage(),
        ]);
      return $this->blank_result;
    }
    catch (\Exception $e) {
      // Catch-all for anything unexpected.
      \Drupal::logger('external_entities_unldirectory')
        ->error('Unexpected error in load(): @message', [
          '@message' => $e->getMessage(),
        ]);
      return $this->blank_result;
    }
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

    if ($results) {
      foreach ($results as &$result) {
        // Cleanup the result so that 'uid' is available at $result['uid'].
        foreach (explode(',', $result['dn']) as $piece) {
          $pieces = explode('=', $piece);
          $pieces[0] = ($pieces[0] == 'CN') ? $pieces[0] = 'uid' : $pieces[0];
          $result[$pieces[1]] = [$pieces[0] => $pieces[1]];
        }
      }

      // Only return a few items in order to limit the requests in load().
      return array_slice($results, 0, 8);
    }
    else {
      return [];
    }
  }

}
