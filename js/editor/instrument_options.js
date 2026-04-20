import { getModule } from "../shared/utils.js";
import { observeNetwork } from "../shared/network_observer.js";

(() => {
  const module = getModule();

  // Network Observers for handling instrument deletion.
  observeNetwork("delete_form.php", module);
})();
