(() => {
  // Retrieve the JS module object name whose value was set as a cookie
  const cookieValue = document.cookie
    .split(";")
    .filter((cookie) => cookie.includes("js_module_object_survey"))[0]
    .split("=")[1];
  const module = cookieValue.split(".").reduce((acc, key) => acc[key], globalThis);

  async function getFiletypeSettings(module) {
    const settings = await module.ajax("get_filefield_settings");
    console.log(settings);
    return settings;
  }

  getFiletypeSettings(module);
})();
