import { Command } from 'ckeditor5/src/core';

/**
 * Creates embedded content
 */
function createEmbeddedContent(writer, attributes) {
  return writer.createElement('embeddedContent', attributes);
}

/**
 * Command for inserting <embedded-content> tag into ckeditor.
 */
export default class EmbeddedContentCommand extends Command {
  execute(attributes) {
    const embeddedContentEditing = this.editor.plugins.get('embeddedContentEditing');

    // Create object that contains supported data-attributes in view data by
    // flipping `DrupalMediaEditing.attrs` object (i.e. keys from object become
    // values and values from object become keys).
    const dataAttributeMapping = Object.entries(embeddedContentEditing.attrs).reduce(
      (result, [key, value]) => {
        result[value] = key;
        return result;
      },
      {},
    );

    // \Drupal\media\Form\EditorMediaDialog returns data in keyed by
    // data-attributes used in view data. This converts data-attribute keys to
    // keys used in model.
    const modelAttributes = Object.keys(attributes).reduce(
      (result, attribute) => {
        if (dataAttributeMapping[attribute]) {
          result[dataAttributeMapping[attribute]] = attributes[attribute];
        }
        return result;
      },
      {},
    );

    this.editor.model.change((writer) => {
      this.editor.model.insertContent(
        createEmbeddedContent(writer, modelAttributes),
      );
    });
  }

  refresh() {
    const model = this.editor.model;
    const selection = model.document.selection;
    const allowedIn = model.schema.findAllowedParent(
      selection.getFirstPosition(),
      'embeddedContent',
    );
    this.isEnabled = allowedIn !== null;
  }
}
