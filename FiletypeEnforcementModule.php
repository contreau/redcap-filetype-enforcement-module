<?php

namespace ExternalModules\FiletypeEnforcementModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
use REDCap;

require_once "default_filetypes.php";

class FiletypeEnforcementModule extends AbstractExternalModule
{
    /**
     * * REDCap HOOKS & ACCOMPANYING FUNCTIONS *
     */

    public function redcap_every_page_top($project_id)
    {
        $this->runModuleInInstrumentEditor($project_id); // * Only executes on instrument editor pages of the Online Designer (i.e. where editors are creating / editing fields).
        $this->runModuleInInstrumentOptions($project_id); // * Only executes on the default view of the Online Designer, when it reads 'Data Collection Instruments'.
    }

    /**
     * Resets file field settings to synchronizes with the enabled filetypes when changed in the module settings.
     * @param string $project_id
     */
    protected function synchronizeEnabledFiletypes(string $project_id): void
    {
        $enabled_filetypes = $this->getEnabledFiletypes($project_id);
        $enabled_keys = array_column($enabled_filetypes, "display_name"); // or whichever key is most stable

        $last_known = $this->getProjectSetting("last_known_enabled_filetypes", $project_id);

        if ($last_known === null) {
            $this->setProjectSetting("last_known_enabled_filetypes", $enabled_keys);
            return;
        }

        if (array_diff($last_known, $enabled_keys) || array_diff($enabled_keys, $last_known)) {
            $this->setProjectSetting("last_known_enabled_filetypes", $enabled_keys);

            $filefield_settings = $this->getProjectSetting("filefield_settings", $project_id);
            if ($filefield_settings !== null) {
                foreach ($filefield_settings as $instrument => $value) {
                    $filefield_settings[$instrument] = [];
                }
                $this->setProjectSetting("filefield_settings", $filefield_settings);
            }
        }
    }

    /**
     * Enables interaction with this module on instrument editor pages.
     * @param string $project_id
     */
    protected function runModuleInInstrumentEditor(string $project_id): void
    {
        $this->synchronizeEnabledFiletypes($project_id);
        $instrument_names = array_keys(REDCap::getInstrumentNames()); // Gets the instrument names or 'form name', which is used in the query string of the designer page URL (?pid={int}&page=form_name)

        // * Since there could be multiple instruments on a project, the module is applied iteratively.
        foreach ($instrument_names as $name) {
            if ($_GET['page'] == $name && !isset($_GET['s'])) { // the second check here prevents the ensuing code block from running on the live /surveys pages, which have a query parameter of 's'
                // Set the JS Module Object name as a cookie for the JS script to grab
                $this->initializeJavascriptModuleObject();
                setcookie("js_module_object", $this->getJavascriptModuleObjectName());
                $this->includeJs("js/editor/instrument_editor.js");
            }
        }
    }

    /**
     * Enables interaction with this module on the base Online Designer page.
     * @param string $project_id
     */
    protected function runModuleInInstrumentOptions(string $project_id): void
    {
        $this->synchronizeEnabledFiletypes($project_id);
        if (
            str_contains($_SERVER["SCRIPT_NAME"], "/Design/online_designer.php") &&
            $_SERVER["QUERY_STRING"] === "pid=$project_id"
        ) {
            $this->initializeJavascriptModuleObject();
            setcookie("js_module_object", $this->getJavascriptModuleObjectName());
            $this->includeJs("js/editor/instrument_options.js");
        }
    }

    // * Runs on survey pages after full page render
    public function redcap_survey_page()
    {
        $this->initializeJavascriptModuleObject();
        setcookie("js_module_object_survey", $this->getJavascriptModuleObjectName());
        $this->includeJs("js/user/survey_page.js");
    }

    // * Handlers for ajax calls from JS
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

            case "sync_filefield":
                return $this->syncFilefield($project_id, $instrument);

            case "sync_instrument":
                return $this->syncInstrument($project_id);

            case "update_fieldname":
                $data = json_decode($payload, true);
                return json_encode($this->updateFieldname($project_id, $instrument, $data));

            case "update_instrument_name":
                return $this->updateInstrumentName($project_id);

            case "remove_filefield":
                return $this->removeFilefield($project_id, $instrument, $payload);
        }
    }

    /**
     * * MODULE METHODS *
     */

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
     * Synchronizes data at the file field key level between the file field settings and the UI.
     * @param string $project_id
     * @param string $instrument
     */
    protected function syncFilefield(string $project_id, string $instrument): string
    {
        $current_fields = REDCap::getFieldNames([$instrument]);
        if ($current_fields == false) {
            // When the last field of an instrument is deleted from the editor and REDCap auto-deletes the instrument, this will just remove the instrument from the file field settings.
            return $this->syncInstrument($project_id);
        }

        $filefield_settings = $this->getProjectSetting("filefield_settings", $project_id);
        $instrument_settings = $filefield_settings[$instrument];
        $deleted_field = "";

        foreach ($instrument_settings as $key => $value) {
            if (!in_array($key, $current_fields)) {
                unset($instrument_settings[$key]);
                $deleted_field = $key;
            }
        }

        $filefield_settings[$instrument] = $instrument_settings;
        $this->setProjectSetting("filefield_settings", $filefield_settings);
        return $deleted_field;
    }

    /**
     * Synchronizes data at the instrument key level between the file field settings and the UI.
     * @param string $project_id
     */
    protected function syncInstrument(string $project_id): string
    {
        $current_instruments = array_keys(REDCap::getInstrumentNames());
        $filefield_settings = $this->getProjectSetting("filefield_settings", $project_id);
        $deleted_instrument = "";

        foreach ($filefield_settings as $instrument_name => $data) {
            if (!in_array($instrument_name, $current_instruments)) {
                unset($filefield_settings[$instrument_name]);
                $deleted_instrument = $instrument_name;
            }
        }

        $this->setProjectSetting("filefield_settings", $filefield_settings);
        return $deleted_instrument;
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
     * Runs when either the instrument list or editor pages load, checking via diff that the instrument names are in sync between the file field settings and the base application.
     * @param string $project_id
     */
    protected function updateInstrumentName(string $project_id): string
    {
        $current_instruments = array_keys(REDCap::getInstrumentNames());
        $filefield_settings = $this->getProjectSetting("filefield_settings", $project_id);
        $deprecated_instrument_name = "";
        $new_instrument_name = "";
        $data = null;

        // Find the deprecated key (in settings but not in current instruments)
        foreach ($filefield_settings as $instrument_name => $value) {
            if (!in_array($instrument_name, $current_instruments)) {
                $deprecated_instrument_name = $instrument_name;
                $data = $value;
                unset($filefield_settings[$instrument_name]);
                break;
            }
        }

        // Find the new key (in current instruments but not in settings)
        foreach ($current_instruments as $name) {
            if (!array_key_exists($name, $filefield_settings)) {
                $new_instrument_name = $name;
                break;
            }
        }

        if ($deprecated_instrument_name !== "" && $new_instrument_name !== "") {
            $filefield_settings[$new_instrument_name] = $data ?? [];
            $this->setProjectSetting("filefield_settings", $filefield_settings);
            return "updated instrument name: $deprecated_instrument_name -> $new_instrument_name";
        }

        return "no rename detected";
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
            return "field not found: $field_name";
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
