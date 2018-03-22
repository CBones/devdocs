<?php

namespace Drupal\devdocs_export\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Devdocs export handler plugins.
 */
interface DevdocsExportHandlerInterface extends PluginInspectionInterface {

  /**
   * Handles document export.
   *
   * @param array $documents
   *   Array of documents.
   * @param array $options
   *   Array of options.
   *
   * @return mixed
   *   Returns result of handling process.
   */
  public function handle(array $documents, array $options);

  /**
   * Builds plugin option form.
   *
   * @return array
   *   Form array.
   */
  public function buildOptionsForm();

}
