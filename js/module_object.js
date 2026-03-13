// Retrieve the JS module object name whose value was set as a cookie
const cookieValue = document.cookie
  .split(";")
  .filter((cookie) => cookie.includes("moduleObject"))[0]
  .split("=")[1];
const module = cookieValue.split(".").reduce((acc, key) => acc[key], globalThis);

console.log("Grabbed module: ", module);
