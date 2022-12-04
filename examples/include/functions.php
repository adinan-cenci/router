<?php 
function html($html) 
{
    return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" /><title>Document</title><style>body{background: black; color: white;}</style></head><body>'.$html.'</body></html>';
}

function userIsLoggedIn() 
{
    return false;
}