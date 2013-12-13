<!DOCTYPE html>
<html>
<head>
	<title><?php $this->block('title');?><?php $this->endBlock();?> TinyArk | PHP Framework For Open Platforms</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <link rel="stylesheet" type="text/css" href="<?php echo ark_asset_url('/assets/app.css');?>">
</head>
<body>
    <a href="https://github.com/ddliu/tinyark"><img style="position: absolute; top: 0; right: 0; border: 0;" src="https://s3.amazonaws.com/github/ribbons/forkme_right_red_aa0000.png" alt="Fork me on GitHub"></a>
<div class="wrap">
    <?php $this->block('content');?><?php $this->endBlock();?>
    <div class="footer">
    	<p>&copy; <?php echo date('Y', ARK_TIMESTAMP)?> <a href="http://codecent.com/tinyark/">TinyArk</a> project</p>
    	<p>Generated in <?php echo sprintf('%.1f', 1000*(microtime(true) - ARK_MICROTIME));?> ms</p>
    </div>
</div>
</body>
</html>