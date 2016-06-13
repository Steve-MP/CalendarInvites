<?php

namespace Bolt\Extension\SteveEMBO\CalendarInvites;

use Bolt\Application;
use Bolt\BaseExtension;

class Extension extends BaseExtension
{
    public function initialize()
    {

        $this->app->mount('/ical/{type}/{id}', new Controllers\CalendarInvitesController($this->config)); 
        
    }

    public function getName()
    {
        return "CalendarInvites";
    }
}
