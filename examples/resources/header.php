<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <base href="<?php echo $baseHref;?>">
        <meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, user-scalable=0" />
        <link rel="stylesheet" href="resources/stylesheet.css" />
        <title>Router examples</title>
    </head>
    <body>
        <div id="app">
            <header>
                <ul id="menu">
                    <li><a href="home/">Home</a></li>
                    <li><a href="about-us/">About Us</a></li>
                    <li><a href="products/">Products</a></li>
                    <li><a href="contact/">Contact</a></li>
                    <li><a href="foo-bar/">Error 404</a></li>    
                    <li><a href="class/method">Method</a></li>
                    <li><a href="class/static-method">Static method</a></li>
                    <li><a href="class/protected-method">Protected method</a></li>
                    <li><a href="function">Function</a></li>
                </ul>
                <div id="route">ROUTE: <?php echo $route;?></div>
            </header>
            <main>

        
