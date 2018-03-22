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
   * @param array $options
   *
   * @return mixed
   */
  public function handle(array $documents, array $options);
}
