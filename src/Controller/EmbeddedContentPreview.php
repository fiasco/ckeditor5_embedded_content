<?php

namespace Drupal\ckeditor5_embedded_content\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Render\Renderer;
use Drupal\ckeditor5_embedded_content\EmbeddedContentPluginManager;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\editor\Entity\Editor;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns the preview for embedded content.
 */
class EmbeddedContentPreview extends ControllerBase {

  use StringTranslationTrait;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The embedded content plugin manager.
   *
   * @var \Drupal\ckeditor5_embedded_content\EmbeddedContentPluginManager
   */
  protected $embeddedContentPluginManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.ckedito5_embedded_content'),
      $container->get('renderer')
    );
  }

  /**
   * The controller constructor.
   *
   * @param \Drupal\ckeditor5_embedded_content\EmbeddedContentPluginManager $embedded_content_plugin_manager
   *   The embedded content plugin manager.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer service.
   */
  public function __construct(EmbeddedContentPluginManager $embedded_content_plugin_manager, Renderer $renderer) {
    $this->embeddedContentPluginManager = $embedded_content_plugin_manager;
    $this->renderer = $renderer;
  }

  /**
   * Controller callback that renders the preview for CKeditor.
   */
  public function preview(Request $request, Editor $editor) {
    $config = $request->query->get('config');

    try {
      if (!$config) {
        throw new \Exception();
      }

      $config = Xss::filter($config);

      $config = Json::decode($config);

      if (!isset($config['plugin']) || !isset($config['plugin_config'])) {
        throw new \Exception();
      }
      /** @var \Drupal\ckeditor5_embedded_content\EmbeddedContentInterface $instance */
      $instance = $this->embeddedContentPluginManager->createInstance($config['plugin'], $config['plugin_config']);
      $build = $instance->build();
    }
    catch (\Exception $e) {
      $build = [
        'markup' => [
          '#type' => 'markup',
          '#markup' => $this->t('Incorrect configuration. Please recreate this embedded content.'),
        ],
      ];
    }
    $renderer = \Drupal::service('renderer');
    return new Response($renderer->renderRoot($build));
  }

  /**
   * Access callback for viewing the preview.
   *
   * @param \Drupal\editor\Entity\Editor $editor
   *   The editor.
   * @param \Drupal\Core\Session\AccountProxy $account
   *   The current user.
   *
   * @return \Drupal\Core\Access\AccessResult|\Drupal\Core\Access\AccessResultReasonInterface
   *   The acccess result.
   */
  public function checkAccess(Editor $editor, AccountProxy $account) {
    return AccessResult::allowedIfHasPermission($account, 'use text format ' . $editor->getFilterFormat()->id());
  }

}
