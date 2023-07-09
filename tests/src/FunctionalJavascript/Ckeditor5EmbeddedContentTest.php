<?php

namespace Drupal\Tests\ckeditor5_embedded_content\FunctionalJavascript;

use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\user\RoleInterface;

/**
 * Defines tests for the ckeditor5 button and javascript functionality.
 * 
 * @group ckeditor5_embedded_content
 */
class Ckeditor5EmbeddedContentTest extends WebDriverTestBase {

  use CKEditor5TestTrait;

  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'ckeditor5',
    'ckeditor5_embedded_content',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->drupalCreateContentType(['type' => 'page']);
    FilterFormat::create(
          [
            'format' => 'test',
            'name' => 'Ckeditor 5 with embedded content',
            'roles' => [RoleInterface::AUTHENTICATED_ID],
            'filters' => [
              'ckeditor5_embedded_content' => [
                'id' => 'ckeditor5_embedded_content',
                'provider' => 'ckeditor5_embedded_content',
                'status' => TRUE,
                'weight' => 1,
              ],
              'filter_html' => [
                'id' => 'filter_html',
                'status' => TRUE,
                'weight' => 2,
                'settings' => [
                  'allowed_html' => '<br> <p> <embedded-content data-plugin-config data-plugin-id>',
                  'filter_html_help' => TRUE,
                  'filter_html_nofollow' => FALSE,
                ],
              ],
            ],
          ]
      )->save();
    Editor::create(
          [
            'format' => 'test',
            'editor' => 'ckeditor5',
            'settings' => [
              'toolbar' => [
                'items' => ['embeddedContent', 'sourceEditing'],
              ],
            ],
          ]
      )->save();

    $this->drupalLogin(
          $this->drupalCreateUser(
              [
                'create page content',
                'edit own page content',
                'access content',
                'use ckeditor5 embedded content',
                'use text format test',
              ]
          )
      );

  }

  /**
   * Tests if CKEditor 5 tooltips can be interacted with in dialogs.
   */
  public function testCkeditor5EmbeddedContent() {

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Add a node with text rendered via the Plain Text format.
    $this->drupalGet('node/add');

    $this->waitForEditor();
    // Ensure the editor is loaded.
    $this->click('.ck-content');

    $this->assertEditorButtonEnabled('Embedded content');
    $this->click('.ck-button');
    $assert_session->waitForText('No embedded content plugins were defined. Enable the examples module to see some examples.');
    $this->container->get('module_installer')
      ->install(['ckeditor5_embedded_content_test'], TRUE);

    // Add a node with text rendered via the Plain Text format.
    $this->drupalGet('node/add');

    $this->waitForEditor();
    // Ensure the editor is loaded.
    $this->click('.ck-content');

    $this->assertEditorButtonEnabled('Embedded content');
    $this->click('.ck-button');
    $assert_session->waitForElement('css', '.ckeditor5-embedded-content-dialog-form');
    $page->selectFieldOption('config[plugin_id]', 'Shape');

    $assert_session->waitForElement('css', '[data-drupal-selector="edit-config-plugin-config-shape"]');

    $page->selectFieldOption('config[plugin_config][shape]', 'polygon');

    $this->click('.ui-dialog-buttonset button');
    $node = $assert_session->waitForElement('css', '.embedded-content-preview > div');
    $this->assertEquals('<svg height="210" width="500"><polygon points="200,10 250,190 160,210" style="fill:lime;stroke:purple;stroke-width:1"></polygon></svg>', $node->getHtml());

    // Test if it is possible to edit a selected embedded content.
    $this->click('figure.ck-widget');

    $this->click('.ck-button');
    $element = $assert_session->waitForElement('css', '[data-drupal-selector="edit-config-plugin-config-shape"]');

    $this->assertEquals('polygon', $element->getValue());
    $page->selectFieldOption('config[plugin_id]', 'Color');
    $assert_session->waitForElement('css', '[data-drupal-selector="edit-config-plugin-config-color"]');
    $page->selectFieldOption('config[plugin_config][color]', 'red');
    $this->click('.ui-dialog-buttonset button');
    $assert_session->waitForElement('css', '.embedded-content-preview [style="background:green;width:20px;height:20px;display:block;border-radius: 10px"]');

    // Test if it is possible to edit on double click.
    $page->find('css', '.embedded-content-preview')->doubleClick();
    $assert_session->waitForElement('css', '[data-drupal-selector="edit-config-plugin-config-color"]');

  }

}
