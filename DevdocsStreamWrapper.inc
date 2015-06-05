<?php

/**
 * Private dynamic (private_dynamic://) stream wrapper class.
 *
 * Provides support for storing privately accessible dyncamic files with the
 * Drupal file interface.
 *
 * Extends DrupalLocalStreamWrapper.
 */
class DevdocsStreamWrapper extends DrupalLocalStreamWrapper {
  /**
   * Implements abstract public function getDirectoryPath()
   */
  public function getDirectoryPath() {
    return variable_get('devdocs_path', '');
  }

  /**
   * Overrides getExternalUrl().
   *
   * Return the HTML URI of a private dynamic file.
   */
  function getExternalUrl() {
    $path = str_replace('\\', '/', $this->getTarget());
    $path = substr($path, 0, -5);
    return url('system/docs/' . $path, array('absolute' => TRUE));
  }
}
