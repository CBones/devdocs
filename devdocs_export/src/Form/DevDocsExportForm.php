<?php
/**
 * @file
 * Contains \Drupal\devdocs_export\Form\DevDocsExportForm
 */
namespace Drupal\devdocs_export\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\markdown\Plugin\Filter\Markdown;
use Drupal\Component\Utility\Xss;
use Michelf\MarkdownExtra;
use Drupal\filter\FilterProcessResult;

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
    if (!\Drupal::config('devdocs.settings')->get('path')) return $this->redirect('devdocs.settings.form');

    $directory = 'docs://';
    $files = file_scan_directory($directory, '/.*\.md$/');

    $form['header'] = array(
      '#type' => 'checkbox',
      '#title' => t('Header'),
      '#description' => t('Add %docpath as document header', array('%docpath' => $directory.'export/assets/header.md')),
    );

    $form['exporttable'] = array(
      '#type' => 'table',
      // '#header' => array(t('File'), t('Weight')),
      '#empty' => 'Empty text',
      // TableSelect: Injects a first column containing the selection widget into
      // each table row.
      // Note that you also need to set #tableselect on each form submit button
      // that relies on non-empty selection values (see below).
      // '#tableselect' => TRUE,
      // TableDrag: Each array value is a list of callback arguments for
      // drupal_add_tabledrag(). The #id of the table is automatically prepended;
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
      // TableDrag: Sort the table row according to its existing/configured weight.
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
      '#description' => t('Add %docpath as document footer', array('%docpath' => $directory.'export/assets/footer.md')),
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
    try {
      // Get array of exportable objects
      $exportables = array();

      foreach ($values['exporttable'] as $entry) {
        if ($entry['export'] == 1) {
          $exportables[$entry['weight']] = $entry['uri'];
        }
      }
      ksort($exportables);

      // Pack PDF
      devdocs_export_render_pdf($exportables, array(
        'header' => $values['header'],
        'footer' => $values['footer'],
      ));
    }
    catch (\Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
    }
  }

}
