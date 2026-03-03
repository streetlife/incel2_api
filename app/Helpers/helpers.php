<?php

  if (!function_exists('secured_path')) {
    function secured_path($path)
    {
        return url($path);
    }
    
}