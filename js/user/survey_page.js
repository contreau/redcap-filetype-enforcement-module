import { getModule } from "../shared/utils.js";
import { applyFiletypeEnforcement } from "../shared/enforcement_script.js";
import { displayFiletypesAtGlance } from "../shared/filetypes_glance.js";

/**
 * Runs on survey pages (the end user's client).
 */
(async () => {
  const module = getModule("js_module_object_survey");
  const settings = await module.ajax("get_filefield_settings");
  if (settings !== null) {
    applyFiletypeEnforcement(settings);
    displayFiletypesAtGlance(null, settings);
  }
})();
