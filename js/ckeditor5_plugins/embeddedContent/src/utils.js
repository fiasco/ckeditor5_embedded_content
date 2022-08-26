import { isWidget } from 'ckeditor5/src/widget';

/**
 * Checks if the provided model element is `embeddedContent`.
 *
 * @param {module:engine/model/element~Element} modelElement
 *   The model element to be checked.
 * @return {boolean}
 *   A boolean indicating if the element is a embeddedContent element.
 *
 * @private
 */
export function isEmbeddedContent(modelElement) {
  return !!modelElement && modelElement.is('element', 'embeddedContent');
}

/**
 * Checks if view element is <embedded-content> element.
 *
 * @param {module:engine/view/element~Element} viewElement
 *   The view element.
 * @return {boolean}
 *   A boolean indicating if the element is a <embedded-content> element.
 *
 * @private
 */
export function isEmbeddedContentWidget(viewElement) {
  console.log(viewElement);
  console.log(isWidget(viewElement));
  console.log(viewElement.getCustomProperty);
  return (
    isWidget(viewElement) && !!viewElement.getCustomProperty('embeddedContent')
  );
}

/**
 * Gets `embeddedContent` element from selection.
 *
 * @param {module:engine/model/selection~Selection|module:engine/model/documentselection~DocumentSelection} selection
 *   The current selection.
 * @return {module:engine/model/element~Element|null}
 *   The `embeddedContent` element which could be either the current selected an
 *   ancestor of the selection. Returns null if the selection has no Drupal
 *   Media element.
 *
 * @private
 */
export function getClosestSelectedEmbeddedContentElement(selection) {
  const selectedElement = selection.getSelectedElement();

  return isEmbeddedContent(selectedElement)
    ? selectedElement
    : selection.getFirstPosition().findAncestor('embeddedContent');
}

/**
 * Gets selected Drupal Media widget if only Drupal Media is currently selected.
 *
 * @param {module:engine/model/selection~Selection} selection
 *   The current selection.
 * @return {module:engine/view/element~Element|null}
 *   The currently selected Drupal Media widget or null.
 *
 * @private
 */
export function getClosestSelectedEmbeddedContentWidget(selection) {
  const viewElement = selection.getSelectedElement();
  if (viewElement && isEmbeddedContentWidget(viewElement)) {
    return viewElement;
  }

  let parent = selection.getFirstPosition().parent;

  while (parent) {
    if (parent.is('element') && isEmbeddedContentWidget(parent)) {
      return parent;
    }

    parent = parent.parent;
  }

  return null;
}

/**
 * Checks if value is a JavaScript object.
 *
 * This will return true for any type of JavaScript object. (e.g. arrays,
 * functions, objects, regexes, new Number(0), and new String(''))
 *
 * @param value
 *   Value to check.
 * @return {boolean}
 *   True if value is an object, else false.
 */
export function isObject(value) {
  const type = typeof value;
  return value != null && (type === 'object' || type === 'function');
}

/**
 * Gets the preview container element from the media element.
 *
 * @param {Iterable.<module:engine/view/element~Element>} children
 *   The child elements.
 * @return {null|module:engine/view/element~Element}
 *   The preview child element if available.
 */
export function getPreviewContainer(children) {
  // eslint-disable-next-line no-restricted-syntax
  for (const child of children) {
    if (child.hasAttribute('data-embedded-content-preview')) {
      return child;
    }

    if (child.childCount) {
      const recursive = getPreviewContainer(child.getChildren());
      // Return only if preview container was found within this element's
      // children.
      if (recursive) {
        return recursive;
      }
    }
  }

  return null;
}

/**
 * Gets model attribute key based on Drupal Element Style group.
 *
 * @example
 *    Example: 'align' -> 'drupalElementStyleAlign'
 *
 * @param {string} group
 *   The name of the group (ex. 'align', 'viewMode').
 * @return {string}
 *   Model attribute key.
 *
 * @internal
 */
export function groupNameToModelAttributeKey(group) {
  // Manipulate string to have first letter capitalized to append in camel case.
  const capitalizedFirst = group[0].toUpperCase() + group.substring(1);
  return `drupalElementStyle${capitalizedFirst}`;
}
