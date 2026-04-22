<?php

namespace GWU\FiletypeEnforcementModule;

const DEFAULT_FILETYPES = [
    "pdf" => [
        "display_name" => "PDF",
        "mimetype" => "application/pdf",
        "extensions" => [".pdf"]
    ],
    "csv" => [
        "display_name" => "CSV",
        "mimetype" => "text/csv",
        "extensions" => [".csv"]
    ],
    "text" => [
        "display_name" => "Text",
        "mimetype" => "text/plain",
        "extensions" => [".text", ".txt"]
    ],
    "jpeg" => [
        "display_name" => "JPEG",
        "mimetype" => "image/jpeg",
        "extensions" => [".jpeg", ".jpg"]
    ],
    "png" => [
        "display_name" => "PNG",
        "mimetype" => "image/png",
        "extensions" => [".png"]
    ],
    "tiff" => [
        "display_name" => "TIFF",
        "mimetype" => "image/tiff",
        "extensions" => [".tiff", ".tif"]
    ],
    "word" => [
        "display_name" => "Word",
        "mimetype" => "application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document",
        "extensions" => [".doc, .docx"]
    ],
    "excel" => [
        "display_name" => "Excel",
        "mimetype" => "application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        "extensions" => [".xls, .xlsx"]
    ],
    "powerpoint" => [
        "display_name" => "PowerPoint",
        "mimetype" => "application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation",
        "extensions" => [".ppt, .pptx"]
    ],
    "rtf" => [
        "display_name" => "RTF",
        "mimetype" => "application/rtf",
        "extensions" => [".rtf"]
    ],
    "dicom" => [
        "display_name" => "DICOM",
        "mimetype" => "application/dicom",
        "extensions" => [".dcm"]
    ],
    "bmp" => [
        "display_name" => "BMP",
        "mimetype" => "image/bmp",
        "extensions" => [".bmp"]
    ],
    "heic" => [
        "display_name" => "HEIC",
        "mimetype" => "image/heic",
        "extensions" => [".heic"]
    ],
    "mp4" => [
        "display_name" => "MP4",
        "mimetype" => "video/mp4",
        "extensions" => [".mp4"]
    ],
    "mp3" => [
        "display_name" => "MP3",
        "mimetype" => "audio/mpeg",
        "extensions" => [".mp3"]
    ],
];
