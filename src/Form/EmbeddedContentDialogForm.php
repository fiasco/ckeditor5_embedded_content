<?php

namespace Drupal\ckeditor5_embedded_content\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\SubformState;
use Drupal\ckeditor5_embedded_content\EmbeddedContentPluginManager;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\editor\Ajax\EditorDialogSave;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Ckeditor dialog form to insert webform submission results in text.
 */
class EmbeddedContentDialogForm extends FormBase {

  /**
   * The embedded content plugin manager.
   *
   * @var \Drupal\ckeditor5_embedded_content\EmbeddedContentPluginManager
   */
  protected $embeddedContentPluginManager;

  /**
   * The ajax wrapper id to use for re-rendering the form.
   *
   * @var string
   */
  protected $ajaxWrapper = 'embedded-content-dialog-form-wrapper';

  /**
   * The form constructor.
   *
   * @param \Drupal\ckeditor5_embedded_content\EmbeddedContentPluginManager $embedded_content_plugin_manager
   *   The embedded content plugin manager.
   */
  public function __construct(EmbeddedContentPluginManager $embedded_content_plugin_manager) {
    $this->embeddedContentPluginManager = $embedded_content_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.ckedito5_embedded_content')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ckeditor5_embedded_content_dialog_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $uuid = NULL) {

    $request = $this->getRequest();

    $config = $form_state->getUserInput()['config'] ?? [];

    if(!$config){
      $config = $request->get('config');
      if($config){
        $config = Xss::filter($config);
        $config = Json::decode($config);
      }
    }


    $form['#modal_selector'] = '#embedded-content-dialog-form';

    $form['#attributes']['style'] = ['min-width: 500px'];

    if (isset($form_state->getUserInput()['editor_object'])) {
      $config = Json::decode($form_state->getUserInput()['editor_object']['config']) ?? [];
    }

    if ($uuid) {
      $form['uuid'] = [
        '#type' => 'value',
        '#value' => $uuid,
      ];
    }
    $definitions = $this->embeddedContentPluginManager->getDefinitions();
    if (!$definitions) {
      $form['warning'] = [
        '#type' => 'markup',
        '#markup' => $this->t('No embedded content plugins were defined. Enable the examples module to see some examples.'),
      ];
      return $form;
    }

    $form['config'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#attributes' => [
        'id' => $this->ajaxWrapper,
      ],
      'plugin' => [
        '#type' => 'select',
        '#title' => $this->t('Embedded content'),
        '#empty_option' => $this->t('- Select a type -'),
        '#default_value' => $config['plugin'] ?? '',
        '#options' => array_map(function ($definition) {
          return $definition['label'];
        }, $definitions),
        '#required' => TRUE,
        '#ajax' => [
          'callback' => [$this, 'updateFormElement'],
          'event' => 'change',
          'wrapper' => $this->ajaxWrapper,
        ],
      ],
    ];
    $plugin_id = $config['plugin'] ?? '';
    if ($plugin_id) {
      /** @var \Drupal\ckeditor5_embedded_content\EmbeddedContentInterface $instance */
      try {
        $instance = $this->embeddedContentPluginManager->createInstance($plugin_id, $config['plugin_config'] ?? []);
        $subform = $form['config']['plugin_config'] ?? [];
        $subform_state = SubformState::createForSubform($subform, $form, $form_state);
        $form['config']['plugin_config'] = $instance->buildConfigurationForm([], $subform_state);
        $form['config']['plugin_config']['#tree'] = TRUE;
      }
      catch (\Exception $exception) {
        $form['message'] = [
          '#type' => 'status_messages',
        ];
      }
    }

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
        '#ajax' => [
          'callback' => [$this, 'ajaxSubmitForm'],
          'wrapper' => $this->ajaxWrapper,
        ],
      ],
    ];

    return $form;
  }

  /**
   * Update the form after selecting a plugin type.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form element for webform elements.
   */
  public function updateFormElement(array $form, FormStateInterface $form_state): array {
    return $form['config'];
  }

  /**
   * Ajax submit callback to insert or replace the html in ckeditor.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|array
   *   Ajax response for injecting html in ckeditor.
   */
  public static function ajaxSubmitForm(array &$form, FormStateInterface $form_state): AjaxResponse|array {
    if ($form_state->getErrors()) {
      return $form['config'];
    }
    $config = $form_state->getValue('config');

    $response = new AjaxResponse();

    $dom = new \DOMDocument();
    $placeholder = $dom->createElement('embedded-content');

    // Set the entire config as JSON attribute.
    $placeholder->setAttribute('data-config', Json::encode($config));

    if (!($uuid = $form_state->getValue('uuid'))) {
      $uuid = \Drupal::service('uuid')->generate();
    }

    $response->addCommand(new EditorDialogSave([
      'attributes' => [
        'data-config' => Json::encode($config),
        'data-uuid' => $uuid,
      ],
    ]));

    // $response->addCommand(new CloseModalDialogCommand(FALSE, '#embedded-content-dialog-form'));
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($collection = $form_state->getValue(['config', 'collection'])) {
      if (empty($collection)) {
        $form_state->setError($form, new TranslatableMarkup('The configuration cannot be empty.'));
      }
      else {
        foreach ($collection as $delta => $item) {
          if ($item['use_other'] && !$item['webform']) {
            $form_state->setError($form['config']['collection'][$delta]['webform'], new TranslatableMarkup('The webform is required'));
          }
          else {
            // Clean the remove button from the configuration.
            unset($item['remove_item']);
            $item['webform'] = $form_state->getValue('current_webform')->id();
          }
        }
      }
      $form_state->setValue(['config', 'collection'], $collection);
    }

    /** @var \Drupal\ckeditor5_embedded_content\EmbeddedContentInterface $instance */
    $plugin_id = $form_state->getValue(['config', 'plugin']);
    if ($plugin_id) {
      try {
        $instance = $this->embeddedContentPluginManager->createInstance($plugin_id, $form_state->getValue([
          'config',
          'plugin_config',
        ]));
        $subform_state = SubformState::createForSubform($form['config']['plugin_config'], $form, $form_state);
        $instance->validateConfigurationForm($form['config']['plugin_config'], $subform_state);
        $config = $form_state->getValue('config');
        unset($config['add_item']);
        $form_state->setValue('config', $config);
      }
      catch (\Exception $exception) {
        $form_state->setValue('config', []);
      }
    }
    else {
      $form_state->setValue('config', []);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Required but not used.
  }

}
