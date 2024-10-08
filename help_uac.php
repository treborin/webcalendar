<?php
require_once 'includes/init.php';
require_once 'includes/help_list.php';

$descStr =
translate ( 'Allows for fine control of user access and permissions. Users can also grant default and per individual permission if authorized by the administrator.' );

print_header ( [], '', '', true, false, true );
echo $helpListStr . '
    <div class="helpbody">
      <h2>' . translate ( 'Help' ) . ': '
       . translate ( 'User Access Control' ) . '</h2>
      <p>' . $descStr . '</p>';
$tmp_arr = [
  translate ( 'Can Email' ) =>
  translate ( 'If disabled, this user cannot send you emails.' ),
  translate ( 'Can Invite' ) =>
  translate ( 'If disabled, this user cannot see you in the participants list.' ),
  translate ( 'Can See Time Only' ) =>
  translate ( 'If enabled, this user cannot view the details of any of your entries.' )];
list_help ( $tmp_arr );
echo '
    </div>
    ' . print_trailer ( false, true, true );

?>
