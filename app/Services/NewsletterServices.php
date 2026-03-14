<?php

namespace App\Services;

use App\Models\Newsletter;

class NewsletterServices
{

    public function sendNewsletter($data)
    {
        $data = Newsletter::create(["email"=>$data]);
        return $data;
    }
}
