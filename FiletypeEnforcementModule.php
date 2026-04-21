<?php

/**
 * REDCap External Module: File Upload Field Type Enforcement
 * Easily configure strict filetypes for file upload fields, directly from the Online Designer's field editor modal.
 * @author Conor Kelley, The George Washington University Academic Medical Enterprise
 */

namespace GWU\FiletypeEnforcementModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
use REDCap;

require_once "default_filetypes.php";

class FiletypeEnforcementModule extends AbstractExternalModule
{
    /**
     * * REDCap HOOKS
     */

    public function redcap_every_page_top($project_id)
    {
        $in_onlineDesigner = str_contains($_SERVER['SCRIPT_NAME'], "/Design/online_designer.php") &&
            $_GET["pid"] === $project_id;
        $in_external_modules_manager = str_contains($_SERVER['SCRIPT_NAME'], "/ExternalModules/manager/project.php") && $_GET['pid'] === $project_id;

        if ($in_onlineDesigner) $this->runModule($project_id);
        if ($in_external_modules_manager) $this->synchronize($project_id);
    }

    public function redcap_survey_page()
    {
        $this->initializeJavascriptModuleObject();
        setcookie("js_module_object_survey", $this->getJavascriptModuleObjectName());
        $this->includeJs("js/user/survey_page.js");
    }

    public function redcap_module_ajax($action, $payload, $project_id, $record, $instrument)
    {
        switch ($action) {
            case "get_enabled_filetypes":
                return $this->getEnabledFiletypes($project_id);

            case "get_filefield_settings":
                return $this->getFilefieldSettings($project_id, $instrument);

            case "get_enforced_filetypes":
                return $this->getEnforcedFiletypes($project_id, $payload, $instrument);

            case "set_filefield_settings":
                $data = json_decode($payload, true);
                return json_encode($this->setFilefieldSettings($project_id, $instrument, $data));

            case "synchronize":
                return $this->synchronize($project_id);

            case "update_fieldname":
                $data = json_decode($payload, true);
                return json_encode($this->updateFieldname($project_id, $instrument, $data));

            case "remove_filefield":
                return $this->removeFilefield($project_id, $instrument, $payload);
        }
    }

    /**
     * * MODULE METHODS *
     */

    /**
     * Performs a full synchronization of the filefield settings against the current state of the project.
     * Checks for: enabled filetype changes, deleted/renamed instruments, and deleted fields.
     * @param string $project_id
     */
    protected function synchronize(string $project_id): void
    {
        $filefield_settings = $this->getProjectSetting("filefield_settings", $project_id);
        if (!$filefield_settings) return;

        $current_instruments = array_keys(REDCap::getInstrumentNames());

        // Check if enabled filetypes have changed — reset all field settings if so
        $enabled_filetypes = $this->getEnabledFiletypes($project_id);
        $enabled_keys = array_column($enabled_filetypes, "display_name");
        $last_known = $this->getProjectSetting("last_known_enabled_filetypes", $project_id);

        if ($last_known === null) {
            $this->setProjectSetting("last_known_enabled_filetypes", $enabled_keys);
        } else if (array_diff($last_known, $enabled_keys) || array_diff($enabled_keys, $last_known)) {
            $this->setProjectSetting("last_known_enabled_filetypes", $enabled_keys);
            foreach ($filefield_settings as $instrument => $value) {
                $filefield_settings[$instrument] = [];
            }
            $this->setProjectSetting("filefield_settings", $filefield_settings);
            return; // no point diffing instruments/fields if we just reset everything
        }

        // Diff instruments — remove any that no longer exist
        foreach ($filefield_settings as $instrument_name => $fields) {
            if (!in_array($instrument_name, $current_instruments)) {
                unset($filefield_settings[$instrument_name]);

                // Check if this looks like a rename rather than a deletion
                $new_name = null;
                foreach ($current_instruments as $name) {
                    if (!array_key_exists($name, $filefield_settings)) {
                        $new_name = $name;
                        break;
                    }
                }

                if ($new_name !== null) {
                    $filefield_settings[$new_name] = $fields;
                }
                continue;
            }

            // Diff fields within each instrument — remove any that no longer exist
            $current_fields = REDCap::getFieldNames([$instrument_name]);
            if ($current_fields) {
                foreach ($fields as $field_name => $value) {
                    if (!in_array($field_name, $current_fields)) {
                        unset($filefield_settings[$instrument_name][$field_name]);
                    }
                }
            }
        }

        $this->setProjectSetting("filefield_settings", $filefield_settings);
    }

    protected function runModule(string $project_id): void
    {
        $this->synchronize($project_id);
        $is_instrument_page = in_array($_GET['page'], array_keys(REDCap::getInstrumentNames()));
        $this->initializeJavascriptModuleObject();
        setcookie("js_module_object", $this->getJavascriptModuleObjectName());

        if ($is_instrument_page) {
            $this->includeJs("js/editor/instrument_editor.js");
        } else {
            $this->includeJs("js/editor/instrument_options.js");
        }
    }

    /**
     * Fetches the file types that have been enabled from the module settings.
     * @param string $project_id
     */
    protected function getEnabledFiletypes(string $project_id): array
    {
        $enabled_files = [];
        foreach ($this->getProjectSettings($project_id) as $key => $value) {
            if (str_contains($key, "enable_") && $value == 1) {
                $file_abbrev = explode("enable_", $key)[1];
                array_push($enabled_files, DEFAULT_FILETYPES[$file_abbrev]);
            }
        }
        return $enabled_files;
    }

    /**
     * Fetches the file field settings for the provided instrument.
     * @param string $project_id
     * @param string $instrument
     * @return array|null The settings array or null if nothing is configured.
     */
    protected function getFilefieldSettings(string $project_id, string $instrument): array | null
    {
        $filefield_settings = $this->getProjectSetting("filefield_settings", $project_id);
        if ($filefield_settings === null || !isset($filefield_settings[$instrument])) {
            return null;
        }

        $result = [];
        foreach ($filefield_settings[$instrument] as $field_name => $mimetype_string) {
            if (empty($mimetype_string)) {
                $result[$field_name] = [
                    "mimetypes" => "",
                    "extensions" => []
                ];
                continue;
            }

            $extensions = [];
            foreach (DEFAULT_FILETYPES as $type) {
                foreach (explode(",", $mimetype_string) as $mime) {
                    if (str_contains($type["mimetype"], trim($mime))) {
                        $extensions = array_merge($extensions, $type["extensions"]);
                        break;
                    }
                }
            }
            $result[$field_name] = [
                "mimetypes" => $mimetype_string,
                "extensions" => array_values($extensions)
            ];
        }
        return $result;
    }

    /**
     * Fetches the file types to be enforced for a provided field name.
     * @param string $project_id
     * @param string $payload Field name data from the client.
     * @param string $instrument
     * @return array|null An array of the enforced file types for the given field, or null if none are saved to the field.
     */
    protected function getEnforcedFiletypes(string $project_id, string $payload, string $instrument): array | null
    {
        // Send back an array of the filetype ids (lowercase keys of DEFAULT_FILETYPES) that are currently saved to be enforced.
        $filefield_settings = $this->getProjectSetting("filefield_settings", $project_id);
        $field_name = $payload;

        if (!$filefield_settings || !isset($filefield_settings[$instrument]) || empty($filefield_settings[$instrument])) {
            return null;
        }

        if (array_key_exists($field_name, $filefield_settings[$instrument]) && $filefield_settings[$instrument][$field_name] !== '') {
            $mimetypes = explode(",", $filefield_settings[$instrument][$field_name]);
            $file_ids = [];
            foreach (DEFAULT_FILETYPES as $key => $value) {
                foreach ($mimetypes as $type) {
                    if (str_contains(DEFAULT_FILETYPES[$key]["mimetype"], $type) && !in_array($key, $file_ids)) {
                        array_push($file_ids, $key);
                    }
                }
            }
            return $file_ids;
        }
        return null;
    }

    /**
     * Updates an instrument's settings.
     * @param string $project_id
     * @param string $instrument
     * @param array|null $settings The data to be the value of an instrument key in the file field settings associated array. 
     */
    protected function updateInstrumentSettings(string $project_id, string $instrument, array | null $settings): void
    {
        $filefield_settings = $this->getProjectSetting("filefield_settings", $project_id) ?? [];
        if ($settings == null) {
            $filefield_settings[$instrument] = null;
        } else {
            $filefield_settings[$instrument] = $settings;
        }
        $this->setProjectSetting("filefield_settings", $filefield_settings);
    }

    /**
     * Updates a field name in the file field settings when done so in the UI.
     * @param string $project_id
     * @param string $instrument
     * @param array $data The field data from the client.
     * @return array The up-to-date file field settings associated array.
     */
    protected function updateFieldname(string $project_id, string $instrument, array $data): array
    {
        $field_name = $data['field_name'];
        $deprecated_field_name = $data['deprecated_field_name'];
        $filetypes = $data['enforced_filetypes'];

        $filefield_settings = $this->getProjectSetting("filefield_settings", $project_id) ?? [];
        unset($filefield_settings[$instrument][$deprecated_field_name]);
        $this->updateInstrumentSettings($project_id, $instrument, $filefield_settings[$instrument]);

        return $this->setFilefieldSettings($project_id, $instrument, [
            'field_name' => $field_name,
            'enforced_filetypes' => $filetypes
        ]);
    }

    /**
     * Saves the enforced filetypes in the project settings as configured in the UI.
     * @param string $project_id
     * @param string $instrument
     * The current instrument name.
     * @param array $data
     * The field data sent from the client to be saved.
     * @return array The up-to-date file field settings associated array.
     */
    protected function setFilefieldSettings(string $project_id, string $instrument, array $data): array
    {
        $field_name = $data['field_name'];
        $filetypes = $data['enforced_filetypes'];

        $mimetype_string = implode(",", array_map(
            fn($type) => DEFAULT_FILETYPES[$type]["mimetype"],
            $filetypes
        ));

        $filefield_settings = $this->getProjectSetting("filefield_settings", $project_id) ?? [];
        $filefield_settings[$instrument][$field_name] = $mimetype_string;
        $this->updateInstrumentSettings($project_id, $instrument, $filefield_settings[$instrument]);

        return $filefield_settings;
    }

    /**
     * Removes a specified field name from the file field settings. Runs when the field dialog is saved and the saved field type is NOT a file.
     * @param string $project_id
     * @param string $instrument
     * @param string $field_name The file field to be directly removed.
     */
    protected function removeFilefield(string $project_id, string $instrument, string $field_name): string
    {
        $filefield_settings = $this->getProjectSetting("filefield_settings", $project_id);
        if (!isset($filefield_settings[$instrument][$field_name])) {
            return "Field not found: $field_name";
        }
        unset($filefield_settings[$instrument][$field_name]);
        $this->setProjectSetting("filefield_settings", $filefield_settings);
        return $field_name;
    }

    /**
     * Injects JavaScript.
     * @param string $filepath
     */
    protected function includeJs(string $filepath): void
    {
        echo '<script type="module" src="' . $this->getUrl($filepath) . '"></script>';
    }
}
