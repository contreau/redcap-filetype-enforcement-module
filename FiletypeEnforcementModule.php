<?php

namespace ExternalModules\FiletypeEnforcementModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
use REDCap;

class FiletypeEnforcementModule extends AbstractExternalModule
{
    // * NOTE: This only runs code on instrument builder pages.
    public function redcap_every_page_top()
    {
        // * Gets the instrument names or 'form name', which is used in the query string of the designer page URL (?pid={int}&page=form_name)
        // * Since there could be multiple instruments on a project, logic from here forward should apply iteratively to the instruments.
        // todo: Make the below logic its own function to call from this hook
        $instrument_names = array_keys(REDCap::getInstrumentNames());
        foreach ($instrument_names as $name) {
            if ($_GET['page'] == $name && !isset($_GET['s'])) { // the second check here prevents the ensuing code block from running on the live /surveys pages, which have a query parameter of 's'
                var_dump($this->getProjectFilefieldIds($name));
                // Set the JS Module Object name as a cookie for the JS script to grab
                $this->initializeJavascriptModuleObject();
                setcookie("js_module_object", $this->getJavascriptModuleObjectName());
                $this->includeJs("js/module_object.js");


                // Dev convenience function for showing enabled files at a glance
                $this->showEnabledFiles();
            }
        }
    }

    // * Handle calls from the JS script here
    public function redcap_module_ajax() {}

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
     * Temporary - See the enabled file types from the project settings at a glance.
     */
    protected function showEnabledFiles()
    {
        $settings = $this->getProjectSettings(PROJECT_ID);
        echo "<strong>Project Settings (Enabled File Types)</strong>";
        echo "<ul>";
        foreach ($settings as $key => $value) {
            if (str_contains($key, "enable_") && $value == 1) {
                echo "<li>$key: true</li>";
            }
        }
        echo "</ul>";
    }

    protected function includeJs($file)
    {
        echo '<script src="' . $this->getUrl($file) . '"></script>';
    }

    protected $mime_types = [
        "application/pdf" => [".pdf"],
        "text/csv" => [".csv"],
        "text/plain" => [".text", ".txt"],
        "image/jpeg" => [".jpeg", ".jpg"],
        "image/png" => [".png"],
        "image/tiff" => [".tiff", ".tif"],
        "application/msword" => [".doc"],
        "application/vnd.openxmlformats-officedocument.wordprocessingml.document" => [".docx"],
        "application/vnd.ms-excel" => [".xls"],
        "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" => [".xlsx"],
    ];
}
