<?php

/**
 * @file
 * Contains \Drupal\devdocs_export\Controller\DocsExport.
 */

namespace Drupal\devdocs_export\Controller;

use Drupal\devdocs\StreamWrapper\DocsStream;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\File\FileSystem;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller routines for page example routes.
 */
class DocsExport extends ControllerBase {

  /**
   * Constructs a page with descriptive content.
   *
   * Our router maps this method to the path 'examples/page_example'.
   */
  public function pdf() {
    $query = UrlHelper::filterQueryParameters(\Drupal::request()->query->all());
    $scheme = 'docs';
    $uri = $scheme . '://' . $query['file'];
    if (file_stream_wrapper_valid_scheme($scheme) && file_exists($uri)) {
      devdocs_export_render_pdf(array($uri), array('filename' => basename($uri). '_'.date('Ymd')));
    }
    else {
      throw new NotFoundHttpException();
    }
    exit();
  }
}
