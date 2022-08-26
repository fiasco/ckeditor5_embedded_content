<?php

namespace Drupal\ckeditor5_embedded_content_examples\Plugin\EmbeddedContent;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ckeditor5_embedded_content\EmbeddedContentInterface;
use Drupal\ckeditor5_embedded_content\EmbeddedContentPluginBase;

/**
 * Renders a box with image, text and info.
 *
 * @EmbeddedContent(
 *   id = "tweet",
 *   label = @Translation("Tweet"),
 * )
 */
class Tweet extends EmbeddedContentPluginBase implements EmbeddedContentInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'tweet' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachments(): array {
    return [
      'library' => [
        'ckeditor5_embedded_content_examples/twitter',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build['tweet'] = [
      '#theme' => 'ckeditor5_embedded_content_tweet',
      '#url' => $this->configuration['url'] ?? NULL,
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['url'] = [
      '#type' => 'url',
      '#title' => $this->t('Url'),
      '#default_value' => $this->configuration['url'] ?? '',
      '#required' => TRUE,
    ];
    return $form;
  }

}
