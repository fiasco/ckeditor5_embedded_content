import {Plugin} from 'ckeditor5/src/core';
import {toWidget, Widget} from 'ckeditor5/src/widget';
import EmbeddedContentCommand from './embeddedcontentcommand';

/**
 * Embedded content editing functionality.
 */
export default class EmbeddedContentEditing extends Plugin {

    /**
     * @inheritdoc
     */
    static get requires()
    {
        return [Widget];
    }

    /**
     * @inheritdoc
     */
    init()
    {
        this.attrs = {
            embeddedContentPluginConfig: 'data-plugin-config',
            embeddedContentPluginId: 'data-plugin-id',
        };
        const options = this.editor.config.get('embeddedContent');
        if (!options) {
            return;
        }
        const {previewURL, themeError} = options;
        this.previewUrl = previewURL;
        this.themeError =
        themeError ||
        `
        <p>${Drupal.t(
            'An error occurred while trying to preview the embedded content. Please save your work and reload this page.',
        )}<p>
        `;

        this._defineSchema();
        this._defineConverters();

        this.editor.commands.add(
            'embeddedContent',
            new EmbeddedContentCommand(this.editor),
        );
    }

    /**
     * Fetches the preview.
     */
    async _fetchPreview(modelElement)
    {
        const query = {
            plugin_id: modelElement.getAttribute('embeddedContentPluginId'),
            plugin_config: modelElement.getAttribute('embeddedContentPluginConfig'),
        };
        const response = await fetch(
            `${this.previewUrl}?${new URLSearchParams(query)}`
        );
        if (response.ok) {
            return await response.text();
        }

        return this.themeError;
    }

    /**
     * Registers embeddedContent as a block element in the DOM converter.
     */
    _defineSchema()
    {
        const schema = this.editor.model.schema;
        schema.register(
            'embeddedContent', {
                allowWhere: '$block',
                isObject: true,
                isContent: true,
                isBlock: true,
                allowAttributes: Object.keys(this.attrs),
            }
        );
        this.editor.editing.view.domConverter.blockElements.push('embedded-content');
    }

    /**
     * Defines handling of drupal media element in the content lifecycle.
     *
     * @private
     */
    _defineConverters()
    {
        const conversion = this.editor.conversion;

        conversion
        .for('upcast')
        .elementToElement(
            {
                view: {
                    name: 'embedded-content',
                },
                model: 'embeddedContent',
            }
        );

        conversion
        .for('dataDowncast')
        .elementToElement(
            {
                model: 'embeddedContent',
                view: {
                    name: 'embedded-content',
                },
            }
        );
        conversion
        .for('editingDowncast')
        .elementToElement(
            {
                model: 'embeddedContent',
                view: (modelElement, {writer}) => {
                    const container = writer.createContainerElement('figure');
                    return toWidget(
                        container, writer, {
                            label: Drupal.t('Embedded content'),
                        }
                    );

                },
            }
        )
        .add(
            (dispatcher) => {
                const converter = (event, data, conversionApi) => {
            const viewWriter = conversionApi.writer;
            const modelElement = data.item;
            const container = conversionApi.mapper.toViewElement(data.item);
            const embeddedContent = viewWriter.createRawElement(
                    'div', {
                        'data-embedded-content-preview': 'loading',
                        'class': 'embedded-content-preview'
                        }
                );
            viewWriter.insert(viewWriter.createPositionAt(container, 0), embeddedContent);
            this._fetchPreview(modelElement).then(
                    (preview) => {
                    if (!embeddedContent) {
                        return;
                    }
                    this.editor.editing.view.change(
                            (writer) => {
                            const embeddedContentPreview = writer.createRawElement(
                                    'div',
                                    {'class': 'embedded-content-preview', 'data-embedded-content-preview': 'ready'},
                                    (domElement) => {
                                    domElement.innerHTML = preview;
                                    },
                                );
                            writer.insert(writer.createPositionBefore(embeddedContent), embeddedContentPreview);
                            writer.remove(embeddedContent);
                            }
                        );
                    }
                );
                };
                dispatcher.on('attribute:embeddedContentPluginId:embeddedContent', converter);
                return dispatcher;
            }
        );

        Object.keys(this.attrs).forEach(
            (modelKey) => {
                const attributeMapping = {
                    model: {
                        key: modelKey,
                        name: 'embeddedContent',
                        },
                    view: {
                        name: 'embedded-content',
                        key: this.attrs[modelKey],
                        },
                };
                conversion.for('dataDowncast').attributeToAttribute(attributeMapping);
                conversion.for('upcast').attributeToAttribute(attributeMapping);
            }
        );
    }

    /**
     * @inheritdoc
     */
    static get pluginName()
    {
        return 'embeddedContentEditing';
    }
}
