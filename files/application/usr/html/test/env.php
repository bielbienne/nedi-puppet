<?php 
function get_all_env_var($example=FALSE) {
       foreach($_SERVER as $env => $value) {
             if($example == TRUE) {
               echo "\$_SERVER['$env'] ";
           } else {
               echo "$env ";
           }
             echo "= <!-- => --> $value \n";
       } // End Foreach
} // End Function

 echo "<pre>";
     get_all_env_var(TRUE);
 echo "</pre>";
?>
