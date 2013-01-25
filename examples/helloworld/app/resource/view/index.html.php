<?php $this->extend('@/layout.html.php');?>
<?php $this->block('title');?>Hello world<?php $this->endBlock();?>
<?php $this->block('content');?>
<div class="page-header">
<h1>Hello word!</h1>
</div>
<p>Congratulations, TinyArk is running on your platform, <a href="http://maxmars.net/projects/tinyark/">visit project homepage</a>.</p>

<ul>
	<li><a href="<?php echo ark_route_url('blog_slug', array('blog_id' => 1, 'blog_slug' => 'abc'));?>">Route Test 1</a></li>
	<li><a href="<?php echo ark_route_url('home');?>">Route Test 2</a></li>
    <li><a href="<?php echo ark_route_url('/about.html');?>">Route Test 3</a></li>
</ul>

<?php $this->endBlock();?>