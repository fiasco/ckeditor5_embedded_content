<?php

namespace Drupal\ckeditor5_embedded_content_examples\Plugin\EmbeddedContent;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ckeditor5_embedded_content\EmbeddedContentInterface;
use Drupal\ckeditor5_embedded_content\EmbeddedContentPluginBase;

/**
 * Renders a list that is styled.
 *
 * @EmbeddedContent(
 *   id = "styled_list",
 *   label = @Translation("Styled list"),
 * )
 */
class StyledList extends EmbeddedContentPluginBase implements EmbeddedContentInterface {

  const GREEN = 'green';

  const RED = 'red';

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'items' => NULL,
      'style' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $items = $this->configuration['items'] ?? NULL;
    if (empty($items)) {
      return [
        '#type' => 'markup',
        '#markup' => $this->t('No items set.'),
      ];
    }
    foreach ($items as $delta => $item) {
      $items[$delta] = [
        '#type' => 'processed_text',
        '#text' => $item['body']['value'],
        '#format' => $item['body']['format'],
      ];
    }
    return [
      '#theme' => 'ckeditor5_styled_list',
      '#items' => $items,
      '#style' => $this->configuration['style'] ?? NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $items = $this->configuration['items'] ?? [];
    if (empty($items)) {
      $items[] = [];
    }
    if ($triggeringElement = $form_state->getTriggeringElement()) {
      if (($triggeringElement['#op'] ?? '') == 'remove_item') {
        unset($items[$triggeringElement['#delta']]);
      }
      if (($triggeringElement['#op'] ?? '') == 'add_item') {
        $items[] = [];
      }
    }

    $form['style'] = [
      '#type' => 'select',
      '#title' => $this->t('Style'),
      '#options' => [
        self::GREEN => $this->t('Green'),
        self::RED => $this->t('Red'),
      ],
    ];

    $form['items'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'items-wrapper',
        'style' => 'min-width:800px',
      ],
    ];

    foreach ($items as $delta => $item) {
      $element = [
        '#type' => 'details',
        '#open' => TRUE,
        '#title' => 'Item',
      ];
      $element['body'] = [
        '#type' => 'text_format',
        '#title' => $this->t('Body'),
        '#format' => $this->configuration['items'][$delta]['body']['format'] ?? 'basic_html',
        '#allowed_formats' => ['basic_html'],
        '#default_value' => $this->configuration['items'][$delta]['body']['value'] ?? '',
        '#required' => TRUE,
      ];
      $element['remove_item'] = [
        '#type' => 'button',
        '#limit_validation_errors' => [],
        '#value' => $this->t('Remove item'),
        '#delta' => $delta,
        '#op' => 'remove_item',
        '#name' => 'remove_item_' . $delta,
        '#ajax' => [
          'wrapper' => 'items-wrapper',
          'callback' => [static::class, 'updateItems'],
        ],
      ];
      $form['items'][$delta] = $element;
    }

    $form['add_item'] = [
      '#type' => 'button',
      '#limit_validation_errors' => [],
      '#value' => $this->t('Add item'),
      '#name' => 'add_item',
      '#op' => 'add_item',
      '#ajax' => [
        'wrapper' => 'items-wrapper',
        'callback' => [static::class, 'updateItems'],
      ],
    ];
    return $form;
  }

  /**
   * Update items form element.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form element.
   */
  public static function updateItems(array &$form, FormStateInterface $form_state) {
    return $form['config']['plugin_config']['items'];
  }

}
