<?php
# an unknown action... ERROR!

function actionUnknown($action)
{
  global $act;
  $verb=$_POST['action'];
  $code=$act[$action];
  echo "<h2>Error</h2>"
  ."<p>An unknown error occurred. I was asked to perform the "
  ."action '$action' ($verb,$code) but I don't know how to do that.</p>"
  ."<p>Please contact <a href='mailto:$ADMINEMAIL'>the system"
  ."administrator</a> to report this error.</p>";
}

?> 
