<?php defined('APPLICATION') or die();

class CalendarModel extends Gdn_Model {

    public $Year;
    
    public $Month = 9;
    
    public $FirstOfMonth;
    
    public $LastOfMonth;
    
    public $DayNames = array();
    
    public $FirstDayOfWeek = 1;
    
    public $PreMonthDays;
    
    public $PostMonthDays;

    public $Events = array();
    
    // init variables
    private function init($Year = 0, $Month = 0, $FirstDayOfWeek = 0) {
        $this->Year = (int)$Year;
        $this->Month = (int)$Month;
                
        //set other variables depending on year and month
        $this->FirstOfMonth = mktime(0, 0, 0, $this->Month, 1, $this->Year);
        $this->LastOfMonth = mktime(0, 0, 0, $this->Month + 1, 0, $this->Year);
        // harmonize vars if month < 1/month > 12 
        $this->Year = (int)date('Y', $this->FirstOfMonth); 
        $this->Month = (int)date('n', $this->FirstOfMonth); 
        
        $this->FirstDayOfWeek = (int)$FirstDayOfWeek;
        
        $this->DayNames[0] = T('Sunday');
        $this->DayNames[1] = T('Monday');
        $this->DayNames[2] = T('Tuesday');
        $this->DayNames[3] = T('Wednesday');
        $this->DayNames[4] = T('Thursday');
        $this->DayNames[5] = T('Friday');
        $this->DayNames[6] = T('Saturday');
        
        for ($k = 0; $k < $FirstDayOfWeek; ++$k) {
            array_push($this->DayNames, array_shift($this->DayNames));
        }
    }
    
    
    // returns array( $Year, $Month, $Days = $Calendar( 'Days' => array (sarray('Day' => 1, 
    // $CalendarEvents['Month']
    // $CalendarEvents['1']['Events'];
    public function getMonth($Year = 0, $Month = 0, $FirstDayOfWeek = 0, $WithPadding = false) {
        $this->init($Year, $Month, $FirstDayOfWeek);

        // if also some days before first of month and some days after last of month should be shown
        if ($WithPadding) {
            $FirstOfMonthWeekday = date('w', $this->FirstOfMonth);
            $this->PreMonthDays = $FirstOfMonthWeekday - $this->FirstDayOfWeek;
            if ($this->PreMonthDays < 0) {
                $this->PreMonthDays += 7;
            }

            $LastOfMonthWeekday = date('w', $this->LastOfMonth);
            $this->PostMonthDays = 6 - $LastOfMonthWeekday + $this->FirstDayOfWeek;
            if ($this->PostMonthDays == 7) {
                $this->PostMonthDays = 0;
            }
            
            $FirstCalendarDate = mktime(0, 0, 0, $this->Month, 1 - $this->PreMonthDays, $this->Year);
            $LastCalendarDate = mktime(0, 0, 0, $this->Month + 1, 0 + $this->PostMonthDays, $this->Year);
        } else {
            $FirstCalendarDate = $this->FirstOfMonth;
            $LastCalendarDate = $this->LastOfMonth;
        }
            
         
        // get events from db
        $DiscussionModel = new DiscussionModel();
        // use GetWhere because it respects permissions
        $CalendarEvents = $DiscussionModel->GetWhere(
            array(
                'CalendarIsEvent' => true,
                'CalendarEventDate >=' => date('Y-m-d', $FirstCalendarDate),
                'CalendarEventDate <=' => date('Y-m-d', $LastCalendarDate)
                ),
            0,
            1000000
        )->ResultArray();
        
        // sort events by event date
        $CalendarEventDates = Array();
        foreach($CalendarEvents as &$CalendarEvent) {
            $CalendarEventDates[] = &$CalendarEvent["CalendarEventDate"];
        }
        array_multisort($CalendarEventDates, $CalendarEvents);
        $this->Events = $CalendarEvents;
        
        // prepare calendar array
        $Result = array();
        // loop through days
        for ($Day = $FirstCalendarDate; $Day <= $LastCalendarDate; $Day += 86400) {
            $Key = date('Y-m-d', $Day);
            if ((int)date('n', $Day) == $this->Month) {
                $ClassCurrentMonth = 'CurrentMonth';
            } else {
                $ClassCurrentMonth = 'WrapperMonth';
            }
            // values to push into array
            $Result[$Key] = array(
                'Day' => date('j', $Day),
                'Date' => date(T('Calendar.Popup.Format', 'l, j. F Y'), $Day),
                'ShortDate' => $Key,
                'ClassCurrentMonth' => $ClassCurrentMonth,
                'ClassToday' => '',
                'ClassHasEvents' => '',
                'CountEvents' => 0,
                'Events' => array()
            );
        }

        // add today class if appropriate
        $Today = date('Y-m-d');
        if (array_key_exists($Today, $Result)) {
            $Result[$Today]['ClassToday'] = 'Today';
        }

        // add events to the calendar array
        foreach ($CalendarEvents as $Event) {
            $Key = substr($Event['CalendarEventDate'], 0, 10);
            $Result[$Key]['Events'][] = $Event;
            $Result[$Key]['ClassHasEvents'] = 'HasEvents';
            $Result[$Key]['CountEvents'] += 1;
        }

        return $Result;        
    }
}
