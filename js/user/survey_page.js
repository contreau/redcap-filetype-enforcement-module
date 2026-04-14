import { getModule } from "../utils.js";
import { applyFiletypeEnforcement } from "./enforcement_script.js";

/**
 * Runs on survey pages (the end user's client).
 */
(() => {
  const module = getModule("js_module_object_survey");
  async function getFilefieldSettings(module) {
    const settings = await module.ajax("get_filefield_settings");
    if (settings !== null) applyFiletypeEnforcement(settings);
  }

  getFilefieldSettings(module);
})();
