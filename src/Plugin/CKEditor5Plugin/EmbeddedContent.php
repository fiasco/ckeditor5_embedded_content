<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5_embedded_content\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\Core\Url;
use Drupal\editor\EditorInterface;

/**
 * Plugin class to add dialog url for embedded content.
 */
class EmbeddedContent extends CKEditor5PluginDefault {

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    $embedded_content_dialog_url = Url::fromRoute('ckeditor5_embedded_content.dialog')
      ->toString(TRUE)
      ->getGeneratedUrl();
    $static_plugin_config['embeddedContent']['dialogURL'] = $embedded_content_dialog_url;
    $embedded_content_preview_url = Url::fromRoute('ckeditor5_embedded_content.preview', [
      'editor' => $editor->id(),
    ])
      ->toString(TRUE)
      ->getGeneratedUrl();
    $static_plugin_config['embeddedContent']['previewURL'] = $embedded_content_preview_url;
    return $static_plugin_config;
  }

}
