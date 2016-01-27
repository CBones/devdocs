<?php

/**
 * @file
 * Contains \Drupal\devdocs\Controller\DocsOutput.
 */

namespace Drupal\devdocs\Controller;

use Drupal\devdocs\StreamWrapper\DocsStream;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\File\FileSystem;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;

/**
 * Controller routines for page example routes.
 */
class DocsOutput extends ControllerBase {

  /**
   * Constructs a page with descriptive content.
   *
   * Our router maps this method to the path 'examples/page_example'.
   */
  public function docs() {
    if (!\Drupal::config('devdocs.settings')->get('path')) return $this->redirect('devdocs.settings.form');

    $output = '';

    $uri = 'docs://index.html';
    $url = file_create_url($uri);

    if (file_exists($uri)) {
      $url_new = Url::fromUri($url);
      $link_options = array(
        'attributes' => array(
          'target' => array(
            '_blank'
          ),
        ),
      );
      $url_new->setOptions($link_options);
      $link = \Drupal::l(t('Open in new window'), $url_new);
      $output .= $link;
      $output .= '<iframe src="' . $url . '" width="100%" height="600px">' . $url . '</iframe>';
    }
    else {
      drupal_set_message(t('There is no index.html file in Documentation directory, recreating sample site documentation.'));
      // devdocs_recreate();
      // reload page
      return $this->redirect('devdocs.settings.form');
    }

    $build = array(
      '#markup' => $output,
      '#allowed_tags' => ['iframe', 'a'],
    );
    return $build;
  }

}
