/**
 * Runs in the user's client on live survey pages, enforcing file types for file upload fields as configured in the project settings.
 * @param {Record<string, string>} settings The file types to be applied per-field name.
 */
export function applyFiletypeEnforcement(settings) {
  /**
   * Click event callback.
   * @param {string} mimetypeString
   */
  function setInputAcceptAttribute(mimetypeString) {
    const fileUploadInput = /** @type {HTMLInputElement} */ (
      document.querySelector("form#form_file_upload div#f1_upload_form div input")
    );
    if (fileUploadInput.getAttribute("accept") === null) {
      fileUploadInput.setAttribute("accept", mimetypeString);
    }
    rejectInvalidFileOnUpload(fileUploadInput, mimetypeString);
  }

  /**
   * Clears the file field out and alerts the user that the type must be of an allowed MIME type.
   * @param {HTMLInputElement} fileUploadInput
   * @param {string} mimetypeString
   */
  function rejectInvalidFileOnUpload(fileUploadInput, mimetypeString) {
    const allowedTypes = mimetypeString.split(",").map((str) => str.trim());
    fileUploadInput.addEventListener("change", (e) => {
      const input = /** @type {HTMLInputElement} */ (e.target);
      const file = /** @type {FileList} */ (input.files)[0];

      if (!allowedTypes.includes(file.type)) {
        // If it doesn't exist, create new <p> element to display file rejection message
        const prevSibling = /** @type {HTMLElement} */ (fileUploadInput.previousElementSibling);

        if (prevSibling === null || prevSibling.id !== "file-rejection-message--container") {
          // Create div wrapper
          const rejectionMessageContainer = document.createElement("div");
          rejectionMessageContainer.id = "file-rejection-message--container";

          // Create message <p>
          const rejectionMessageElement = document.createElement("p");

          // Set id and inner HTML
          rejectionMessageElement.id = "file-rejection-message";
          rejectionMessageElement.innerHTML = `<i class='fas fa-triangle-exclamation'></i>Unsupported file type.`;

          // Set styles
          rejectionMessageElement.style.fontWeight = "bold";
          rejectionMessageElement.style.color = "#b30000";

          rejectionMessageContainer.appendChild(rejectionMessageElement);
          fileUploadInput.before(rejectionMessageContainer);
        }

        // Clear out file
        fileUploadInput.value = "";
      } else {
        // Remove the rejection message upon successful upload, if it's there
        document.querySelector("#file-rejection-message--container")?.remove();
      }
    });
  }

  /**
   * Registers click events and mutation observers for each file field type.
   * @param {string} fieldname
   * @param {HTMLTableRowElement} fieldNode
   * @param {string} mimetypeString
   */
  function registerFileValidation(fieldname, fieldNode, mimetypeString) {
    // - Case 1: Initial click event assignment
    const uploadButton = fieldNode.querySelector(`a.fileuploadlink`);
    uploadButton?.addEventListener("click", () => {
      if (mimetypeString !== "") setInputAcceptAttribute(mimetypeString);
    });

    // - Case 2: A file is already uploaded, but the user chooses to either upload a new file (in-place replacement) or delete the current one.
    // - The upload link (a.fileuploadlink) re-renders when a file is uploaded, which removed the click event that we initially assigned it.
    // - Observe its parent div for that mutation, and add back the click event assignment to a.fileuploadlink.
    const observer = new MutationObserver((mutationList) => {
      let addedListener = false; // prevents memory leak of adding more than one listener
      for (const mutation of mutationList) {
        if (mutation.type === "childList") {
          if (!addedListener) {
            const uploadButton = fieldNode.querySelector(`a.fileuploadlink`);
            uploadButton?.addEventListener("click", () => {
              if (mimetypeString !== "") setInputAcceptAttribute(mimetypeString);
            });
            addedListener = true;
          }
        }
      }
    });

    const parentContainerOfUploadButton = /** @type {Element} */ (
      fieldNode.querySelector(`#${fieldname}-linknew`)
    );
    observer.observe(parentContainerOfUploadButton, { childList: true });
  }

  // *** EXECUTION ***
  // - Create mutation observer for each file field on page
  for (const fieldname of Object.keys(settings)) {
    const fieldNode = document.querySelector(`tr[sq_id="${fieldname}"]`);
    if (!fieldNode) continue;
    const mimetypeString = settings[fieldname];
    registerFileValidation(fieldname, fieldNode, mimetypeString);
  }
}
