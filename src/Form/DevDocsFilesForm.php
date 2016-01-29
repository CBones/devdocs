<?php
/**
 * @file
 * Contains \Drupal\devdocs\Form\DevDocsFilesForm
 */
namespace Drupal\devdocs\Form;

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
class DevDocsFilesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'devdocs_files_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $config_name = '') {
    if (!\Drupal::config('devdocs.settings')->get('path')) return $this->redirect('devdocs.settings.form');

    $directory = 'docs://';

    $form['tabs'] = array(
      '#type' => 'vertical_tabs',
      '#parents' => ['tabs'],
    );
    $files = file_scan_directory($directory, '/.*\.md$/');

    foreach ($files as $uri => $object) {
      $form['files']['file_'.$object->name] = array(
        '#type' => 'details',
        '#title' => $object->name,
        '#parents' => array('files', 'file_'.$object->name),
        '#group' => 'tabs',
      );
      $markdown = file_get_contents($uri);

      if (\Drupal::moduleHandler()->moduleExists('markdown')) {
        if (\Drupal::moduleHandler()->moduleExists('libraries')) {
          libraries_load('php-markdown', 'markdown-extra');
          $text = MarkdownExtra::defaultTransform($markdown);
          $output = new FilterProcessResult($text);
        }
        else {
          $output = '<pre>' . check_plain($markdown) . '</pre>';
        }
      }
      else {
        $output = check_plain($markdown);
      }

      $form['files']['file_'.$object->name]['htabs'] = array(
        '#type' => 'horizontal_tabs',
        '#parents' => ['files'],
      );

      $form['files']['file_'.$object->name]['htabs']['htabs_output'] = array(
        '#type' => 'details',
        '#title' => t('View'),
        '#group' => 'htabs',
        '#open' => FALSE
      );
      $form['files']['file_'.$object->name]['htabs']['htabs_output']['output'] = array(
        '#markup' => $output,
        '#group' => 'htabs',
      );

      $form['files']['file_'.$object->name]['htabs']['edit_'.$object->name] = array(
        '#type' => 'details',
        '#title' => t('Edit'),
        '#group' => 'htabs',
      );

      $form['files']['file_'.$object->name]['htabs']['edit_'.$object->name]['locked_'.$object->name] = array(
        '#type' => 'checkbox',
        '#title' => t('Locked'),
        '#default_value' => (strpos($markdown, 'devdocs:locked')) ? TRUE : FALSE,
      );

      $form['files']['file_'.$object->name]['htabs']['edit_'.$object->name]['id_'.$object->name] = array(
        '#type' => 'textarea',
        '#default_value' => $markdown,
        '#rows' => 10,
        '#group' => 'htabs',
      );
      if (strpos($markdown, 'devdocs:locked')) {
        $form['files']['file_'.$object->name]['htabs']['edit_'.$object->name]['#disabled'] = TRUE;
      }

      $form['files']['file_'.$object->name]['htabs']['edit_'.$object->name]['uri_'.$object->name] = array(
        '#type' => 'hidden',
        '#value' => $uri,
      );
      $form['files']['file_'.$object->name]['htabs']['edit_'.$object->name]['generate_'.$object->name] = array(
        '#type' => 'select',
        '#title' => 'Generate content',
        '#options' => array(
          '_none' => '-- Select --',
          'views' => 'Output Views information',
          // 'features' => 'Output Features information',
          'content_types' => 'Output Content Types information',
        ),
        '#group' => 'htabs',
      );
      $form['files']['file_'.$object->name]['htabs']['edit_'.$object->name]['delete_'.$object->name] = array(
        '#type' => 'checkbox',
        '#title' => t('Delete'),
      );

    }

    $form['new'] = array(
      '#type' => 'textfield',
      '#title' => t('New file'),
      '#description' => 'Filename without extension',
      '#default_value' => ''
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
      '#attributes' => array('class' => array('button--primary')),
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
      foreach ($values as $key => $value) {

        if ($key == 'new' && !empty($value)) {
          $uri = 'docs://' . $value . '.md';
          file_unmanaged_save_data('', $uri, FILE_EXISTS_RENAME);
        }
        elseif (substr($key, 0, 3) == 'id_') {
          $file_name = substr($key, 3);
          $markdown = '';
          if (isset($values['locked_' . $file_name]) && $values['locked_' . $file_name] == '1') {
            if (!strpos($value, 'devdocs:locked')) {
              $markdown = '<!---devdocs:locked-->' . PHP_EOL;
            }
            $markdown .= $value;
          }
          elseif (isset($values['locked_' . $file_name]) && $values['locked_' . $file_name] == '0') {
            $markdown .= $value;
            if (strpos($value, 'devdocs:locked')) {
              $markdown = str_replace('<!---devdocs:locked-->' . PHP_EOL, '', $value);
            }
          }
          $uri = $values['uri_' . $file_name];
          file_unmanaged_save_data($markdown, $uri, FILE_EXISTS_REPLACE);
        }
        elseif (substr($key, 0, 7) == 'delete_' && $value == '1') {
          $file_name = substr($key, 7);
          $uri = $values['uri_' . $file_name];
          file_unmanaged_delete($uri);
        }
        elseif (substr($key, 0, 9) == 'generate_') {
          $file_name = substr($key, 9);
          switch ($value) {
            case 'views':
              $markdown = devdocs_views_info_output();
              break;
            case 'features':
              $markdown = devdocs_features_info_output();
              break;
            case 'content_types':
              $markdown = devdocs_content_types_info_output();
              break;
          }
          $uri = $values['uri_' . $file_name];
          file_unmanaged_save_data($markdown, $uri, FILE_EXISTS_REPLACE);
        }

      }
    }
    catch (\Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
    }
  }

}
