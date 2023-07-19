<?php

declare(strict_types=1);

namespace Drupal\ckeditor5_embedded_content;

use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Defines a class for render element callbacks.
 */
final class ElementCallbacks implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return ['preRenderTextFormat'];
  }

  /**
   * Pre-render callback.
   */
  public static function preRenderTextFormat(array $element): array {
    return $element;
  }

}
