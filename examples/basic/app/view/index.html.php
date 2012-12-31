<?php $this->extend('layout.html.php');?>
<?php $this->block('title');?>Hello world<?php $this->endBlock();?>
<?php $this->block('content');?>
<h1>Hello word!</h1>
<p>Congratulations, TinyArk is running on your platform, <a href="http://maxmars.net/projects/tinyark/">visit project homepage</a>.</p>
<ul>
	<li><a href="<?php echo ark_url('blog/1.html');?>">Route Test 1</a></li>
	<li><a href="<?php echo ark_url('about.html');?>">Route Test 2</a></li>
</ul>
<?php $this->endBlock();?>