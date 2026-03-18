import { observeFieldEditor } from "./field_editor.js";

/**
 * Runs on the instrument editor page.
 */
(() => {
  // Retrieve the JS module object name whose value was set as a cookie
  const cookieValue = document.cookie
    .split(";")
    .filter((cookie) => cookie.includes("js_module_object"))[0]
    .split("=")[1];
  const module = cookieValue.split(".").reduce((acc, key) => acc[key], globalThis);

  /**
   * @returns {Promise<Record<string, string[]>[]>}
   */
  async function getEnabledFiletypes() {
    const enabledFiletypes = await module.ajax("get_enabled_filetypes"); // resolves to array
    console.log(enabledFiletypes);
    return enabledFiletypes;
  }

  getEnabledFiletypes();
  observeFieldEditor();
})();
