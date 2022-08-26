import { Plugin } from 'ckeditor5/src/core';
import EmbeddedContentEditing from './embeddedcontentediting';
import EmbeddedContentUI from './embeddedcontentui';

/**
 * Main entry point to the embedded content.
 */
export default class EmbeddedContent extends Plugin {

  /**
   * @inheritdoc
   */
  static get requires() {
    return [
      EmbeddedContentEditing,
      EmbeddedContentUI
    ];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'embeddedContent';
  }
}
