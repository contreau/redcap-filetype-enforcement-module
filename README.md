# REDCap File Upload Field Type Enforcement • External Module

Allows survey/form builders to configure strict acceptable file (MIME) types for file upload fields, directly from the Online Designer's field editor modal. Restrictions are configured per-field and enforced client-side when end users attempt to upload files on live survey pages.

## Requirements

- REDCap External Modules Framework v16 or higher (REDCap 14.6.4+)

## Configuration

1. Enable the module for your project from the External Modules page.
2. Navigate to the module's project settings and check each file type you want to make available for enforcement. Only enabled file types will appear as options in the field editor modal.

!(Module configuration settings)[img/module-config.jpg]

## Usage

### Configuring a File Upload Field

1. Open the Online Designer and navigate to the instrument containing your file upload field(s).
2. Either create a new file upload field or click the edit (pencil) icon on any existing one to open the field editor modal.
3. A **File Types** panel will appear below the field label section. Check each file type you want to enforce for that field.
4. Click **Save** — the selected restrictions will be saved to the project settings, and you will now be able to see at a glance which file extensions are supported by that file field.

#### Example of a Field Editor Modal with Selectable File Types

!(Field editor modal showing selectable file types)[img/field-editor-modal.jpg]

#### Preview of a File Upload Field with Applied File Types

!(A preview of a file field in the editor after applying strict file types)[img/file-field-preview.jpg]

### On Survey Pages

When an end user attempts to upload a file on a live survey page, the module checks the file type against the configured MIME type(s) for that field. If not allowed, the file is rejected and a message is displayed prompting the end user to upload a supported file type.

!()[img/error-message-ui.jpg]

## Supported File Types

The following file types can be enabled in the module settings:

- PDF, Word, Excel, PowerPoint, RTF
- CSV, Text
- JPEG, PNG, TIFF, BMP, HEIC
- DICOM
- MP4, MP3

## Notes

- This module's settings are saved in the **project settings**.
- If a file upload field is changed to a different field type in the editor, its saved file type configuration is automatically removed.
- If an instrument or field is renamed or deleted in the Online Designer, the module automatically keeps its settings in sync.
- Changing the enabled file types in the module settings will reset all saved field configurations, as previously configured types may no longer be available.
