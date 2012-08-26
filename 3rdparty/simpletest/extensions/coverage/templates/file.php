<html>
<head>
<title><?php echo $title ?></title>
</head>
<style type="text/css">
body {
  font-family: "Gill Sans MT", "Gill Sans", GillSans, Arial, Helvetica, sans-serif;
}
h1 {
  font-size: medium;
}
#code {
  border-spacing: 0;
}
.lineNo {
  color: #ccc;
}
.code, .lineNo {
  white-space: pre;
  font-family: monospace;
}
.covered {
  color: #090;
}
.missed {
  color: #f00;
}
.dead {
  color: #00f;
}
.comment {
  color: #333;
}
</style>
<body>
<h1 id="title"><?php echo $title ?></h1>
<table id="code">
  <tbody>
<?php foreach ($lines as $lineNo => $line) { ?>
    <tr>
       <td><span class="lineNo"><?php echo $lineNo ?></span></td>
       <td><span class="<?php echo $line['lineCoverage'] ?> code"><?php echo htmlentities($line['code']) ?></span></td>
    </tr>
<?php } ?>
  </tbody>
</table>
<h2>Legend</h2>
<dl>
  <dt><span class="missed">Missed</span></dt>
  <dd>lines code that <strong>were not</strong> excersized during program execution.</dd>
  <dt><span class="covered">Covered</span></dt>
  <dd>lines code <strong>were</strong> excersized during program execution.</dd>
  <dt><span class="comment">Comment/non executable</span></dt>
  <dd>Comment or non-executable line of code.</dd>
  <dt><span class="dead">Dead</span></dt>
  <dd>lines of code that according to xdebug could not be executed.  This is counted as coverage code because 
  in almost all cases it is code that runnable.</dd>
</dl>
</body>
</html>
