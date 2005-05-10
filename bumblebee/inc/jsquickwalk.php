<?php
# $Id$
# Some javascript functions for use in the pages

class JSQuickWalk {

  var $back;
  var $fwd;
  var $keys;
  var $values;
  var $counter;

  function JSQuickWalk($namebase, $back, $fwd, $keys, $values, $counter) {
    $this->namebase = $namebase;
    $this->back = $back;
    $this->fwd  = $fwd;
    $this->keys = $keys;
    $this->values = $values;
    $this->counter = $counter;
  }
  
  function displayJS() {
    $eol="\n";
    $t = '<script type="text/javascript">'.$eol;
    $t .= $this->namebase.'walkarray = new Array();'.$eol;
    for ($i=0; $i<count($this->values); $i++) {
      $t .= $this->namebase.'walkarray['.$i.']= new Array();'.$eol;
      foreach ($this->keys as $k) {
        $t .= $this->namebase.'walkarray['.$i.']["'.$k.'"]="'
                                    .$this->values[$i][$k]->datestring.'";'.$eol;
      }
    }
    $c = $this->namebase.'walkcounter';
    $t .= 'var '.$c.'='.$this->counter.';'.$eol;
    $t .= 'function '.$this->namebase.'walkfwd() {'.$eol
         //.'  alert("FOO"+'.$c.')'.$eol
         .'  ('.$c.' < '.(count($this->values)-1).' && '.$c.'++);'.$eol;
    foreach ($this->keys as $k) {
      $t .= '  document.forms[0].'.$this->namebase.$k.'.value='
                              .$this->namebase.'walkarray['.$c.']["'.$k.'"];'.$eol;
    }
    
//         .'  alert('.$c.'['..'])'.$eol
         //.'  return false;'
    $t .= '}'.$eol;

    $t .= 'function '.$this->namebase.'walkback() {'.$eol
         .'  ('.$c.' > 0 && '.$c.'--);'.$eol;
    foreach ($this->keys as $k) {
      $t .= '  document.forms[0].'.$this->namebase.$k.'.value='
                              .$this->namebase.'walkarray['.$c.']["'.$k.'"];'.$eol;
    }
    $t .= '}'.$eol;
    $t .= '</script>'.$eol;
    return $t;
  }
  
  function displayFwd() {
    return '<a href="javascript:'.$this->namebase.'walkfwd()">'
            .$this->fwd.'</a>';
  }
  
  function displayBack() {
    return '<a href="javascript:'.$this->namebase.'walkback()">'
            .$this->back.'</a>';
  }








}

?>
<script type='text/javascript'>
function selectall () {
  return setcheckboxes(true, 0, 1);
}

function deselectall () {
  return setcheckboxes(false, 0, 1);
}

function selectsome (offset, mod) {
  return setcheckboxes(true, offset, mod);
}

function deselectsome (offset, mod) {
  return setcheckboxes(false, offset, mod);
}

function setcheckboxes (setval, offset, mod) {
  //alert("start");
  count = 0;
  for (var i=0; i<document.forms[0].length; i++) {
    if (document.forms[0].elements[i].type == "checkbox") {
    //alert('c='+count+'\no='+offset+'\nm='+mod+'\ny='+((count-offset)%mod));
      if ((count-offset) % mod == 0) {
        document.forms[0].elements[i].checked=setval;
      }
      count++;
    }
    //alert(document.forms[0].elements[i].value);
  } 
  return false;
}
</script>
