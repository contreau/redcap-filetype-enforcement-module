import { getModule, devConsoleLog } from "../shared/utils.js";
import { observeNetwork } from "../shared/network_observer.js";

(async () => {
  const module = getModule();
  const updateCheckResponse = await module.ajax("update_instrument_name");
  devConsoleLog(updateCheckResponse);

  // Network Observers for handling instrument deletion.
  observeNetwork("delete_form.php", module);
})();
