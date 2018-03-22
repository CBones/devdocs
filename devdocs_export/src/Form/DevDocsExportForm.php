<?php

namespace Drupal\devdocs_export\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\devdocs_export\Plugin\DevdocsExportHandlerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure devdocs settings for this site.
 */
class DevDocsExportForm extends FormBase {

  /**
   * @var MessengerInterface
   */
  public $messenger;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $settings;

  /**
   * @var DevdocsExportHandlerManager
   */
  private $exportHandlerManager;

  /**
   * Class constructor.
   */
  public function __construct(MessengerInterface $messenger, ConfigFactoryInterface $configFactory, DevdocsExportHandlerManager $devdocsExportHandlerManager) {
    $this->messenger = $messenger;
    $this->settings = $configFactory->get('devdocs.settings');
    $this->exportHandlerManager = $devdocsExportHandlerManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('config.factory'),
      $container->get('plugin.manager.devdocs_export_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'devdocs_export_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $config_name = '') {
    if (!$this->settings->get('path')) {
      return $this->redirect('devdocs.settings.form');
    }

    $directory = 'docs://';
    $files = file_scan_directory($directory, '/.*\.md$/');

    $exportHandlers = $this->exportHandlerManager->getDefinitions();
    $handler_options = [];
    foreach ($exportHandlers as $handler) {
      $handler_options[$handler['id']] = $handler['label'];
    }

    $form['#prefix'] = '<div id="export_handle_form_wrapper">';
    $form['#suffix'] = '</div>';

    $form['export_handler'] = [
      '#type' => 'select',
      '#title' => t('Export handler'),
      '#options' => $handler_options,
      '#required' => TRUE,
//      '#ajax' => [
//        'event' => 'change',
//        'wrapper' => 'export_handle_form_wrapper',
//        'callback' => '::ajaxCallback',
//      ],
    ];

    $form['header'] = array(
      '#type' => 'checkbox',
      '#title' => t('Header'),
      '#description' => t('Add %docpath as document header', [
        '%docpath' => $directory . 'export/assets/header.md',
      ]),
    );

    $form['exporttable'] = array(
      '#type' => 'table',
      '#empty' => 'Empty text',
      // TableSelect: Injects a first column containing the selection widget
      // into each table row.
      // Note that you also need to set #tableselect on each form submit button
      // that relies on non-empty selection values (see below).
      // '#tableselect' => TRUE,
      // TableDrag: Each array value is a list of callback arguments for
      // drupal_add_tabledrag(). The #id of the table is automatically
      // prepended;
      // if there is none, an HTML ID is auto-generated.
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'exporttable-order-weight',
        ),
      ),
    );

    $i = 0;

    foreach ($files as $uri => $object) {
      // TableDrag: Mark the table row as draggable.
      $form['exporttable'][$object->name]['#attributes']['class'][] = 'draggable';
      // TableDrag: Sort the table row according to its configured weight.
      $form['exporttable'][$object->name]['#weight'] = $i;

      $form['exporttable'][$object->name]['export'] = array(
        '#type' => 'checkbox',
        '#title' => str_replace($directory, '', $object->uri),
      );

      $form['exporttable'][$object->name]['uri'] = array(
        '#type' => 'hidden',
        '#value' => $object->uri,
      );

      $form['exporttable'][$object->name]['weight'] = array(
        '#type' => 'weight',
        '#default_value' => $i,
        '#title_display' => 'invisible',
        '#attributes' => array('class' => array('exporttable-order-weight')),
      );

      $i++;
    }

    $form['footer'] = array(
      '#type' => 'checkbox',
      '#title' => t('Footer'),
      '#description' => t('Add %docpath as document footer', [
        '%docpath' => $directory . 'export/assets/footer.md',
      ]),
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Export'),
      '#tableselect' => TRUE,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();


    $exportHandler = $this->exportHandlerManager->createInstance($values['export_handler']);

    try {
      // Get array of exportable objects.
      $exportables = array();

      foreach ($values['exporttable'] as $entry) {
        if ($entry['export'] == 1) {
          $exportables[$entry['weight']] = $entry['uri'];
        }
      }
      ksort($exportables);
      // Pack PDF.
      $exportHandler->handle($exportables, array(
        'header' => $values['header'],
        'footer' => $values['footer'],
      ));
    }
    catch (\Exception $e) {
      $this->messenger->addMessage($e->getMessage(), 'error');
    }
  }

}
