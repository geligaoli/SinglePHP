<?php
$data = array(
    'title' => $title,
    'body_class' => 'bs-docs-home',
);
?>
<include 'Public/header'/>
<main class="bs-docs-masthead" id="content" role="main">
  <div class="container">
  <h1><?php echo $title;?></h1>
    <p class="lead">单文件PHP框架，羽量级网站开发首选</p>
      <p>SinglePHP项目来源于<a href='http://leo108.com' target='_blank'>leo108</a>开发，遵循MIT协议。</p>
      <p>SinglePHP-Ex当前由<a href='https://github.com/geligaoli/SinglePHP-Ex' target='_blank'>geligaoli</a>维护，遵循MIT协议。</p>
    <p>
      <a href="https://github.com/geligaoli/SinglePHP-Ex" target='_blank' class="btn btn-outline-inverse btn-lg" >Fork On Github</a>
    </p>
  </div>
</main>
<include 'Public/footer'/>
