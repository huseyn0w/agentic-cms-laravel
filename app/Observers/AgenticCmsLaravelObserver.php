<?php

/**
 * AgenticCms-Laravel
 * File: AgenticCmsLaravelObserver.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 01.12.2019
 */

namespace App\Observers;

class AgenticCmsLaravelObserver
{
    protected $locale;

    protected $request;

    public function __construct()
    {
        $this->locale = get_current_lang();
        $this->request = app('request');
    }
}
