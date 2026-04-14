import { getModule } from "../shared/utils.js";
import { observeNetwork } from "../shared/network_observer.js";

(() => {
  getModule()
    .ajax("update_instrument_name")
    .then((res) => {
      console.log(res);
    });

  // Network Observers for handling instrument deletion.
  observeNetwork("delete_form.php", getModule());
})();
