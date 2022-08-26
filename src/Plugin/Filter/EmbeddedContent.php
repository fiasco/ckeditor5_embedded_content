<?php

namespace Drupal\ckeditor5_embedded_content\Plugin\Filter;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\ckeditor5_embedded_content\EmbeddedContentPluginManager;
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
      $container->get('plugin.manager.ckedito5_embedded_content')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $document = Html::load($text);
    $crawler = new Crawler($document);
    $attachments = [];
    $crawler->filter('embedded-content')
      ->each(function (Crawler $crawler) use ($document, &$attachments) {
        /** @var \DOMElement $node */
        $node = $crawler->getNode(0);
        $config = Json::decode($node->getAttribute('data-config'));
        /** @var \Drupal\ckeditor5_embedded_content\EmbeddedContentInterface $instance */
        $instance = $this->embeddedContentPluginManager->createInstance($config['plugin'], $config['plugin_config']);
        $attachments = BubbleableMetadata::mergeAttachments($attachments, $instance->getAttachments());
        $replacement = $instance->build();
        $render = $this->renderer->renderPlain($replacement);
        // Don't throw html5 errors such as embedded media.
        libxml_use_internal_errors(TRUE);
        $new = new \DOMDocument();
        $new->loadHTML($render);
        libxml_clear_errors();
        $new_node = $document->importNode($new->documentElement, TRUE);
        libxml_use_internal_errors(FALSE);
        $node->parentNode->replaceChild($new_node, $node);
      });
    $result = new FilterProcessResult(Html::serialize($document));
    $result->addAttachments($attachments);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return [];
  }

}
