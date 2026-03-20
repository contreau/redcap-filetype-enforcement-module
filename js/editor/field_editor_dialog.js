/**
 * Creates the mutation observer for the field editor dialog in the instrument builder page.
 */
export function observeFieldEditorDialog(component, module) {
  const tagName = component.tagName ?? "filetype-checkboxes";
  let dialogIsOpen = false;
  let fieldname = "";

  if (!customElements.get(tagName)) {
    customElements.define(tagName, component);
  }

  const isFileUploadField = (select) =>
    select.value === "file" && select.options[select.selectedIndex].getAttribute("sign") === "0";

  const mountComponent = (fieldname) => {
    if (document.querySelector(tagName)) return; // already mounted
    const instance = new component(module, fieldname);
    document.querySelector("#div_field_annotation").before(instance);
  };

  const unmountComponent = () => {
    document.querySelector(tagName)?.remove();
  };

  // onchange callback for the field type <select>, which needs to be removed to prevent multiple assigned event listeners
  const onFieldTypeChange = (e, fieldname) => {
    if (isFileUploadField(e.target)) {
      mountComponent(fieldname);
    } else {
      unmountComponent();
    }
  };

  const onDialogClose = () => {
    if (document.querySelector(tagName)) {
      dialogIsOpen = false;
      fieldname = "";
      unmountComponent();
    }
  };

  const onDialogSave = async () => {
    const filetypeCheckboxes = document.querySelector(tagName);
    if (filetypeCheckboxes) {
      const savedData = await filetypeCheckboxes.saveEnforcedFiletypes();
      console.log("saved. ", savedData);
      dialogIsOpen = false;
      fieldname = "";
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
          if (!dialogIsOpen) {
            fieldname = document.querySelector("input#field_name").value.trim();
            dialogIsOpen = true;
          }

          // File Upload is selected in the 'Field Type' select element
          const fieldTypeSelect = document.querySelector("#field_type");
          isFileUploadField(fieldTypeSelect) ? mountComponent(fieldname) : unmountComponent();
          fieldTypeSelect.addEventListener("change", (e) => {
            onFieldTypeChange(e, fieldname);
          });

          // Modal is closed out (the 'X' button at the top-right corner)
          const dialogCloseButton = document.querySelector("button[title='Close']");
          dialogCloseButton.addEventListener("click", onDialogClose);

          // Modal is cancelled ('Cancel' button next to the 'Save' button at the bottom-right corner)
          const cornerButtonsDiv = document.querySelector(".ui-dialog-buttonset");
          let dialogCancelButton = null;
          let dialogSaveButton = null;
          for (const node of cornerButtonsDiv.childNodes) {
            node.textContent.trim().toLowerCase() === "cancel"
              ? (dialogCancelButton = node)
              : (dialogSaveButton = node);
          }
          dialogCancelButton.addEventListener("click", onDialogClose);

          // Modal is saved ('Save' button is clicked) - invokes ajax to send the selected filetyps to the project settings
          dialogSaveButton.addEventListener("click", onDialogSave);
          return;
        }
      }
    }
  });

  fieldEditorObserver.observe(document.body, {
    childList: true,
  });
}
