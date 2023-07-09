<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5_embedded_content\Kernel;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Language\Language;
use Drupal\Core\Render\RenderContext;
use Drupal\filter\FilterPluginCollection;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Defines a test for the EmbeddedContent filter.
 *
 * @covers \Drupal\ckeditor5_embedded_content\Plugin\Filter\EmbeddedContent
 * @group ckeditor5_embedded_content
 */
final class EmbeddedContentTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'filter',
    'ckeditor5_embedded_content',
    'ckeditor5_embedded_content_test',
  ];

  /**
   * The filter to test.
   *
   * @var \Drupal\filter\Plugin\FilterInterface
   */
  protected $filter;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $manager = $this->container->get('plugin.manager.filter');
    $bag = new FilterPluginCollection($manager, []);
    $this->filter = $bag->get('ckeditor5_embedded_content');
  }

  /**
   * Get filtered and rendered content.
   *
   * @param string $content
   *   The content to render.
   *
   * @return string
   *   The filtered and rendered content.
   */
  protected function getRenderedContent(string $content): string {
    $context = new RenderContext();
    return \Drupal::service('renderer')
      ->executeInRenderContext($context, fn() => (string) $this->filter->process($content, Language::LANGCODE_NOT_SPECIFIED));
  }

  /**
   * Encodes plugin config.
   *
   * @param array $config
   *   Plugin config.
   *
   * @return string
   *   Encoded config.
   */
  protected function encodePluginConfig(array $config): string {
    return htmlspecialchars(Json::encode($config));
  }

  /**
   * Tests embed filter.
   */
  public function testFilter(): void {
    $config = $this->encodePluginConfig(
          [
            'color' => 'green',
          ]
      );
    $markup = <<<HTML
<embedded-content data-plugin-config="$config" data-plugin-id="color">&nbsp;</embedded-content>
HTML;

    $context = new RenderContext();
    $content = \Drupal::service('renderer')
      ->executeInRenderContext($context, fn() => (string) $this->filter->process($markup, Language::LANGCODE_NOT_SPECIFIED));

    $crawler = new Crawler($content);

    $this->assertCount(1, $crawler->filter('div[style="background:green;width:20px;height:20px;display:block;border-radius: 10px"]'));
    $this->assertCount(0, $crawler->filter('meta'));

    $markup = <<<HTML
<embedded-content data-plugin-config="[]" data-plugin-id="no_config">&nbsp;</embedded-content>
HTML;

    $result = $this->filter->process($markup, Language::LANGCODE_NOT_SPECIFIED);

    $this->assertEquals(0, $result->getCacheMaxAge());
    $this->assertEquals('ckeditor5_embedded_content_test/test', $result->getAttachments()['library'][0]);
  }

}
