<?php
/**
 * @file
 * Contains \Drupal\devdocs\Form\DevDocsSettingsForm
 */
namespace Drupal\devdocs\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\devdocs\StreamWrapper\DocsStream;

/**
 * Configure devdocs settings for this site.
 */
class DevDocsSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'devdocs_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'devdocs.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('devdocs.settings');
    $directory = 'docs://';
    if (DocsStream::basePath() && file_valid_uri($directory)) {
      file_prepare_directory($directory, FILE_CREATE_DIRECTORY);
      file_save_htaccess('docs://', TRUE);
    }

    $form['file_docs_path'] = array(
      '#type' => 'textfield',
      '#title' => t('Developer Documentation directory path'),
      '#default_value' => $config->get('path'),
      '#maxlength' => 255,
      '#description' => t('An existing local file system path for storing Developer Documentation files. It should be writable by Drupal and not accessible over the web. See the online handbook for <a href="@handbook">more information about securing private files</a>.', array('@handbook' => 'http://drupal.org/documentation/modules/file')),
      '#after_build' => array('system_check_directory'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('devdocs.settings')
      ->set('path', $form_state->getValue('file_docs_path'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
