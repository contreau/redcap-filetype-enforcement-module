export class FiletypeCheckboxesComponent extends HTMLElement {
  static tagName = "filetype-checkboxes";

  constructor(module) {
    super();
    this.module = module;
    this.enabledFiletypes = null;
  }

  #initialized = false;

  async connectedCallback() {
    console.log("connected.");
    const shadow = this.shadowRoot ?? this.attachShadow({ mode: "open" });

    if (!this.#initialized) {
      // Create element if not already made
      this.#initialized = true;

      // Fetch enabled filetypes for the project
      this.enabledFiletypes = await this.getEnabledFiletypes(this.module);
      const checkboxes = this.generateCheckboxes(this.enabledFiletypes);

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
    console.log("disconnected.");
  }

  /**
   * Fetches the filetypes enabled in the module settings.
   * @returns {Promise<Record<string, string[]>[]>}
   */
  async getEnabledFiletypes(module) {
    // todo: handle possibility of ajax failing, fallback to a sessionStorage value of the enabled filetypes - prob worth caching anyway.
    const enabledFiletypes = await module.ajax("get_enabled_filetypes"); // resolves to array
    return enabledFiletypes;
  }

  /**
   * Generates the checkbox HTML for the filetypes enabled in the module settings.
   * @param {Promise<Record<string, string[]>[]>} filetypes
   * @returns {string}
   */
  generateCheckboxes(filetypes) {
    let checkboxesString = ``;
    for (const filetype of filetypes) {
      const name = filetype.display_name.toLowerCase();
      checkboxesString += `<div><input type="checkbox" id="${name}" name="${name}" /><label for="${name}">${filetype.display_name}</label></div>`;
    }
    return checkboxesString;
  }
}
