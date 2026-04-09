import { observeFieldEditorDialog } from "./field_editor_dialog.js";
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

  // Mutation Observer for field editor dialog.
  observeFieldEditorDialog(FiletypeCheckboxesComponent, getModule());

  // Network Observer for handling field deletion.
  let performanceObserverIsProcessing = false;
  const networkObserver = new PerformanceObserver(async (list) => {
    if (performanceObserverIsProcessing) return;

    const entries = list.getEntries();
    for (let i = 0; i < entries.length; i++) {
      const entry = entries[i];
      if (
        entry.initiatorType === "xmlhttprequest" &&
        entry.responseStatus === 200 &&
        entry.name.includes("delete_field.php")
      ) {
        performanceObserverIsProcessing = true;
        const module = getModule();
        const res = await module.ajax("delete_filefield");
        console.log("deleted field.", res);
        performanceObserverIsProcessing = false;
        break;
      }
    }
  });
  networkObserver.observe({ type: "resource" });
})();
