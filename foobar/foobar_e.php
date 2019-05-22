#!/usr/bin/php

<?php
      //PHP Foobar script(3 & 5)
      //CLI flag "-n int" is the flag for length of the foobar loop, if not set
      //defaults to 100

      //Get options for script
      $option = getopt("n:");
      //assign length to be read flag or 100 by default
      if(isset($option["n"])){
          if (filter_var($option["n"], FILTER_VALIDATE_INT)){
              $n = $option["n"];
          }
      } else {
        $n = 100;
        echo "Supplied var not an int, default 100 set\n";
      }

      //execute loop, checking for matches from highest priority to lowest
      for ($i = 1; $i <= $n; $i++){
          if ($i%3 == 0){
              echo "foo";
          }
          if ($i%5 == 0){
              echo "bar";
          }
          if (($i%3 != 0) && ($i%5 !=0)){
              echo "$i";
          }
          echo "\n";     
      }
?>
