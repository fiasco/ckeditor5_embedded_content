<?php

namespace Drupal\ckeditor5_embedded_content_test\Plugin\EmbeddedContent;

use Drupal\ckeditor5_embedded_content\EmbeddedContentInterface;
use Drupal\ckeditor5_embedded_content\EmbeddedContentPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Renders a color sample based on the selected color.
 *
 * @EmbeddedContent(
 *   id = "color",
 *   label = @Translation("Color"),
 * )
 */
class Color extends EmbeddedContentPluginBase implements EmbeddedContentInterface {

  const GREEN = 'green';

  const RED = 'red';

  const BLUE = 'blue';

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'color' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      'sample' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'style' => 'background:' . $this->configuration['color'] . ';width:20px;height:20px;display:block;border-radius: 10px',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['color'] = [
      '#type' => 'select',
      '#title' => $this->t('Color'),
      '#options' => [
        self::GREEN => $this->t('Green'),
        self::RED => $this->t('Red'),
        self::BLUE => $this->t('Blue'),
      ],
      '#default_value' => $this->configuration['color'],
    ];

    return $form;
  }

}
