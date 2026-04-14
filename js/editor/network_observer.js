/**
 * Network Observer for handling field and instrument deletion.
 * @param {string} phpFilename Target PHP file to observe
 * @param {*} module JS Module Object
 */
export async function observeNetwork(phpFilename, module) {
  let performanceObserverIsProcessing = false;
  const networkObserver = new PerformanceObserver(async (list) => {
    if (performanceObserverIsProcessing) return;

    const entries = list.getEntries();
    for (let i = 0; i < entries.length; i++) {
      const entry = entries[i];
      if (
        entry.initiatorType === "xmlhttprequest" &&
        entry.responseStatus === 200 &&
        entry.name.includes(phpFilename)
      ) {
        performanceObserverIsProcessing = true;

        if (phpFilename === "delete_field.php") {
          const res = await module.ajax("delete_filefield");
          console.log(`deleted field. (${res})`);
        } else if (phpFilename === "delete_form.php") {
          const res = await module.ajax("delete_instrument");
          console.log(`deleted instrument. (${res})`);
        }

        performanceObserverIsProcessing = false;
        break;
      }
    }
  });
  networkObserver.observe({ type: "resource" });
}
