/**
 * Retrieves the JS module object name whose value was set as a cookie
 * @param {string} cookieName
 * @returns JS Module Object
 */
export function getModule(cookieName = "js_module_object") {
  const cookieValue = document.cookie
    .split(";")
    .filter((cookie) => cookie.includes(cookieName))[0]
    ?.split("=")[1];
  if (!cookieValue) throw new Error(`${cookieName} cookie not found.`);
  return cookieValue.split(".").reduce((acc, key) => acc[key], globalThis);
}

/**
 * Only logs to the console in a development environment (i.e. when localhost is detected).
 * @param {string} message
 */
export function devConsoleLog(message) {
  window.location.hostname === "localhost" ? console.log(message) : null;
}
