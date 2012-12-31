<!DOCTYPE html>
<html>
<head>
	<title><?php $this->block('title');?>Home<?php $this->endBlock();?> - TinyArk | Tiny PHP Framework On Open Platforms</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>
<body>
<div id="header">
</div>
<?php $this->block('content');?><?php $this->endBlock();?>
<div id="footer">
	<p>&copy; 2012 <a href="http://maxmars.net/projects/tinyark">TinyArk</a> project</p>
	<p>Generated in <?php echo sprintf('%.1f', 1000*(microtime(true) - ARK_MICROTIME));?> ms</p>
</div>
</body>
</html>