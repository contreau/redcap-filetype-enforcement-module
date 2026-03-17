export function observeFieldEditor() {
  // onchange callback for the field type <select>, which needs to be removed to prevent multiple assigned event listeners
  const onFieldTypeChange = (e) => {
    const select = e.target;
    if (
      select.value === "file" &&
      select.options[select.selectedIndex].getAttribute("sign") === "0" // the 'sign' attribute of value 0 is specific to the file upload field
    ) {
      console.log("selected file upload");
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

          if (
            fieldTypeSelect.value === "file" &&
            fieldTypeSelect.options[fieldTypeSelect.selectedIndex].getAttribute("sign") === "0"
          ) {
            console.log("current field selection: file upload");
          }

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
