<?php

namespace Bolt\Extension\SteveEMBO\CalendarInvites;

use Bolt\Application;
use Bolt\BaseExtension;

class Extension extends BaseExtension
{
    public function initialize()
    {
    	//define a new route to create the ical file for the chosen content type (variable 1) with the chosen ID (variable 2)
        $this->app->mount('/ical/{type}/{id}', new Controllers\CalendarInvitesController($this->config)); 
        
        //define a new twig function to generate the ical link in your templates
        //e.g. {{ createIcalLink(event.contenttype.singular_name, event.id) }}
        $this->addTwigFunction('createIcalLink','twigCreateIcalLink');
    }

    /**
     * Twig function {{ createIcalLink("contentType", "contentId") }} in SteveEMBO
     */
    public function twigCreateIcalLink($contentType, $contentId){

    	//get the base url for this site
    	$baseUrl = $this->app["resources"]->getUrl("rooturl");

    	//generate the Ical link
    	$link = $baseUrl . 'ical/'. $contentType . '/' . $contentId;

    	return $link;


    }

    public function getName()
    {
        return "CalendarInvites";
    }
}
