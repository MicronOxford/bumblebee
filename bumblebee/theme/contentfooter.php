<?
  # $Id$
  # Footer HTML to be included on every page

  include 'systemstats.php';
  $stat = new SystemStats;
?>

<div class='footer'>
  <p>
    System managed by 
    <a href="http://www.nanonanonano.net/projects/bumblebee/">BumbleBee</a>,
    released under the 
    <a href="http://www.gnu.org/licenses/gpl.html">GNU GPL</a>.
  <br />
    This installation of BumbleBee currently manages
    <?
      echo $stat->get('users') . " users, ";
      echo $stat->get('projects') . " projects, ";
      echo $stat->get('instruments') . " instruments and ";
      echo $stat->get('bookings') . " bookings. ";
    ?>
  <br />
    Email the <a href="mailto:<?=$ADMINEMAIL?>">system administrator</a>
    for help.
  </p>
  <div class='copyright'>
    Booking information Copyright &copy; <?=date("Y")?> University of Melbourne.
  </div>
</div>

</body>
