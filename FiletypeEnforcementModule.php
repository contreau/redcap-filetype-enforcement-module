<?php

namespace ExternalModules\FiletypeEnforcementModule;

// These 2 lines should always be included and be the same in every (advanced) module
use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

class FiletypeEnforcementModule extends AbstractExternalModule
{
    public function redcap_project_home_page()
    {
        $settings = $this->getProjectSettings(PROJECT_ID);
        echo "<ul>";
        foreach ($settings as $key => $value) {
            if (str_contains($key, "enable_") && $value == 1) {
                echo "<li>$key: true</li>";
            }
        }
        echo "</ul>";

        echo "<ul>";
        foreach ($this::$mime_types as $key => $value) {
            echo "<li>$value[0]</li>";
        }
        echo "</ul>";
    }

    public static $mime_types = [
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
