<?php
/*
 * $Id$
 *
 * Description:
 * This script is intended to be used outside of normal WebCalendar
 * use,  as an RSS 2.0 feed to a RSS client.
 *
 * You must have "Enable RSS feed" set to "Yes" in both System
 * Settings and in the specific user's Preferences.
 *
 * Simply use the URL of this file as the feed address in the client.
 * For public user access:
 * http://xxxxx/aaa/rss.php
 * For any other user (where "joe" is the user login):
 * http:/xxxxxx/aaa/rss.php?user=joe
 *
 * By default (if you do not edit this file), events
 * will be loaded for either:
 *   - the next 30 days
 *   - the next 10 events
 *
 * Input parameters:
 * You can override settings by changing the URL parameters:
 *   - days: number of days ahead to look for events
 *   - cat_id: specify a category id to filter on
 *   - repeats: output all events including all repeat instances
 *       repeats=0 do not output repeating events (default)
 *       repeats=1 outputs repeating events 
 *       repeats=2 outputs repeating events but suppresses display of
 *           2nd & subsequent occurences of daily events
 *   - user: login name of calendar to display (instead of public
 *     user).  You must have the
 *     following System Settings configured for this:
 *       Allow viewing other user's calendars: Yes
 *       Public access can view others: Yes
 *
 * Security:
 * $RSS_ENABLED must be set true
 * $USER_RSS_ENABLED must be set true unless this is for the public user
 * $USER_REMOTE_ACCESS can be set as follows in pref.php
 *      0 = Public entries only
 *      1 = Public & Confidential entries only
 *      2 = All entries are included in the feed *USE WITH CARE
 *   
 * We do not include unapproved events in the RSS feed.
 *
 * TODO
 * Add other RSS 2.0 options such as media
 * Add <managingEditor>: dan@spam_me.com (Dan Deletekey)
 */

$debug=FALSE;

 require_once 'includes/classes/WebCalendar.class';
 require_once 'includes/classes/Event.class';
 require_once 'includes/classes/RptEvent.class';
     
 $WebCalendar =& new WebCalendar ( __FILE__ );    
     
 include 'includes/config.php';    
 include 'includes/dbi4php.php';    
 include 'includes/functions.php';    
     
 $WebCalendar->initializeFirstPhase();    
     
 include "includes/$user_inc";
    
 include_once 'includes/validate.php';    
 include 'includes/translate.php';    
 include 'includes/site_extras.php';
 
include_once 'includes/xcal.php';

 $WebCalendar->initializeSecondPhase();

load_global_settings ();

$WebCalendar->setLanguage();


if ( empty ( $RSS_ENABLED ) || $RSS_ENABLED != 'Y' ) {
  header ( 'Content-Type: text/plain' );
  etranslate( 'You are not authorized' );
  exit;
}
/*
 *
 * Configurable settings for this file.  You may change the settings
 * below to change the default settings.
 * These settings will likely move into the System Settings in the
 * web admin interface in a future release.
 *
 */


// Default time window of events to load
// Can override with "rss.php?days=60"
$numDays = 30;

// Max number of events to display
// Can override with "rss.php?max=20"
$maxEvents = 10;

// Login of calendar user to use
// '__public__' is the login name for the public user
$username = '__public__';

// Allow the URL to override the user setting such as
// "rss.php?user=craig"
$allow_user_override = true;

// Load layers
$load_layers = false;

// Load just a specified category (by its id)
// Leave blank to not filter on category (unless specified in URL)
// Can override in URL with "rss.php?cat_id=4"
$cat_id = '';

// Load all repeating events
// Can override with "rss.php?repeats=1"
$allow_repeats = false;

// Load show only first occurence within the given time span of daily repeating events
// Can override with "rss.php?repeats=2"
$show_daily_events_only_once = false;

// End configurable settings...

// Set for use elsewhere as a global
$login = $username;

if ( $allow_user_override ) {
  $u = getValue ( 'user', "[A-Za-z0-9_\.=@,\-]+", true );
  if ( ! empty ( $u ) ) {
    $username = $u;
    $login = $u;
    // We also set $login since some functions assume that it is set.
  }
}

load_user_preferences ();

// Determine what remote access has been set up by user
// This will only be used if $username is not __public__
if ( ! empty ( $USER_REMOTE_ACCESS ) && $username != '__public__' ) {
  if ( $USER_REMOTE_ACCESS == 1 ) { //public or confidential
    $allow_access = array('', 'P', 'C');
  } else if ( $USER_REMOTE_ACCESS == 2 ){ //all entries included
    $allow_access = array('', 'P', 'C', 'R');
  } 
} else { //public entries only
  $allow_access = array('', 'P');
}

user_load_variables ( $login, 'rss_' );
$creator = ( $username == '__public__' ) ? 'Public' : $rss_fullname;

if ( $username != '__public__' && ( empty ( $USER_RSS_ENABLED ) || 
  $USER_RSS_ENABLED != 'Y' ) ) {
  header ( 'Content-Type: text/plain' );
  etranslate( 'You are not authorized' );
  exit;
}

$cat_id = '';
if ( $CATEGORIES_ENABLED == 'Y' ) {
  $x = getIntValue ( 'cat_id', true );
  if ( ! empty ( $x ) ) {
    load_user_categories ();
    $cat_id = $x;
    $category = $categories[$cat_id];
  }
}

if ( $load_layers ) {
  load_user_layers ( $username );
}


// Calculate date range
$date = getIntValue ( 'date', true );
if ( empty ( $date ) || strlen ( $date ) != 8 ) {
  // If no date specified, start with today
  $date = date ( 'Ymd' );
}
$thisyear = substr ( $date, 0, 4 );
$thismonth = substr ( $date, 4, 2 );
$thisday = substr ( $date, 6, 2 );

$startTime = mktime ( 0, 0, 0, $thismonth, $thisday, $thisyear );

$x = getIntValue ( 'days', true );
if ( ! empty ( $x ) ) {
  $numDays = $x;
}
// Don't let a malicious user specify more than 365 days
if ( $numDays > 365 ) {
  $numDays = 365;
}
$x = getIntValue ( 'max', true );
if ( ! empty ( $x ) ) {
  $maxEvents = $x;
}
// Don't let a malicious user specify more than 100 events
if ( $maxEvents > 100 ) {
  $maxEvents = 100;
}

$x = getIntValue ( 'repeats', true );
if ( ! empty ( $x ) ) {
  $allow_repeats = $x;
  if ( $x==2 ) {
    $show_daily_events_only_once = $true;
  }
}

$endTime = mktime ( 0, 0, 0, $thismonth, $thisday + $numDays -1,
  $thisyear );
$endDate = date ( 'Ymd', $endTime );


/* Pre-Load the repeated events for quicker access */
if (  $allow_repeats == true )
  $repeated_events = read_repeated_events ( $username, $cat_id, $startTime );

/* Pre-load the non-repeating events for quicker access */
$events = read_events ( $username, $startTime, $endTime, $cat_id );

$charset = ( ! empty ( $LANGUAGE )?translate( 'charset' ): 'iso-8859-1' );
// This should work ok with RSS, may need to hardcode fallback value
$lang = languageToAbbrev ( ( $LANGUAGE == 'Browser-defined' || 
  $LANGUAGE == 'none' )? $lang : $LANGUAGE );
if ( $lang == 'en' ) $lang = 'en-us'; //the RSS 2.0 default

//header('Content-type: application/rss+xml');
header('Content-type: text/xml');
echo '<?xml version="1.0" encoding="' . $charset . '"?>';
?>
<rss version="2.0" xml:lang="<?php echo $lang ?>">
 
<channel>
<title><![CDATA[<?php etranslate ( $APPLICATION_NAME ); ?>]]></title>
<link><?php echo $SERVER_URL; ?></link>
<description><![CDATA[<?php etranslate ( $APPLICATION_NAME ); ?>]]></description>
<language><?php echo $lang; ?></language>
<generator>:"http://www.k5n.us/webcalendar.php?v=<?php 
echo $PROGRAM_VERSION; ?>"</generator>
<image>
<title><![CDATA[<?php etranslate ( $APPLICATION_NAME ); ?>]]></title>
<link><?php echo $PROGRAM_URL; ?></link>
<url>http://www.k5n.us/k5n_small.gif</url>
</image>
<?php
$numEvents = 0;
$reventIds = array();

for ( $i = $startTime; date ( 'Ymd', $i ) <= date ( 'Ymd', $endTime ) &&
  $numEvents < $maxEvents; $i += ONE_DAY ) {
  $eventIds=array();
  $d = date ( 'Ymd', $i );
  $entries = get_entries ( $d, false  );
  $rentries = get_repeating_entries ( $username, $d );
  $entrycnt = count ( $entries );
  $rentrycnt = count ( $rentries );
  if ($debug) echo "\n\ncountentries==". $entrycnt . " " . $rentrycnt . "\n\n";
  if ( $entrycnt > 0 || $rentrycnt > 0 ) {
    for ( $j = 0; $j < $entrycnt && $numEvents < $maxEvents; $j++ ) {
      // Prevent non-Public events from feeding
      if ( array_search ( $entries[$j]->getAccess(), $allow_access ) ) {
        $eventIds[] = $entries[$j]->getID();
        $unixtime = date_to_epoch ( $entries[$j]->getDateTime() );
        echo "\n<item>\n";
        echo "<title><![CDATA[" . 
          $entries[$j]->getName() . "]]></title>\n";
        echo '<link>' . $SERVER_URL . 'view_entry.php?id=' . 
          $entries[$j]->getID() . "&amp;friendly=1&amp;rssuser=$login&amp;date=" . 
          $d . "</link>\n";
        echo "<description><![CDATA[" .
          $entries[$j]->getDescription() . "]]></description>\n";
        if ( ! empty ( $category ) )
          echo "<category><![CDATA[" . $category . "]]></category>\n";
        //echo '<creator><![CDATA[' . $creator . "]]></creator>\n";
        //RSS 2.0 date format Wed, 02 Oct 2002 13:00:00 GMT
        echo '<pubDate>' . gmdate ( 'D, d M Y H:i:s', $unixtime ) ." GMT</pubDate>\n";
        echo '<guid>' . $SERVER_URL . 'view_entry.php?id=' . 
          $entries[$j]->getID() . "&amp;friendly=1&amp;rssuser=$login&amp;date=" . 
          $d . "</guid>\n";
        echo "</item>\n";
        $numEvents++;
      }
    }
    for ( $j = 0; $j < $rentrycnt && $numEvents < $maxEvents; $j++ ) {

          //to allow repeated daily entries to be suppressed
          //step below is necessary because 1st occurence of repeating 
          //events shows up in $entries AND $rentries & we suppress display
          //of it in $rentries
       if ( in_array($rentries[$j]->getID(),$eventIds)  && 
             $rentries[$j]->getrepeatType()== 'daily' ) {
               $reventIds[]=$rentries[$j]->getID(); 
          }


      // Prevent non-Public events from feeding
      // Prevent a repeating event from displaying if the original event 
      // has alreay been displayed; prevent 2nd & later recurrence
      // of daily events from displaying if that option has been selected
      if ( ! in_array($rentries[$j]->getID(),$eventIds ) && 
         ( ! $show_daily_events_only_once || ! in_array($rentries[$j]->getID(),$reventIds )) && 
         ( array_search ( $rentries[$j]->getAccess(), $allow_access ) ) ) { 
  
          //show repeating events only once
          if ( $rentries[$j]->getrepeatType()== 'daily' ) 
                  $reventIds[]=$rentries[$j]->getID(); 


        echo "\n<item>\n";
        $unixtime = date_to_epoch ( $entries[$j]->getDateTime() );
        echo "<title><![CDATA[" . 
          $rentries[$j]->getName() . "]]></title>\n";
        echo '<link>' . $SERVER_URL . "view_entry.php?id=" . 
          $rentries[$j]->getID() . "&amp;friendly=1&amp;rssuser=$login&amp;date=" . 
          $d . "</link>\n";
        echo "<description><![CDATA[" .
          $rentries[$j]->getDescription() . "]]></description>\n";
        if ( ! empty ( $category ) )
          echo "<category><![CDATA[" . $category . "]]></category>\n";
       // echo '<creator><![CDATA[' . $creator . "]]></creator>\n";
        echo '<pubDate>' . gmdate ( 'D, d M Y H:i:s', $unixtime ) . " GMT</pubDate>\n";
        echo '<guid>' . $SERVER_URL . 'view_entry.php?id=' . 
          $entries[$j]->getID() . "&amp;friendly=1&amp;rssuser=$login&amp;date=" . 
          $d . "</guid>\n";
        echo "</item>\n";   
        $numEvents++;
      }
    }
  }
}
echo "</channel></rss>\n";
// Clear login...just in case
$login = '';
exit;

?>
