<?php

namespace Drupal\devdocs_export\Plugin\DevdocsExportHandler;

use Symfony\Component\HttpFoundation\Response;
use Dompdf\Dompdf;
use Drupal\devdocs\StreamWrapper\DocsStream;
use Drupal\devdocs_export\Plugin\DevdocsExportHandlerBase;
use Drupal\filter\FilterProcessResult;
use Michelf\MarkdownExtra;

/**
 * PDF handler for Devdocs export.
 *
 * @DevdocsExportHandler(
 *   id = "open_pdf",
 *   label = @Translation("Open in PDF format"),
 * )
 */
class OpenPDF extends DevdocsExportHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function handle(array $documents, array $options) {

    $options = [
      'format' => 'pdf',
      'header' => FALSE,
      'footer' => FALSE,
      'filename' => 'Documentation_export_' . date('Ymd'),
    ];

    $options = array_merge($options, $options);

    // $markdownParser = new \Michelf\MarkdownExtra();
    $dd_base_path = DocsStream::basePath() . '/export/';

    $header_html = $footer_html = $css = '';
    $pages = [];

    // CSS.
    if (file_exists($dd_base_path . 'assets/inline_style.css')) {
      $css = '<link type="text/css" href="assets/inline_style.css" rel="stylesheet" />';
    }

    // header.
    if ($options['header'] && file_exists($dd_base_path . 'assets/header.md')) {
      $header_markdown = file_get_contents($dd_base_path . 'assets/header.md');
      $header_markdown = MarkdownExtra::defaultTransform($header_markdown);
      $header_html = new FilterProcessResult($header_markdown);
    }

    // Footer.
    if ($options['footer'] && file_exists($dd_base_path . 'assets/footer.md')) {
      $footer_markdown = file_get_contents($dd_base_path . 'assets/footer.md');
      $footer_markdown = MarkdownExtra::defaultTransform($footer_markdown);
      $footer_html = new FilterProcessResult($footer_markdown);
    }

    // Generate pages.
    foreach ($documents as $document) {
      $markdown = file_get_contents($document);
      $markdown = MarkdownExtra::defaultTransform($markdown);
      $pages[] = new FilterProcessResult($markdown);
    }

    $html = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>';
    $html .= $css;
    $html .= '</head><body>';
    $html .= $header_html;
    $html .= $footer_html;
    $html .= implode('<div style="page-break-before: always;"></div>', $pages);
    $html .= '</body></html>';

    $dompdf = new Dompdf();
    $dompdf->setBasePath($dd_base_path);
    $dompdf->loadHtml($html);
    $dompdf->setPaper("A4");
    $dompdf->render();
    $content = $dompdf->output(['compress' => 0]);
    $filename = 'pdf-me.pdf';
    $response = new Response($content, 200, [
      'Content-Type' => 'application/pdf',
      'Cache-Control' => 'private',
      'Pragma' => 'no-cache',
      'Expires' => '0',
      'Content-Length' => strlen($content),
      'Content-Disposition' => 'inline; filename=' . rawurlencode($filename) . '; filename*=UTF-8\'\'' . rawurlencode($filename) . '\'',
    ]);
    $response->send();
  }

}
