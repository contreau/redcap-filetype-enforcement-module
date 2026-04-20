/**
 * Creates observer to append/update the at-a-glance filetypes per file field.
 * @param {*} module JS Module Object
 */
export async function observeFieldPreviewRerender(module) {
  displayFiletypesAtGlance(module); // initial appending of filetype info
  const parent = document.querySelector("div#draggablecontainer_parent");
  const observer = new MutationObserver((mutationList) => {
    for (const mutationRecord of mutationList) {
      for (const node of mutationRecord.addedNodes) {
        if (node.nodeType === Node.ELEMENT_NODE && node.hasAttribute("sq_id")) {
          // Handles rendering behavior when a field is added or edited (only that field rerenders)
          const fieldname = node.getAttribute("sq_id");
          displaySingleFieldFiletypes(module, fieldname);
        } else if (node.nodeType === Node.ELEMENT_NODE && node.id === "draggablecontainer") {
          // Handles rerendering behavior when a field is deleted (all of div#draggablecontainer is rerendered)
          displayFiletypesAtGlance(module);
        }
      }
    }
  });
  observer.observe(parent, { childList: true, subtree: true });
}

/**
 * Displays the enforced filetypes per file field on the instrument editor page; enables knowing active filetypes without having to open a field editor dialog.
 * Also displays beneath a file field's title on live survey pages.
 * @param {*} module JS Module Object
 * @param {string[] | null} filefieldSettings
 */
export async function displayFiletypesAtGlance(module, filefieldSettings = null) {
  if (!filefieldSettings && module) filefieldSettings = await module.ajax("get_filefield_settings");
  if (!filefieldSettings) return;

  for (const fieldname of Object.keys(filefieldSettings)) {
    createFiletypesLabel(fieldname, filefieldSettings);
  }
}

/**
 * Appends the at-a-glance file types label for a single file field.
 * @param {*} module JS Module Object
 * @param {string} fieldname
 */
async function displaySingleFieldFiletypes(module, fieldname) {
  const filefieldSettings = await module.ajax("get_filefield_settings");
  createFiletypesLabel(fieldname, filefieldSettings);
}

/**
 * Helper for generating the at-a-glance file types label.
 * @param {string} fieldname
 * @param {string[]} filefieldSettings
 */
function createFiletypesLabel(fieldname, filefieldSettings) {
  if (filefieldSettings[fieldname]["extensions"].length === 0) return; // Cancels if there are no checked filetypes
  const elementTarget = document.querySelector(`div[data-mlm-field="${fieldname}"]`);
  const filetypesLabel = document.createElement("p");
  filetypesLabel.style.fontSize = "13px";
  filetypesLabel.style.fontWeight = "500";
  filetypesLabel.style.color = "#515151";
  filetypesLabel.innerHTML = `Accepted: <strong>${filefieldSettings[fieldname]["extensions"].join(", ")}</strong>`;
  elementTarget.after(filetypesLabel);
}
