<?php

namespace Drupal\devdocs_export\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Devdocs export handler plugin manager.
 */
class DevdocsExportHandlerManager extends DefaultPluginManager {

  /**
   * Constructs a new DevdocsExportHandlerManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/DevdocsExportHandler', $namespaces, $module_handler, 'Drupal\devdocs_export\Plugin\DevdocsExportHandlerInterface', 'Drupal\devdocs_export\Annotation\DevdocsExportHandler');

    $this->alterInfo('devdocs_export_devdocs_export_handler_info');
    $this->setCacheBackend($cache_backend, 'devdocs_export_devdocs_export_handler_plugins');
  }

}
