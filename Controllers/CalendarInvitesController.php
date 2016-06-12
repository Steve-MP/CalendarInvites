<?php

namespace Bolt\Extension\SteveEMBO\CalendarInvites\Controllers;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;
use Silex\ControllerProviderInterface;

class CalendarInvitesController implements ControllerProviderInterface{

	protected $app;
	protected $record;

	public function connect(Application $app){
		//get a local copy of the app variable that contains all
		//the program's settings and functions
		$this->app = $app;

		//create a controller for this route
		$icalMaker = $this->app['controllers_factory'];
		$icalMaker->get('', function($type,$id){

			//check if there's a record in the database with this ID
			$this->record = $this->app['storage']->getContent($type, array('id' => $id, 'returnsingle' => true));
			
			//if there is no record with the chosen type or ID throw a 404 (page not found)
			if(!$this->record) throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('There is no content at this address');

			//create iCal string
			$myIcal = $this->createIcal($this->record);

			//send response with correct headers
			$response = new Response();
			$response->headers->set('Content-type','text/calendar; charset=utf-8');
			$response->headers->set('Content-Disposition',"attachment; filename=EMBO-{$this->record['slug']}.ics");
			$response->setContent($myIcal);
			$response->send();
			return "\r\n";

		})->assert('type','^[a-zA-Z0-9]+$')->assert('id','\d+');

		return $icalMaker;
	}

	/**
	 * createIcal will generate a text string to use as an iCal invite
	 * @return string A formatted string laid out as an iCal invite
	 */
	protected function createIcal(\Bolt\Content $record){

		$iCalString = <<<HERE
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//hacksw/handcal//NONSGML v1.0//EN
BEGIN:VEVENT
UID:uid1@example.com
DTSTAMP:20160714T170000Z
ORGANIZER;CN=John Doe:MAILTO:john.doe@example.com
DTSTART:20160714T170000Z
DTEND:20160715T035959Z
SUMMARY:Bastille Day Party
END:VEVENT
END:VCALENDAR
HERE;

		//return the iCal string
		return $iCalString;


	}


}//end of class