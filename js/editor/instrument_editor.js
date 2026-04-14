import { observeFieldEditorDialog } from "./field_editor_dialog.js";
import { observeNetwork } from "./network_observer.js";
import { FiletypeCheckboxesComponent } from "./FiletypeCheckboxesComponent.js";

/**
 * Runs on the instrument editor page.
 */
(() => {
  // Retrieve the JS module object name whose value was set as a cookie
  const getModule = () => {
    const cookieValue = document.cookie
      .split(";")
      .filter((cookie) => cookie.includes("js_module_object"))[0]
      ?.split("=")[1];

    if (!cookieValue) throw new Error("js_module_object cookie not found.");
    return cookieValue.split(".").reduce((acc, key) => acc[key], globalThis);
  };

  getModule()
    .ajax("update_instrument_name")
    .then((res) => {
      console.log(res);
    });

  // Mutation Observer for field editor dialog.
  observeFieldEditorDialog(FiletypeCheckboxesComponent, getModule());

  // Network Observer for handling field deletion.
  observeNetwork("delete_field.php", getModule());
})();
