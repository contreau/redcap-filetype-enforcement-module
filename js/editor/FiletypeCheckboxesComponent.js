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
    // Todo: handle possibility of ajax failing, fallback to a sessionStorage value of the enabled filetypes - prob worth caching anyway.
    const enabledFiletypes = await module.ajax("get_enabled_filetypes"); // resolves to array
    return enabledFiletypes;
  }

  /**
   * Saves the checked filetypes in the module settings. Called from field_editor.js
   * @returns {Record<string, string | string[]>}
   */
  async saveEnforcedFiletypes() {
    const checkedFiletypes = Array.from(this.shadowRoot.querySelectorAll("input[type='checkbox'"))
      .filter((box) => box.checked)
      .map((box) => box.id);

    const payload = {
      field_name: this.fieldname, // i.e. file_upload
      enforced_filetypes: checkedFiletypes, // data
    };

    console.log(payload);

    const response = await this.module.ajax("set_filefield_settings", JSON.stringify(payload));
    return response;
  }

  /**
   * Fetches the filetypes already applied to fields, if they exist.
   * @returns {Promise<string[]> | Promise<null>} An array of the enforced file ids for the field name that are saved in the module settings.
   */
  async #getEnforcedFiletypes(module) {
    const enforcedFiletypes = await module.ajax("get_enforced_filetypes", this.fieldname);
    return enforcedFiletypes;
  }
}
