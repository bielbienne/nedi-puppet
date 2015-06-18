<?php
  $latency = 5;
  $ptime = NULL;
  $time = time();
  if (array_key_exists('time', $_GET)) {
    $ptime = intval($_GET['time']);
  }

  $url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];

  if ($ptime > 0) {
    print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<HTML><HEAD>
<TITLE>Test page</TITLE>
</HEAD>
<BODY><P>
started: ' . date('Y.m.d h:i:s', $ptime) . '<BR/>
finished: ' . date('Y.m.d h:i:s', $time) . '<BR/>
Note: if flush() works in PHP, then the difference between start and end dates
 should be approx. 0-1 second. If flush() fails (PHP output is buffered), then the
 difference will be >= ' . $latency . ' seconds.
<BR/><BR/>
';
    if (abs($ptime - $time) >= $latency) {
      print '<SPAN STYLE="color: red">Flush failed. :-(</SPAN>';
    }
    else {
      print '<SPAN STYLE="color: green">Flush succeeded! :-)</SPAN>';
    }
    print '<BR/>
<FORM>
<INPUT TYPE="button" ONCLICK="document.location=\'' . $url . '\';" VALUE="Retry">
</FORM>
</P></BODY>
</HTML>
';
  }
  else {
    // we detect the presence of the zlib extension by looking for the existence of the gzencode function
    if (function_exists('gzencode')) {
      $zlib_compression = strtolower(ini_get('zlib.output_compression'));
      if ($zlib_compression != '' && $zlib_compression != 'off' && $zlib_compression != '0') {
        ini_set('zlib.output_compression', 'Off');
      }
    }

    ob_start();

    print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<HTML><HEAD>
<TITLE>Test page</TITLE>
<SCRIPT TYPE="text/javascript">
<!--
  document.location="' . $url . '?time=' . $time . '";
//-->
</SCRIPT>
</HEAD>
<BODY><P></P></BODY>
</HTML>
';
    header('Connection: close');
    header('Content-Length: ' . ob_get_length());

    // Note: the "Content-Length" header with the proper length is absolutely required by IE.
    // Firefox does render/interpret flushed server output even without it, but IE wont.
    // If the connection is not closed by the server, but the output is flushed to the browser,
    // and there is no Content-Length, then IE will simply wait till output is finished ...
    // even if it got a "Location" header and should redirect.

    // end all output buffering so we can flush
    while (ob_get_level()) {
      ob_end_flush();
    }
    
    flush();
    
    sleep($latency);
  }
?>
