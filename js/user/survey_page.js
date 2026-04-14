import { applyFiletypeEnforcement } from "./enforcement_script.js";

/**
 * Runs on survey pages (the end user's client).
 */
(() => {
  // Retrieve the JS module object name whose value was set as a cookie
  function getModule() {
    const cookieValue = document.cookie
      .split(";")
      .filter((cookie) => cookie.includes("js_module_object_survey"))[0]
      ?.split("=")[1];

    if (!cookieValue) throw new Error("js_module_object_survey cookie not found.");
    return cookieValue.split(".").reduce((acc, key) => acc[key], globalThis);
  }

  const module = getModule();
  async function getFilefieldSettings(module) {
    const settings = await module.ajax("get_filefield_settings");
    if (settings !== null) applyFiletypeEnforcement(settings);
  }

  getFilefieldSettings(module);
})();
