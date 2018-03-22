<?php

namespace Drupal\devdocs_export\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Devdocs export handler item annotation object.
 *
 * @see \Drupal\devdocs_export\Plugin\DevdocsExportHandlerManager
 * @see plugin_api
 *
 * @Annotation
 */
class DevdocsExportHandler extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
