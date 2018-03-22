<?php

namespace Drupal\devdocs_export\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\File\FileSystemInterface;
use Drupal\devdocs_export\Plugin\DevdocsExportHandlerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Docs export controller.
 *
 * @package Drupal\devdocs_export\Controller
 */
class DocsExport extends ControllerBase {

  /**
   * Filesystem object.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  private $fileSystem;

  /**
   * Devdocs pdf handler plugin.
   *
   * @var \Drupal\devdocs_export\Plugin\DevdocsExportHandler\OpenPDF
   */
  private $PDFExportHandler;

  /**
   * DocsExport constructor.
   *
   * @param \Drupal\devdocs_export\Plugin\DevdocsExportHandlerManager $devdocsExportHandlerManager
   *   Devdocs Handler Manager.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   Filesystem.
   */
  public function __construct(DevdocsExportHandlerManager $devdocsExportHandlerManager, FileSystemInterface $fileSystem) {
    $this->PDFExportHandler = $devdocsExportHandlerManager->createInstance('open_pdf');
    $this->fileSystem = $fileSystem;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.devdocs_export_handler'),
      $container->get('file_system')
    );
  }

  /**
   * Constructs a page with descriptive content.
   *
   * Our router maps this method to the path 'examples/page_example'.
   */
  public function pdf() {
    $query = UrlHelper::filterQueryParameters(\Drupal::request()->query->all());
    $scheme = 'docs';
    $uri = $scheme . '://' . $query['file'];
    if ($this->fileSystem->validScheme($scheme) && file_exists($uri)) {
      $this->PDFExportHandler->handle([$uri], ['filename' => basename($uri) . '_' . date('Ymd')]);
    }
    else {
      throw new NotFoundHttpException();
    }
    exit();
  }

}
