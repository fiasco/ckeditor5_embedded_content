import { Plugin } from 'ckeditor5/src/core';
import { ButtonView } from 'ckeditor5/src/ui';
import EmbeddedContentIcon from '../theme/icons/embeddedcontent.svg';
import {DomEventObserver} from "ckeditor5/src/engine";

/**
 * Ckeditor5 doesn't support double click out of the box.
 * Register it here so we can use it.
 *
 * @Todo Replace double click with a balloon style popup menu to
 *   edit the embedded content item.
 */
class DoubleClickObserver extends DomEventObserver {
  constructor( view ) {
    super( view );
    this.domEventType = 'dblclick';
  }

  onDomEvent( domEvent ) {
    this.fire( domEvent.type, domEvent );
  }
}

/**
 * Provides the embedded content button and editing.
 */
export default class EmbeddedContentUI extends Plugin {

  init() {
    const editor = this.editor;
    const options = this.editor.config.get('embeddedContent');
    if (!options) {
      return;
    }

    const { dialogURL, openDialog, dialogSettings = {} } = options;
    if (!dialogURL || typeof openDialog !== 'function') {
      return;
    }
    editor.ui.componentFactory.add('embeddedContent', (locale) => {
      const command = editor.commands.get('embeddedContent');
      const buttonView = new ButtonView(locale);

      buttonView.set({
        label: Drupal.t('Embedded content'),
        icon: EmbeddedContentIcon,
        tooltip: true,
      });


      // Bind the state of the button to the command.
      buttonView.bind('isOn', 'isEnabled').to(command, 'value', 'isEnabled');

      this.listenTo(buttonView, 'execute', () => {
        const modelElement = editor.model.document.selection.getSelectedElement();
        if (modelElement && typeof modelElement.name !== 'undefined' && modelElement.name === 'embeddedContent') {
          const query = {
            plugin_id: modelElement.getAttribute('embeddedContentPluginId'),
            plugin_config: modelElement.getAttribute('embeddedContentPluginConfig'),
          };
          console.log(query)
          openDialog(
              `${dialogURL}?${new URLSearchParams(query)}`,
              ({ attributes }) => {
                editor.execute('embeddedContent', attributes);
              },
              dialogSettings,
          );
        } else {
          openDialog(
              dialogURL,
              ({attributes}) => {
                editor.execute('embeddedContent', attributes);
              },
              dialogSettings,
          );
        }
      });

      return buttonView;
    });

    const view = editor.editing.view;
    const viewDocument = view.document;

    view.addObserver( DoubleClickObserver );

    editor.listenTo( viewDocument, 'dblclick', ( evt, data ) => {
      const modelElement = editor.editing.mapper.toModelElement( data.target);
      if(modelElement && typeof modelElement.name !== 'undefined' && modelElement.name === 'embeddedContent'){
        const query = {
          plugin_id: modelElement.getAttribute('embeddedContentPluginId'),
          plugin_config: modelElement.getAttribute('embeddedContentPluginConfig'),
        };
        openDialog(
          `${dialogURL}?${new URLSearchParams(query)}`,
          ({ attributes }) => {
            editor.execute('embeddedContent', attributes);
          },
          dialogSettings,
        );
      }
    } );
  }
}
