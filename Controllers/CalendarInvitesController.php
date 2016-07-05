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
	protected $config;

	public function __construct($config){
		//get a local copy of the configuration file (this is copied from the extension
		//director to app/config/extensions when the extension is installed)
		$this->config = $config;
	
	}

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
			if(!$myIcal) throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('iCal could not be generated at this address');

			//if the ical was not able to be created, throw a 404
			

			//send response with correct headers
			$response = new Response();
			$response->headers->set('Content-type','text/calendar; charset=utf-8');
			$response->headers->set('Content-Disposition',"attachment; filename=EMBO-{$this->record['slug']}.ics");
			$response->setContent($myIcal);
			$response->send();
			//bolt freaks out if you don't return a value, so here's a line break for the end of the file
			return "\r\n";

		})->assert('type','^[a-zA-Z0-9]+$')->assert('id','\d+');

		return $icalMaker;
	}

	/**
	 * createIcal will generate a text string to use as an iCal invite
	 * @return string A formatted string laid out as an iCal invite
	 */
	protected function createIcal(\Bolt\Content $record){

		//if this record type is not configured in the config yaml file, exit
		if(!$this->shallIProceed($record, $this->config)) return false;

		//escape funny characters
		$toReplace = array(',',';', '"');
		$replaceWith = array('\,','\;', '\"');

		//get the names of the variables we will need
		$nameField = $this->config[$record->contenttype["singular_name"]]["Name"];
		$startField = $this->config[$record->contenttype["singular_name"]]["Start"];
		$finishField = $this->config[$record->contenttype["singular_name"]]["Finish"];
		$locationField = $this->config[$record->contenttype["singular_name"]]["Location"];
		$uidField =  $this->config[$record->contenttype["singular_name"]]["UID"];


		//get the fields with the above names - they contain values to fill in the ical file
		$currentdatestamp = date("Ymd\THis",(new \DateTime())->getTimestamp());
		$name = str_replace($toReplace, $replaceWith, $record->$nameField());
		$start = date("Ymd", strtotime($record->$startField()));
		$finish = date("Ymd", strtotime($record->$finishField(). '+1 day'));
		$location = str_replace($toReplace, $replaceWith, $record->$locationField());
		
		//generate random ID for UID if --random special value was configured
		if($uidField=="--random"){
			$uid = sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
		}else{
			$uid = $record->$uidField();
		}

		//create page URL of original page
		$url = $this->app['resources']->getUrl("rooturl").$record->contenttype["slug"].'/'.$record->slug();


		$iCalString = <<<HERE
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//hacksw/handcal//NONSGML v1.0//EN
CALSCALE:GREGORIAN
BEGIN:VEVENT
DTSTAMP:$currentdatestamp
UID:$uid
DTSTART;VALUE=DATE:$start
DTEND;VALUE=DATE:$finish
SUMMARY:$name
URL:$url
DESCRIPTION:EMBO $name in $location
LOCATION:$location
END:VEVENT
END:VCALENDAR
HERE;

		//return the iCal string
		return $iCalString;


	}

	/**
	 * Function checks whether config file is correctly populated for this content type
	 * @param  \Bolt\Content $record a Bolt content record
	 * @param  \Bolt\Config $config Bolt Configuration object read in from yaml file
	 * @return boolean         true for successful config check, false if configuration is not correct for this content type
	 */
	protected function shallIProceed($record, $config){

		$thisEventType = $record->contenttype["singular_name"];

		//check if the record type is one of the configured types
		try{
			//if this event type does not have a config setup, exit
			if(!array_key_exists($thisEventType, $config)) return FALSE;

			//if any of the fields defined in the config file do not exist
			//in the record, exit
			foreach($config[$thisEventType] as $key=>$value){
				
				if($value!="--random" && !$record->$value()) throw new \Exception("User tried to access an ical file built from an incomplete record");

			}

		}catch(\Exception $ex){

			error_log($ex->getMessage());
			return FALSE;

		}

		return TRUE;

	}


}//end of class