import { observeNetwork } from "./network_observer.js";

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

  getModule()
    .ajax("update_instrument_name")
    .then((res) => {
      console.log(res);
    });

  // Network Observers for handling instrument deletion.
  observeNetwork("delete_form.php", getModule());
})();
