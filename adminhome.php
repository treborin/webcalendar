<?php
/* $Id$

 Page Description:
  Serves as the home page for administrative functions.
 Input Parameters:
  None
 Security:
  Users will see different options available on this page.
 */
include_once 'includes/init.php';

define ( 'COLUMNS', 3 );

print_header ( '', '
    <style type="text/css">
      table.admin {
        border: 1px solid #000;
        padding: 5px;
        background-color: ' . $CELLBG . ';
      }
      table.admin td {
        padding: 20px;
        text-align: center;
      }
      .admin td a {
        border: 1px solid #EEE;
        border-color: #EEE #777 #777 #EEE;
        padding: 10px;
        background-color: ' . $CELLBG . ';
        text-align: center;
      }
      .admin td a:hover {
        border-color: #777 #EEE #EEE #777;
        background-color: #AAA;
      }
    </style>
'
  );

$names = $links = array ();
/* Disabled for now...will move to menu when working properly
if ( $is_admin && ! empty ( $SERVER_URL ) &&
    access_can_access_function ( ACCESS_SYSTEM_SETTINGS ) ) {
  $names[] = translate ( 'Control Panel' );
  $links[] = 'controlpanel.php';
}
*/
if ( $is_nonuser_admin ) {
  if ( ! access_is_enabled () ||
      access_can_access_function ( ACCESS_PREFERENCES ) ) {
    $names[] = translate ( 'Preferences' );
    $links[] = 'pref.php?user=' . $user;
  }

  if ( $single_user != 'Y' ) {
    if ( ! access_is_enabled () ||
        access_can_access_function ( ACCESS_ASSISTANTS ) ) {
      $names[] = translate ( 'Assistants' );
      $links[] = 'assistant_edit.php?user=' . $user;
    }
  }
} else {
  if ( ( $is_admin && ! access_is_enabled () ) || ( access_is_enabled () &&
        access_can_access_function ( ACCESS_SYSTEM_SETTINGS ) ) ) {
    $names[] = translate ( 'System Settings' );
    $links[] = 'admin.php';
  }

  if ( ! access_is_enabled () ||
      access_can_access_function ( ACCESS_PREFERENCES ) ) {
    $names[] = translate ( 'Preferences' );
    $links[] = 'pref.php';
  }

  $names[] = ( $is_admin ? translate ( 'Users' ) : translate ( 'Account' ) );
  $links[] = 'users.php';

  if ( access_is_enabled () &&
      access_can_access_function ( ACCESS_ACCESS_MANAGEMENT ) ) {
    $names[] = translate ( 'User Access Control' );
    $links[] = 'access.php';
  }

  if ( $single_user != 'Y' ) {
    if ( ! access_is_enabled () ||
        access_can_access_function ( ACCESS_ASSISTANTS ) ) {
      $names[] = translate ( 'Assistants' );
      $links[] = 'assistant_edit.php';
    }
  }

  if ( $CATEGORIES_ENABLED == 'Y' ) {
    if ( ! access_is_enabled () ||
        access_can_access_function ( ACCESS_CATEGORY_MANAGEMENT ) ) {
      $names[] = translate ( 'Categories' );
      $links[] = 'category.php';
    }
  }

  if ( ! access_is_enabled () ||
      access_can_access_function ( ACCESS_VIEW_MANAGEMENT ) ) {
    $names[] = translate ( 'Views' );
    $links[] = 'views.php';
  }

  if ( ! access_is_enabled () ||
      access_can_access_function ( ACCESS_LAYERS ) ) {
    $names[] = translate ( 'Layers' );
    $links[] = 'layers.php';
  }

  if ( $REPORTS_ENABLED == 'Y' &&
    ( ! access_is_enabled () ||
        access_can_access_function ( ACCESS_REPORT ) ) ) {
    $names[] = translate ( 'Reports' );
    $links[] = 'report.php';
  }

  if ( $is_admin ) {
    $names[] = translate ( 'Delete Events' );
    $links[] = 'purge.php';
  }
  // This Activity Log link shows ALL activity for ALL events, so you really need
  // to be an admin user for this. Enabling "Activity Log" in UAC just gives you
  // access to the log for your _own_ events or other events you have access to.
  if ( $is_admin && ( ! access_is_enabled () ||
        access_can_access_function ( ACCESS_ACTIVITY_LOG ) ) ) {
    $names[] = translate ( 'Activity Log' );
    $links[] = 'activity_log.php';
  }

  if ( $is_admin && ! empty ( $PUBLIC_ACCESS ) && $PUBLIC_ACCESS == 'Y' ) {
    $names[] = translate ( 'Public Preferences' );
    $links[] = 'pref.php?public=1';
  }

  if ( $is_admin && ! empty ( $PUBLIC_ACCESS ) && $PUBLIC_ACCESS == 'Y' &&
    $PUBLIC_ACCESS_CAN_ADD == 'Y' && $PUBLIC_ACCESS_ADD_NEEDS_APPROVAL == 'Y' ) {
    $names[] = translate ( 'Unapproved Public Events' );
    $links[] = 'list_unapproved.php?user=__public__';
  }
}

echo '
    <h2>' . translate ( 'Administrative Tools' ) . '</h2>
    <table class="admin">';

for ( $i = 0, $cnt = count ( $names ); $i < $cnt; $i++ ) {
  echo ( $i % COLUMNS == 0 ? '
      <tr>' : '' ) . '
        <td>' . ( ! empty ( $links[$i] ) ? '<a href="' . $links[$i] . '">' : '' )
   . $names[$i] . ( ! empty ( $links[$i] ) ? '</a>' : '' ) . '</td>'
   . ( $i % COLUMNS == COLUMNS - 1 ? '
      </tr>' : '' );
}

if ( $i % COLUMNS != 0 )
  while ( $i % COLUMNS != 0 ) {
  echo '
        <td>&nbsp;</td>';
  $i++;
}
echo '
      </tr >
    </table>
    ' . print_trailer ();

?>
