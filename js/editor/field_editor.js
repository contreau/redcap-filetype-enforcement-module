/**
 * Creates the mutation observer for the field editor dialog in the instrument builder page.
 */
export function observeFieldEditor(component, module) {
  // todo: unmount the component when either the 'Cancel' or 'X' buttons close the dialog.

  const tagName = component.tagName ?? "filetype-checkboxes";

  if (!customElements.get(tagName)) {
    customElements.define(tagName, component);
  }

  const isFileUploadField = (select) =>
    select.value === "file" && select.options[select.selectedIndex].getAttribute("sign") === "0";

  const mountComponent = () => {
    if (document.querySelector(tagName)) return; // already mounted
    const instance = new component(module);
    document.querySelector("#div_field_annotation").before(instance);
  };

  const unmountComponent = () => {
    document.querySelector(tagName)?.remove();
  };

  // onchange callback for the field type <select>, which needs to be removed to prevent multiple assigned event listeners
  const onFieldTypeChange = (e) => {
    if (isFileUploadField(e.target)) {
      mountComponent();
    } else {
      unmountComponent();
    }
  };

  // Observe body for the field editor dialog to appear
  const fieldEditorObserver = new MutationObserver((mutationList) => {
    for (const mutation of mutationList) {
      for (const node of mutation.addedNodes) {
        if (
          node.nodeType === Node.ELEMENT_NODE &&
          node.getAttribute("role") === "dialog" &&
          node.querySelector('form[name="addFieldForm"]')
        ) {
          const fieldTypeSelect = document.querySelector("#field_type");
          isFileUploadField(fieldTypeSelect) ? mountComponent() : unmountComponent();
          fieldTypeSelect.removeEventListener("change", onFieldTypeChange);
          fieldTypeSelect.addEventListener("change", onFieldTypeChange);
          return;
        }
      }
    }
  });

  fieldEditorObserver.observe(document.body, {
    childList: true,
  });
}
