<?php

namespace Drupal\devdocs\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Michelf\MarkdownExtra;
use Drupal\filter\FilterProcessResult;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure devdocs settings for this site.
 */
class DevDocsFilesForm extends FormBase {

  /**
   * Configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  public $configFactory;

  /**
   * Devdocs logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  public $loggerChannel;

  /**
   * DevDocsFilesForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Configuration factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   Devdocs logger channel.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerInterface $logger) {
    $this->configFactory = $config_factory;
    $this->loggerChannel = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('logger.factory')->get('devdocs')
    );
  }

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
    if (!$this->configFactory->get('devdocs.settings')->get('path')) {
      return $this->redirect('devdocs.settings.form');
    }

    $directory = 'docs://';

    $form['tabs'] = [
      '#type' => 'vertical_tabs',
      '#parents' => ['tabs'],
    ];
    $files = file_scan_directory($directory, '/.*\.md$/');

    foreach ($files as $uri => $object) {
      $form['files']['file_' . $object->name] = [
        '#type' => 'details',
        '#title' => $object->name,
        '#parents' => ['files', 'file_' . $object->name],
        '#group' => 'tabs',
      ];
      $markdown = file_get_contents($uri);

      try {
        $output = new FilterProcessResult(MarkdownExtra::defaultTransform($markdown));
      }
      catch (\Exception $exception) {
        $this->loggerChannel->warning($exception->getMessage());
        $output = Html::escape($markdown);
      }

      $form['files']['file_' . $object->name]['htabs'] = [
        '#type' => 'horizontal_tabs',
        '#parents' => ['files'],
      ];

      $form['files']['file_' . $object->name]['htabs']['htabs_output'] = [
        '#type' => 'details',
        '#title' => t('View'),
        '#group' => 'htabs',
        '#open' => FALSE,
      ];
      $form['files']['file_' . $object->name]['htabs']['htabs_output']['output'] = [
        '#markup' => $output,
        '#group' => 'htabs',
      ];

      $form['files']['file_' . $object->name]['htabs']['edit_' . $object->name] = [
        '#type' => 'details',
        '#title' => t('Edit'),
        '#group' => 'htabs',
      ];

      $form['files']['file_' . $object->name]['htabs']['edit_' . $object->name]['locked_' . $object->name] = [
        '#type' => 'checkbox',
        '#title' => t('Locked'),
        '#default_value' => (strpos($markdown, 'devdocs:locked')) ? TRUE : FALSE,
      ];

      $form['files']['file_' . $object->name]['htabs']['edit_' . $object->name]['id_' . $object->name] = [
        '#type' => 'textarea',
        '#default_value' => $markdown,
        '#rows' => 10,
        '#group' => 'htabs',
      ];
      if (strpos($markdown, 'devdocs:locked')) {
        $form['files']['file_' . $object->name]['htabs']['edit_' . $object->name]['#disabled'] = TRUE;
      }

      $form['files']['file_' . $object->name]['htabs']['edit_' . $object->name]['uri_' . $object->name] = [
        '#type' => 'hidden',
        '#value' => $uri,
      ];
      $form['files']['file_' . $object->name]['htabs']['edit_' . $object->name]['generate_' . $object->name] = [
        '#type' => 'select',
        '#title' => 'Generate content',
        '#options' => [
          '_none' => '-- Select --',
          'views' => 'Output Views information',
          // 'features' => 'Output Features information',.
          'content_types' => 'Output Content Types information',
        ],
        '#group' => 'htabs',
      ];
      $form['files']['file_' . $object->name]['htabs']['edit_' . $object->name]['delete_' . $object->name] = [
        '#type' => 'checkbox',
        '#title' => t('Delete'),
      ];

    }

    $form['new'] = [
      '#type' => 'textfield',
      '#title' => t('New file'),
      '#description' => 'Filename without extension',
      '#default_value' => '',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
      '#attributes' => ['class' => ['button--primary']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

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
      $this->loggerChannel->error($e->getMessage());
    }
  }

}
