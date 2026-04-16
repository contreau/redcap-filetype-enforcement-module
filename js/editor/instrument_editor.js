import { getModule } from "../shared/utils.js";
import { observeFieldPreviewRerender } from "../shared/filetypes_glance.js";
import { observeFieldEditorDialog } from "../shared/field_editor_dialog.js";
import { observeNetwork } from "../shared/network_observer.js";
import { FiletypeCheckboxesComponent } from "../shared/FiletypeCheckboxesComponent.js";

/**
 * Runs on the instrument editor page.
 */
(() => {
  const module = getModule();

  module.ajax("update_instrument_name").then((res) => {
    window.location.hostname === "localhost" ? console.log(res) : null;
  });

  // Mutation Observer that shows filetypes at-a-glance per file field.
  observeFieldPreviewRerender(module);

  // Mutation Observer for field editor dialog.
  observeFieldEditorDialog(FiletypeCheckboxesComponent, module);

  // Network Observer for handling field deletion.
  observeNetwork("delete_field.php", module);
})();
