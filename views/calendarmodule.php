<div id="CalendarModule" class="Box CalendarModuleBox">
  <h4 class="Calendar"><?= date('F Y', $CalendarModel->FirstOfMonth) ?></h4>
  <div class="Calendar">
    <div class="DayNames">
      <?php foreach($CalendarModel->DayNames as $DayName): ?>
      <div><?= substr($DayName, 0, 1) ?></div>
      <?php endforeach ?>
    </div>
    <div class="Week">
      <?php
      foreach ($Calendar as $Day):
        if ($Week < 7) {
          ++$Week;
        } else {
          $Week = 1;
          echo '</div><div class="Week">';
        }
        $Class = $Day['ClassCurrentMonth'].' '.$Day['ClassToday'].' '.$Day['ClassHasEvents'];
        if ($Day['ClassHasEvents'] != '') {
          $Class .= ' Button';
          $Events .= '<div id="Calendar_'.$Day['ShortDate'].'" class="CalendarEvent Hidden">';
          $Events .= '<h4><a href="'.Url('discussions/calendar/'.$Day['ShortDate']).'">'.$Day['Date'].'</a></h4>';
          $Events .= '<ul class="PanelInfo">';
          foreach ($Day['Events'] as $Event) {
            $Events .= '<li><a href="'.$Event['Url'].'">'.$Event['Name'].'</a></li>';
          }
          $Events .= '</ul></div>';
        }
      ?>
      <div class="<?= $Class ?>" title="<?= $Day['Date'] ?>" value="<?= $Day['ShortDate'] ?>" onclick="calendarModuleHide(this);">
        <?= $Day['Day'] ?>
      </div>
      <?php endforeach ?>
    </div>
    <div class="Navigation">
      <button class="NavButton" value="<?= $NavPrevMonth ?>" onclick="calendarModule(this); return false;">&lt;</button>
      <button class="NavButton" value="<?= $NavNextMonth ?>" onclick="calendarModule(this);">&gt;</button>
    </div>
  </div>
  <div class="CalendarEventList Hidden">
    <?= $Events ?>
    <button class="NavButton" onclick="calendarModuleShow();"><?= T('Back to Calendar') ?></button>
  </div>
</div>
