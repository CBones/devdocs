<?php

namespace Drupal\devdocs_export\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure devdocs settings for this site.
 */
class DevDocsExportForm extends FormBase {

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
    if (!\Drupal::config('devdocs.settings')->get('path')) {
      return $this->redirect('devdocs.settings.form');
    }

    $directory = 'docs://';
    $files = file_scan_directory($directory, '/.*\.md$/');

    $exportHandlers = \Drupal::service('plugin.manager.devdocs_export_handler')->getDefinitions();
    $handler_options = [];
    foreach ($exportHandlers as $handler) {
      $handler_options[$handler['id']] = $handler['label'];
    }
    $plugin = $form_state->getValue('export_handler') ? $form_state->getValue('export_handler') : 'open_pdf';

    $form['#tree'] = TRUE;
    $form['#prefix'] = '<div id="export_handle_form_wrapper">';
    $form['#suffix'] = '</div>';

    $form['export_handler'] = [
      '#type' => 'select',
      '#title' => t('Export handler'),
      '#options' => $handler_options,
      '#required' => TRUE,
      '#default_value' => $plugin,
      '#ajax' => [
        'event' => 'change',
        'wrapper' => 'export_handle_form_wrapper',
        'callback' => [$this, 'ajaxCallback'],
      ],
    ];

    $handler_options = \Drupal::service('plugin.manager.devdocs_export_handler')
      ->createInstance($plugin)
      ->buildOptionsForm();
    if (!empty($handler_options)) {
      $form['export_handler_options'] = [
        '#type' => 'fieldset',
        '#title' => t('Handler options'),
      ];

      $form['export_handler_options'] += $handler_options;
    }

    $form['header'] = [
      '#type' => 'checkbox',
      '#title' => t('Header'),
      '#description' => t('Add %docpath as document header', [
        '%docpath' => $directory . 'export/assets/header.md',
      ]),
    ];

    $form['exporttable'] = [
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
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'exporttable-order-weight',
        ],
      ],
    ];

    $i = 0;

    foreach ($files as $uri => $object) {
      // TableDrag: Mark the table row as draggable.
      $form['exporttable'][$object->name]['#attributes']['class'][] = 'draggable';
      // TableDrag: Sort the table row according to its configured weight.
      $form['exporttable'][$object->name]['#weight'] = $i;

      $form['exporttable'][$object->name]['export'] = [
        '#type' => 'checkbox',
        '#title' => str_replace($directory, '', $object->uri),
      ];

      $form['exporttable'][$object->name]['uri'] = [
        '#type' => 'hidden',
        '#value' => $object->uri,
      ];

      $form['exporttable'][$object->name]['weight'] = [
        '#type' => 'weight',
        '#default_value' => $i,
        '#title_display' => 'invisible',
        '#attributes' => ['class' => ['exporttable-order-weight']],
      ];

      $i++;
    }

    $form['footer'] = [
      '#type' => 'checkbox',
      '#title' => t('Footer'),
      '#description' => t('Add %docpath as document footer', [
        '%docpath' => $directory . 'export/assets/footer.md',
      ]),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Export'),
      '#tableselect' => TRUE,
    ];

    return $form;
  }

  /**
   * Ajax callback for options form.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\core\form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return array
   *   Returns form array.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
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

    $exportHandler = \Drupal::service('plugin.manager.devdocs_export_handler')
      ->createInstance($values['export_handler'], ['export_options' => isset($values['export_handler_options']) ? $values['export_handler_options'] : []]);
    try {
      // Get array of exportable objects.
      $exportables = [];

      foreach ($values['exporttable'] as $entry) {
        if ($entry['export'] == 1) {
          $exportables[$entry['weight']] = $entry['uri'];
        }
      }
      ksort($exportables);
      // Pack PDF.
      $exportHandler->handle($exportables, [
        'header' => $values['header'],
        'footer' => $values['footer'],
      ]);
    }
    catch (\Exception $e) {
      \Drupal::messenger()->addMessage($e->getMessage(), 'error');
    }
  }

}
