import { getModule } from "../shared/utils.js";
import { observeFieldEditorDialog } from "../shared/field_editor_dialog.js";
import { observeNetwork } from "../shared/network_observer.js";
import { FiletypeCheckboxesComponent } from "../shared/FiletypeCheckboxesComponent.js";

/**
 * Runs on the instrument editor page.
 */
(() => {
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
