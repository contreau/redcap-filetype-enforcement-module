export class FiletypeCheckboxesComponent extends HTMLElement {
  static tagName = "filetype-checkboxes";

  constructor(module, fieldname) {
    super();
    this.module = module;
    this.fieldname = fieldname;
  }

  #initialized = false;
  #enabledFiletypes;

  async connectedCallback() {
    console.log(`connected (${this.fieldname})`);
    const shadow = this.shadowRoot ?? this.attachShadow({ mode: "open" });

    this.preexistingFieldname = "";
    if (this.fieldname !== "") this.preexistingFieldname = this.fieldname;

    if (!this.#initialized) {
      // Create element if not already made
      this.#initialized = true;

      // Fetch enabled filetypes for the project
      this.#enabledFiletypes = await this.#getEnabledFiletypes(this.module);
      const checkboxes = await this.#generateCheckboxes(this.#enabledFiletypes);

      const wc = document.createElement("template");
      wc.innerHTML = `
      <div id="filetype-checkboxes-wrapper">
        <strong>Filetypes</strong>
        <div id="filetype-checkboxes-explanation">Select all filetypes to be enforced for this upload.</div>
        <div id="filetype-checkboxes">${checkboxes}</div>
      </div>

      <style>
        div#filetype-checkboxes-wrapper {
          box-sizing: border-box;
          border: 1px solid rgb(211, 211, 211);
          padding: 6px 8px;
          margin-block: 8px;
          display: block;
          max-width: 725px;
        }
        div#filetype-checkboxes-explanation {
          color: #808080;
          font-size: 12px;
          font-family: tahoma;
          padding-block: 5px;
        }
        div#filetype-checkboxes {
          display: grid;
          grid-template-columns: repeat(3, 0.15fr);
          div {
            display: flex;
            align-items: center;
          }
          label {
            font-size: 13px;
            padding-left: 5px;
          }
        } 
      </style>
    `;

      shadow.append(wc.content.cloneNode(true));
    }
  }

  disconnectedCallback() {
    console.log(`disconnected (${this.fieldname})`);
  }

  setFieldname(fieldname) {
    this.fieldname = fieldname;
  }

  /**
   * Generates the checkbox HTML for the filetypes enabled in the module settings.
   * @param {Record<string, string[]>[]} filetypes
   * @returns {string}
   */
  async #generateCheckboxes(filetypes) {
    const enforcedTypes = await this.#getEnforcedFiletypes(this.module);
    let checkboxesString = ``;
    for (const filetype of filetypes) {
      const name = filetype.display_name.toLowerCase();
      if (enforcedTypes !== null && enforcedTypes.includes(name)) {
        checkboxesString += `<div><input type="checkbox" id="${name}" name="${name}" checked /><label for="${name}">${filetype.display_name}</label></div>`;
      } else {
        checkboxesString += `<div><input type="checkbox" id="${name}" name="${name}" /><label for="${name}">${filetype.display_name}</label></div>`;
      }
    }
    return checkboxesString;
  }

  // ** ASYNC METHODS **

  /**
   * Fetches the filetypes enabled in the module settings.
   * @returns {Promise<Record<string, string[]>[]>}
   */
  async #getEnabledFiletypes(module) {
    const enabledFiletypes = await module.ajax("get_enabled_filetypes"); // resolves to array
    return enabledFiletypes;
  }

  /**
   * Saves the checked filetypes in the module settings. Called from field_editor.js
   * @returns {Promise<Record<string, string | string[]>>}
   */
  async saveEnforcedFiletypes(fieldnameInputValue) {
    const checkedFiletypes = Array.from(this.shadowRoot.querySelectorAll("input[type='checkbox'"))
      .filter((box) => box.checked)
      .map((box) => box.id);

    if (fieldnameInputValue !== this.preexistingFieldname) {
      this.fieldname = fieldnameInputValue;
      return await this.#updateFieldname(this.module, checkedFiletypes);
    } else {
      return await this.#setFilefieldSettings(this.module, checkedFiletypes);
    }
  }

  /**
   * Fetches the filetypes already applied to fields, if they exist.
   * @returns {Promise<string[]> | Promise<null>} An array of the enforced file ids for the field name that are saved in the module settings.
   */
  async #getEnforcedFiletypes(module) {
    if (this.fieldname === "") {
      return null;
    } else {
      const enforcedFiletypes = await module.ajax("get_enforced_filetypes", this.fieldname);
      return enforcedFiletypes;
    }
  }

  /**
   * Updates a field name and its data in the filefield settings.
   * @param {*} module The JS module object.
   * @param {string[]} checkedFiletypes The checked boxes of the selected filetypes.
   * @returns {Promise<Record>} The current state of the saved data indicating the updated fieldname.
   */
  async #updateFieldname(module, checkedFiletypes) {
    const payload = {
      field_name: this.fieldname,
      deprecated_field_name: this.preexistingFieldname,
      enforced_filetypes: checkedFiletypes,
    };
    console.log(`updating field name: ${this.preexistingFieldname} -> ${this.fieldname}`);
    const response = await module.ajax("update_fieldname", JSON.stringify(payload));
    return response;
  }

  /**
   * Saves a field name and its data to the filefield settings.
   * @param {*} module The JS module object.
   * @param {string[]} checkedFiletypes The checked boxes of the selected filetypes.
   * @returns {Promise<Record>} The current state of the filefield settings.
   */
  async #setFilefieldSettings(module, checkedFiletypes) {
    const payload = {
      field_name: this.fieldname, // i.e. file_upload
      enforced_filetypes: checkedFiletypes, // data
    };
    const response = await module.ajax("set_filefield_settings", JSON.stringify(payload));
    return response;
  }
}
