<?php
# $Id$
# maintain a list of actions that require the approval of an admin

  function actionAdminconfirm() {
    if (! isset($_POST['confirm'])) {
      selectconfirmations();
    } else {
      performconfirmations();
    }
  }

  function selectconfirmations() {
    echo "<h2>Please confirm the following actions</h2>";
    /*
    $q = "SELECT * "
        ."FROM adminconfirm "
        ."LEFT JOIN bookings "
        ."ON adminconfirm.booking=bookings.id "
        ."ORDER BY bookings.bookwhen";
    */
    $q = "SELECT *, users.name AS uname, instruments.name AS iname "
        ."FROM adminconfirm "
        ."LEFT JOIN bookings "
        ."ON adminconfirm.booking=bookings.id "
        ."LEFT JOIN users "
        ."ON users.id=bookings.userid "
        ."LEFT JOIN instruments "
        ."ON instruments.id=bookings.instrument "
        ."ORDER BY bookings.bookwhen,bookings.instrument";
    $sql = mysql_query($q);
    if (! $sql) die (mysql_error());
    if (mysql_num_rows($sql)==0) {
      echo "<p>Sorry: no actions found</p>";
    } else {
      echo "<table>";
      echo "<tr><th>Date</th><th>Time</th><th>User</th><th>Instrument</th>"
          ."<th>Action</th>"
          ."<th>Approve</th><th>Reject</th><th>Defer</th></tr>";
      $j = 0;
      while ($g = mysql_fetch_array($sql)) {
        $j++;
        echo "<tr>"
            ."<td>".$g['bookwhen']."</td>"
            ."<td>".$g['starttime']." - ".$g['stoptime']."</td>"
            ."<td>".$g['uname'].' ('.$g['username'].")</td>"
            ."<td>".$g['iname']."</td>"
            ."<td>".$g['action']."</td>"
            ."<td><input type='radio' name='approve-$j' value='1' checked='chekced' /></td>"
            ."<td><input type='radio' name='approve-$j' value='0' /></td>"
            ."<td><input type='radio' name='approve-$j' value='-1' /></td>"
            ."</tr>"
            ."<input type='hidden' name='book-$j' value='".$g['booking']."' />";
      }
      echo "
        <input type='hidden' name='confirm' value='1' />
        <tr><td><button name='action' type='submit' value='adminconfirm'>
            Submit responses
          </button>
        </td></tr>
      </table>
      ";
    }
  }

  function performconfirmations() {
    for ($j=1; isset($_POST['approve-'.$j]); $j++) {
      $approve = $_POST['approve-'.$j];
      $book = $_POST['book-'.$j];
      #echo "$j ($book) => $approve<br />";
      if ($approve == 0) {
        # send approved email?
        echo "Booking reference $book approved<br />";
      } elseif ($approve == 1) {
        # send reject email?
        echo "Booking reference $book rejected<br />";
        $q = "DELETE FROM bookings WHERE id='$book'";
        if (!mysql_query($q)) die(mysql_error());
        echo "delete booking: '$q' successful<br />";
      } else {
        echo "Booking reference $book deferred for later confirmation<br />";
      }
      
      if ($approve == 0 || $approve == 1) {
        # if $approve == -1 then we defer the confirmation, otherwise we 
        # have either approved or rejected the request and we now can
        # delete the request for confirmation
        $del = "DELETE FROM adminconfirm WHERE booking='$book'";
        if (!mysql_query($del)) die(mysql_error());
        echo "delete adminconfirm entry: '$del' successful<br />";
      }
    }
  }
?> 
