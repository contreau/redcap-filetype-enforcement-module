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
        $this->runModuleInInstrumentEditor(); // * Only executes on instrument editor pages of the Online Designer (i.e. where editors are creating / editing fields).
        $this->runModuleInInstrumentOptions($project_id); // * Only executes on the default view of the Online Designer, when it reads 'Data Collection Instruments'.

        // Dev convenience function for showing enabled files at a glance
        $this->showEnabledFiles();
    }

    protected function runModuleInInstrumentEditor(): void
    {
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

    protected function runModuleInInstrumentOptions(string $project_id): void
    {
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

    // * Handle ajax calls from JS here
    public function redcap_module_ajax($action, $payload, $project_id, $record, $instrument)
    {
        switch ($action) {
            case "get_enabled_filetypes": // called in FiletypeCheckboxesComponent.js
                return $this->getEnabledFiletypes($project_id);

            case "get_filefield_settings":  // called in survey_page.js
                return $this->getFilefieldSettings($project_id, $payload);

            case "get_enforced_filetypes": // called in FiletypeCheckboxesComponent.js
                return $this->getEnforcedFiletypes($project_id, $payload, $instrument);

            case "set_filefield_settings":
                $data = json_decode($payload, true);
                return json_encode($this->setFilefieldSettings($project_id, $instrument, $data));

            case "delete_filefield":
                return $this->deleteFilefield($project_id, $instrument);

            case "delete_instrument":
                return $this->deleteInstrument($project_id);

            case "update_fieldname":
                $data = json_decode($payload, true);
                return json_encode($this->updateFieldname($project_id, $instrument, $data));
        }
    }

    /**
     * * MODULE METHODS *
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

    protected function getFilefieldSettings(string $project_id, string $payload): array | null
    {
        // If it exists, send back the entire filefield settings object.
        $filefield_settings = $this->getProjectSetting("filefield_settings", $project_id);
        $field_name = $payload;
        if ($filefield_settings !== null) {
            return $filefield_settings;
        }
        return null;
    }

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

    protected function deleteFilefield(string $project_id, string $instrument): string
    {
        $current_fields = REDCap::getFieldNames([$instrument]);
        if ($current_fields == false) {
            // When the last field of an instrument is deleted from the editor and REDCap auto-deletes the instrument, this will just remove the instrument from the file field settings.
            return $this->deleteInstrument($project_id);
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

    protected function deleteInstrument(string $project_id): string
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
     * @param string $field_name
     * The unique identifier of the file field. 
     * @param array $filetypes
     * An array of the filetypes to be enforced, saved from the UI checkboxes.
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
     * Returns the sq_id values for all file fields in the project.
     * @param string $instrument_name 
     * The 'form name' or unique name of the instrument.
     * @return array Associated Array of the sq_id strings.
     */
    protected function getProjectFilefieldIds(string $instrument_name): array
    {
        $field_names = REDCap::getFieldNames($instrument_name);
        return array_values(array_filter($field_names, fn($field_name) => REDCap::getFieldType($field_name) == "file"));
    }

    /**
     * Temporary - See the file field settings at a glance.
     */
    protected function showEnabledFiles(): void
    {
        $settings = $this->getProjectSetting("filefield_settings", PROJECT_ID);
        var_dump($settings);
        // * representation of filefield data saved in the project/module settings
        // $filefield_settings = [
        //     "instrument_1" => [
        //         "instrument_1_field_1_name" => "mimetypes",
        //         "instrument_1_field_2_name" => "mimetypes",
        //         etc.
        //     ],
        //     "instrument_2" => [
        //         "instrument_2_field_1_name" => "mimetypes",
        //         "instrument_2_field_2_name" => "mimetypes", 
        //         etc.
        //     ]
        // ];
    }

    protected function includeJs($file)
    {
        echo '<script type="module" src="' . $this->getUrl($file) . '"></script>';
    }
}
