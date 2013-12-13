<?php $this->extend('@/layout.html.php');?>
<?php $this->block('title');?>Hello world<?php $this->endBlock();?>
<?php $this->block('content');?>
<div class="page-header">
<h1>Hello word!</h1>
</div>

<div class="content">
<p>Congratulations, TinyArk is up and running on your platform.</p>


<ul>
    <li><a href="http://codecent.com/tinyark/">Visit project homepage</a></li>
    <li><a href="https://github.com/ddliu/tinyark/issues/new">Report an issue</a></li>
    <li><a href="http://github.com/ddliu/tinyark">Contribute</a></li>
</ul>
</div>

<?php $this->endBlock();?>