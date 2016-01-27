<?php

/**
 * @file
 * Contains \Drupal\devdocs\StreamWrapper\DocsStream.
 */

namespace Drupal\devdocs\StreamWrapper;

// These classes are used to implement a stream wrapper class.
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\LocalStream;

/**
 * Drupal documentation (docs://) stream wrapper class.
 *
 * Provides support for storing privately accessible documentation files with the Drupal file
 * interface.
 */
class DocsStream extends LocalStream {

  use UrlGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public static function getType() {
    return StreamWrapperInterface::LOCAL_NORMAL;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return t('Documentation files');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Documentation local files served by Drupal.');
  }

  /**
   * {@inheritdoc}
   */
  public function getDirectoryPath() {
    return static::basePath();
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl() {
    $path = str_replace('\\', '/', $this->getTarget());
    return $this->url('devdocs.docs_file_download', ['filepath' => $path], ['absolute' => TRUE]);
  }


  /**
   * Returns the base path for private://.
   *
   * Note that this static method is used by \Drupal\system\Form\FileSystemForm
   * so you should alter that form or substitute a different form if you change
   * the class providing the stream_wrapper.private service.
   *
   * @return string
   *   The base path for private://.
   */
  public static function basePath() {
    // return Settings::get('file_docs_path'); // Use in settings.php
    return \Drupal::config('devdocs.settings')->get('path');
  }


}
