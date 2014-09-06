<?php defined('APPLICATION') or die();

/*
klassischer kalender
CalendarUpcomingModule soll kommende events zeigen
*/
class CalendarModule extends Gdn_Module {
    protected $_Year = 2014;
    protected $_Month = 9;
    protected $_FirstDayOfWeek = 0;
    protected $_WithPadding = true;

    public function __construct($Sender, $Args = array()) {
        // sanitize input
        $this->_Year = (int)val('Year', $Args, date('Y'));     
        $this->_Month = (int)val('Month', $Args, date('n'));
        $this->_FirstDayOfWeek = (int)val('FirstDayOfWeek', $Args, 0);
        $this->_WithPadding = (bool)val('WithPadding', $Args, true);
        
        parent::__construct($Sender, 'Vanilla');
    }

    public function AssetTarget() {
        return 'Panel';
    }

    public function ToString() {
        // get days and events
        $CalendarModel = new CalendarModel();
        $Calendar = $CalendarModel->getMonth($this->_Year, $this->_Month, $this->_FirstDayOfWeek, $this->_WithPadding);
        
        // prepare template variables
        $Week = 0;
        $Events = '';
        
        $NavPrevMonth = 'plugin/calendarmodule/'.$this->_Year.'/'.($this->_Month - 1).'/'.$this->_FirstDayOfWeek.'/'.$this->_WithPadding;
        $NavNextMonth = 'plugin/calendarmodule/'.$this->_Year.'/'.($this->_Month + 1).'/'.$this->_FirstDayOfWeek.'/'.$this->_WithPadding;

        require_once(Gdn::Controller()->FetchViewLocation('calendarmodule', '', 'plugins/Calendar'));
    }
}