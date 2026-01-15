<?php

function FeedbackLabel(string $message, string $type = "default") {
    if (!$message) return "";
    
    $typeClasses = [
        "default" => "text-white",
        "success" => "text-green-600",
        "error"   => "text-red-400",
        "warning" => "text-yellow-400",
    ];
    
    $class = $typeClasses[$type] ?? $typeClasses["default"];
    return "<p class='$class font-semibold text-sm'>$message</p>";
}
