<?php

namespace Drupal\ckeditor5_embedded_content_test\Plugin\EmbeddedContent;

use Drupal\ckeditor5_embedded_content\EmbeddedContentInterface;
use Drupal\ckeditor5_embedded_content\EmbeddedContentPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Renders a shape.
 *
 * @EmbeddedContent(
 *   id = "shape",
 *   label = @Translation("Shape"),
 * )
 */
class Shape extends EmbeddedContentPluginBase implements EmbeddedContentInterface {

  const RECTANGLE = 'rectangle';

  const POLYGON = 'polygon';

  const CIRCLE = 'circle';

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'shape' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $markup = '';
    switch ($this->configuration['shape']) {
      case static::RECTANGLE;
        $markup = '<svg width="400" height="110"><rect width="300" height="100" style="fill:rgb(0,0,255);stroke-width:3;stroke:rgb(0,0,0)"></rect></svg>';
        break;

      case 'polygon':
        $markup = '<svg height="210" width="500"><polygon points="200,10 250,190 160,210" style="fill:lime;stroke:purple;stroke-width:1"></polygon></svg>';
        break;

      case'circle':
        $markup = '<svg height="100" width="100"><circle cx="50" cy="50" r="40" stroke="black" stroke-width="3" fill="red"></circle></svg>';
        break;
    }
    return [
      'shape' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => Markup::create($markup),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['shape'] = [
      '#type' => 'select',
      '#title' => $this->t('Shape'),
      '#options' => [
        self::RECTANGLE => $this->t('Rectangle'),
        self::POLYGON => $this->t('Polygon'),
        self::CIRCLE => $this->t('Circle'),
      ],
      '#default_value' => $this->configuration['shape'],
    ];

    return $form;
  }

}
