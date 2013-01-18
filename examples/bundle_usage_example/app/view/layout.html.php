<!DOCTYPE html>
<html>
<head>
	<title><?php $this->block('title');?>Home<?php $this->endBlock();?> - TinyArk | Tiny PHP Framework On Open Platforms</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <link rel="stylesheet" type="text/css" href="<?php echo ark_assets('/assets/app.css');?>">
</head>
<body>
<div class="wrap">
    <div class="topnav">
        <ul class="right">
            <li><a href="#">Test</a></li>
        </ul>
        <ul>
            <li><a href="#">Home</a></li>
            <li><a href="#">Examples</a></li>
            <li><a href="#">Document</a></li>
            <li><a href="#">Github</a></li>
        </ul>
    </div>
    <?php $this->block('content');?><?php $this->endBlock();?>
    <div class="footer">
    	<p>&copy; <?php echo date('Y', ARK_TIMESTAMP)?> <a href="http://maxmars.net/projects/tinyark">TinyArk</a> project</p>
    	<p>Generated in <?php echo sprintf('%.1f', 1000*(microtime(true) - ARK_MICROTIME));?> ms</p>
    </div>
</div>
</body>
</html>