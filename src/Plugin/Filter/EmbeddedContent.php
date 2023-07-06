<?php

namespace Drupal\ckeditor5_embedded_content\Plugin\Filter;

use Drupal\ckeditor5_embedded_content\EmbeddedContentPluginManager;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Provides a text filter that turns < embedded-content > tags into markup.
 *
 * @Filter(
 *   id = "ckeditor5_embedded_content",
 *   title = @Translation("Embedded content"),
 *   description = @Translation("Converts < embedded-content > tags to results."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   weight = 100,
 * )
 *
 * @internal
 */
class EmbeddedContent extends FilterBase implements ContainerFactoryPluginInterface, TrustedCallbackInterface {

  /**
   * The plugin manager for embedded content.
   *
   * @var \Drupal\ckeditor5_embedded_content\EmbeddedContentPluginManager
   */
  protected $embeddedContentPluginManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Renderer $renderer, EmbeddedContentPluginManager $embedded_content_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->embeddedContentPluginManager = $embedded_content_plugin_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('renderer'),
      $container->get('plugin.manager.ckeditor5_embedded_content')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $document = Html::load($text);
    $crawler = new Crawler($document);
    $bubbleable = new BubbleableMetadata();
    $crawler->filter('embedded-content')
      ->each(function (Crawler $crawler) use ($document, $bubbleable) {
        /** @var \DOMElement $node */
        $node = $crawler->getNode(0);
        $plugin_config = Json::decode($node->getAttribute('data-plugin-config'));
        $plugin_id = $node->getAttribute('data-plugin-id');

        if (!$plugin_id || !$plugin_config) {
          return;
        }
        try {
          /** @var \Drupal\ckeditor5_embedded_content\EmbeddedContentInterface $instance */
          $instance = $this->embeddedContentPluginManager->createInstance($plugin_id, $plugin_config);
          $bubbleable->addAttachments($instance->getAttachments());
          $replacement = $instance->build();
          $context = new RenderContext();
          $render = $this->renderer->executeInRenderContext($context, fn() => $this->renderer->render($replacement));
          if (!$context->isEmpty()) {
            $bubbleable->merge($context->pop());
          }
        }
        catch (\Exception $e) {
          $render = (new TranslatableMarkup('Something went wrong.'));
        }
        // Don't throw html5 errors such as embedded media.
        libxml_use_internal_errors(TRUE);
        $new = Html::load($render);
        libxml_clear_errors();
        $xpath = new \DOMXPath($new);
        $new_node = $document->importNode($xpath->query('//body')->item(0), TRUE);
        libxml_use_internal_errors(FALSE);
        $node->parentNode->replaceChild($new_node, $node);
      });
    $result = new FilterProcessResult(Html::serialize($document));
    $result->merge($bubbleable);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return [];
  }

}
