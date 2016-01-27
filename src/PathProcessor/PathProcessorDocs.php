<?php

/**
 * @file
 * Contains \Drupal\devdocs\PathProcessor\PathProcessorDocs.
 */

namespace Drupal\devdocs\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a path processor to rewrite file URLs.
 *
 * As the route system does not allow arbitrary amount of parameters convert
 * the file path to a query parameter on the request. This is similar to what
 * Core does for the system/files/* URLs.
 */
class PathProcessorDocs implements InboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    if (strpos($path, '/system/docs/') === 0 && !$request->query->has('file')) {
      $file_path = preg_replace('|^\/system\/docs\/|', '', $path);
      $request->query->set('file', $file_path);
      // We return the route we want to match.
      return '/system/docs';
    }
    return $path;
  }

}
