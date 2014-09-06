<?php defined('APPLICATION') or die();

$PluginInfo['Calendar'] = array(
    'Name' => 'Calendar',
    'Description' => 'Simple Calendar that lets you turn discussions into events. Comes with a module and a Calendar view.',
    'Version' => '0.1',
    'RequiredApplications' => array('Vanilla' => '2.1'),
    'MobileFriendly' => true,
    'HasLocale' => true,
    'Author' => 'Robin Jurinka',
    'SettingsUrl' => '/dashboard/settings/Calendar',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'RegisterPermissions' => array('Plugins.Calendar.Add')
);

/**
 * Simple Calendar that lets you turn a discussion into an event.
 *
 * Comes with two module: a small Calendar and upcoming events.
 */
class CalendarPlugin extends Gdn_Plugin {
    /**
     * Initial changes in db.
     *
     * @return void
     */
    public function setup() {
        $this->structure();
    }

    /**
     * Adds an additional column to the discussion table.
     *
     * @return void
     */
    public function structure() {
        Gdn::Structure()
            ->Table('Discussion')
            ->Column('CalendarEventDate', 'datetime', '1999-12-31')
            ->Column('CalendarIsEvent', 'int', '0')
            ->Set();
    }

    /**
     * Adds EventDate input to New Discussion.
     *
     * @param object $Sender PostController.
     * @return void
     */
    public function postController_beforeBodyInput_handler($Sender) {
        // check for user permission
        if (!Gdn::Session()->CheckPermission('Plugins.Calendar.Add')) {
            return;
        }

        $Sender->AddJsFile('calendar.js', 'plugins/Calendar');

        if ($Sender->Discussion->CalendarIsEvent != 1) {
            $CssClass = 'Hidden ';
        } else {
            $CssClass = '';
        }

        // allowed year range is this and next year
        $CurrentYear = date("Y");
        $NextYear = $CurrentYear + 1;

        // insert checkbox and date dropdowns
        echo '<div class="P Calendar">';
        echo $Sender->Form->CheckBox('CalendarIsEvent', T('Event'), array('onClick' => 'toggleCalendarEventDate(this);'));
        echo $Sender->Form->Date('CalendarEventDate', array(
            'class' => $CssClass.'CalendarEventDate',
            'Fields' => explode(',', T('Calendar.Input.Order', 'month,day,year')),
            'YearRange' => "{$CurrentYear}-{$NextYear}"
        ));
        echo '</div>';
    }

    /**
     * Check user permissions and validates input before save.
     *
     * @param object $Sender Discussionmodel.
     * @return void
     */
    public function discussionModel_beforeSaveDiscussion_handler ($Sender) {
        // check for user permission and event checkbox
        $Session = Gdn::Session();
        if (
            !$Session->CheckPermission('Plugins.Calendar.Add') ||
            $Sender->EventArguments['FormPostValues']['CalendarIsEvent'] != 1
        ) {
            // delete event date from form values
            unset($Sender->EventArguments['FormPostValues']['CalendarEventDate']);
            return;
        }

        // add validation for event date
        $Sender->Validation->ApplyRule('CalendarEventDate', 'Date', T('The event date is not valid.'));

        $CurrentYear = date("Y");
        $NextYear = $CurrentYear + 1;
        $CalendarEventYear = $Sender->EventArguments['FormPostValues']['CalendarEventDate_Year'];

        if ($CalendarEventYear < $CurrentYear || $CalendarEventYear > $NextYear) {
            $Sender->Validation->AddValidationResult('CalendarEventDate', T('The event date is not valid.'));
        }
    }

    /**
     * Add info to discussions.
     *
     * @param object $Sender DiscussionController.
     * @return void
     */
    public function discussionController_discussionInfo_handler ($Sender) {
        if (!$Sender->Discussion->CalendarIsEvent) {
            return;
        }
        // echo '<span class="MItem CalendarEvent">'.T('Event date: ').Gdn_Format::Date().'</span>';
        $EventDate = $Sender->Discussion->CalendarEventDate;
        echo Wrap(
            T('Event date: ').Anchor(
                Gdn_Format::Date($EventDate),
                '/discussions/calendar/'.date("Y-m-d", strtotime($EventDate))
            ),
            'span',
            array('class' => 'MItem CalendarEvent')
        );
    }

    /**
     * Add info to discussions list.
     *
     * @param object $Sender DiscussionsController.
     * @return void
     */
    public function discussionsController_discussionMeta_handler ($Sender) {
        if (!$Sender->EventArguments['Discussion']->CalendarIsEvent) {
            return;
        }
        $EventDate = $Sender->EventArguments['Discussion']->CalendarEventDate;
        echo Wrap(
            T('Event date: ').Anchor(
                Gdn_Format::Date($EventDate),
                '/discussions/calendar/'.date("Y-m-d", strtotime($EventDate))
            ),
            'span',
            array('class' => 'MItem CalendarEvent')
        );
    }

    /**
     * Custom page for overview of events per day.
     *
     * The function is a copy of vanilla/controllers/discussions/mine function.
     * Takes a date as a parameter. If no date is given, current date is used.
     *
     * @param object $Sender DiscussionsController.
     * @param mixed $Args Can contain a date in yyyy-mm-dd format and the page.
     * @return void
     */
    public function discussionsController_calendar_create($Sender, $Args) {
        // sanitize date input
        if (!isset($Args[0])) {
            $EventDate = date("Y-m-d");
        } else {
            $EventDate = date("Y-m-d", strtotime($Args[0]));
        }

        // get page input
        if (isset($Args[1])) {
            $Page = $Args[1];
        } else {
            $Page = 'p1';
        }

        Gdn_Theme::Section('DiscussionList');

        // Set criteria & get discussions data
        list($Offset, $Limit) = OffsetLimit($Page, Gdn::Config('Vanilla.Discussions.PerPage', 30));
        $Session = Gdn::Session();
        $Wheres = array('d.CalendarIsEvent' => '1', 'd.CalendarEventDate' => $EventDate);
        $DiscussionModel = new DiscussionModel();
        $Sender->DiscussionData = $DiscussionModel->Get($Offset, $Limit, $Wheres);
        $Sender->SetData('Discussions', $Sender->DiscussionData);
        $CountDiscussions = $Sender->SetData('CountDiscussions', $DiscussionModel->GetCount($Wheres));

        // Build a pager
        $PagerFactory = new Gdn_PagerFactory();
        $Sender->EventArguments['PagerType'] = 'MorePager';
        // $Sender->FireEvent('BeforeBuildCalendarDayPager');
        $Sender->Pager = $PagerFactory->GetPager($Sender->EventArguments['PagerType'], $Sender);
        $Sender->Pager->MoreCode = 'More Events';
        $Sender->Pager->LessCode = 'Newer Events';
        $Sender->Pager->ClientID = 'Pager';
        $Sender->Pager->Configure(
            $Offset,
            $Limit,
            $CountDiscussions,
            'discussions/calendar/'.$EventDate.'/%1$s'
        );

        $Sender->SetData('_PagerUrl', 'discussions/calendar/'.$EventDate.'/{Page}');
        $Sender->SetData('_Page', $Page);
        $Sender->SetData('_Limit', $Limit);
        // $Sender->FireEvent('AfterBuildCalendarDayPager');

        // Deliver JSON data if necessary
        if ($Sender->DeliveryType() != DELIVERY_TYPE_ALL) {
            $Sender->SetJson('LessRow', $Sender->Pager->ToString('less'));
            $Sender->SetJson('MoreRow', $Sender->Pager->ToString('more'));
            $Sender->View = 'discussions';
        }

        // Add modules
        $Sender->AddModule('DiscussionFilterModule');
        $Sender->AddModule('NewDiscussionModule');
        $Sender->AddModule('CategoriesModule');
        $Sender->AddModule('BookmarkedModule');

        $Title = sprintf(T('Calendar.Day.Title', 'Events on %s'), Gdn_Format::Date($EventDate));
        $Breadcrumb = sprintf(T('Calendar.Day.Breadcrumb', 'Events on %s'), Gdn_Format::Date($EventDate));

        $Sender->SetData('Title', $Title);
        $Sender->SetData('Breadcrumbs', array(array('Name' => $Breadcrumb, 'Url' => '/discussions/calendar/'.$EventDate)));
        $Sender->Render('Index');
    }

    /**
     * Adds CSS class to events in discussions list.
     *
     * @param object $Sender DiscussionsController.
     * @return void
     */
    public function discussionsController_beforeDiscussionName_handler ($Sender) {
        if (!$Sender->EventArguments['Discussion']->CalendarIsEvent) {
            return;
        }
        $Sender->EventArguments['CssClass'] .= ' CalendarEvent';
    }
    
    /**
     * Adds plugins css file and loads module to discussions list.
     *  
     * @param object $Sender DiscussionsController.
     * @return void
     */
    public function discussionsController_Render_Before ($Sender) {
        $this->addCalendarModule($Sender);
    }
    
    /**
     * Adds plugins css file and loads module to categories overview.
     *  
     * @param object $Sender CategoriesController.
     * @return void
     */
    public function categoriesController_Render_Before ($Sender) {
        $this->addCalendarModule($Sender);
    }
    
    /**
     * Adds plugins css file and loads module to a discussion.
     *  
     * @param object $Sender DiscussionController.
     * @return void
     */
    public function discussionController_Render_Before ($Sender) {
        $this->addCalendarModule($Sender);
    }
    
    /**
     *  
     */
    private function addCalendarModule ($Sender) {
        $Sender->AddCssFile('calendar.css', 'plugins/Calendar');
        $Sender->AddJsFile('calendar.js', 'plugins/Calendar');
    
        if (isset($Sender->Assets['Panel']) && $Sender->MasterView != 'admin') {
            $Year = (int)date('Y');
            $Month = (int)date('n');
            $FirstDayOfWeek = (int)Gdn::Config('Plugins.Calendar.FirstDayOfWeek', 0);
            $CalendarModule = new CalendarModule(
                $Sender,
                array(
                    'Year' => $Year,
                    'Month' => $Month,
                    'FirstDayOfWeek' => $FirstDayOfWeek,
                    'WithPadding' => true
                )
            );
            $Sender->AddModule($CalendarModule);
        }
    }
    
    /**
     * Function that makes CalendarModule->ToString() accessible for AJAX calls
     */
    public function pluginController_calendarModule_create($Sender, $Args = array()) {
        if (sizeof($Args) < 4) {
            return;
        }

        $CalendarModule = new CalendarModule(
            $Sender,
            array(
                'Year' => (int)$Args[0],
                'Month' => (int)$Args[1],
                'FirstDayOfWeek' => (int)$Args[2],
                'WithPadding' => (bool)$Args[3]
            )
        );
        
        $CalendarModule->ToString();
        return;
    }
}
